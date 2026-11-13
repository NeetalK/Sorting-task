# Task Manager

A small task manager built with Laravel 13 and PHP 8.3. Tasks belong to projects, and their priority is the visible order in the project list. Drag a task by its handle to save a new order.

## Requirements

- PHP 8.3+ with PDO, `pdo_mysql`, `mbstring`, `openssl`, `tokenizer`, `xml`, `ctype`, and `fileinfo` enabled
- Composer 2
- MySQL 8.0+ (or a compatible MySQL server)

On Windows, XAMPP, Laragon, or a standalone PHP installation can provide PHP. Make sure the required PHP extensions above are enabled in `php.ini`. If you install PHP, Composer, or MySQL while VS Code is open, restart VS Code so its terminals load the updated PATH.

## Local setup

1. In a terminal at the project folder, install the project dependencies:

   ```sh
   composer install
   ```

2. Create the MySQL database and a dedicated local application user (change the password to a private value):

   ```sql
   CREATE DATABASE task_manager CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
   CREATE USER 'task_manager'@'localhost' IDENTIFIED BY 'choose-a-local-password';
   GRANT ALL PRIVILEGES ON task_manager.* TO 'task_manager'@'localhost';
   ```

3. Copy the example environment file and generate the app key. In PowerShell, use `Copy-Item` instead of `cp`:

   ```sh
   Copy-Item .env.example .env
   php artisan key:generate
   ```

   Edit `.env` and set `DB_HOST`, `DB_PORT`, `DB_DATABASE`, `DB_USERNAME`, and `DB_PASSWORD` to match the MySQL user you created.

4. Create the tables and sample projects:

   ```sh
   php artisan migrate --seed
   ```

5. Start the development server and open http://127.0.0.1:8000:

   ```sh
   php artisan serve
   ```

The seed command creates General, Personal, and Work projects. You can add further projects from the project selector on the home page.

## Using the application

- Add tasks using the task name field. New tasks are added to the bottom of the selected project.
- Change the project selector to view that project's tasks only.
- Drag a task by the dotted handle to reorder it. Priorities are saved consecutively: 1 at the top, then 2, and so on.
- Use Edit to rename a task or move it to a different project. Use Delete to remove it.
- Creation and last-update timestamps are maintained automatically by Laravel.

## Tests

The feature tests use an in-memory SQLite database and do not require MySQL:

```sh
composer test
```

Ensure PHP's `pdo_sqlite` extension is enabled to run them. The test suite uses SQLite; the application itself defaults to MySQL.

## Production deployment

1. Provision PHP 8.3+, Composer, and a MySQL database. Configure the web server document root to the project's `public/` directory; do not expose the repository root.
2. Deploy the application code and install production dependencies:

   ```sh
   composer install --no-dev --optimize-autoloader
   ```

3. Set production environment values in `.env` (including a unique `APP_KEY`, `APP_ENV=production`, `APP_DEBUG=false`, `APP_URL`, and the MySQL credentials). Keep `.env` private and out of source control.
4. Run the deployment commands:

   ```sh
   php artisan migrate --force
   php artisan config:cache
   php artisan route:cache
   php artisan view:cache
   ```

5. Ensure `storage/` and `bootstrap/cache/` are writable by the PHP process, serve the site over HTTPS, and point the domain to `public/`.

When deploying for the first time, generate the key before caching configuration with `php artisan key:generate`. Back up the MySQL database before migrations in an existing production environment.
