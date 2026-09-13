# Serenity Stay — Hotel Booking System (PHP + Oracle)

Web application for managing hotel/hostel bookings: user registration and login, room reservations, payment tracking, and a full admin dashboard (room management, bookings, users, reports).

## Features

- User registration/login (passwords hashed with bcrypt for new accounts)
- Room booking with automatic price calculation based on stay duration
- Payment tracking linked to each booking
- "My Bookings" section for users
- Admin dashboard: room management, bookings, users, and reports
- Connection to an **Oracle Database (XE)**

## Tech Stack

- PHP (vanilla, no framework)
- Oracle Database XE (via the PHP `oci8` extension)
- HTML / CSS / JavaScript on the front end

## Database Schema

4 main tables (see `database_oracle/TABLES_Creation.sql`):
- **users** — user and admin accounts
- **rooms** — available rooms (number, type, capacity, price, status)
- **bookings** — reservations (dates, status, total price)
- **payments** — payments linked to a booking

##  Prerequisites (heavier setup than a typical MySQL project)

This project requires:
1. **A PHP + Apache server** (e.g., XAMPP or WampServer)
2. **Oracle Database XE** installed and configured (heavier than a standard MySQL/SQLite database — around 2-3 GB)
3. **The PHP `oci8` extension** enabled in your `php.ini` (not enabled by default, requires the Oracle Instant Client)

## Installation

1. Clone the repo:
   ```bash
   git clone <YOUR_REPO_URL>
   ```

2. Place the `Hostel/` folder in your web server's directory (e.g., `htdocs/` for XAMPP).

3. Install Oracle Database XE, then run the SQL scripts in order:
   ```
   database_oracle/creating_the_user_in_oracle.sql   -- creates the Oracle user
   database_oracle/TABLES_Creation.sql               -- creates the tables
   database_oracle/insert_Data.sql                   -- inserts demo data
   ```

4. Check/update the connection credentials in `includes/config.php` if needed (configured by default for a local Oracle XE instance).

5. Enable the `oci8` extension in PHP (see the screenshots in `database_oracle/test_connection/` for a working configuration example).

6. Test the database connection with `database_oracle/test_connection/test_oracle.php`.

7. Access the application at `http://localhost/Hostel/`.

##  Important Notes / Known Limitations

- **Demo accounts**: the admin account created by `insert_Data.sql` (`admin@hostel.com`) uses a plaintext password (`hostel123`) for demo/testing purposes only. **New accounts created through the registration form are properly hashed with bcrypt** (see `includes/functions.php`).
- The Oracle connection credentials in `includes/config.php` belong to a local development database — update them for your own setup.
- This project is an academic/portfolio exercise, not deployed to production.

## Technologies
PHP, Oracle Database (oci8), HTML/CSS, JavaScript
