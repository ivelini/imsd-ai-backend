<?php

namespace Database\Seeders;

use App\Actions\Booking\GenerateSlotGrid;
use Illuminate\Database\Seeder;

class BookingSlotSeeder extends Seeder
{
    public function run(): void
    {
        // ADR 0014: сетка генерируется тем же действием, что и планировщик (slots:generate)
        app(GenerateSlotGrid::class)->execute();
    }
}
