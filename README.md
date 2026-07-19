# ClassPulse

Live Classroom Quiz & Response System (Socrative-style) — Laravel 12 university Web Programming project.

## Stack

- **Backend:** Laravel 12, PHP 8.2+, Eloquent, Breeze (Blade auth)
- **Frontend:** Bootstrap 5, Bootstrap Icons, vanilla JS (Fetch API polling)
- **Database:** MySQL (XAMPP) — SQLite also works for local demos
- **Real-time:** Short-interval AJAX polling (no WebSockets / Reverb / Pusher)

## Why polling (not WebSockets)?

This course stack does not include a Node real-time layer. Live counters, leaderboards, timers, and notifications use Fetch polling every 2–4 seconds against JSON endpoints, with client timers resynced from server timestamps to limit drift.

## Quick start (SQLite)

```bash
composer install
copy .env.example .env
php artisan key:generate
# ensure database/database.sqlite exists
php artisan migrate:fresh --seed
php artisan storage:link
php artisan serve
```

Open http://127.0.0.1:8000

Login accounts (password for all: `password`):

| Role    | Email                    |
|---------|--------------------------|
| Admin   | admin@classpulse.test    |
| Teacher | teacher1@classpulse.test |
| Student | student1@classpulse.test |

No classrooms or quizzes are seeded by default — create those in the UI.

Optional full demo pack (classroom + quiz sample data):

```bash
php artisan db:seed --class=ClassPulseSeeder
```

## MySQL (XAMPP)

1. Create database `classpulse` in phpMyAdmin.
2. In `.env`:

```env
DB_CONNECTION=mysql
DB_HOST=127.0.0.1
DB_PORT=3306
DB_DATABASE=classpulse
DB_USERNAME=root
DB_PASSWORD=
```

3. Run `php artisan migrate:fresh --seed`

Raw SQL reference: `database/schema/classpulse.sql`

## Login accounts

Password for all: `password`

| Role    | Email                    |
|---------|--------------------------|
| Admin   | admin@classpulse.test    |
| Teacher | teacher1@classpulse.test |
| Student | student1@classpulse.test |

These are accounts only — no mock classrooms or live exams.

Optional full demo pack: `php artisan db:seed --class=ClassPulseSeeder` (room code **CPULSE**)

## Features

- Multi-role auth (admin / teacher / student) + middleware
- Classroom CRUD, 6-char room codes, QR join URLs
- Quiz builder (MCQ, True/False, Short Answer) + question images
- Live session control room (pause / next / reveal / end)
- Student live answering with auto-submit on timer expiry
- Kahoot-style scoring service + derived leaderboard
- Session analytics + CSV export
- DB notifications (navbar bell polling)
- Admin stats, user suspend/activate, activity log
- Cookies: theme preference, last room code
- Throttled poll endpoints (`throttle:120,1`)

## Project layout (high level)

```
app/Http/Controllers/   Teacher, Student, Admin, Api\PollController
app/Http/Middleware/    AdminMiddleware, TeacherMiddleware, StudentMiddleware
app/Services/           RoomCode, QrCode, Scoring, Leaderboard, Notification
app/Models/             User, Classroom, Quiz, Question, QuizSession, …
resources/views/        Bootstrap Blade UI
public/css/classpulse.css
public/js/              Poller, timers, live session scripts
database/migrations/
database/seeders/ClassPulseSeeder.php
```

## Viva talking points

1. **Polling vs WebSockets** — works on shared hosting / XAMPP without Node; trade-off is slightly higher latency vs push.
2. **Timer sync** — client countdown uses `question_started_at` + `time_limit_seconds`; periodic poll of `server_now` corrects drift.
3. **Leaderboard** — no stored ranks table; `GROUP BY student_id` over `session_responses` with index on `(quiz_session_id, student_id)`.
4. **Scoring** — documented formula in `App\Services\ScoringService` (speed-weighted correct answers).

Local tip: run `START-CLASSPULSE.bat` or `php artisan serve`.

