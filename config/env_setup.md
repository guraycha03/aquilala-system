# Environment setup for aquilala-system (MySQL)

## 1) Create / edit your `.env`

This project’s `.env` cannot be read by this tool, so you must update it manually.

In your project root (`aquilala-system/.env`), set these (adjust values to your MySQL credentials):

```env
APP_NAME="Aquilala Irrigators"
APP_ENV=local
APP_KEY=base64:YOUR_GENERATED_KEY
APP_DEBUG=true
APP_URL=http://127.0.0.1:8000

LOG_CHANNEL=stack

DB_CONNECTION=mysql
DB_HOST=127.0.0.1
DB_PORT=3306
DB_DATABASE=aquilala_db
DB_USERNAME=your_mysql_user
DB_PASSWORD=your_mysql_password

CACHE_DRIVER=file
SESSION_DRIVER=file
QUEUE_CONNECTION=sync
```

## 2) Create the database

Make sure this database exists:

- `aquilala_db`

## 3) Run migrations

Once PHP + MySQL are available:

```bash
php artisan migrate
```

## 4) (Optional) Create an admin quickly

Use `/register` and choose role=`admin`.

## 5) Start the server

```bash
php artisan serve
```

Then test:

- `/register`
- `/login`
- `/dashboard/admin` (must redirect/403 correctly by role)
