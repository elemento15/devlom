# DevLom

DevLom is a Laravel 13, Vue, and Bootstrap single-page application for managing
clients, collaborators, projects, tasks, and task time entries.

## Setup

Requirements: PHP 8.3+, Composer, Node.js 20.19+ (or 22.12+), and npm.

```sh
composer run setup
php artisan serve
```

In another terminal, run `npm run dev` while developing. The app defaults to a
SQLite database at `database/database.sqlite`; use `php artisan migrate:fresh
--seed` to reset it.

## Initial access

- Email: `admin@example.com`
- Password: `DevLom-Admin!2026`

The database seeder creates this single account and the initial task statuses.
There is no registration flow.
