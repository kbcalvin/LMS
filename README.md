# Oak & Ink Community Library

A PHP and MySQL library management system with member accounts, a book catalog,
borrowing requests, librarian approvals, and returns.

## Requirements

- PHP 8.1 or later with PDO MySQL enabled
- MySQL 8 or a compatible MariaDB release
- A web server such as Apache (XAMPP works)

## Setup

1. Create a MySQL database named `cornerstone_library`.
2. Import [`library.sql`](./library.sql) into that database.
3. Configure the database connection using environment variables:

   - `DB_HOST` (default `127.0.0.1`)
   - `DB_PORT` (default `3306`)
   - `DB_NAME` (default `cornerstone_library`)
   - `DB_USER` (default `root`)
   - `DB_PASS` (default empty)

   Do not commit real database credentials.

4. Point Apache's document root to this project directory, or run PHP's
   development server from the project directory:

   ```sh
   php -S 127.0.0.1:8000
   ```

5. Open `http://127.0.0.1:8000/` in a browser and register a member account.

## Creating the first librarian

Registration creates member accounts only. After registering the intended
librarian account, promote it using MySQL, replacing the username as needed:

```sql
UPDATE users SET role = 'librarian' WHERE username = 'your_username';
```

Use a unique, trusted account and do not publish its credentials.

## Uploaded files

Member profile pictures are stored under `uploads/profile_pictures/` and are
excluded from Git. The public library background image in `auth/uploads/lib.jpg`
is part of the application.
