# Resource Booking for HumHub

An MRBS-inspired resource booking module for HumHub. Users book resources grouped in categories; administrators
manage the resources, categories and booking rules.

- Full documentation: [docs/README.md](docs/README.md)
- Installation guide: [docs/INSTALLATION.md](docs/INSTALLATION.md)
- Developer documentation: [docs/DEVELOPER.md](docs/DEVELOPER.md)
- Changelog: [docs/CHANGELOG.md](docs/CHANGELOG.md)

## Quick start

1. Download `resource-booking-<version>.zip` from the releases page, or copy this directory to
   `protected/modules/resource-booking` of your HumHub installation.
2. Enable **Resource Booking** under **Administration → Modules**. The migrations run automatically.
3. Create a category and at least one resource under **Administration → Resource Booking**.
4. Configure the default booking rules under **Administration → Resource Booking → Settings**.

See [docs/INSTALLATION.md](docs/INSTALLATION.md) for the console installation and the reminder queue setup.

Requires HumHub 1.19+ and PHP 8.2+.

## Local development stack

A ready-to-use HumHub + MariaDB stack is included in `docker-compose.yml`:

```bash
docker compose up -d
```

- HumHub is available at <http://localhost:8081> (the container listens on `8080`, the host port is `8081`
  to avoid clashes with other local services).
- The module directory is bind-mounted to `/data/modules-custom/resource-booking`, so edits are picked up
  immediately.
- Database credentials are `humhub` / `humhub` (database `humhub`) and are pre-configured for the installer.
  They are used by HumHub to reach MariaDB and are **not** a web login.
- The web login for this stack is **admin / humhub-admin**.

On a fresh volume the database is set up automatically; complete the remaining installer steps in the browser
or create the admin account and enable the module from the console:

```bash
docker compose exec -u www-data humhub /app/yii installer/create-admin-account admin admin@example.com humhub-admin
docker compose exec -u www-data humhub /app/yii module/enable resource-booking
```

Change the admin password before exposing the stack beyond your machine.
