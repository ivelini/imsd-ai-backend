<?php

namespace Database\Factories\Storage;

use App\Enums\Storage\StorageContractStatus;
use App\Models\Storage\StorageContract;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/** @extends Factory<StorageContract> */
class StorageContractFactory extends Factory
{
    protected $model = StorageContract::class;

    public function definition(): array
    {
        return [
            'user_id' => User::factory()->bookingClient(),
            'personal_document' => '75 18 074294',
            'starts_on' => now()->toDateString(),
            'ends_on' => now()->addMonths(6)->toDateString(),
            'price' => 600000,
            'status' => StorageContractStatus::Active,
        ];
    }
}
