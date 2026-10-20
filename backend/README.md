# Fitness App — Backend (Laravel)

The API powering the fitness SaaS MVP: auth, onboarding, AI-assisted workout
plan generation, workout logging, and accountability "pods".

## Requirements

- PHP 8.2+ with the `pdo_mysql` extension
- Composer 2.x
- Docker (used here to run MySQL/MariaDB — see below)

## Local setup

### 1. Start the database

A MySQL-compatible database is required. This project was developed against
a MariaDB container (the host's MySQL install was not accessible without
root/sudo, and Docker-based per-project databases is this server's existing
convention):

```bash
docker run -d --name fitness_mysql \
  -e MARIADB_ROOT_PASSWORD=root \
  -e MARIADB_DATABASE=fitness_app \
  -e MARIADB_USER=fitness \
  -e MARIADB_PASSWORD=fitness \
  -p 3309:3306 \
  -v fitness_mysql_data:/var/lib/mysql \
  mariadb:11.4

# Also create the test database used by phpunit.xml
docker exec fitness_mysql mariadb -uroot -proot -e \
  "CREATE DATABASE IF NOT EXISTS fitness_app_testing; GRANT ALL ON fitness_app_testing.* TO 'fitness'@'%';"
```

If you already have a MySQL/MariaDB server, just create a `fitness_app`
database and a `fitness_app_testing` database and update `.env` accordingly.

### 2. Install dependencies and configure environment

```bash
composer install
cp .env.example .env   # if not already present
php artisan key:generate
```

Set these values in `.env` (already the defaults checked into this repo's
`.env` during development):

```
DB_CONNECTION=mysql
DB_HOST=127.0.0.1
DB_PORT=3309
DB_DATABASE=fitness_app
DB_USERNAME=fitness
DB_PASSWORD=fitness

# Optional. Leave blank to disable the LLM "coach note" feature — the
# rule-based plan generator (the actual workout logic) works fully without it.
OPENAI_API_KEY=
OPENAI_MODEL=gpt-4o-mini
```

### 3. Run migrations and start the server

```bash
php artisan migrate
php artisan serve --port=8010
```

The API is now available at `http://localhost:8010/api`.

### 4. Run the test suite

```bash
./vendor/bin/phpunit
```

23 feature tests cover auth, onboarding/plan generation (including adaptive
progression logic), workout logging, and pod auto-matching/check-ins. You may
see "Test Considered Risky" annotations — this is a known benign PHPUnit
11 + Laravel 12 cosmetic warning, not a real failure (confirmed via `--debug`
output and overall exit code 0).

## Architecture notes

- **AI plan generation** is deliberately split into a deterministic
  [`RuleBasedPlanGenerator`](app/Services/Plan/RuleBasedPlanGenerator.php)
  (always used — exercise selection, sets/reps/rest, adaptive progression
  based on logged adherence) wrapped by an optional
  [`LlmAssistedPlanGenerator`](app/Services/Plan/LlmAssistedPlanGenerator.php)
  that only adds a short encouraging `coach_note` via OpenAI when
  `OPENAI_API_KEY` is set. This keeps the core product deterministic,
  testable, and free to run.
- **Auth** uses Sanctum personal access tokens (Bearer tokens), not
  cookie-based SPA auth, so the same mechanism works uniformly across the
  web app, the Flutter mobile app, and any future client.
- See [routes/api.php](routes/api.php) for the full API surface.
