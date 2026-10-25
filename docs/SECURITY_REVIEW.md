# Initial Security Review

This is a first-pass review of the supplied source files, not a full penetration test. Resolve these items before using the system with real member or financial data.

## Functional issue found during validation

- **PHP syntax error / incomplete file:** `hands/contributions.php` ends unexpectedly at line 223 (`unexpected end of file`). The file appears truncated. Restore the missing remaining markup/code from a known-good copy, then run `php -l` on every PHP file and test the contributions page. This was not guessed or auto-repaired because the intended ending is unknown.

## High priority

1. **Hard-coded administrator password bypass** — `login.php` accepts a fixed password (`admin123`) in addition to verifying the stored password hash. Remove the bypass and use `password_verify()` only. If this code has ever been deployed publicly, treat the bypass as exposed and review access logs.
2. **Placeholder administrator hash** — `onix_njangi_database.sql` seeds an administrator with a placeholder hash, not a usable password hash. Provide a secure first-run admin setup/reset process; do not ship a known default password.
3. **Production data backups** — a SQL backup was included in the uploaded archive and contained stored application records. It has been excluded from this cleaned portfolio copy. Never commit backups or real user records.
4. **Authorization and CSRF review** — check that every admin/member action verifies the correct session and role, and add CSRF protection to state-changing forms.

## Medium priority

5. **SQL injection review** — replace string-built SQL statements with prepared statements, including places that currently escape only some values.
6. **Error handling** — avoid displaying raw database errors to public visitors; log details privately.
7. **Uploads** — validate upload type, size, and file contents; prevent executable uploads and avoid committing uploaded profile photos.
8. **Database configuration** — keep credentials in deployment environment variables and verify the configured database port per environment. Do not rely on the current defaults for production.
9. **Backup/restore permissions** — restrict backup/restore functions to authorized administrators and protect backup files from direct web access.

## Suggested acceptance criteria

- No shared or hard-coded admin password exists in the code.
- First administrator setup requires a unique password stored using `password_hash()`.
- Production backups, credentials, and personal uploads are absent from Git history and the current repository. If secrets were committed previously, rotate them; deleting the current file does not remove it from Git history.
- Access-control and CSRF tests cover all create, update, delete, payout, and settings actions.
- SQL queries handling user input use prepared statements.
