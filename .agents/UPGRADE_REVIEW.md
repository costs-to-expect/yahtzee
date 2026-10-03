# Yahtzee: upgrade and auth review

**Date:** 2026-10-02  
**Status:** Laravel 12 on PHP 8.2, forgot password added, the design built into every page (Tailwind, a teal theme, Figtree,
Bootstrap and Node removed), share links encrypted, the token revoked after account deletion, the Sanctum leftovers removed.
469 PHPUnit tests with GitHub Actions CI, and browser tests (`tests/e2e`) for the scripts.
Open follow-ups are at the bottom.

## What this app is

A Laravel front end for the Costs to Expect API. There are no local users: signing in posts to the API, the bearer
token and user id go in two cookies, and the "user" is whoever the API says owns the token. Players, games and score
sheets live in the API as resource types, resources, categories (players) and items (games, with the score sheet
stored as item data). The app's own database holds sessions, cache, the queue, `share_token` (public score sheet
links) and `partial_registration`.

It has the same shape as Budget Pro, Cashflow and Expense:

| | Yahtzee | Budget Pro | Cashflow / Expense |
|---|---|---|---|
| API client | `App\Api\{Service, Http, Uri}` | `App\Service\Api\{ApiService, Http, Uri}` | same as Budget Pro |
| Auth | `App\Auth\Guard\Api\{Guard, UserProvider, User}` | same | same |
| Laravel / PHP | 12 / 8.2 (was 10 / 8.2) | 11 / 8.3 | 12 / 8.4 and 8.3 |
| CSS | Tailwind v4 standalone CLI, `bin/css` (was Bootstrap 5) | Tailwind (v3 config) standalone CLI | Tailwind v4 standalone CLI, `bin/css` |
| Tests | PHPUnit 11, in-memory SQLite, every API call faked | same approach | same approach |

## Auth review against Budget Pro

The API only gates two routes with `X-Internal-Api-Key` (`VerifyInternalApiKey` middleware, it fails closed when
`INTERNAL_API_KEY` is empty): `auth/register` and `auth/forgot-password`.

| | Budget Pro | Yahtzee before | Yahtzee now |
|---|---|---|---|
| Internal key on register | yes | yes (`Http::post(internal: true)`) | yes, tested |
| Internal key on forgot password | yes | no flow existed | flow added, sends the key, tested |
| Key sent on nothing else | yes | yes | proved by tests, no other request carries it |
| Sign-out revokes the API token | yes | no, only forgot the cookies | yes, and the account deletion jobs revoke it once the API has been asked to delete |
| Guard caches the resolved user | yes | no, every `Auth::user()` called the API | yes |
| User provider authenticates with the player's token | yes | **no, it sent the cookie's name as the token** | yes |
| `rehashPasswordIfRequired` on the provider | yes | missing, a fatal error on Laravel 11+ | yes |
| Queued jobs that carry the token are encrypted | yes | no | yes (`ShouldBeEncrypted`) |
| Stay signed-in checkbox works | yes | **never submitted** | yes |

One deliberate difference remains:

- **Account deletion revokes the token from the queued job, not from the redirect.** Yahtzee queues the delete job with a
  five second delay and the job uses the player's token, so the redirect that signs the player out only forgets the
  cookies (`Guard::logout(false)`) and the job revokes the token (`App\Jobs\Concerns\RevokesBearerToken`) once the API has been
  asked to delete, whether or not that request worked. Budget Pro's `.env.example` uses the sync queue, so its jobs have
  finished before it revokes. If no queue worker is running the token stays valid until it expires.
- **The sign-in request carries no bearer**, a stale token from an earlier sign-in used to be sent to the public route.

Not ported because Yahtzee has no equivalent: the payment lock middleware, the registered-user bookkeeping on sign-in.

The local `.env` does not have `COSTS_TO_EXPECT_INTERNAL_API_KEY` set, register and forgot password will be
refused by the local API until it is.

## Bugs found by the tests, fixed, each has a test

1. The winner of a completed game was wrong for some scores (a sort that never answered "before"), `[92, 181, 197]` crowned 181.
2. The stay signed-in checkbox had no `name`, so it was never submitted.
3. The create password page threw on the API's validation errors, and sent the email and token the wrong way round.
4. A 401 from the API when signing in was a server error (the error had a different shape).
5. Typed passwords were flashed into the session, and written back into the create password form, on a failed attempt.
6. Players typed into the "Let's get started" textarea kept stray `\r` characters on Windows, and a blank line became an empty name.
7. Completing or deleting a game that does not exist was a 500, the 404 was caught and re-thrown as a 500.
8. The public score sheet crashed (`JsonResponse` used as an array) when the API could not return the score sheet.
9. A failed account deletion carried on, removed the player's sessions and emailed them to say it was done.
10. The games page was a server error when the API failed, and a failure creating the Yahtzee resource right after
    creating its resource type crashed instead of reporting the API's status.

