<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    private const OLD_INDEX = 'product_origins_vendor_manufacture_country_manufacture_year_uni';

    private const NEW_INDEX = 'product_origins_triplet_unique';

    /**
     * pgsql: jsonb-значения с длинными описаниями превышают лимит btree (2704 байта,
     * ошибка «index row size exceeds btree maximum») — заменяем индекс на md5-хеши
     * (подсказка самой БД). sqlite (тесты): обычный UNIQUE — значений хватает.
     * NULL-семантика прежняя: строки с null не блокируются unique.
     */
    public function up(): void
    {
        if (DB::getDriverName() === 'pgsql') {
            // Имя старого индекса обрезано pgsql до 63 символов (..._uni)
            Schema::table('product_origins', fn (Blueprint $table) => $table->dropUnique(self::OLD_INDEX));
            DB::statement(
                'CREATE UNIQUE INDEX '.self::NEW_INDEX.' ON product_origins '
                .'(md5(vendor::text), md5(manufacture_country::text), md5(manufacture_year::text))'
            );

            return;
        }

        // sqlite не обрезает имена — Laravel сгенерировал полное (..._unique); IF EXISTS на оба варианта
        DB::statement('DROP INDEX IF EXISTS "product_origins_vendor_manufacture_country_manufacture_year_unique"');
        DB::statement('DROP INDEX IF EXISTS "'.self::OLD_INDEX.'"');

        Schema::table('product_origins', function (Blueprint $table) {
            $table->unique(['vendor', 'manufacture_country', 'manufacture_year'], self::NEW_INDEX);
        });
    }

    public function down(): void
    {
        Schema::table('product_origins', fn (Blueprint $table) => $table->dropUnique(self::NEW_INDEX));

        if (DB::getDriverName() === 'pgsql') {
            DB::statement(
                'CREATE UNIQUE INDEX '.self::OLD_INDEX.' ON product_origins '
                .'(vendor, manufacture_country, manufacture_year)'
            );

            return;
        }

        Schema::table('product_origins', function (Blueprint $table) {
            $table->unique(['vendor', 'manufacture_country', 'manufacture_year'], self::OLD_INDEX);
        });
    }
};
