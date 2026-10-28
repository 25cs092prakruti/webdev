# Practical 12: Event Management

1. Import `schema.sql` in phpMyAdmin after the existing Student Hub database setup.
2. Sign in with an administrator account.
3. Open **Event Management** from the Admin Dashboard.

The module supports:

- Admin-only add, view, update, and delete event operations.
- Event category and open/closed status management.
- Poster uploads limited to JPG, PNG, GIF, and WEBP images.
- A server-side 2 MB file-size limit, MIME validation, and image validation.
- Random poster filenames and a protected upload directory.
- Success and failure messages, CSRF protection, and prepared MySQLi statements.
