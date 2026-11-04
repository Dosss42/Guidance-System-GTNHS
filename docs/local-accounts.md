# Local sample accounts

These accounts exist only on a **local development** copy of the GTNHS Guidance Management System. `database/seeders/DatabaseSeeder.php` creates them, and only when `APP_ENV=local`. They use synthetic names. They are not real staff accounts and must never be used in production.

## Sign in

Start the app with `composer run dev`, then open **http://localhost:8000/login**.

| Role | Name | Email | Password | Lands on |
| --- | --- | --- | --- | --- |
| System Administrator | Ricardo Torres | `admin@gtnhs.test` | `Sample-Pass-2026` | `/admin`: overview, accounts, audit log |
| Guidance Counselor | Marites Santos | `m.santos@gtnhs.test` | `Sample-Pass-2026` | `/counselor`: today's desk |
| Guidance Counselor | Jonathan Ramos | `j.ramos@gtnhs.test` | `Sample-Pass-2026` | `/counselor`: today's desk |

Two counselor accounts are included so you can check shared access: each counselor signs in separately, and every entry is signed with the name of whoever made it.

## What to expect

- Each role sees only its own workspace. A counselor who opens an `/admin` page gets "403 Forbidden", and so does an administrator who opens `/counselor`.
- After 5 wrong passwords for the same email from the same computer, sign-in pauses for one minute.
- A deactivated account can't sign in. If it's deactivated while the person is signed in, they're signed out on their next click.
- Every sign-in, failed sign-in, sign-out and account change appears in the administrator's **Audit log**.

## Reset the sample data

This command deletes everything in the local database and recreates the roles and the three accounts above:

```sh
php artisan migrate:fresh --seed
```

Only run it on your own development copy. It erases every record in that database.

## Create more accounts

Sign in as the administrator, open **Accounts**, then choose **Create account**. New passwords need at least 10 characters, with upper- and lower-case letters and a number.

## Before going live

- The sample accounts are never created outside the local environment.
- Real accounts are created by the System Administrator, one per person, with no shared logins.
- Never use the sample password, or any password written in this file, for a real account.
