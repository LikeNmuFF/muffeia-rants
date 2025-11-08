# MUFFEIA — Anonymous Support Platform

## System Architecture

```
┌─────────────────┐
│  Auth System    │  ← Login, Register, Password Reset
└─────────────────┘
         │
         ▼
┌─────────────────┐
│   Base Frame    │  ← Header, Navigation, Footer, Theme System
└─────────────────┘
         │
         ├─────────────────┬─────────────────┬─────────────────┐
         ▼                 ▼                 ▼                 ▼
┌─────────────────┐ ┌─────────────────┐ ┌─────────────────┐ ┌─────────────────┐
│  User Profile   │ │ Post Problems   │ │ Messages        │ │ Notifications   │
└─────────────────┘ └─────────────────┘ └─────────────────┘ └─────────────────┘
         │                 │                  │                      │
         │                 │                  │                      │
         └─────────────────┴──────────┬──────┴──────────────────────┘
                                     │
                                     ▼
                          ┌─────────────────┐
                          │  File Uploads   │  ← Profile Pictures, Attachments
                          └─────────────────┘
                                     │
                                     ▼
                          ┌─────────────────┐
                          │ MySQL Database  │  ← Data Storage
                          └─────────────────┘
```

## Deployment Information

This is a plain PHP + MySQL web application that can be deployed to InfinityFree (a free shared PHP host) with a few configuration steps and caveats. This README explains what to change and how to test the site after deployment.

## Quick summary
- The app uses mysqli (prepared statements) and plain PHP files.
- No Composer or vendor/ directory detected.
- Uploads use PHP `$_FILES` + `move_uploaded_file()` (profile images limited to ~5 MB in code).
- Email currently uses `mail()` (may not work reliably on InfinityFree).

## Prerequisites on InfinityFree
- An InfinityFree account and a hosted domain/subdomain provisioned.
- A MySQL database created in InfinityFree's control panel.
- FTP access (or use the InfinityFree file manager) to upload files.

## Files and directories to check before upload
- `includes/db.php` — update DB host, username, password, and database name (example below).
- `uploads/` and `uploads/profile_pics/` — ensure these directories exist on the host and are writable.
- `test_email.php` — used for a quick email test; note that `mail()` may fail.

## Step-by-step deploy instructions
1. Export your local MySQL database (e.g., using phpMyAdmin or `mysqldump`).
   - If using phpMyAdmin: select your DB -> Export -> Quick -> SQL.
2. Create a new MySQL database in the InfinityFree control panel. Note the DB hostname, DB name, DB username and password provided by InfinityFree.
3. Upload all project files to the InfinityFree site's `htdocs` (or appropriate public folder) using FTP or the file manager. Keep the same relative structure.
4. Import your SQL dump via InfinityFree phpMyAdmin.
5. Update database credentials in `includes/db.php`.

Example change (replace values with ones from InfinityFree):

```php
// includes/db.php (example)
$servername = "sql123.epizy.com"; // InfinityFree DB host
$username = "epiz_12345678";    // InfinityFree DB user
$password = "your_password_here"; // InfinityFree DB password
$dbname = "epiz_dbname";         // InfinityFree DB name

$conn = new mysqli($servername, $username, $password, $dbname);
if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}
```

6. Ensure upload directories exist and are writable:
   - Use the file manager to create `uploads/` and `uploads/profile_pics/` if they aren't present.
   - Default permissions on shared hosts typically allow PHP to write to folders under your `htdocs`.
7. (Optional) If you hit upload size limits, add a `.user.ini` in your project root with desired PHP limits (subject to host support):

```ini
upload_max_filesize = 8M
post_max_size = 8M
memory_limit = 128M
max_execution_time = 30
```

Note: InfinityFree may restrict some php.ini settings; consult their docs.

8. Test main workflows:
   - Visit the site, register/login, post a problem, upload a profile picture, and send a message.
   - If file uploads fail, verify `upload_max_filesize` and `post_max_size`.

## Email (important)
- `test_email.php` and other code currently use the built-in `mail()` function, which is commonly restricted on free hosts. Do not rely on `mail()` working on InfinityFree.
- Recommended approaches:
  1. Use a transactional email provider (SendGrid, Mailgun, Mailjet) and send via their HTTP API.
  2. Use PHPMailer (or similar) with authenticated SMTP if InfinityFree allows outbound connections on SMTP ports. Note: many free hosts block SMTP ports to prevent spam.

Example PHPMailer (very small snippet):

```php
// send-via-smtp.php (example)
use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\SMTP;

require 'path/to/PHPMailer/src/Exception.php';
require 'path/to/PHPMailer/src/PHPMailer.php';
require 'path/to/PHPMailer/src/SMTP.php';

$mail = new PHPMailer(true);
try {
    $mail->isSMTP();
    $mail->Host = 'smtp.example.com';
    $mail->SMTPAuth = true;
    $mail->Username = 'smtp-user';
    $mail->Password = 'smtp-pass';
    $mail->SMTPSecure = PHPMailer::ENCRYPTION_STARTTLS;
    $mail->Port = 587;

    $mail->setFrom('from@example.com', 'Muffeia');
    $mail->addAddress('recipient@example.com');
    $mail->Subject = 'Test';
    $mail->Body = 'Hello from PHPMailer';

    $mail->send();
    echo 'Message sent';
} catch (Exception $e) {
    echo "Message could not be sent. Mailer Error: {$mail->ErrorInfo}";
}
```

For API-based services (recommended), follow provider docs to call their HTTP API instead of SMTP.

## Cron and background tasks
- InfinityFree free hosting does not provide cron jobs. If your app needs scheduled tasks (e.g., clearing old notifications, sending digest emails), use an external cron service (e.g., cron-job.org) or move to hosting that provides cron.

## Configuration suggestion (make switching hosts easier)
Create a minimal `includes/config.php` and load it from `includes/db.php` (or directly in the bootstrapping file). Example pattern:

```php
// includes/config.php
// For local development, keep a .env or set constants. For production, update these values.
define('DB_HOST', 'localhost');
define('DB_USER', 'root');
define('DB_PASS', '');
define('DB_NAME', 'muffeia');
```

Then in `includes/db.php`:

```php
require_once __DIR__ . '/config.php';
$conn = new mysqli(DB_HOST, DB_USER, DB_PASS, DB_NAME);
```

This avoids having to edit many files when switching environments.

## Troubleshooting tips
- Blank page / 500 error: enable PHP error display in `.user.ini` temporarily or check InfinityFree logs.
- Database connection errors: confirm DB host/name/user/password exactly as in the control panel.
- Upload failures: check `upload_max_filesize` and `post_max_size` and ensure the HTML form's `enctype="multipart/form-data"` is set.
- Email failures: try a simple SMTP test with PHPMailer and check firewall / port blocking.

## What I can do next for you
- Create `includes/config.php` and update `includes/db.php` to use it (small, safe change) so switching between local and InfinityFree is easier.
- Add a PHPMailer example integrated with a provider (SendGrid) if you want to send email reliably.

If you want me to proceed with any of the above, tell me which option to apply and I'll make the change and run a quick local sanity check.
