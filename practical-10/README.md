# Practical 10: Secure login, logout, roles, and sessions

## Setup

1. Start Apache and MySQL in XAMPP.
2. Register the Practical 9 database first by importing `../practical-9/schema.sql` if it is not already set up.
3. In phpMyAdmin, select `studenthub_db` and import `user_registration.sql`. This adds roles, last-login timestamps, remember-token storage, and the administration tables, and seeds two demonstration accounts. If the file was already imported, run it again after updating the project so the new tables are created.
4. Open `http://localhost/webdev/practical-10/login.php` or the portal's
   `http://localhost/webdev/login.html` shortcut.

The demo credentials are:

| Role | Username or email | Password |
|---|---|---|
| Student | `student_demo` or `student.demo@studenthub.test` | `Student@123` |
| Admin | `admin_demo` or `admin.demo@studenthub.test` | `Admin@123` |

These are lab-only accounts. Change the passwords or remove the accounts before deployment.
New Practical 9 registrations receive the `student` role by default. To grant an account admin access, use phpMyAdmin as the database administrator:

```sql
UPDATE user_accounts SET role = 'admin' WHERE username = 'your_admin_username';
```

Administrators sign in with their own account and are sent to the administrator dashboard. From there they can add/remove student accounts, record attendance, manage the timetable, and approve or reject leave requests. Every administration page checks the `admin` role on the server. Student accounts are restricted to the student dashboard, profile, and student services; they cannot open administration pages.

## Security behavior

- Passwords are checked with `password_verify()`, and the session ID is regenerated on login.
- Admin and student dashboards each enforce their role server-side; direct cross-role access returns HTTP 403.
- Authenticated requests enforce a 15-minute idle timeout and redirect expired sessions to `timeout.php`.
- Logout requires a CSRF token, revokes the current remember-me token, expires cookies, and destroys the session.
- Remember-me uses a random selector and validator. Only the validator's SHA-256 hash is stored in MySQL; a valid token is rotated on use.
- Sessions use strict mode, HTTP-only cookies, and `SameSite=Lax`; cookies are marked secure when served over HTTPS.
- Successful login updates `last_login_at`.

## Test report checklist

Capture screenshots for:

1. Student and admin login success and their different dashboards.
2. Invalid email/username or password error.
3. A student opening `admin_dashboard.php` and receiving HTTP 403.
4. An admin opening `student_dashboard.php` and receiving HTTP 403.
5. Successful logout followed by denied dashboard access.
6. Session timeout redirect. For a quick local demonstration, set `SESSION_IDLE_TIMEOUT` to a small value in `auth.php`, then restore it to 900 seconds.
7. Session ID change across login. Compare the PHP session cookie before and after authentication in browser developer tools.
8. Remember-me behavior after closing and reopening the browser; verify the token row stores a hash, not the cookie validator.
