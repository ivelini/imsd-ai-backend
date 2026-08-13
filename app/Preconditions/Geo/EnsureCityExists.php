<?php

namespace App\Preconditions\Geo;

use App\Models\Delivery\City;
use DomainException;

/** Проверка: город из конфига по умолчанию существует в справочнике. */
final readonly class EnsureCityExists
{
    public function ensure(string $name): City
    {
        $city = City::query()->where('name', $name)->first();

        if ($city === null) {
            throw new DomainException("Город «{$name}» не найден", 409);
        }

        return $city;
    }
}
