<?php

namespace App\DTOs\Catalog\Tire;

use JsonSerializable;

/** Евро-лейбл шины: сопротивление качению (A–G), сцепление с мокрым (A–G), шум (dB). */
final readonly class EuroLabel implements JsonSerializable
{
    public function __construct(
        public string $rollingResistance,
        public string $wetGrip,
        public string $noiseEmission,
    ) {}

    /** @param  array{rollingResistance?: mixed, wetGrip?: mixed, noiseEmission?: mixed}  $data */
    public static function fromArray(array $data): ?self
    {
        if (! isset($data['rollingResistance'], $data['wetGrip'], $data['noiseEmission'])) {
            return null;
        }

        return new self(
            rollingResistance: (string) $data['rollingResistance'],
            wetGrip: (string) $data['wetGrip'],
            noiseEmission: (string) $data['noiseEmission'],
        );
    }

    /** @return array{rollingResistance: string, wetGrip: string, noiseEmission: string} */
    public function jsonSerialize(): array
    {
        return [
            'rollingResistance' => $this->rollingResistance,
            'wetGrip' => $this->wetGrip,
            'noiseEmission' => $this->noiseEmission,
        ];
    }
}
