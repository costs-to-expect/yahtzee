# Browser tests

The score sheet script, the sheets, the snackbar and the rest of `public/js` can't be tested by PHPUnit. These run the
real app, a mock of the Costs to Expect API and Chromium, nothing leaves the machine and nothing touches your `.env` or
your database (the app is given its settings in the environment and a throwaway SQLite file).

```bash
tests/e2e/run.sh              # everything
tests/e2e/run.sh score-sheet  # only the files with that in their name
tests/e2e/run.sh accessibility
```

It needs `php`, `node` and [Playwright](https://playwright.dev) with a browser, none of which are dependencies of the
app:

```bash
npm install -g playwright axe-core
npx playwright install chromium
```

If node can't find Playwright set `PLAYWRIGHT_PATH` to it, to use a Chromium Playwright did not download set
`PLAYWRIGHT_CHROMIUM`. Set `E2E_KEEP=1` to keep the logs of a run, `E2E_SHOTS` is where screenshots go.

| File | What it covers |
|---|---|
| `pages.js` | Every page at phone and laptop width: it loads, nothing overflows sideways, no broken image, no script error |
| `flows.js` | The home page (game night, nothing running, first visit), the dialogs, the player picker, finishing, deleting, adding and removing players |
| `landing.js` | The landing page signed out: the score sheet to try (opening a row, scoring, the bonus, nothing sent), the walkthrough following the scroll, the pictures without scripts, axe |
| `score-sheet.js` | Every way of scoring, the number pad, the done card, failed saves and retry, saves arriving in order. With `CORRECTIONS=1` (the runner does this against an app started with `SCORE_CORRECTIONS=true`) undo, change and clear |
| `share-and-account.js` | A public link, requests that must be refused, an expired session, completing a game, a finished sheet, signing out and deleting an account, and that the token is revoked after the deletion request |
| `accessibility.js` | [axe-core](https://github.com/dequelabs/axe-core) on every page and every dialog, only run when asked for |

`mock-api.js` is a small in-memory stand-in for the API with a few scenarios (`first`, `idle`, `busy`, `two`, `done`,
`nearly`), `/__scenario?name=busy` resets it, `/__state` shows what it holds, `/__calls` what it has been asked and
`/__fail?pattern=/data/p-1` makes writes to a matching address fail with a 503 until it is called without a pattern.
It is not the API: it keeps one token, it does not validate and it replaces a score sheet when it is patched, which is
the behaviour `SCORE_CORRECTIONS` needs and has to be confirmed against the real API.
