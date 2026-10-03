// Playwright is not a dependency of the app, install it where you like (npm install -g playwright) and, if node can't
// find it, point PLAYWRIGHT_PATH at it
function loadPlaywright() {
    try { return require('playwright'); } catch (error) { return require(process.env.PLAYWRIGHT_PATH); }
}
const { chromium } = loadPlaywright();
const http = require('http');

const APP = process.env.E2E_APP || 'http://127.0.0.1:8001';
const API = process.env.E2E_API || 'http://127.0.0.1:8099';

function get(path) { return new Promise((resolve, reject) => http.get(API + path, res => { let d = ''; res.on('data', c => d += c); res.on('end', () => resolve(JSON.parse(d || 'null'))); }).on('error', reject)); }
const scenario = name => get('/__scenario?name=' + name);

async function browser(opts = {}) {
    const options = { args: ['--no-sandbox'] };
    if (process.env.PLAYWRIGHT_CHROMIUM) { options.executablePath = process.env.PLAYWRIGHT_CHROMIUM; }
    const b = await chromium.launch(options);
    return b;
}

async function context(b, kind = 'phone') {
    const o = kind === 'phone'
        ? { viewport: { width: 390, height: 844 }, deviceScaleFactor: 2, isMobile: true, hasTouch: true }
        : { viewport: { width: 1280, height: 900 } };
    return b.newContext(o);
}

async function login(page) {
    await page.goto(APP + '/sign-in');
    await page.fill('input[name=email]', 'ada@example.test');
    await page.fill('input[name=password]', 'correct horse battery');
    await Promise.all([page.waitForURL('**/home'), page.click('button[type=submit]')]);
}

module.exports = { chromium, APP, API, get, scenario, browser, context, login };

const { execSync } = require('child_process');

// The app issues a public link for every player when it creates a game, games the mock starts with have none
function seedTokens(game, players) {
    const code = "App\\Models\\ShareToken::query()->delete();" + players.map(([id, name]) => `App\\Models\\ShareToken::issue('rt-1','r-1','${game}','${id}','${name}','mock-token');`).join('');
    return execSync(`php artisan tinker --execute="${code.replace(/"/g, '\\"')}"`, { cwd: require('path').resolve(__dirname, '../..'), stdio: 'pipe' }).toString();
}
module.exports.seedTokens = seedTokens;
