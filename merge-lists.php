<?php
/**
 * Consolidate grocery lists written in free text:
 * - Parses lines like "[  ] Ingredient Name: 123g" (or ml)
 * - Sums quantities across multiple lists
 * - Adds common kitchen measures (tablespoons, cups)
 * Notes:
 * - Lines without numeric quantity+unit are ignored (e.g., spices with empty qty)
 * - Uses generic conversions (can be customized per-ingredient if needed)
 */

// ---------- Config: conversion factors (generic) ----------
$conversionFactors = [
    'g'  => ['tablespoon' => 15, 'cup' => 240],
    'ml' => ['tablespoon' => 15, 'cup' => 240],
];

// ---------- Conversion helpers ----------
function convertToCommonMeasure(int $quantity, string $unit, string $ingredientName = ''): string {
    global $conversionFactors;

    $normalizedName = function_exists('mb_strtolower')
        ? mb_strtolower($ingredientName)
        : strtolower($ingredientName);
    if ($normalizedName !== '' && strpos($normalizedName, 'huevo crudo') !== false && $unit === 'g') {
        $gramsPerEgg = 60; // average weight of a medium raw egg
        if ($gramsPerEgg > 0) {
            $eggs = $quantity / $gramsPerEgg;
            $roundedEggs = round($eggs, 2);
            $formattedEggs = rtrim(rtrim(number_format($roundedEggs, 2, '.', ''), '0'), '.');
            return $formattedEggs . ' huevos';
        }
    }

    $unit = strtolower($unit);
    if (!isset($conversionFactors[$unit])) {
        return 'Conversion not available';
    }

    $tbsp = $quantity / $conversionFactors[$unit]['tablespoon'];
    $cups = $quantity / $conversionFactors[$unit]['cup'];

    return sprintf('%.2f tablespoons or %.2f cups', $tbsp, $cups);
}

// ---------- Parsing helpers ----------
/**
 * Parse a single free-text list.
 * Accepts Unicode names, variable spaces, and "g|ml" units.
 * Examples matched:
 *   "[  ] Tomate Rojo: 350g"
 *   "[ ] Agua: 202ml"
 *   "- Item: 40g"  (fallback)
 */
function extractIngredients(string $text): array {
    $ingredients = [];

    // Normalize line endings and split
    $lines = preg_split('/\R/u', $text);

    foreach ($lines as $line) {
        $line = trim($line);
        if ($line === '') continue;

        // Primary pattern: checkbox style
        $pattern1 = '/^\[\s*\]\s*(.+?)\s*:\s*(\d+)\s*(g|ml)\s*$/imu';

        // Fallback pattern: dash/asterisk style
        $pattern2 = '/^[-*]\s*(.+?)\s*:\s*(\d+)\s*(g|ml)\s*$/imu';

        $match = null;
        if (preg_match($pattern1, $line, $m)) {
            $match = $m;
        } elseif (preg_match($pattern2, $line, $m)) {
            $match = $m;
        }

        if ($match) {
            $name     = trim($match[1]);
            $quantity = (int)$match[2];
            $unit     = strtolower($match[3]); // 'g' or 'ml'

            // Sum quantities if repeated (keeping unit consistency)
            if (isset($ingredients[$name])) {
                if ($ingredients[$name]['unit'] === $unit) {
                    $ingredients[$name]['quantity'] += $quantity;
                } else {
                    // Different unit for the same name -> separate keyed entry
                    $altKey = $name . ' [' . $unit . ']';
                    if (isset($ingredients[$altKey])) {
                        $ingredients[$altKey]['quantity'] += $quantity;
                    } else {
                        $ingredients[$altKey] = ['quantity' => $quantity, 'unit' => $unit];
                    }
                }
            } else {
                $ingredients[$name] = ['quantity' => $quantity, 'unit' => $unit];
            }
        }
        // Lines without numeric qty are intentionally ignored
    }

    return $ingredients;
}

/**
 * Process multiple lists: merge + sum + add common measures.
 */
function processIngredients(array $lists): array {
    $consolidated = [];

    foreach ($lists as $listText) {
        $parsed = extractIngredients($listText);
        foreach ($parsed as $name => $data) {
            if (isset($consolidated[$name])) {
                if ($consolidated[$name]['unit'] === $data['unit']) {
                    $consolidated[$name]['quantity'] += $data['quantity'];
                } else {
                    $altKey = $name . ' [' . $data['unit'] . ']';
                    if (isset($consolidated[$altKey])) {
                        $consolidated[$altKey]['quantity'] += $data['quantity'];
                    } else {
                        $consolidated[$altKey] = $data;
                    }
                }
            } else {
                $consolidated[$name] = $data;
            }
        }
    }

    // Add common measures
    foreach ($consolidated as $name => $data) {
        $consolidated[$name]['commonMeasure'] = convertToCommonMeasure($data['quantity'], $data['unit'], $name);
    }

    // Optional: sort by name (case-insensitive, natural order)
    uksort($consolidated, fn($a, $b) => strnatcasecmp($a, $b));

    return $consolidated;
}

// ---------- Example lists already supported ----------
$listA = <<<TXT
HUEVOS
[  ] Clara De Huevo: 93g
[  ] Huevo Crudo: 231g

