# Overview
The `merge-lists.php` script consolidates multiple ingredient lists exported from the INDYA app into a single shopping checklist. It parses plain-text checklist lines, aggregates repeated entries, and accompanies quantities with simple household conversions so that the merged output can be pasted directly into iOS Notes or Reminders. The primary problem it tackles is the proliferation of duplicate items, inconsistent quantities, and disparate list sources when exporting several recipes at once. This documentation targets INDYA power users and maintainers who curate weekly meal plans and need a reliable way to combine numerous exports without manual copy-editing.

# Current Behavior (based on code analysis)
## Input ingestion
- Lists are provided as raw multiline strings (see `$listA` and `$listB` in `merge-lists.php`).
- There is no command-line interface yet; inputs are hard-coded, and the script must be edited to change sources.
- Each list is passed to `processIngredients()` as an array element, so the script can theoretically handle any number of preloaded lists.

## Parsing
- `extractIngredients()` splits each list into individual lines and applies two regular expressions that match checklist (`[ ] Ingredient: 123g`) and dash (`- Ingredient: 123g`) syntaxes.
- Only integer quantities followed by `g` or `ml` are recognized. Lines without numeric quantities (e.g., spices without measured amounts) are ignored.
- Ingredient names are kept exactly as written; units are lowercased.

## Normalization and merging
- No linguistic normalization is performed: names retain capitalization, accents, pluralization, and synonyms exactly as supplied.
- Duplicate detection is based on exact string matches. If the same name reappears with the same unit, quantities are summed. If the unit differs, the script creates a separate key by appending the unit (e.g., `"Tomate Rojo [ml]"`).
- Ingredients are sorted alphabetically using `strnatcasecmp`, yielding a case-insensitive natural order for the final list.

## Quantity enrichment
- `convertToCommonMeasure()` adds household conversions. For `g` and `ml`, it reports the equivalent number of tablespoons and cups using fixed conversion factors (15 ml per tablespoon, 240 ml per cup) and returns a friendly string such as `"2.00 tablespoons or 0.50 cups"`.
- A bespoke rule translates `Huevo Crudo` (raw egg) weights into egg counts, assuming 60 g per egg.
- Units beyond `g` and `ml` yield the placeholder `Conversion not available`.

## Output format
- The script writes to STDOUT, prefacing the list with the Spanish label `Lista de la compra (pegar en Apple Reminders o Notas para checklist):`.
- Each ingredient becomes a Markdown checkbox entry: `- [ ] Name: 123g (1.00 tablespoons or 0.50 cups)`.
- Blank lines, category headers, or indentation from the source lists are not preserved.

## Current limitations
- Inputs must be edited directly in the script; there is no `--input` or `--output` flag handling.
- Name handling is fragile: it does not lowercase, strip accents, singularize, or map synonyms, so `Tomate` and `Tomates` remain separate entries.
- Unit handling is limited to `g` and `ml`; it cannot merge entries expressed in cups, teaspoons, or whole units.
- Ingredient types are unknown; everything is treated uniformly, so solids, liquids, and packaged goods cannot be grouped or converted appropriately.
- Household conversions always report tablespoons and cups, even when they are not meaningful (e.g., for oils or powders) and never adjust for densities.
