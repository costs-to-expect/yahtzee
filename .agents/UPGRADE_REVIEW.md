# Yahtzee: upgrade and auth review

**Date:** 2026-10-02  
**Status:** Laravel 12 on PHP 8.2, tests added (354) with GitHub Actions CI, forgot password added, Tailwind set up with a teal theme, design chosen (mockups only).
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
| CSS | Bootstrap 5, Tailwind v4 set up (teal theme) | Tailwind (v3 config) standalone CLI | Tailwind v4 standalone CLI, `bin/css` |
| Tests | PHPUnit 11, in-memory SQLite, every API call faked | same approach | same approach |

## Auth review against Budget Pro

The API only gates two routes with `X-Internal-Api-Key` (`VerifyInternalApiKey` middleware, it fails closed when
`INTERNAL_API_KEY` is empty): `auth/register` and `auth/forgot-password`.

| | Budget Pro | Yahtzee before | Yahtzee now |
|---|---|---|---|
| Internal key on register | yes | yes (`Http::post(internal: true)`) | yes, tested |
| Internal key on forgot password | yes | no flow existed | flow added, sends the key, tested |
| Key sent on nothing else | yes | yes | proved by tests, no other request carries it |
| Sign-out revokes the API token | yes | no, only forgot the cookies | yes, except the account delete redirect |
| Guard caches the resolved user | yes | no, every `Auth::user()` called the API | yes |
| User provider authenticates with the player's token | yes | **no, it sent the cookie's name as the token** | yes |
| `rehashPasswordIfRequired` on the provider | yes | missing, a fatal error on Laravel 11+ | yes |
| Queued jobs that carry the token are encrypted | yes | no | yes (`ShouldBeEncrypted`) |
| Stay signed-in checkbox works | yes | **never submitted** | yes |

Two deliberate differences:

- **Account deletion does not revoke the token.** Yahtzee queues the delete job with a five second delay and the job
  uses the player's token, so the redirect that signs the player out only forgets the cookies
  (`Guard::logout(false)`). Budget Pro's `.env.example` uses the sync queue, so its jobs have finished before it
  revokes.
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

## Not changed, and worth knowing

1. **MySQL is still 8.0**, which reached end of life in April 2026. The 8.4 image is already on this machine. A
   Docker volume that has been run by 8.0 is upgraded in place by 8.4 on first start and cannot go back, take a
   `mysqldump` first. The local database only holds sessions, cache, jobs and share links.
2. **Share links store the owner's bearer token in plain text** (`share_token.parameters.owner_bearer`), so anyone who can
   read that table can act as the owner until the game is completed. Options: an `encrypted:array` cast (existing rows
   need a data migration) or storing a server side reference instead.
3. **Scores are not validated on the server.** `scoreUpper` and `scoreLower` store any `dice`, `combo` and `score`
   they are sent, from a signed-in player or from anyone holding a public link. See `design/README.md`, it is also
   what makes undo safe.
4. **After "Delete Yahtzee account" the player's token stays valid until it expires.** The cookies are forgotten
   but the token is not revoked, the delete job could call the API's logout when it has finished.
5. **Forgot password shows whether an email has an account** (the API's 404 is put on the form, as Budget Pro does).
   Register has always allowed this, show the confirmation page for a 404 if you would rather not.
6. **Unused leftovers**: Sanctum, `App\Models\User`, `routes/api.php` and the `users` and `personal_access_tokens`
   tables come from the skeleton, auth is API backed. Candidates for removal.
7. **Production needs a queue worker** (`QUEUE_CONNECTION=database`). The new forgot password email is queued like
   the register one, check the Forge daemon is running.
8. **CI** is `.github/workflows/tests.yml` (the Budget Pro template, PHP 8.2 to 8.5). It has not run on GitHub yet.
   Its commands were run on a clean checkout of the working tree, without a `.env` or any of the container's
   environment, on PHP 8.2 in Docker and on PHP 8.5, 354 tests each. That run found the suite was borrowing the
   application key from the dev `.env` (274 tests failed without it), `phpunit.xml` now sets its own.
9. Small things left alone: the "is the a name taken by another player?" copy, an unclosed `<li>` in the game lists,
   the footer's hard coded 2023, and `Controller::bootstrap()` creating another resource type whenever the API returns
   more than one.
10. **The design is mockups only.** `design/` holds the Launchpad home page and a score sheet prototype in the new teal
    theme, the Costs to Expect purple is kept for the footer lockup and the account pages, `design/README.md` has the
    reasoning and the build order. It is meant to carry to Scrabble and Carcassonne. Server side it needs score
    validation (item 3), a way to remove a score from a sheet (undo, change and clear), the players of the last game for
    "Play again" and a created time for "started 40 min ago". The theme in `resources/css/app.css` and the Figtree files in
    `public/fonts` are in place, no view uses them yet.

## Moving to PHP 8.4 or later

Change the Dockerfile image, `require.php` and `config.platform.php` together, then `composer update`. Laravel 13
resolves with this app's dependencies (checked) and needs PHP 8.3 or later. The suite already passes on PHP 8.5,
`config/database.php` has the PHP 8.5 safe `Mysql::ATTR_SSL_CA` constant from the Laravel skeleton.
