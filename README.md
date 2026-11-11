# Hikaku Lite

A hotel price comparison website. It shows the price of the same hotel on several booking sites, marks the cheapest one, shows the price history in a chart, and tells the visitor whether to book now or wait. It can also email a visitor when a price falls to their target.

> **Note:** This is a demo project. The 4 booking sites (StayDeal, RoomHub, TripNest, BookEasy) are pretend, and all prices are sample data. Real booking sites give prices only to approved business partners. The system is built so that a real source can be added in the same way.

## What it does

- Search hotels by name or area, filter by city, stars, review score and price, sort five ways, and page through the results.
- Shows each booking site's price for every hotel, the cheapest one marked **BEST**, and the change since yesterday.
- A popup for each hotel with a 30 or 90-day price chart and a "good time to book" or "consider waiting" message.
- A dashboard with counters (hotels tracked, sources, average best price, price drops today).
- Prices refresh every 60 seconds without reloading the page.
- Click a booking site to open its demo page.
- **Price drop alerts:** the visitor enters an email and a target price, and gets an email when the price falls to it. Each email has a safe unsubscribe link.
- Works on phones and computers (mobile first).

## Tech stack

| Tool | Used for |
|---|---|
| PHP 8.3, Laravel 13 | Server code: routes, controllers, models, migrations, seeders, scheduler, email |
| MySQL 8.4 | Database |
| HTML, CSS, JavaScript, jQuery | Web pages |
| Chart.js | Price charts |
| Colorlib "Villa" (Bootstrap 4) | Page design and scroll effects |
| Laragon, Composer, Git | Local setup and version control |

## How it works

1. **2:00 AM every night:** a Laravel Scheduler job (`prices:ingest`) saves the new day's prices from the 4 sample sources and clears the hotel list cache.
2. **2:10 AM:** a second job (`alerts:check`) emails visitors whose target price was reached. The same alert is not emailed again for 7 days.
3. **A visitor opens the page:** jQuery asks the Laravel server for hotels, the server reads MySQL (or the cache) and replies with the prices.

## Database

| Table | What it holds |
|---|---|
| `hotels` | Hotel name, city, area, stars, review score |
| `sources` | The booking sites |
| `price_snapshots` | One price for one hotel, one site and one day (unique key stops duplicates) |
| `price_alerts` | Email, target price and when the visitor was last emailed |

## Speed test

I wanted to see if the site stays fast with a lot of data, so I made a command that adds about 900,000 test price rows (907,280 in total).

| Request | Before the index | After the index |
|---|---|---|
| Hotel list | about 3.9 s | about 0.4 to 0.5 s (8 to 10 times faster) |
| Dashboard numbers | about 2.3 s | about 0.05 to 0.06 s |

How I measured: one warm-up run, then 10 timed runs, and I report the median. I found the slow query by timing each SQL query, used MySQL `EXPLAIN` to check how it reads the data, and added an index that starts with the date. The hotel list is also cached for 5 minutes.

These numbers come from my own computer with generated test data, not from real visitors. They vary by about 5% between runs, and a little more between sessions.

## Run it on your computer

You need PHP 8.3 or newer, Composer and MySQL.

```
composer install
copy .env.example .env
php artisan key:generate
```

Create an empty MySQL database named `hikaku_lite`, then set these lines in `.env`:

```
DB_CONNECTION=mysql
DB_HOST=127.0.0.1
DB_PORT=3306
DB_DATABASE=hikaku_lite
DB_USERNAME=root
DB_PASSWORD=
MAIL_MAILER=log
```

Then:

```
php artisan migrate --seed
php artisan serve
```

Open http://127.0.0.1:8000. Alert emails are written to `storage/logs/laravel.log` instead of being sent.

## Useful commands

| Command | What it does |
|---|---|
| `php artisan prices:ingest` | Saves a new day of prices |
| `php artisan alerts:check --dry-run` | Shows which alerts would be emailed |
| `php artisan schedule:work` | Runs the nightly jobs locally |
| `php artisan perf:load --hotels=2500` | Adds about 900,000 test price rows |
| `php artisan perf:load --clear` | Removes the test data |
| `php artisan perf:bench --label=name` | Measures how fast each request is |
| `php artisan perf:queries` | Shows the time of each SQL query |

After `migrate:fresh --seed` or `perf:load`, run `php artisan cache:clear`.

## Limits

- Prices and booking sites are sample data.
- Speed numbers are from one laptop with generated data.
- The project runs locally. Deployment to AWS is planned.

## Credits

Page template: [Villa](https://colorlib.com) by Colorlib, licensed CC BY 3.0.