LÁCTEOS
[  ] Kéfir: 598g
[  ] Leche Semidesnatada: 620g
[  ] Queso Cottage: 47g
[  ] Queso De Cabra Semicurado: 35g
[  ] Queso Manchego Semicurado: 29g

CEREALES
[  ] Copos De Avena: 173g
[  ] Maíz Dulce: 30g
[  ] Pan De Centeno: 60g
[  ] Pan Integral: 110g
[  ] Pasta De Sarraceno En Seco: 27g
[  ] Tortita Wrap De Trigo: 103g

LEGUMBRES
[  ] Garbanzo Cocido Envasado: 261g
[  ] Haba Cocida: 99g
[  ] Lentejas Cocidas: 177g
[  ] Soja Texturizada Fina: 64g
[  ] Tofu Firme: 81g

FRUTAS
[  ] Aguacate: 87g
[  ] Fresa: 94g
[  ] Plátano: 417g

VERDURAS
[  ] Ajo: 27g
[  ] Ajos Tiernos: 159g
[  ] Calabacín: 40g
[  ] Canónigos: 47g
[  ] Cebolla: 68g
[  ] Cebolla Morada: 20g
[  ] Espinacas: 230g
[  ] Espárrago Triguero: 119g
[  ] Rúcula: 20g
[  ] Tomate Rojo: 255g
[  ] Tomates Cherry: 81g

FRUTOS SECOS
[  ] Almendras Al Natural: 22g
[  ] Nueces: 55g

GRASAS
[  ] Aceite De Oliva: 40g
[  ] Mantequilla: 10g

SALSAS
[  ] Salsa De Soja (Tamari): 10g
[  ] Vinagre De Módena: 6g

ESPECIAS
[  ] Curry En Polvo: 
[  ] Orégano Seco: 
[  ] Pimentón: 
[  ] Pimienta Negra: 
[  ] Sal De Mesa: 

CAFÉS E INFUSIONES
[  ] Café Solo: 500g

BEBIDAS SIN
[  ] Agua: 104g

DULCES
[  ] Miel: 24g

SUPLEMENTOS
[  ] Proteína Whey: 170g
[  ] Wada / Monohidrato De Creatina Elite (Myprotein): 30g
TXT;

$listB = <<<TXT
HUEVOS
[  ] Clara De Huevo: 38g
[  ] Huevo Crudo: 336g

LÁCTEOS
[  ] Leche Desnatada: 300g
[  ] Queso Havarti: 23g
[  ] Queso De Cabra Semicurado: 29g
[  ] Queso Manchego Semicurado: 11g
[  ] Yogur Natural: 465g

CEREALES
[  ] Copos De Avena: 27g
[  ] Maíz Dulce: 26g
[  ] Pan De Centeno: 98g
[  ] Pan De Molde: 26g
[  ] Pan Integral: 360g
[  ] Pasta De Sarraceno En Seco: 46g
[  ] Tortita Wrap De Trigo: 131g

LEGUMBRES
[  ] Bebida De Soja: 135g
[  ] Garbanzo Cocido Envasado: 508g
[  ] Haba Cocida: 117g
[  ] Soja Texturizada Fina: 57g

FRUTAS
[  ] Aguacate: 52g
[  ] Arándano: 34g
[  ] Higo: 400g
[  ] Mango: 260g

VERDURAS
[  ] Ajo: 51g
[  ] Ajos Tiernos: 188g
[  ] Brócoli: 56g
[  ] Calabacín: 35g
[  ] Cebolla: 109g
[  ] Cebolla Morada: 18g
[  ] Espinacas: 361g
[  ] Espárrago Triguero: 141g
[  ] Lechuga Iceberg: 16g
[  ] Rúcula: 9g
[  ] Tomate Rojo: 528g
[  ] Tomates Cherry: 104g

FRUTOS SECOS
[  ] Nueces: 7g

GRASAS
[  ] Aceite De Oliva: 62g
[  ] Mantequilla: 4g

SALSAS
[  ] Ligeresa: 16g
[  ] Salsa De Soja (Tamari): 9g

ESPECIAS
[  ] Albahaca Fresca: 
[  ] Orégano Seco: 
[  ] Pimentón: 
[  ] Sal De Mesa: 

CAFÉS E INFUSIONES
[  ] Café Solo: 300g

BEBIDAS SIN
[  ] Agua: 202g

DULCES
[  ] Chocolate Negro 70-85%: 24g
[  ] Miel: 3g

SUPLEMENTOS
[  ] Proteína Whey: 80g
[  ] Vitamina D: 4g
TXT;

// ---------- Run with all lists you want to consolidate ----------
$lists = [$listA, $listB];
$result = processIngredients($lists);

// ---------- Output ----------
echo "Lista de la compra (pegar en Apple Reminders o Notas para checklist):\n";
foreach ($result as $name => $d) {
    $line = "- [ ] {$name}: {$d['quantity']}{$d['unit']}";
    if (!empty($d['commonMeasure']) && $d['commonMeasure'] !== 'Conversion not available') {
        $line .= " ({$d['commonMeasure']})";
    }
    echo $line . "\n";
}
