# Developer documentation

## Structure

| Path | Purpose |
| --- | --- |
| `Module.php` | Module registration, configuration access, permissions |
| `Events.php` | Top menu and admin menu entries |
| `config.php` | Module ID, namespace and event listeners |
| `migrations/` | Database schema |
| `models/Category.php` | Resource category and category-level rule overrides |
| `models/Resource.php` | Bookable resource |
| `models/Booking.php` | Booking record, status and permission helpers |
| `models/BookingForm.php` | Form model and server-side booking validation |
| `models/Configuration.php` | Global module settings |
| `services/BookingRules.php` | Resolves global defaults and category overrides |
| `services/BookingReminders.php` | Schedules and dispatches reminder notifications |
| `jobs/` | Delayed queue jobs |
| `notifications/` | Notification types, category and mail views |
| `controllers/IndexController.php` | Day calendar |
| `controllers/BookingController.php` | Create, edit, view, cancel, approve, reject |
| `controllers/AdminController.php` | Admin settings, categories, resources and bookings |
| `helpers/TimeHelper.php` | UTC storage and user-timezone display |
| `helpers/Url.php` | Module URL helpers |
| `assets/Assets.php` | CSS and JavaScript asset bundle |
| `resources/css/` | Module styles |
| `resources/js/` | Timeline drag-to-book behaviour and toolbar auto-submit |
| `views/` | HumHub views |
| `views/index/_agenda.php` | Agenda layout of the day view |

## Compatibility

- Requires HumHub 1.19+ and PHP 8.2+.
- The module still uses `humhub\modules\ui\menu\MenuLink` and `humhub\modules\ui\icon\widgets\Icon`,
  which are deprecated in HumHub 1.20 and removed in 1.21. Before targeting 1.21, migrate these
  to `humhub\widgets\menu\MenuLink` and `humhub\widgets\Icon`, and switch the Font Awesome 4 icon
  names (e.g. `clock-o`, `calendar-o`, `cogs`) to their Tabler counterparts. See the
  [1.20 migration guide](https://github.com/humhub/humhub/blob/develop/docs/develop/module-migrate-1.20.md).

## Releasing

Marketplace metadata lives in `module.json`. The `screenshots` entries are file names relative to `resources/`,
and `resources/module_image.png` is the module icon.

1. Bump `version` in `module.json` following semantic versioning.
2. Add a dated section to `docs/CHANGELOG.md`.
3. Build the distributable archive:

   ```bash
   ./scripts/build-release.sh
   ```

   This creates `dist/resource-booking-<version>.zip` with a single top-level `resource-booking` directory and
   excludes development-only files (`docker-compose.yml`, `tests/`, `composer.*`, `.phpactor*`, `scripts/`).
4. Commit the changes and tag the release as `v<version>`, attaching the archive to the release.
5. Submit the release to the marketplace. Listings are curated by HumHub; publish the source in a public GitHub
   repository and contact HumHub through <https://www.humhub.com/contact/> (or `info@humhub.com`) with the
   repository URL and the release archive.

## Time handling

All booking timestamps are stored as UTC `DATETIME` values. Form input is interpreted in the current user's
timezone and converted with `TimeHelper`:

- `TimeHelper::combine($date, $time)` creates a local `DateTimeImmutable`.
- `TimeHelper::toDatabase($date)` converts it to a UTC database string.
- `TimeHelper::fromDatabase($value)` converts it back to the user's timezone for display.

`BookingForm` therefore works entirely with local times for rule checks and only converts when writing to the
database. Opening hours, weekdays and quota periods are evaluated in the current user's timezone.

## Rule resolution

`BookingRules::resolve($category)` returns an immutable value object with the effective values. Global defaults
come from `Configuration`, category values override them when they are not `NULL`.

To add a new rule:

1. Add a nullable column to `resourcebooking_category` (and optionally a global setting).
2. Add the field to `Category`, `Configuration` and their views.
3. Add the resolved property to `BookingRules`.
4. Enforce it in `BookingForm::validateBookingRules()`.

## Permissions

- `CreateBooking` is enabled for all users by default.
- `ManageBookings` is denied by default, fixed-allowed for the admin group, and assignable to other groups.
- Admin controllers extend `humhub\modules\admin\components\Controller` and therefore require `ManageSettings`.

## Reminders

The reminder duration is a global setting (`Configuration::$reminderMinutesBefore`, `0` disables reminders).
`BookingReminders::schedule()` pushes a delayed `BookingReminderJob` onto the HumHub queue when a confirmed
booking is created, edited or approved. The job calls `BookingReminders::sendIfDue()`, which re-checks the booking
state and the current reminder window before sending; `resourcebooking_booking.reminder_sent_at` makes delivery
idempotent, so stale jobs from an earlier schedule cannot send twice.

This relies on HumHub's queue worker. When the module runs outside the Docker image, make sure `yii queue/listen`
is running.

## IDE support

This repository is a standalone module, so the PHP language server cannot see the HumHub and Yii classes that
only exist inside a HumHub installation. `.phpactor.json` therefore points Phpactor at a local copy of the HumHub
core via `.phpactor/composer/`. Create that copy from the running Docker stack:

```bash
mkdir -p ~/.cache/phpactor/humhub-core/protected
docker compose cp humhub:/opt/humhub/protected/vendor ~/.cache/phpactor/humhub-core/protected/vendor
docker compose cp humhub:/opt/humhub/protected/humhub ~/.cache/phpactor/humhub-core/protected/humhub
```

Set `HUMHUB_IDE_CORE` if the copy lives elsewhere. Re-run `phpactor index:build` after changing or refreshing the
copy, and restart the language server so the new configuration is picked up.

The `tests/` directory is excluded from Phpactor diagnostics because the HumHub test bootstrap
(`tests\codeception\_support\HumHubDbTestCase`) is only generated in a full HumHub development installation.

## Concurrency

`BookingForm::save()` acquires a MySQL/MariaDB advisory lock per category before the final conflict check and insert.
This prevents two simultaneous requests from double-booking a resource or exceeding the per-user quota of a
category. The lock is released in a `finally` block and is also released automatically when the database
connection ends.

## Tests

The module follows the HumHub module test layout:

```bash
cd /path/to/humhub/protected/modules/resource-booking
vendor/bin/codecept run unit
```

The test bootstrap assumes the module resides in the standard `protected/modules` directory or that `HUMHUB_PATH`
is set.

## Code style

The module follows the HumHub module coding standards:

```bash
composer install
composer fixer
composer rector
```
