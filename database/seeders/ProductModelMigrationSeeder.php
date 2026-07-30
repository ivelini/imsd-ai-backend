<?php

namespace Database\Seeders;

use App\Models\Catalog\Brand\Brand;
use App\Models\Catalog\Model\ProductModel;
use App\Models\Catalog\Tire\TireProduct;
use App\Models\Catalog\Wheel\WheelProduct;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use Illuminate\Support\Str;

/** Перенос name из tire_products/wheel_products в таблицу product_models. */
class ProductModelMigrationSeeder extends Seeder
{
    use WithoutModelEvents;

    public function run(): void
    {
        $this->migrateTireModels();
        $this->migrateWheelModels();
    }

    private function migrateTireModels(): void
    {
        $brands = Brand::pluck('slug', 'id');

        $names = TireProduct::select('brand_id', 'name')
            ->distinct()
            ->get();

        foreach ($names as $row) {
            $brandSlug = $brands[$row->brand_id] ?? 'unknown';
            $slug = $brandSlug.'-'.Str::slug($row->name);

            $model = ProductModel::firstOrCreate(
                ['brand_id' => $row->brand_id, 'slug' => $slug],
                ['name' => $row->name, 'type' => 'tire'],
            );

            TireProduct::where('brand_id', $row->brand_id)
                ->where('name', $row->name)
                ->whereNull('model_id')
                ->update(['model_id' => $model->id]);
        }
    }

    private function migrateWheelModels(): void
    {
        $brands = Brand::pluck('slug', 'id');

        $names = WheelProduct::select('brand_id', 'name')
            ->distinct()
            ->get();

        foreach ($names as $row) {
            $modelName = $this->parseWheelModelName($row->name);
            $brandSlug = $brands[$row->brand_id] ?? 'unknown';
            $slug = $brandSlug.'-'.Str::slug($modelName);

            $model = ProductModel::firstOrCreate(
                ['brand_id' => $row->brand_id, 'slug' => $slug],
                ['name' => $modelName, 'type' => 'wheel'],
            );

            WheelProduct::where('brand_id', $row->brand_id)
                ->where('name', $row->name)
                ->whereNull('model_id')
                ->update(['model_id' => $model->id]);
        }
    }

    /** Извлекает название модели из полного названия диска. */
    private function parseWheelModelName(string $name): string
    {
        // Убираем префикс «Диск »
        $stripped = mb_substr($name, 5);

        // Находим первое вхождение размерности: {W}x{D} (например, 16x7, 17x7,5)
        if (preg_match('/\d+[.,]?\d*x\d+/u', $stripped, $m, PREG_OFFSET_CAPTURE)) {
            return trim(mb_substr($stripped, 0, $m[0][1] - 1));
        }

        // Если размерность не найдена — используем всё, что после «Диск »
        return trim($stripped);
    }
}
