[![Minimum PHP Version](https://img.shields.io/badge/php-^8.2-8892BF.svg)](https://php.net/)
[![License](https://img.shields.io/badge/license-MIT-blue.svg)](https://github.com/costs-to-expect/yahtzee/blob/main/LICENSE)
[![Tests](https://github.com/costs-to-expect/yahtzee/actions/workflows/tests.yml/badge.svg)](https://github.com/costs-to-expect/yahtzee/actions/workflows/tests.yml)
[![Laravel Forge Site Deployment Status](https://img.shields.io/endpoint?url=https%3A%2F%2Fforge.laravel.com%2Fsite-badges%2Fbf7e7ccf-6a96-4e8d-91ab-07c44754f4d0%3Fdate%3D1&style=plastic)](https://forge.laravel.com/servers/581137/sites/2028557)

# Yahtzee Game Scoring

## Overview

Game scoring for Yahtzee, powered by the Costs to Expect API.

![Score sheet](/resources/art/score-sheet.png)

There are no local users, the app signs players in with the API and keeps their bearer token in a cookie. Everything
else, players, games and score sheets, is stored in the API, the app's own database only holds sessions, the queue,
the public score sheet links, registrations that are waiting for a password and the stats of finished games, see
**Stats**.

## Other Apps

[Yatzy](https://github.com/costs-to-expect/yatzy)

We plan to create Apps for each of the Board and dice games we play, the Apps will all be Open Source, you 
are free to create your own and then submit a PR to the Costs to Expect [API](https://github.com/costs-to-expect/api) 
to add the new game type.

## Set up

I'm going to assume you are using Docker, if not, you should be able to work out what you need to run for your 
development setup.

Go to the project root directory and run the below.

### Environment

* $ `docker network create costs.network` *
* $ `cp .env.example .env` and set the empty values, see **Configuration** below
* $ `docker compose build`
* $ `docker compose up -d`
* $ `docker exec yahtzee.app composer install`
* $ `docker exec yahtzee.app php artisan key:generate`

After generating the key, you need to restart your containers, so run down and up again to force the new key to be used.

* $ `docker exec yahtzee.app php artisan migrate`
* $ `docker exec yahtzee.app php artisan queue:work`

The queue worker sends the emails (create password, forgot password, account deletion) and collects the stats of a
player's older games, leave it running while you develop, nothing is sent without it. Without a worker (`QUEUE_CONNECTION=sync`)
the first visit of a player to the home page runs that job inside the request, it can take minutes, use the database queue.

*We include a network for local development purposes, I need to connect to a local version of the Costs to Expect
API, You probably don't need this so remove the network section from your docker compose file and don't create the
network.

Composer is part of the app image, run it with `docker exec yahtzee.app composer ...` so dependencies are always
installed with the PHP the app runs on.

### Configuration

| Variable | Purpose |
|---|---|
| `API_URL` | The Costs to Expect API |
| `APP_DEV`, `API_URL_DEV` | Set `APP_DEV=true` to use `API_URL_DEV` instead, for a local copy of the API |
| `ITEM_TYPE_ID`, `ITEM_SUBTYPE_ID` | The API's Yahtzee game item type and subtype |
| `COSTS_TO_EXPECT_INTERNAL_API_KEY` | See below |
| `SESSION_NAME_USER`, `SESSION_NAME_BEARER` | Names of the cookies that hold the player's id and bearer token |
| `ERROR_EMAIL` | Where failed API calls, such as a score that could not be logged, are reported |
| `SCORE_CORRECTIONS` | `true` switches on undo, change and clear for a score, see below, it is `false` unless you set it |
| `STATS_BACKFILL_PAUSE_MS` | How long the job that collects a player's older games waits between its requests to the API, in milliseconds, 400 unless you set it, see **Stats** |
| `STATS_BACKFILL_BACKOFF_SECONDS` | How long that job waits when the API says it is being asked for too much, 60 unless you set it |

**The internal API key.** The API only lets its own trusted apps register an account and request a password reset, 
both return a token which the app emails, so they are protected by an `X-Internal-Api-Key` header. Set 
`COSTS_TO_EXPECT_INTERNAL_API_KEY` to the same value as `INTERNAL_API_KEY` in the API's `.env`. Without it, 
registering and forgot password both fail with "This route can only be called by a trusted internal service", 
nothing else needs it.

## Testing

```bash
docker exec yahtzee.app composer test
```

The tests use an in-memory SQLite database and fake every request to the API, they never touch the development 
database (the test case refuses to run against anything else) or a real API. `phpunit.xml` sets everything they 
use, including a throwaway application key, so they need no `.env`.

Visiting the home page or the stats page starts the job that collects a player's older games, `tests/TestCase.php` fakes
that job so a test that is not about it only sees it started, the job's own tests (`BackfillStatsTest`) run it directly.

GitHub Actions runs them on PHP 8.2, 8.3, 8.4 and 8.5 for every push and pull request, see 
`.github/workflows/tests.yml`. 8.2 is the version the app runs on, the others are the versions it is moving to.

### Browser tests

The score sheet and the other scripts (`public/js`) can't be tested by PHPUnit, `tests/e2e/run.sh` runs the real app
against a mock of the API (`tests/e2e/mock-api.js`) in Chromium: every page at phone and laptop width, the home page
flows, every way of scoring, failed saves and retry, a public link, finishing a game, signing out and deleting an
account (and that the token is revoked). It is not part of CI and needs Playwright installed by whoever runs it, the
app has no Node dependencies, see [tests/e2e](tests/e2e/README.md).

## Frontend assets

CSS is compiled with the standalone Tailwind CLI, there is no Node or yarn involved.

```bash
bin/css          # one-off build
bin/css --watch  # rebuild on change
```

The first run downloads the right binary for your machine into `bin/`. The output goes to 
`public/css/{version}/app.css`, the version is the `css` value in `config/app/version.php`, bump it when 
the CSS changes so deployed apps don't serve stale files, bump `js` when `public/js` changes. Commit the compiled file,
the server does not build it.

Every page is built from the Blade layouts and components in `resources/views/components` (the layouts, the icon
sprite, the avatar and ring, the sheet, the fields and alerts), the classes the pages share (buttons, cards, form
controls) are in `resources/css/app.css`, and the scripts are plain JavaScript, no build step: `public/js/ui.js` is on
every page (sheets, the snackbar, confetti, copy a link), `public/js/score-sheet.js` draws the score sheet and `public/js/landing.js` is the score sheet to try and the walkthrough on the landing page. Only the
places listed in `resources/css/app.css` are scanned for classes, add a path there if classes are ever built
somewhere new. The reasoning behind the look, the colours and the typeface is in [design](design/README.md).

What the pages say about the game (its name, mark, words and number of turns) is in `config/app/game.php` and the marks
are in `app/View/Icons.php`, a sibling scorer (Scrabble, Carcassonne) copies the theme and the components and changes those.

## Score sheets

A score sheet is stored in the API as the whole sheet, the app reads it, adds or removes a combination, works out the
totals and writes it back (`App\Actions\Game\ChangeScore`, the rules are in `App\Support\ScoreRules` and the script has
the same ones). The server decides what is allowed, the browser is only asked nicely.

**Undo, change and clear are off** (`SCORE_CORRECTIONS=false`). Removing a combination from a sheet only works when
the API replaces the sheet it is sent, rather than merging into the stored one. Everything else the app does only adds
a combination, so that has never been needed. Before switching it on, clear a score on a test game and reload the
page: if the score comes back the API merges and it has to stay off. When it is off a scored row is locked, as it always
was, and the server refuses to overwrite or clear a score.

## Stats

The stats page has the records (highest score, most wins, most consecutive wins, lowest score, most consecutive losses,
most Yahtzees in a game, most consecutive games with a Yahtzee) and a card for each player. Unlike everything else they
are kept in the app's own database: the API stores games and does not know what a Yahtzee is.

**What is kept.** A game is counted when every player in it played all 13 turns, a game that was completed early is
not. Each player in each counted game has a row in `game_stat`: their total, the upper section, the upper bonus, the
lower section, the Yahtzees scored (the Yahtzee and its bonuses, not Yahtzees that were rolled and scored somewhere
else) and a copy of their score sheet. Wins, ranks and streaks are not stored, `App\Support\GameStats` works them out
when the page is read, so a rule can change without the games being collected again. The rules are at the top of that
class: a tie for the top score is a win for everyone who tied, a game with one player is not a win or a loss, a streak
runs through the games a player took part in and games are taken in the order they were created.

**Recording.** A game is recorded when it is completed (`App\Actions\Game\RecordStats`, called by `Complete`). It never
stops a game being completed, a failure, or a game that should have counted and could not be read, is emailed to
`ERROR_EMAIL`. The stats are deleted with the game, with the player's account and with their Yahtzee account.

**Games finished before the stats existed.** `App\Jobs\BackfillStats` collects them, one job for each user, ever,
started the first time a signed-in player visits the home page or the stats page. It can't be run once for everyone:
reading a player's games needs their bearer token, the app never keeps tokens and the API only lets a player read their
own games. The job carries the token of the visit (encrypted, as the account deletion jobs do), reads every finished
game 100 at a time, oldest first, and the score sheets of each game that is not recorded yet, with a pause between the
requests (`STATS_BACKFILL_PAUSE_MS`) because the API allows a player 300 a minute and they are using it too. It needs the
queue worker.

The row in `stats_backfill` is the job's progress and what makes it one job, only a queued row can be started:

| State | Means |
|---|---|
| `queued` | The row is claimed and the job is on the queue |
| `running` | The job is working through the games |
| `paused` | The player signed out, which revokes their token. The next time they visit the row is queued again, with their new token |
| `failed` | The queue tried three times and gave up, `last_error` says why and the error is emailed. It stays failed until a person looks at it |
| `complete` | Every finished game was counted or skipped, the job never runs again for this player |

The games that were skipped are counted by reason on the row and the stats page tells the player. `unfinished`: a
player has no score sheet or a turn left. `mismatch`: a finished sheet that breaks the rules, a score its combination
can't produce, a Yahtzee bonus with no Yahtzee or totals that are not the totals of the scores (until 1.13.0 the app
stored whatever score it was sent). `unreadable`: not what the app writes. A skipped game stays skipped.

* **A failed row.** Fix the cause, then `UPDATE stats_backfill SET state = 'paused' WHERE state = 'failed';`, the next
  visit of each player carries on from the same row.
* **A row that lost its job** (the queue was emptied, the worker was killed) says queued or running and does nothing,
  after 30 minutes the next visit queues it again.
* **Collecting a player's games again**, after changing what counts, means deleting their rows from `stats_backfill` and
  `game_stat`. Deleting only the `stats_backfill` row collects the games that are not recorded yet and leaves the rest.
* **Deploying.** Run `php artisan queue:restart` so the worker loads the job. The database queue waits 960 seconds
  (`retry_after` in `config/queue.php`) before it gives a job to another worker, the job is allowed 15 minutes, a
  longer `retry_after` than the longest job is what stops two workers running it.

## Share links

Every player in a game has a public link that lets anyone who has it score for that player, it lives until the game is
completed or deleted. The link is a token that the app maps back to the game, the player and the **owner's bearer
token**, so the parameters are encrypted with `APP_KEY` in the `share_token` table (`App\Casts\EncryptedParameters`). Someone
who can read the table but not the key learns nothing. Changing `APP_KEY` makes the links of games in progress
unreadable, so change it between games. A link stops working when the owner signs out (the API revokes the token).

## PHP and Laravel versions

The app runs on Laravel 12 and PHP 8.2, `composer.json` pins the platform to 8.2 so a `composer update` on a newer 
PHP on your machine can't pick packages the production server can't run. When the app moves to PHP 8.4 or later,
change the Dockerfile image, `require.php` and `config.platform.php` together, Laravel 13 needs PHP 8.3 or later, 
the tests pass on PHP 8.2 and 8.5.
