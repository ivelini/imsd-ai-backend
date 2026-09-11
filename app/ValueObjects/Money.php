<?php

namespace App\ValueObjects;

use InvalidArgumentException;
use JsonSerializable;
use Livewire\Wireable;

/**
 * Деньги в копейках — единственный тип для денежных полей монолита (решение №10 плана переноса):
 * хранение и расчёты — только через Money, форматирование в рубли — formatted().
 * Отрицательные суммы невозможны (fail fast).
 */
final readonly class Money implements JsonSerializable, Wireable
{
    private function __construct(private int $kopecks) {}

    public static function fromKopecks(int $kopecks): self
    {
        if ($kopecks < 0) {
            throw new InvalidArgumentException("Отрицательная сумма: {$kopecks} копеек");
        }

        return new self($kopecks);
    }

    /** Рубли (float, int или строка «1700.50») — округление до копеек по правилам round(). */
    public static function fromRubles(int|float|string $rubles): self
    {
        return self::fromKopecks((int) round((float) $rubles * 100));
    }

    public function toKopecks(): int
    {
        return $this->kopecks;
    }

    public function toRubles(): float
    {
        return $this->kopecks / 100;
    }

    /** «1 700 ₽», «1 700,50 ₽» — отображение, не хранение. */
    public function formatted(): string
    {
        $rubles = intdiv($this->kopecks, 100);
        $remainder = $this->kopecks % 100;

        $amount = $remainder === 0
            ? number_format($rubles, 0, ',', ' ')
            : number_format($this->kopecks / 100, 2, ',', ' ');

        return $amount.' ₽';
    }

    /** Цена позиции = цена за единицу × количество (количество ≥ 1). */
    public function multiply(int $factor): self
    {
        if ($factor < 1) {
            throw new InvalidArgumentException("Множитель цены должен быть ≥ 1, получен {$factor}");
        }

        return self::fromKopecks($this->kopecks * $factor);
    }

    public function add(self $other): self
    {
        return self::fromKopecks($this->kopecks + $other->kopecks);
    }

    public function jsonSerialize(): int
    {
        return $this->kopecks;
    }

    /** Livewire-гидратация записей панели: по проводу — копейки. */
    public function toLivewire(): int
    {
        return $this->kopecks;
    }

    public static function fromLivewire($value): self
    {
        return self::fromKopecks((int) $value);
    }
}
