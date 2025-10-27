# GroceryMates

## Ingredient List Merger (Laravel + Inertia)

This repository hosts a minimal web interface that merges two ingredient lists exported from the INDYA app. It reuses the historic `merge-lists.php` logic, normalises ingredient names case-insensitively, sums duplicate quantities expressed in grams, and returns a plain text checklist ready for iOS Notes or Reminders.

### Prerequisites

- PHP 8.2+
- Composer
- Node.js 18+ and npm

### Installation

```bash
git clone <your-fork-url>
cd grocery-mates
cp .env.example .env
composer install
php artisan key:generate
npm install
```

If Vite hot reloading requires a specific hostname, update `APP_URL` in `.env` accordingly (e.g. `APP_URL=http://127.0.0.1:8000`).

### Running the app

In one terminal:

```bash
npm run dev
```

In another terminal:

```bash
php artisan serve
```

Then open [http://127.0.0.1:8000/merge](http://127.0.0.1:8000/merge).

### Usage

1. Paste an INDYA-exported checklist into “Lista A” and optionally another into “Lista B”.
2. Press **Merge**.
3. Copy the merged plain text from the “Resultado” area.

The merger trims whitespace, groups duplicates by name ignoring case, and sums quantities when the unit is grams. Lines without quantities are kept once, and any unparseable lines display as validation errors.

### Original CLI logic reference

The original procedural script remains available at the repository root as `merge-lists.php` for comparison with the newer web workflow.
