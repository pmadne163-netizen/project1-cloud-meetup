# KuCL Meetup Project

PHP app for the EC2 + S3 deployment guide — the S3-backed follow-up to
KuCL Mini Project.

- **Meetups** — create / view / edit / delete, each one a single JSON
  object in S3 (`meetups/<id>.json`)
- **RSVPs** — stored as an array *inside* each meetup's JSON object, so
  one meetup = one file, no database at all

## Structure

```
kucl-meetup-project/
├── .env.example        # copy to .env and fill in (see deployment guide Step 6)
├── composer.json
├── config.php           # env loading, session, error handling
├── s3.php                # S3 client + JSON get/put/delete/list helpers
├── index.php              # dashboard
├── meetups.php             # list + create
├── meetup_view.php          # detail view + RSVP form + attendee list
├── meetup_edit.php
├── meetup_delete.php
├── rsvp_add.php / rsvp_delete.php
├── includes/header.php, footer.php
├── assets/style.css
└── api/setup_bucket.php  # verifies bucket access, lays down the prefix (Step 7)
```

## Data model

Each meetup is stored as one JSON object:

```json
{
  "id": "20260815-4af9c2b1",
  "title": "KuCL July Meetup",
  "description": "Monthly community meetup.",
  "date": "2026-08-15",
  "location": "Udaipur Tech Hub",
  "organizer": "Jane Doe",
  "capacity": 30,
  "created_at": "2026-07-06T10:00:00+00:00",
  "updated_at": "2026-07-06T10:00:00+00:00",
  "rsvps": [
    { "rsvp_id": "a1b2c3d4e5f6", "name": "Sam Patel", "email": "sam@example.com", "created_at": "2026-07-06T11:00:00+00:00" }
  ]
}
```

## Local setup (on the EC2 instance)

Follow the deployment guide PDF for the AWS-side steps (IAM role, S3 bucket,
EC2 launch, LAMP install). Once the code is on the instance:

```bash
cd /var/www/html/kucl-meetup
composer install --no-dev --optimize-autoloader
cp .env.example .env
nano .env                       # fill in S3_BUCKET, leave AWS keys blank
php api/setup_bucket.php        # should print "Bucket is ready."
```

Then point Apache's doc root at this folder (Step 8 of the guide) and visit
`http://<EC2_PUBLIC_IP>/`.

## Notes

- `display_errors` is off by design (`config.php`) — check the Apache error
  log if something looks broken.
- Leave `AWS_ACCESS_KEY_ID` / `AWS_SECRET_ACCESS_KEY` blank in `.env` on EC2;
  the SDK picks up the instance's IAM role automatically.
- Because everything lives in one JSON object per meetup, there's no risk of
  RSVPs and meetup details getting out of sync across tables — a single
  `putObject` call updates both together.
- This is a demo pattern: S3 has no row-level locking, so concurrent RSVPs
  at the same instant can in rare cases overwrite each other. Fine for a
  mini project; for production, consider DynamoDB (see the KuCL Mini
  Project) or S3 conditional writes.