## Done since

1. **The design is built** (`design/README.md` has the reasoning, `README.md` how it fits together). Every page uses the
   Blade layouts and components, the Launchpad home page and the score sheet follow the design (the mockups were removed once it was built, they are in the history). Bootstrap, the SCSS,
   `public/package.json`, `public/yarn.lock` and axios are gone, the app needs no Node.
2. **Scores are validated on the server** (`App\Support\ScoreRules`, `App\Actions\Game\ChangeScore`) for a signed-in
   player and a public link alike: the combination has to exist, the score has to be one it can produce, a Yahtzee bonus
   needs a Yahtzee and a turn left to play, and a combination that is already scored is not overwritten.
3. **Share links are encrypted** (`App\Casts\EncryptedParameters`, `share_token.parameters` is now `text`), existing rows are
   encrypted by the migration and a row that is still plain JSON is read and encrypted when it is next saved.
4. **The token is revoked after "Delete Yahtzee account" and "Delete account"**, by the job, once the API has been asked.
5. **The skeleton leftovers are gone**: Sanctum, `App\Models\User`, the factory, `routes/api.php`, `routes/channels.php`, the
   broadcast provider and the three empty tables (dropped only when empty).
6. Removing a player from a game is a POST, it deleted a score sheet from a link.
7. Scores are saved one at a time, in the order they were tapped. The server reads the whole sheet, adds the score and
   writes it back, two saves at once lost one of them.

## Not changed, and worth knowing

1. **MySQL is still 8.0**, which reached end of life in April 2026, to be dealt with in a server move. A Docker volume
   that has been run by 8.0 is upgraded in place by 8.4 on first start and cannot go back, take a `mysqldump` first.
   The local database only holds sessions, cache, jobs and share links.
2. **The share link migration was only run on SQLite.** It changes a `json` column to `text` with `->change()` and
   encrypts the rows, MySQL 8.0 was not available to run it on (no Docker in the session). It is plain Laravel and
   reversible (`down()` decrypts the rows), run `php artisan migrate --pretend` and take a `mysqldump` of `share_token`
   (it only holds links for games in progress) before deploying. Changing `APP_KEY` later makes the links of games in
   progress unreadable.
3. **Undo, change and clear a score are built and switched off** (`SCORE_CORRECTIONS=false`). Removing a combination only
   works if the API replaces the score sheet it is sent rather than merging into it, nothing the app did before needed
   that. Check on a test game (clear a score, reload) before setting it to `true`. The browser tests run it both ways
   against a mock that replaces.
4. **A share link stops working when the owner signs out**, the API revokes the token the link holds. Sign-out revoking
   the token is wanted, but a game that is still being played through links ends for everyone who has one. Links are only
   as long lived as the owner's session.
5. **The "started 40 min ago" and "last played on" labels need a created time from the API**, the app reads `created_at`
   (or `created`) from a game and leaves the label out when there is none, nothing else changes. "Play again" and the
   preselected players come from the last finished game, **the app assumes the API returns the newest finished game first**
   (the old home page's Recent Games relied on the same order), if that is not so the wrong game's players are offered.
6. **Forgot password shows whether an email has an account** (the API's 404 is put on the form, as Budget Pro does).
   Register has always allowed this, show the confirmation page for a 404 if you would rather not.
7. **Production needs a queue worker** (`QUEUE_CONNECTION=database`). The forgot password email is queued like the
   register one, the account deletion jobs revoke the token, check the Forge daemon is running.
8. **The score sheet script has no PHPUnit coverage**, `tests/e2e` drives it in Chromium against a mock of the API (every way of
   scoring, failed saves, the order of saves, links, deletion), it is not part of CI because it needs Playwright.
9. **The bonus messages were removed** (the endpoints, `bonus.blade.php` and `BonusMessageTest`), the bonus tracker above the upper
   section replaced them. They are in the history if you want the old messages back.
10. Small things left alone: the footer's version date is no longer shown, and `Controller::bootstrap()` creates another
    resource type whenever the API returns more than one. The player scores for the Everyone panel are read every ten
    seconds by every open sheet, that is one request for the game's players and one for its score sheets each time.
11. **The design is built for Yahtzee only.** The Scrabble and Carcassonne scorers copy `resources/css/app.css`,
    `public/fonts`, the Blade components and `public/js/ui.js`, and change `config/app/game.php` and their own sheet, see
    `design/README.md`. Not designed: dark mode and the Scrabble and Carcassonne sheets themselves.

## Moving to PHP 8.4 or later

Change the Dockerfile image, `require.php` and `config.platform.php` together, then `composer update`. Laravel 13
resolves with this app's dependencies (checked) and needs PHP 8.3 or later. The suite already passes on PHP 8.5,
`config/database.php` has the PHP 8.5 safe `Mysql::ATTR_SSL_CA` constant from the Laravel skeleton.
