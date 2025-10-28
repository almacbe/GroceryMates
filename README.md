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

### Deploying to Render

This repository includes a `Dockerfile`, `render.yaml`, and `render-start.sh` so you can deploy with a single Render blueprint:

1. Sign in to [Render](https://render.com/) and click **New → Blueprint Instance**.
2. Point it at this repository; Render reads `render.yaml` and provisions:
   - A web service built from the Dockerfile (Apache + PHP 8.2, Node-built assets, Composer dependencies).
   - A managed PostgreSQL database (free tier by default).
3. Once the service is created, open the dashboard and set `APP_KEY` to the value printed by `php artisan key:generate --show` (run locally). The startup script will auto-generate a key if the placeholder remains, but storing it in Render keeps the value stable across deploys.
4. After the first build finishes, update the service’s `APP_URL` env var if Render assigns a different hostname.
4. Optional: adjust the database plan or disable auto-deploy previews if not needed.

The `render-start.sh` entrypoint caches configuration, runs database migrations, ensures `public/storage` is linked, and starts Apache. Migrations expect the managed PostgreSQL database Render provisions; switch to another database by editing `render.yaml` or the service env vars.

### Usage

1. Paste an INDYA-exported checklist into “Lista A” and optionally another into “Lista B”.
2. Press **Merge**.
3. Copy the merged plain text from the “Resultado” area.

The merger trims whitespace, groups duplicates by name ignoring case, and sums quantities when the unit is grams. Lines without quantities are kept once, and any unparseable lines display as validation errors.

### Original CLI logic reference

The original procedural script remains available at the repository root as `merge-lists.php` for comparison with the newer web workflow.
