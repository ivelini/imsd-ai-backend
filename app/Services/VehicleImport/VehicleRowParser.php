<?php

namespace App\Services\VehicleImport;

use App\DTOs\VehicleImport\ParsedTireSize;
use App\DTOs\VehicleImport\ParsedWheelSpec;
use App\Enums\Vehicle\Position;
use RuntimeException;

/** Парсинг одной CSV-ячейки с размерами шин или дисков. */
final class VehicleRowParser
{
    /** @return ParsedTireSize[] */
    public function parseTireSizes(string $cell, string $type): array
    {
        if ($cell === '') {
            return [];
        }

        $alternatives = explode('|', $cell);
        $result = [];

        foreach ($alternatives as $alt) {
            $alt = trim($alt);
            $staggered = explode(',', $alt);

            if (count($staggered) > 2) {
                throw new RuntimeException("Некорректный staggered-формат шины (ожидалось ≤2 значения): {$cell}");
            }

            foreach ($staggered as $i => $sizeStr) {
                $sizeStr = trim($sizeStr);
                $parsed = $this->parseSingleTireSize($sizeStr);
                $result[] = new ParsedTireSize(
                    width: $parsed['width'],
                    profile: $parsed['profile'],
                    diameter: $parsed['diameter'],
                    type: $type,
                    position: $this->resolvePosition($staggered, $i),
                );
            }
        }

        return $result;
    }

    /** @return ParsedWheelSpec[] */
    public function parseWheelSpecs(
        string $cell,
        string $type,
        string $pcd,
        string $hubDiameter,
        string $bolts,
    ): array {
        if ($cell === '') {
            return [];
        }

        $alternativeSpecs = explode('|', $cell);
        $result = [];

        foreach ($alternativeSpecs as $alt) {
            $alt = trim($alt);
            $staggered = explode(',', $alt);

            if (count($staggered) > 2) {
                throw new RuntimeException("Некорректный staggered-формат диска (ожидалось ≤2 значения): {$cell}");
            }

            foreach ($staggered as $i => $specStr) {
                $specStr = trim($specStr);
                $parsed = $this->parseSingleWheelSpec($specStr);
                $result[] = new ParsedWheelSpec(
                    width: $parsed['width'],
                    diameter: $parsed['diameter'],
                    et: $parsed['et'],
                    pcd: $pcd,
                    hubDiameter: $hubDiameter !== '' ? (float) $hubDiameter : 0.0,
                    bolts: $bolts,
                    type: $type,
                    position: $this->resolvePosition($staggered, $i),
                );
            }
        }

        return $result;
    }

    /**
     * @return array{width: int, profile: int, diameter: string}
     */
    private function parseSingleTireSize(string $size): array
    {
        if (! preg_match('/^(\d+)\/(\d+)\s*R?(\d+(?:\.\d+)?)$/', $size, $matches)) {
            throw new RuntimeException("Не удалось разобрать размер шины: {$size}");
        }

        return [
            'width' => (int) $matches[1],
            'profile' => (int) $matches[2],
            'diameter' => $matches[3],
        ];
    }

    /**
     * @param  string[]  $staggered
     */
    private function resolvePosition(array $staggered, int $index): ?Position
    {
        if (count($staggered) !== 2) {
            return null;
        }

        return $index === 0 ? Position::Front : Position::Rear;
    }

    /**
     * @return array{width: float, diameter: int, et: float}
     */
    private function parseSingleWheelSpec(string $spec): array
    {
        if (! preg_match('/^([\d.]+)\s*x\s*(\d+)\s*ET([\d.]+)$/i', $spec, $matches)) {
            throw new RuntimeException("Не удалось разобрать спецификацию диска: {$spec}");
        }

        return [
            'width' => (float) $matches[1],
            'diameter' => (int) $matches[2],
            'et' => (float) $matches[3],
        ];
    }
}
