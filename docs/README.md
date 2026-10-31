# Resource Booking for HumHub

A lightweight, MRBS-inspired resource booking module for HumHub. Users book shared resources such as meeting
rooms, desks, vehicles or equipment. Administrators organise resources into categories and define the rules that
apply to bookings.

The module is deliberately dependency-free beyond HumHub itself. It uses the HumHub UI, database layer, permission
system and modal framework.

## Features

- **Categories** with a colour, description, sort order and active state.
- **Resources** with category, location, capacity, description and an optional own colour.
- **Day view** with two layouts: a resource timeline with one column per resource and an agenda list. Both show
  every resource for the selected day. Resources scroll horizontally when they do not fit.
- **Drag to book**: click and drag over one or more timeline slots to create a booking for that exact time span.
  A single click still creates a booking for one slot.
- **Bookings** with title, description, start/end time and status.
- **Approval workflow**: optionally require approval per category or globally. Booking managers approve or reject
  pending bookings from a dedicated pending approvals page or from the booking details.
- **Booking rules**, configurable globally and overridable per category:
  - minimum and maximum duration
  - bookable hours
  - bookable weekdays
  - maximum advance booking period
  - buffer between two bookings of the same resource
  - maximum bookings per user per day and per week
  - cancellation deadline
- **Conflict detection** prevents overlapping bookings and respects the buffer. Booked slots are not selectable
  in the timeline.
- **Reminders**: optionally notify users a configurable number of minutes before their booking starts, through
  any notification target they have enabled (web, e-mail, mobile).
- **My bookings** page with all bookings and their status, linked from the day view header.
- **Admin area** for settings, categories, resources and all bookings.
- **Timezone aware**: bookings are stored in UTC and displayed in the user's HumHub timezone.
- **HumHub permissions**: `Create bookings` and `Manage bookings`.

## Requirements

- HumHub 1.19 or later
- PHP 8.2 or later
- Pretty URLs enabled (HumHub requirement for modules with custom routes)

## Installation

See [INSTALLATION.md](INSTALLATION.md) for release archives, console installation, updates and the reminder queue setup.

1. Copy this directory to `protected/modules/resource-booking` of your HumHub installation.
2. Open **Administration → Modules** and enable **Resource Booking**. The module migrations run automatically.
3. Open **Administration → Resource Booking** and create at least one category and one resource.
4. Review the booking rules under **Administration → Resource Booking → Settings**.

### Permissions

The module registers two global permissions:

| Permission | Default | Purpose |
| --- | --- | --- |
| `Create bookings` | Allowed for all users | Create and edit own bookings |
| `Manage bookings` | Admin group only | Approve, reject and cancel bookings of other users |

Assign `Manage bookings` to additional groups under **Administration → Users → Permissions** if other users should
act as booking managers.

## How booking rules are resolved

Each category can override a global rule. An empty category value means "inherit the global default". The following
values are resolved at booking time:

- approval required
- minimum and maximum duration
- maximum advance days
- maximum bookings per user per day/week
- buffer minutes
- cancellation deadline
- opening and closing time
- allowed weekdays

Rules are enforced server-side in `models/BookingForm.php`; the same resolved values are shown in the booking form
and in the admin interface.

## Booking statuses

| Status | Meaning |
| --- | --- |
| `pending` | Waiting for a booking manager |
| `confirmed` | Booked and visible in the calendar |
| `rejected` | Rejected by a booking manager |
| `cancelled` | Cancelled by the owner or a manager |

Pending and confirmed bookings block the selected resource. Rejected and cancelled bookings do not.

All bookings in these two states are visible to every logged-in user, including who made the booking. Only the
owner (or a booking manager) can edit or cancel a booking.

## Limitations

The initial release intentionally focuses on the core MRBS workflow:

- Bookings start and end on the same day.
- Recurring bookings are not included yet.
- The calendar is a day view; week and month views can be added without changing the storage model.
- No external calendar synchronisation (iCal/CalDAV) is included.

## Development

See [DEVELOPER.md](DEVELOPER.md).

## License

See [LICENCE.md](LICENCE.md).
