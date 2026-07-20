# Alumni Network System

PHP + MySQL alumni community platform for profiles, posts, jobs, messaging, notifications, and admin management.

## Features

- Approved-ID based registration
- OTP email verification for registration
- OTP password reset for verified accounts
- Alumni profiles, directory, posts, likes, comments, jobs, messaging, notifications
- Admin dashboard, reports, approvals, and content management
- Responsive UI for mobile, tablet, laptop, and desktop

## Tech Stack

- PHP
- MySQL
- Tailwind CSS
- JavaScript
- PHPMailer

## Setup

1. Import `config/database.sql` into MySQL.
2. Update `config/db.php` with your database credentials.
3. Configure SMTP settings in `include/mail_helper.php`.
4. Place the project in your web server root and open `alumni/homepage.php`.

## Notes

- OTPs expire after 10 minutes.
- Passwords are stored with `password_hash()`.
- Registration requires a valid Approved ID and email verification.
