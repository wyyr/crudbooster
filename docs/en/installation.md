# Install CRUDBooster

## Requirements

- Laravel 12 or 13 and the PHP version it requires (8.2 or later)
- PHP extensions: Mbstring, OpenSSL, PDO, Tokenizer, XML, GD, and Fileinfo
- Composer and a configured database

Some database-inspection features use MySQL-specific queries, so other database drivers may not be fully supported.

## Installation

Set up your Laravel database in `.env`, then install CRUDBooster:

```bash
composer require wyyr/crudbooster
```

Back up your database before running the installer. It publishes files, runs migrations, and offers to seed the database:

```bash
php artisan crudbooster:install
```

The seeder creates an admin account if none exists:

- Email: `admin@crudbooster.com`
- Password: `123456`

Change this password immediately, and never leave it active on a public site.

Admin login: `/admin/login`

## What's Next

- [How To Create A Module (CRUD)](./how-to-create-module.md)
- [Back To Index](./index.md)
