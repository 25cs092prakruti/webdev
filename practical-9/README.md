# Practical 9: Secure user registration

## Setup

1. Start Apache and MySQL in XAMPP.
2. Open phpMyAdmin and import `schema.sql`. It creates the `user_accounts` and
   `registration_audit` tables in `studenthub_db` without changing the Practical 8 tables.
3. If your local MySQL credentials differ from XAMPP's defaults, update
   `db.php`.
4. Open `http://localhost/webdev/practical-9/register.php`.
5. Register an account, then sign in at `http://localhost/webdev/login.html`.
   The dashboard and `http://localhost/webdev/profile.php` show the account
   currently signed in. Use **Sign out** to end that session.

The form performs browser-side and PHP-side validation, checks email and
username uniqueness, inserts with MySQLi prepared statements, stores passwords
using `password_hash()`, and writes a registration audit event in the same
database transaction. The schema's unique indexes also prevent duplicates if
two registration requests arrive at the same time.
The portal login accepts the registered username or email and verifies the
password with `password_verify()`.

## Registration test cases

| Case | Example | Expected result |
|---|---|---|
| Valid registration | `prakruti1`, `prakruti1@example.com`, matching password of at least 8 characters | Account and audit event are inserted; success message is shown |
| Empty fields | Leave one or more fields empty | Browser and server reject the submission |
| Invalid email | `not-an-email` | Browser and server reject the submission |
| Invalid username | `ab` or `bad name` | Browser and server reject the submission |
| Password mismatch | Different password and confirmation | Browser and server reject the submission |
| Short/long password | Under 8 characters or over 72 bytes | Browser and server reject the submission |
| Duplicate email | Reuse an existing account's email | No account is created; duplicate-email message is shown |
| Duplicate username | Reuse an existing account's username | No account is created; duplicate-username message is shown |
| Password storage | Query `user_accounts.password_hash` for a test account | Stored value is a password hash, not the submitted password |
