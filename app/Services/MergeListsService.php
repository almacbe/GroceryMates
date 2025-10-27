<?php

declare(strict_types=1);

namespace App\Services;

use InvalidArgumentException;

class MergeListsService
{
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

        return array_map(static function (array $entry): string {
            if ($entry['quantity'] === null) {
                return $entry['display_name'];
            }

            $quantity = $entry['quantity'];
            $formattedQuantity = (fmod($quantity, 1.0) === 0.0)
                ? (string) ((int) $quantity)
                : rtrim(rtrim(number_format($quantity, 2, '.', ''), '0'), '.');

            $unit = $entry['unit'] ?? '';

            return $unit !== ''
                ? sprintf('%s: %s%s', $entry['display_name'], $formattedQuantity, $unit)
                : sprintf('%s: %s', $entry['display_name'], $formattedQuantity);
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
}
