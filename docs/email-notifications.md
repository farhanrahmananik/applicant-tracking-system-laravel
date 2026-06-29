# Email Notifications

The ATS uses queued Laravel Notifications for candidate-facing workflow messages. Notifications currently cover application creation, interview scheduling changes, interview cancellation, and sent or resolved offers.

## Local Development

Local environments should use these safe defaults:

```dotenv
MAIL_MAILER=log
MAIL_LOG_CHANNEL=stack
MAIL_FROM_ADDRESS="no-reply@ats.test"
MAIL_FROM_NAME="${APP_NAME}"
QUEUE_CONNECTION=database
```

Queued notifications are stored in the `jobs` table. Process them locally with:

```bash
php artisan queue:work --queue=notifications,default
```

With the log mailer, rendered messages are written to `storage/logs/laravel.log`; no external email is delivered. Stop the worker with `Ctrl+C` after testing.

## Production SMTP

Set `MAIL_MAILER=smtp` and provide the SMTP host, port, scheme, username, password, and verified sender through deployment secrets. Do not commit credentials or production addresses to `.env.example`.

Run a supervised queue worker in production so notification jobs are retried and failed jobs are retained. Use TLS, a verified sending domain, least-privilege credentials, and provider-side delivery monitoring.

## Recipient Safety

Messages are routed only to the email stored on the related candidate record. Missing or malformed addresses are skipped. The templates contain workflow context but no private resume content, interview feedback, internal notes, or public ATS links.
