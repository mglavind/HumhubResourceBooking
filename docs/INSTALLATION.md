# Installation

## Requirements

- HumHub 1.19 or later
- PHP 8.2 or later
- The HumHub queue worker for reminder notifications

## Install from a release archive

1. Download `resource-booking-<version>.zip`.
2. Extract it into the `protected/modules` directory of your HumHub installation. The archive contains a
   top-level `resource-booking` directory, so the final path is `protected/modules/resource-booking`.
3. Open **Administration → Modules**, find **Resource Booking** and click **Enable**. The module migrations run
   automatically.
4. Open **Administration → Resource Booking** and create at least one category and one resource.
5. Review the booking rules under **Administration → Resource Booking → Settings**.

On a console-only installation:

```bash
unzip resource-booking-<version>.zip -d /path/to/humhub/protected/modules
cd /path/to/humhub
php yii module/enable resource-booking
```

## Permissions

| Permission | Default | Purpose |
| --- | --- | --- |
| `Create bookings` | Allowed for all users | Create and edit own bookings |
| `Manage bookings` | Admin group only | Approve, reject and cancel bookings of other users |

Assign `Manage bookings` to additional groups under **Administration → Users → Permissions**.

## Reminder notifications

Reminders are optional and disabled when the reminder duration is `0`. Set the duration under
**Administration → Resource Booking → Settings**.

The reminder is delivered through HumHub's notification system, so it uses whichever notification targets the
user has enabled (web, e-mail, mobile). HumHub's queue worker must be running:

```bash
php yii queue/listen
```

E-mail delivery additionally requires a configured SMTP transport under **Administration → Settings → Advanced →
E-Mail**. The official HumHub Docker image does not include a local `sendmail` binary.

## Updating

1. Disable the module under **Administration → Modules**.
2. Replace `protected/modules/resource-booking` with the contents of the new release archive.
3. Enable the module again. New migrations run automatically.

## Uninstalling

Disable the module under **Administration → Modules**, then remove the `protected/modules/resource-booking`
directory. Disabling keeps the booking data; removing the module directory does not drop the database tables.
