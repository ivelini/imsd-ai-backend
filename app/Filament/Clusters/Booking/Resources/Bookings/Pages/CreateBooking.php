<?php

namespace App\Filament\Clusters\Booking\Resources\Bookings\Pages;

use App\Actions\Booking\CreateAdminBooking;
use App\DTOs\Booking\BookingItemInput;
use App\DTOs\Booking\CreateAdminBookingInput;
use App\Enums\Booking\CarType;
use App\Filament\Clusters\Booking\Resources\Bookings\BookingResource;
use App\Filament\Clusters\Booking\Resources\StorageContracts\StorageContractResource;
use App\Filament\Support\StorageContractPrefill;
use App\Models\Auth\Admin;
use App\Models\Booking\Booking;
use App\Models\Booking\Slot;
use App\ValueObjects\Money;
use DomainException;
use Filament\Actions\Action;
use Filament\Notifications\Notification;
use Filament\Resources\Pages\CreateRecord;
use Filament\Support\Icons\Heroicon;
use Livewire\Attributes\Url;

class CreateBooking extends CreateRecord
{
    protected static string $resource = BookingResource::class;

    /** Слот из строки запроса — переход со страницы слота (?slot_id=): форма открывается с выбранным слотом. */
    #[Url]
    public ?int $slot_id = null;

    /** Кнопка «Создать договор хранения» сохраняет запись — после сохранения оператор уходит на договор. */
    protected bool $redirectToStorageContract = false;

    /** Слот в заголовке — только при переходе со страницы слота: с кнопки списка записей id в строке запроса нет. */
    public function getTitle(): string
    {
        if ($this->slot_id === null) {
            return 'Новая запись';
        }

        $slot = Slot::findOrFail($this->slot_id);

        return sprintf('Создать запись в слот: %s, %02d:00', $slot->date->format('d.m.Y'), $slot->hour);
    }

    protected function fillForm(): void
    {
        parent::fillForm();

        if ($this->slot_id === null) {
            return;
        }

        // Родитель уже применил дефолты полей — состояние дописываем сырым: fill() дефолты теряет,
        // а getState() в этой версии Filament валидирует форму и на пустой форме бросает исключение.
        // Слот в форме не показан, но его номер лежит в состоянии — на нём варианты времени (11:00–11:59);
        // начало часа подставляем сразу: оператор правит его, только если клиент приедет позже
        $this->form->rawState([
            ...$this->form->getRawState(),
            'slot_id' => $this->slot_id,
            'start_time' => self::hourStart($this->slot_id),
        ]);
    }

    /** Начало часа слота в формате поля «Время» (HH:MM); нет такого слота — время выбирает оператор. */
    private static function hourStart(int $slotId): ?string
    {
        $hour = Slot::query()->whereKey($slotId)->value('hour');

        return is_numeric($hour) ? sprintf('%02d:00', (int) $hour) : null;
    }

    /** @return array<int, Action> */
    protected function getFormActions(): array
    {
        return [
            ...parent::getFormActions(),
            $this->createStorageContractAction(),
        ];
    }

    /** Кнопка появляется на услуге категории «Хранение»: с ней запись продолжается договором. */
    protected function createStorageContractAction(): Action
    {
        return Action::make('createStorageContract')
            ->label('Создать договор хранения')
            ->icon(Heroicon::OutlinedArchiveBox)
            ->color('gray')
            ->visible(fn (): bool => StorageContractPrefill::hasStorageService($this->data['items'] ?? []))
            ->action('createStorageContract');
    }

    /** Запись ещё не сохранена: кнопка сначала создаёт её (с валидацией), затем ведёт на договор. */
    public function createStorageContract(): void
    {
        $this->redirectToStorageContract = true;

        $this->create();
    }

    /** Обычный путь — в список записей; после кнопки — на форму договора с данными записи. */
    protected function getRedirectUrl(): string
    {
        if (! $this->redirectToStorageContract) {
            return parent::getRedirectUrl();
        }

        /** @var Booking $booking */
        $booking = $this->getRecord();

        return StorageContractResource::getUrl('create', StorageContractPrefill::paramsFor($booking));
    }

    /**
     * Создание — через Action CreateAdminBooking: состав и цены строк берутся из формы
     * (как на правке), клиент ищется/создаётся по телефону, запись на начало часа занимает час.
     */
    public function handleRecordCreation(array $data): Booking
    {
        try {
            return app(CreateAdminBooking::class)->execute(new CreateAdminBookingInput(
                operator: $this->operator(),
                phone: (string) $data['phone'],
                name: (string) $data['name'],
                surname: (string) $data['surname'],
                patronymic: filled($data['patronymic'] ?? null) ? (string) $data['patronymic'] : null,
                plate: $data['plate'],
                slotId: (int) $this->slot_id, // слот — из адреса страницы, в форме его нет
                radius: (int) $data['radius'],
                carType: CarType::from($data['car_type']),
                items: self::items($data['items'] ?? []),
                startTime: (string) $data['start_time'],
            ));
        } catch (DomainException $exception) {
            Notification::make()->danger()->title($exception->getMessage())->send();
            $this->halt();
        }

        // Недостижимо (halt() бросает Halt) — throw только для анализатора
        throw new DomainException('Запись не создана', 422);
    }

    /**
     * @param  array<int|string, array<string, mixed>>  $rows
     * @return list<BookingItemInput>
     */
    private static function items(array $rows): array
    {
        return collect($rows)
            ->map(function (array $row): BookingItemInput {
                /** @var Money $price цена за единицу в рублях — Money отдаёт dehydration поля формы */
                $price = $row['price'];

                return new BookingItemInput(
                    serviceId: (int) $row['service_id'],
                    quantity: (int) $row['quantity'],
                    price: $price,
                );
            })
            ->values()
            ->all();
    }

    private function operator(): Admin
    {
        /** @var Admin $operator */
        $operator = auth('admin')->user();

        return $operator;
    }
}
