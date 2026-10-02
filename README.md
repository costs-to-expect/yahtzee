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
the public score sheet links and registrations that are waiting for a password.

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

The queue worker sends the emails (create password, forgot password, account deletion), leave it running while you 
develop, nothing is sent without it.

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

GitHub Actions runs them on PHP 8.2, 8.3, 8.4 and 8.5 for every push and pull request, see 
`.github/workflows/tests.yml`. 8.2 is the version the app runs on, the others are the versions it is moving to.

## Frontend assets

CSS is compiled with the standalone Tailwind CLI, there is no Node or yarn involved.

```bash
bin/css          # one-off build
bin/css --watch  # rebuild on change
```

The first run downloads the right binary for your machine into `bin/`. The output goes to 
`public/css/{version}/app.css`, the version is the `css` value in `config/app/version.php`, bump it when 
the CSS changes so deployed apps don't serve stale files.

The pages still use Bootstrap (`public/css/theme.css`) for now, Tailwind is set up for the move. Proposals for the 
new home page and score sheet are in [design](design/README.md).

## PHP and Laravel versions

The app runs on Laravel 12 and PHP 8.2, `composer.json` pins the platform to 8.2 so a `composer update` on a newer 
PHP on your machine can't pick packages the production server can't run. When the app moves to PHP 8.4 or later,
change the Dockerfile image, `require.php` and `config.platform.php` together, Laravel 13 needs PHP 8.3 or later, 
the tests pass on PHP 8.2 and 8.5.
