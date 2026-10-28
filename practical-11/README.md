# Practical 11: Student Management CRUD

1. Import `schema.sql` in phpMyAdmin after the Practical 9/10 database setup.
2. Sign in with an administrator account.
3. Open `student_management.php` from the Admin Dashboard.

The module supports:

- MySQLi prepared-statement `SELECT`, `INSERT`, `UPDATE`, and soft-delete `UPDATE` operations.
- Search by student name, email, or course.
- Course and active/inactive status filtering.
- Add, edit, restore, and delete confirmation actions.
- Pagination and CSV export compatible with Excel.
- CSRF protection and admin-only server-side authorization.
