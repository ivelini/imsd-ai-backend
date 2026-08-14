<?php

namespace App\DTOs\Catalog\Tire;

/** Фасетные значения фильтра каталога шин: все ключи фасетов и диапазон цены. */
final readonly class TireFilterValues
{
    /** @var list<array{label: int, value: int}> */
    public array $width;

    /** @var list<array{label: int, value: int}> */
    public array $profile;

    /** @var list<array{label: string, value: string}> */
    public array $diameter;

    /** @var list<array{label: string, value: string}> */
    public array $season;

    /** @var list<array{label: string, value: string}> */
    public array $studded;

    /** @var list<array{label: string, value: string|null}> */
    public array $brand;

    /** @var list<array{label: string, value: string|null}> */
    public array $country;

    /** @var list<array{label: string, value: string}> */
    public array $delivery;

    /** @var array{min: float, max: float} */
    public array $price;

    private function __construct(
        array $width,
        array $profile,
        array $diameter,
        array $season,
        array $studded,
        array $brand,
        array $country,
        array $delivery,
        array $price,
    ) {
        $this->width = $width;
        $this->profile = $profile;
        $this->diameter = $diameter;
        $this->season = $season;
        $this->studded = $studded;
        $this->brand = $brand;
        $this->country = $country;
        $this->delivery = $delivery;
        $this->price = $price;
    }

    /**
     * @param  array{
     *     width: list<array{label: int, value: int}>,
     *     profile: list<array{label: int, value: int}>,
     *     diameter: list<array{label: string, value: string}>,
     *     season: list<array{label: string, value: string}>,
     *     studded: list<array{label: string, value: string}>,
     *     brand: list<array{label: string, value: string|null}>,
     *     country: list<array{label: string, value: string|null}>,
     *     delivery: list<array{label: string, value: string}>,
     *     price: array{min: float, max: float},
     * }  $data
     */
    public static function fromArray(array $data): self
    {
        return new self(
            $data['width'],
            $data['profile'],
            $data['diameter'],
            $data['season'],
            $data['studded'],
            $data['brand'],
            $data['country'],
            $data['delivery'],
            $data['price'],
        );
    }
}
