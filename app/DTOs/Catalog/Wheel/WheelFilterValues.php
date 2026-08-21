<?php

namespace App\DTOs\Catalog\Wheel;

/** Фасетные значения фильтра каталога дисков: все ключи фасетов и диапазон цены. */
final readonly class WheelFilterValues
{
    /** @var list<array{label: int|string, value: int|string}> */
    public array $width;

    /** @var list<array{label: int|string, value: int|string}> */
    public array $diameter;

    /** @var list<array{label: string, value: string}> */
    public array $pcd;

    /** @var list<array{label: string, value: string}> */
    public array $et;

    /** @var list<array{label: string, value: string}> */
    public array $hub_diameter;

    /** @var list<array{label: string, value: string}> */
    public array $type;

    /** @var list<array{label: string, value: string}> */
    public array $color;

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
        array $diameter,
        array $pcd,
        array $et,
        array $hub_diameter,
        array $type,
        array $color,
        array $brand,
        array $country,
        array $delivery,
        array $price,
    ) {
        $this->width = $width;
        $this->diameter = $diameter;
        $this->pcd = $pcd;
        $this->et = $et;
        $this->hub_diameter = $hub_diameter;
        $this->type = $type;
        $this->color = $color;
        $this->brand = $brand;
        $this->country = $country;
        $this->delivery = $delivery;
        $this->price = $price;
    }

    /**
     * @param  array{
     *     width: list<array{label: int|string, value: int|string}>,
     *     diameter: list<array{label: int|string, value: int|string}>,
     *     pcd: list<array{label: string, value: string}>,
     *     et: list<array{label: string, value: string}>,
     *     hub_diameter: list<array{label: string, value: string}>,
     *     type: list<array{label: string, value: string}>,
     *     color: list<array{label: string, value: string}>,
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
            $data['diameter'],
            $data['pcd'],
            $data['et'],
            $data['hub_diameter'],
            $data['type'],
            $data['color'],
            $data['brand'],
            $data['country'],
            $data['delivery'],
            $data['price'],
        );
    }
}
