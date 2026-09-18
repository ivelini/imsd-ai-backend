<?php

namespace Tests\Feature\Admin;

use App\Filament\Resources\UserResource\Pages\CreateUser;
use App\Filament\Resources\UserResource\Pages\EditUser;
use App\Filament\Resources\UserResource\Pages\ListUsers;
use App\Models\Auth\Admin;
use App\Models\User;
use Filament\Actions\DeleteAction;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\Concerns\CreatesAdmin;
use Tests\TestCase;

/** Раздел «Клиенты» панели: клиента заводят и правят руками, телефон хранится в каноне. */
class UserResourceTest extends TestCase
{
    use CreatesAdmin, RefreshDatabase;

    private Admin $admin;

    protected function setUp(): void
    {
        parent::setUp();

        $this->admin = $this->createAdmin();
        $this->actingAs($this->admin, 'admin');
    }

    /** @return array<string, mixed> */
    private function formData(): array
    {
        return [
            'surname' => 'Петров',
            'name' => 'Иван',
            'patronymic' => 'Иванович',
            'phone' => '8 (912) 345-67-89',
            'email' => null,
        ];
    }

    /** Канон «7XXXXXXXXXX»: по нему клиента находит запись на шиномонтаж */
    public function test_store_normalizes_phone(): void
    {
        Livewire::test(CreateUser::class)
            ->fillForm($this->formData())
            ->call('create')
            ->assertHasNoFormErrors();

        $this->assertSame('79123456789', User::firstOrFail()->phone);
    }

    /** Тот же номер в другом формате — дубль: канон применяется до проверки уникальности */
    public function test_store_rejects_duplicate_phone(): void
    {
        User::factory()->bookingClient()->create(['phone' => '79123456789']);

        Livewire::test(CreateUser::class)
            ->fillForm([...$this->formData(), 'phone' => '8 912 345 67 89'])
            ->call('create')
            ->assertHasFormErrors(['phone']);

        $this->assertSame(1, User::count());
    }

    /** Удаления нет: на клиента ссылаются записи и договоры хранения */
    public function test_edit_has_no_delete_action(): void
    {
        $client = User::factory()->bookingClient()->create();

        Livewire::test(EditUser::class, ['record' => $client->id])
            ->assertActionDoesNotExist(DeleteAction::class);
    }

    public function test_list_searches_by_phone_and_surname(): void
    {
        $client = User::factory()->bookingClient()->create([
            'surname' => 'Петров',
            'name' => 'Иван',
            'phone' => '79123456789',
        ]);
        $other = User::factory()->bookingClient()->create([
            'surname' => 'Сидоров',
            'name' => 'Пётр',
            'phone' => '79005554433',
        ]);

        Livewire::test(ListUsers::class)
            ->searchTable('7912')
            ->assertCanSeeTableRecords([$client])
            ->assertCanNotSeeTableRecords([$other])
            ->searchTable('Петр')
            ->assertCanSeeTableRecords([$client])
            ->assertCanNotSeeTableRecords([$other]);
    }

    public function test_edit_updates_client(): void
    {
        $client = User::factory()->bookingClient()->create(['surname' => 'Петров']);

        Livewire::test(EditUser::class, ['record' => $client->id])
            ->fillForm(['surname' => 'Смирнов', 'email' => 'smirnov@example.com'])
            ->call('save')
            ->assertHasNoFormErrors();

        $client->refresh();
        $this->assertSame('Смирнов', $client->surname);
        $this->assertSame('smirnov@example.com', $client->email);
    }
}
