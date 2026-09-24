# PickleHub

PickleHub is a Laravel-based pickleball club website for court reservations, events, matchmaking, and live scoring. It is designed for a single court and supports both authenticated players and guests.

## Features

### Court bookings

- Monthly availability calendar
- Daily schedule from **7:00 AM through midnight**
- Single-court configuration: `PickleHub Court`
- Multiple-hour selection before confirmation
- Guest bookings without an account
- Persistent bookings stored in the database
- Conflict prevention for already-booked hours
- Cancelled booking slots become available again
- Admin booking management and cancellation

### Events and matchmaking

- Admin event creation and editing
- Event capacity, date, time, description, and scorekeeper PIN
- Authenticated users can register for events
- Players can choose or update their display name
- Randomized doubles matchmaking
- Public event pages with player lists, matchups, and results
- Guests can generate the first bracket once an event has enough players

### Event scoring

- Public event match score viewing
- PIN-protected score updates
- Traditional pickleball scoring validation
- Winning score must reach at least 11 points and lead by 2
- Admin controls for match scoring and bracket regeneration

### Public scoreboard

The standalone scoreboard is available at [`/scoring`](http://localhost:8000/scoring) and does not require login.

- Singles and doubles modes
- Side-out scoring
- Only the serving side scores
- Doubles Server 1/Server 2 tracking
- Opening doubles exception starts at Server 2
- Right- and left-court serve indicator
- Target scores of 11, 15, or 21
- Win-by-two logic
- Best-of-1, best-of-3, and best-of-5 matches
- Team name entry
- Large courtside-friendly scoring buttons
- Undo for every recorded rally
- Game and match winner indicators
- Standard score call display
- Serve-point and return-win statistics
- Event log for points and side-outs

## Requirements

- PHP 8.2 or newer
- Composer
- Node.js and npm
- SQLite, MySQL, or another Laravel-supported database

## Installation

Clone the repository and install the dependencies:

```bash
git clone https://github.com/dariusagbon/Garaje-PickleHub.git
cd PickleHub
composer install
npm install
```

Create the environment file and application key:

```bash
copy .env.example .env
php artisan key:generate
```

Configure the database in `.env`. The default setup uses SQLite:

```env
DB_CONNECTION=sqlite
```

Create the SQLite database if it does not exist, then run migrations:

```bash
type nul > database\database.sqlite
php artisan migrate
```

Build the frontend assets:

```bash
npm run build
```

Start the local application:

```bash
php artisan serve
```

Open [http://localhost:8000](http://localhost:8000).

For frontend development with hot reload:

```bash
npm run dev
```

## Admin access

Add the administrator values to `.env`:

```env
ADMIN_NAME=Administrator
ADMIN_EMAIL=admin@example.com
ADMIN_PASSWORD=change-this-password
```

Run the admin seeder:

```bash
php artisan db:seed --class=AdminUserSeeder
```

Then sign in at [`/login`](http://localhost:8000/login) and open [`/admin`](http://localhost:8000/admin).

The admin dashboard includes:

- Event creation, editing, and cancellation
- Scorekeeper PIN management
- Event matchmaking controls
- Court booking review and status updates

## Application routes

| Route | Purpose | Access |
| --- | --- | --- |
| `/` | Landing page, events, and court calendar | Public |
| `/scoring` | Standalone public scoreboard | Public |
| `/events/{event}` | Event details, registrations, matchups, and scores | Public |
| `/login` | User login | Public |
| `/register` | Player account creation | Public |
| `/admin` | Admin dashboard | Admin |
| `/admin/events` | Event management | Admin |
| `/admin/bookings` | Booking management | Admin |

## Testing

Run the Laravel feature and unit tests:

```bash
php artisan test
```

Run the standalone scoring reducer tests:

```bash
npm run test:scoring
```

The scoring tests cover:

- Doubles server-number transitions
- The opening Server 2 exception
- Side-outs without score changes
- Singles scoring
- Win-by-two rules
- Best-of match completion
- Undo behavior
- Point and side-out event logging

## Project structure

```text
app/
  Http/Controllers/       Booking, events, authentication, and admin actions
  Models/                 Users, bookings, events, registrations, and matches
database/
  migrations/             Application schema
  seeders/                Admin account setup
resources/
  css/app.css             Landing, auth, admin, and scoreboard styles
  js/app.js               Calendar, booking, and scoreboard UI behavior
  js/scoring-reducer.js   Pure scoring state reducer
  views/                  Blade pages and shared admin components
routes/
  web.php                 Public, authenticated, and admin routes
tests/
  Feature/                Laravel feature coverage
  scoring-reducer.test.js Pure JavaScript scoring tests
```

## Scoring model

The public scoreboard keeps scoring logic independent from the UI. Its reducer tracks:

- Each team's current score
- Serving team
- Doubles server number
- Game and match format
- Games won
- Rally statistics
- Full point and side-out event history

Actions are dispatched as:

```js
{ type: 'SCORE', team: 'A' }
{ type: 'UNDO' }
```

This makes the scoring rules testable without a browser and keeps the courtside interface responsive and simple.

## License

This project is based on Laravel and is intended for the PickleHub application. Refer to the repository owner for project-specific licensing and deployment terms.
