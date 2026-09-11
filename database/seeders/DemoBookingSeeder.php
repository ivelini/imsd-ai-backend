<?php

namespace Database\Seeders;

use App\Enums\Booking\BookingSource;
use App\Enums\Booking\BookingStatus;
use App\Enums\Booking\CarType;
use App\Models\Booking\Booking;
use App\Models\Booking\BookingItem;
use App\Models\Booking\BookingService;
use App\Models\Booking\Slot;
use App\Models\User;
use App\Services\Booking\PriceCalculator;
use Carbon\CarbonInterface;
use Illuminate\Database\Seeder;
use Illuminate\Support\Collection;

/**
 * Демо-данные «неделя вокруг today»: клиенты с авто и записи за прошлую неделю
 * (история: done/cancelled/no_show) и на неделю вперёд (confirmed).
 * Даты — относительно today: сид всегда актуален.
 */
class DemoBookingSeeder extends Seeder
{
    /** @var array<int, array{radius: int, car_type: CarType, plate: string|null}> профиль клиента (параметры последней записи) */
    private array $profiles = [];

    private const CUSTOMER_NAMES = [
        'Иван Петров', 'Мария Соколова', 'Алексей Ковалёв', 'Ольга Новикова',
        'Дмитрий Морозов', 'Наталья Волкова', 'Сергей Лебедев', 'Елена Козлова',
        'Андрей Павлов', 'Татьяна Семёнова', 'Николай Голубев', 'Анна Виноградова',
        'Павел Кузнецов', 'Юлия Ефимова', 'Максим Орлов', 'Светлана Титова',
        'Владимир Захаров', 'Ксения Беляева',
    ];

    public function run(): void
    {
        $services = BookingService::where('is_active', true)->get()->keyBy('name');
        $mounting = $services->get('Снятие и установка колёс');
        $balancing = $services->get('Балансировка колёс');

        $this->profiles = [];
        $users = $this->seedUsers();

        $this->seedHistorySlots(); // прошлая неделя: строки сетки, которые генератор уже не трогает

        foreach (range(-7, 6) as $dayOffset) {
            $date = now()->addDays($dayOffset);
            if ($date->isSunday() || $date->isToday()) {
                continue; // сегодняшний день не заполняем: статусы дня разворачивает оператор
            }

            $bookingsCount = $date->isPast() ? rand(4, 7) : rand(3, 5);

            foreach (range(1, $bookingsCount) as $ignored) {
                $this->seedOneBooking($date, $users, $mounting, $balancing);
            }
        }
    }

    /** @return Collection<int, User> */
    private function seedUsers(): Collection
    {
        $usedPhones = [];
        $users = collect();

        foreach (self::CUSTOMER_NAMES as $name) {
            do {
                $phone = '79'.rand(100000000, 999999999); // канон «7XXXXXXXXXX» (11 цифр)
            } while (isset($usedPhones[$phone]));
            $usedPhones[$phone] = true;

            $users->push(User::create(['name' => $name, 'phone' => $phone]));
        }

        return User::all();
    }

    /**
     * Параметры авто из «последней записи» клиента (cars упразднены, ФТ-18 tireslot):
     * первый раз генерируются, дальше клиент пишется с теми же параметрами.
     *
     * @return array{radius: int, car_type: CarType, plate: string|null}
     */
    private function profileFor(User $user): array
    {
        return $this->profiles[$user->id] ??= [
            'radius' => rand(13, 21),
            'car_type' => fake()->randomElement(CarType::bookable()),
            'plate' => $this->randomPlate(),
        ];
    }

    /**
     * Прошлая неделя: генератор создаёт сетку только от today (в прошлое не пишет),
     * поэтому строки истории создаём явно — как их создавали бы записи из админки.
     */
    private function seedHistorySlots(): void
    {
        $rows = [];
        foreach (range(-7, -1) as $dayOffset) {
            $date = now()->addDays($dayOffset);
            if ($date->isSunday()) {
                continue;
            }
            foreach (range(9, 18) as $hour) {
                $rows[] = ['date' => $date->toDateString(), 'hour' => $hour];
            }
        }

        foreach ($rows as $row) {
            $exists = Slot::whereDate('date', $row['date'])->where('hour', $row['hour'])->exists();

            if (! $exists) {
                Slot::create(['date' => $row['date'], 'hour' => $row['hour']]);
            }
        }
    }

    private function seedOneBooking(
        CarbonInterface $date,
        Collection $users,
        ?BookingService $mounting,
        ?BookingService $balancing,
    ): void {
        $user = $users->random();
        $profile = $this->profileFor($user);

        $workingHours = range(9, 18);
        $hour = $workingHours[array_rand($workingHours)];
        $minute = $date->isFuture() ? 0 : rand(0, 3) * 15; // виджет — целые часы, админка — любое время
        $slot = Slot::whereDate('date', $date)->where('hour', $hour)->first()
            ?? Slot::create(['date' => $date->format('Y-m-d'), 'hour' => $hour]);

        $services = [];
        if ($mounting !== null && rand(1, 100) <= 80) {
            $services[] = $mounting;
        }
        if ($balancing !== null && $services !== [] && rand(1, 100) <= 70) {
            $services[] = $balancing;
        }
        if ($services === []) {
            return;
        }

        $status = $this->statusFor($date);
        $source = $minute === 0 ? BookingSource::Site : BookingSource::Admin;

        $booking = Booking::create([
            'user_id' => $user->id,
            'slot_id' => $slot->id,
            'start_time' => sprintf('%02d:%02d:00', $hour, $minute),
            'status' => $status,
            'source' => $source,
            'cancel_reason' => $status === BookingStatus::Cancelled ? 'Клиент отменил' : null,
            'radius' => $profile['radius'],
            'car_type' => $profile['car_type'],
            'plate' => $profile['plate'],
            'total_price' => 0, // пересчитается ниже по составу
        ]);

        // Виджет записывает сезонный комплект (4 колеса); админка — частичный заказ
        $quantity = $source === BookingSource::Site
            ? PriceCalculator::DEFAULT_QUANTITY
            : rand(PriceCalculator::MIN_QUANTITY, PriceCalculator::MAX_QUANTITY);
        $quantities = collect($services)->mapWithKeys(fn (BookingService $service): array => [$service->id => $quantity])->all();

        // Расчёт — единый PriceCalculator, как на сайте и при подтверждении
        $quote = app(PriceCalculator::class)->calculate(
            collect($services),
            $profile['radius'],
            $profile['car_type'],
            $quantities,
        );

        foreach ($quote->lines as $line) {
            BookingItem::create([
                'booking_id' => $booking->id,
                'service_id' => $line->service->id,
                'price' => $line->unitPrice, // снимок: цена за единицу
                'quantity' => $line->quantity,
            ]);
        }
        $booking->update(['total_price' => $quote->total]);
    }

    private function statusFor(CarbonInterface $date): BookingStatus
    {
        if ($date->isFuture()) {
            return BookingStatus::Confirmed;
        }

        $roll = rand(1, 100);

        if ($roll <= 10) {
            return BookingStatus::NoShow;
        }

        if ($roll <= 20) {
            return BookingStatus::Cancelled;
        }

        return BookingStatus::Done;
    }

    private function randomPlate(): string
    {
        $letters = 'АВЕКМНОРСТУХ';
        $letter = fn (): string => mb_substr($letters, rand(0, 11), 1);

        return $letter().rand(100, 999).$letter().$letter().rand(2, 99);
    }
}
