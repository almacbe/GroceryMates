<?php

declare(strict_types=1);

namespace App\Services;

use InvalidArgumentException;

class MergeListsService
{
    private const HOUSEHOLD_CONVERSIONS = [
        'g' => [
            'tablespoon' => 15.0,
            'cup' => 240.0,
        ],
        'ml' => [
            'tablespoon' => 15.0,
            'cup' => 240.0,
        ],
    ];

    /**
     * Merge multiple INDYA-style ingredient lists into a single normalized list.
     *
     * @param  array<int, string|null>  $lists
     * @return array<int, string>
     */
    public function merge(array $lists): array
    {
        $entries = [];
        $unparsed = [];

        foreach ($lists as $list) {
            if ($list === null) {
                continue;
            }

            foreach ($this->parseList($list) as $line => $item) {
                if ($item === null) {
                    $unparsed[] = $line;
                    continue;
                }

                $nameKey = $this->normalizeName($item['name']);
                $unitKey = $item['unit'] ?? '';
                $compositeKey = $nameKey.'|'.$unitKey;

                if (! isset($entries[$compositeKey])) {
                    $entries[$compositeKey] = [
                        'display_name' => $item['name'],
                        'quantity' => $item['quantity'],
                        'unit' => $item['unit'],
                    ];
                    continue;
                }

                $aggregate = &$entries[$compositeKey];

                if ($item['quantity'] === null) {
                    // Nothing to sum; keep existing best data.
                    unset($aggregate);
                    continue;
                }

                if ($aggregate['quantity'] === null) {
                    $aggregate['quantity'] = $item['quantity'];
                    unset($aggregate);
                    continue;
                }

                $aggregate['quantity'] += $item['quantity'];
                unset($aggregate);
            }
        }

        if (! empty($unparsed)) {
            throw new InvalidArgumentException(
                'Unable to parse the following lines: '.implode('; ', $unparsed)
            );
        }

        // Sort by display name for determinism.
        usort($entries, static function (array $left, array $right): int {
            return strcasecmp($left['display_name'], $right['display_name']);
        });

        return array_map(function (array $entry): string {
            if ($entry['quantity'] === null) {
                return $entry['display_name'];
            }

            $quantity = $entry['quantity'];
            $formattedQuantity = $this->formatNumber($quantity);

            $unit = $entry['unit'] ?? '';
            $quantityWithUnit = $unit !== ''
                ? sprintf('%s%s', $formattedQuantity, $unit)
                : $formattedQuantity;

            $household = $this->formatHouseholdMeasure($quantity, $unit, $entry['display_name']);
            $suffix = $household !== null ? sprintf(' (%s)', $household) : '';

            return sprintf('%s: %s%s', $entry['display_name'], $quantityWithUnit, $suffix);
        }, $entries);
    }

    /**
     * @return array<int, array{name: string, quantity: float|null, unit: string|null}|null>
     */
    private function parseList(string $list): array
    {
        $results = [];
        $lines = preg_split("/\r\n|\n|\r/", $list) ?: [];

        foreach ($lines as $rawLine) {
            $line = trim($rawLine);

            if ($line === '') {
                continue;
            }

            $line = preg_replace('/^\[\s*]\s*/u', '', $line);
            $line = preg_replace('/^[-*]\s*/u', '', $line);

            if ($line === null) {
                $results[$rawLine] = null;
                continue;
            }

            $parts = explode(':', $line, 2);
            if (count($parts) < 2) {
                continue;
            }

            $name = trim($parts[0]);

            if ($name === '') {
                $results[$rawLine] = null;
                continue;
            }

            $quantity = null;
            $unit = null;

            if (count($parts) === 2) {
                $tail = trim($parts[1]);

                if ($tail !== '') {
                    if (preg_match('/^(-?\d+(?:[.,]\d+)?)([a-zA-Z]+)?$/u', $tail, $matches)) {
                        $quantity = (float) str_replace(',', '.', $matches[1]);
                        $unit = $matches[2] ?? null;
                    } elseif (preg_match('/^(-?\d+(?:[.,]\d+)?)\s+([a-zA-Z]+)$/u', $tail, $matches)) {
                        $quantity = (float) str_replace(',', '.', $matches[1]);
                        $unit = $matches[2];
                    } else {
                        $results[$rawLine] = null;
                        continue;
                    }
                }
            }

            $results[$rawLine] = [
                'name' => $this->cleanName($name),
                'quantity' => $quantity,
                'unit' => $this->normalizeUnit($unit),
            ];
        }

        return $results;
    }

    private function normalizeName(string $name): string
    {
        $normalized = $this->cleanName($name);

        return mb_strtolower($normalized, 'UTF-8');
    }

    private function cleanName(string $name): string
    {
        // Collapse internal whitespace and trim.
        $collapsed = preg_replace('/\s+/u', ' ', trim($name));

        return $collapsed ?? trim($name);
    }

    private function normalizeUnit(?string $unit): ?string
    {
        if ($unit === null || $unit === '') {
            return null;
        }

        $unitLower = mb_strtolower($unit, 'UTF-8');

        return match ($unitLower) {
            'gr', 'grs', 'gramo', 'gramos', 'gram', 'grams' => 'g',
            'mil', 'mls' => 'ml',
            default => $unitLower,
        };
    }

    private function formatHouseholdMeasure(float $quantity, ?string $unit, string $displayName): ?string
    {
        if ($unit === null || $unit === '') {
            return null;
        }

        $unitLower = mb_strtolower($unit, 'UTF-8');

        if ($unitLower === 'g' && $this->containsHuevoCrudo($displayName)) {
            $eggs = $quantity / 60.0;
            if ($eggs <= 0) {
                return null;
            }

            return sprintf('%s huevos', $this->formatNumber($eggs));
        }

        $factors = self::HOUSEHOLD_CONVERSIONS[$unitLower] ?? null;
        if ($factors === null) {
            return null;
        }

        $tablespoons = $quantity / $factors['tablespoon'];
        $cups = $quantity / $factors['cup'];

        return sprintf(
            '%s cucharadas o %s tazas',
            $this->formatNumber($tablespoons),
            $this->formatNumber($cups)
        );
    }

    private function containsHuevoCrudo(string $displayName): bool
    {
        return mb_stripos($displayName, 'huevo crudo', 0, 'UTF-8') !== false;
    }

    private function formatNumber(float $value): string
    {
        if (fmod($value, 1.0) === 0.0) {
            return (string) ((int) $value);
        }

        $formatted = number_format($value, 2, '.', '');

        return rtrim(rtrim($formatted, '0'), '.');
    }
}
