<?php

namespace App\DTOs\Catalog;

use JsonSerializable;

/** Происхождение товара: короткая метка (badge) и развёрнутое описание (description). */
final readonly class OriginInfo implements JsonSerializable
{
    public function __construct(
        public string $badge,
        public ?string $description,
    ) {}

    /** @param  array{badge?: mixed, description?: mixed}  $data */
    public static function fromArray(array $data): ?self
    {
        if (! isset($data['badge'])) {
            return null;
        }

        return new self(
            badge: (string) $data['badge'],
            description: isset($data['description']) ? (string) $data['description'] : null,
        );
    }

    /** @return array{badge: string, description: string|null} */
    public function jsonSerialize(): array
    {
        return [
            'badge' => $this->badge,
            'description' => $this->description,
        ];
    }
}
