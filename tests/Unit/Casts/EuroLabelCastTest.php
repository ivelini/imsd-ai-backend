<?php

namespace Tests\Unit\Casts;

use App\Casts\EuroLabelCast;
use App\DTOs\Catalog\Tire\EuroLabel;
use App\Models\Catalog\Tire\TireProduct;
use PHPUnit\Framework\TestCase;

/** Каст euro_label: JSON-строка → EuroLabel и обратно. */
class EuroLabelCastTest extends TestCase
{
    private EuroLabelCast $cast;

    private TireProduct $model;

    protected function setUp(): void
    {
        $this->cast = new EuroLabelCast;
        $this->model = new TireProduct;
    }

    public function test_get_returns_euro_label_or_null(): void
    {
        $label = $this->cast->get(
            $this->model,
            'euro_label',
            '{"rollingResistance":"D","wetGrip":"C","noiseEmission":"71"}',
            [],
        );

        $this->assertInstanceOf(EuroLabel::class, $label);
        $this->assertSame('D', $label->rollingResistance);
        $this->assertSame('C', $label->wetGrip);
        $this->assertSame('71', $label->noiseEmission);

        $this->assertNull($this->cast->get($this->model, 'euro_label', null, []));
        $this->assertNull($this->cast->get($this->model, 'euro_label', 'мусор', []));
    }

    public function test_set_serializes_euro_label(): void
    {
        $json = $this->cast->set($this->model, 'euro_label', new EuroLabel('D', 'C', '71'), []);

        $this->assertSame(
            '{"rollingResistance":"D","wetGrip":"C","noiseEmission":"71"}',
            $json,
        );

        $this->assertNull($this->cast->set($this->model, 'euro_label', null, []));
    }
}
