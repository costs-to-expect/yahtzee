// The home page, the game overview, finishing and deleting a game, players: the flows around a game
const L = require('./lib');
const SHOTS = process.env.E2E_SHOTS || require('os').tmpdir();
let pass = 0, fail = 0;
function ok(cond, msg) { if (cond) { pass++; console.log('  ok   ' + msg); } else { fail++; console.log('  FAIL ' + msg); } }
const txt = async (page, sel) => ((await page.textContent(sel)) || '').replace(/\s+/g, ' ').trim();

(async () => {
    const b = await L.browser();
    const ctx = await L.context(b, 'phone');
    await ctx.grantPermissions(['clipboard-read', 'clipboard-write']);
    const page = await ctx.newPage();
    const errors = [];
    page.on('pageerror', e => errors.push('pageerror ' + e.message));
    page.on('console', m => { if (m.type() === 'error' && !/status of (4|5)\d\d/.test(m.text())) errors.push('console ' + m.text()); });

    console.log('Home: game night');
    await L.scenario('busy');
    L.seedTokens('g-1', [['p-1', 'Ada'], ['p-2', 'Ben'], ['p-3', 'Cleo']]);
    await L.login(page);
    ok((await txt(page, 'h1')).startsWith('Who'), 'shows Who is scoring');
    // share dialog
    await page.click('[data-dialog-open=share-dialog]');
    await page.waitForSelector('#share-dialog[open]');
    ok(await page.isVisible('#share-dialog'), 'share dialog opens');
    const copyButtons = await page.$$('#share-dialog [data-copy]');
    ok(copyButtons.length === 3, 'a copy button per player (' + copyButtons.length + ')');
    await copyButtons[0].click();
    await page.waitForTimeout(200);
    ok((await txt(page, '#share-dialog [data-copy-label]')) === 'Copied', 'copy says Copied');
    const clip = await page.evaluate(() => navigator.clipboard.readText()).catch(() => '');
    ok(/\/public\/score-sheet\/[0-9a-f-]{36}$/.test(clip) || clip === '', 'clipboard holds a public link (' + clip.slice(0, 60) + ')');
    await page.keyboard.press('Escape');
    ok(!(await page.isVisible('#share-dialog')), 'Escape closes the dialog');

    // finish dialog
    await page.click('[data-dialog-open=finish-dialog]');
    ok((await txt(page, '#finish-dialog')).includes('Not everyone has scored all 13 turns yet.'), 'finish warns about unfinished turns');
    ok((await txt(page, '#finish-dialog')).includes('Ada is in the lead with 142'), 'finish names the leader');
    await page.click('#finish-dialog [data-dialog-close].btn');
    ok(!(await page.isVisible('#finish-dialog')), 'Keep playing closes it');

    // chips
    const startBtn = '[data-picker-submit]';
    // The players of the last finished game (Ada, Ben and Cleo) are chosen for the next one
    ok((await txt(page, startBtn)).startsWith('Start game with 3 players'), 'start button counts the preselected players: ' + await txt(page, startBtn));
    for (const name of ['Ada', 'Ben', 'Cleo']) { await page.click(`label.chip:has-text("${name}")`); }
    ok(await page.isDisabled(startBtn), 'start is disabled with nobody chosen');
    ok((await txt(page, startBtn)) === 'Choose the players to start', 'and says why');
    await page.click('label.chip:has-text("Ada")');
    await page.click('label.chip:has-text("Dev")');
    ok((await txt(page, startBtn)) === 'Start game with 2 players', 'two chosen');
    await Promise.all([page.waitForURL('**/games/g-*'), page.click(startBtn)]);
    ok(page.url().includes('/games/g-1'), 'redirects to the new game ' + page.url());
    let st = await L.get('/__state');
    const created = st.games[st.games.length - 1];
    ok(st.assigned[created.id].length === 2, 'new game has two players assigned');

    console.log('Game overview: remove a player, delete the game');
    await page.click('[data-dialog-open=remove-dialog]').catch(async () => { await page.click('details[data-menu] summary'); await page.click('[data-dialog-open=remove-dialog]'); });
    ok(await page.isVisible('#remove-dialog'), 'remove dialog opens from the menu');
    await Promise.all([page.waitForURL('**/home'), page.click('#remove-dialog form button')]);
    st = await L.get('/__state');
    ok(st.assigned[created.id].length === 1, 'one player removed from the API');
    await page.goto(L.APP + '/games/' + created.id);
    await page.click('details[data-menu] summary');
    await page.click('[data-dialog-open=delete-dialog]');
    await Promise.all([page.waitForURL('**/home'), page.click('#delete-dialog form button')]);
    st = await L.get('/__state');
    ok(!st.games.some(g => g.id === created.id), 'game deleted in the API');

    console.log('Home: finish and play again');
    await L.scenario('busy');
    await page.goto(L.APP + '/home');
    await page.click('[data-dialog-open=finish-dialog]');
    await Promise.all([page.waitForURL('**/games/g-*'), page.click('#finish-dialog form:nth-of-type(2) button')]);
    st = await L.get('/__state');
    ok(st.games.find(g => g.id === 'g-1').complete === 1, 'game g-1 completed');
    ok(st.games.find(g => g.id === 'g-1').game.winner.player_name === 'Ada', 'Ada is the winner');
    ok(st.games[st.games.length - 1].complete === 0, 'a new game was started');

    console.log('Home: idle and play again');
    await L.scenario('idle');
    await page.goto(L.APP + '/home');
    ok((await txt(page, 'h1')) === 'Ready for another game?', 'idle state');
    ok((await txt(page, '#idle-heading ~ form button')) === 'Play again with Ada, Ben & Cleo', 'play again button: ' + await txt(page, '#idle-heading ~ form button'));
    await Promise.all([page.waitForURL('**/games/g-*'), page.click('#idle-heading ~ form button')]);
    st = await L.get('/__state');
    ok(st.assigned[st.games[st.games.length - 1].id].map(a => a.category.name).join() === 'Ada,Ben,Cleo', 'play again started a game for the last game\'s players');

    console.log('Home: first visit');
    await L.scenario('first');
    await page.goto(L.APP + '/home');
    ok((await txt(page, 'h1')).startsWith('Let'), 'first visit state');
    await page.fill('textarea[name=players]', 'Ada\r\nBen\r\n\r\nCleo');
    await Promise.all([page.waitForURL('**/games/g-*'), page.click('button:has-text("Start the first game")')]);
    st = await L.get('/__state');
    ok(st.players.map(p => p.name).join() === 'Ada,Ben,Cleo', 'players created without stray whitespace: ' + st.players.map(p => p.name));

    console.log('Players: new player and add players');
    await L.scenario('busy');
    await page.goto(L.APP + '/new-player');
    await page.fill('input[name=name]', 'Ada');
    await page.click('button[type=submit]');
    await page.waitForSelector('.form-error');
    ok((await txt(page, '.form-error')).includes('already been taken'), 'duplicate player shows the API error');
    ok((await page.getAttribute('input[name=name]', 'aria-invalid')) === 'true', 'field is marked invalid');
    await page.fill('input[name=name]', 'Eve');
    await Promise.all([page.waitForURL('**/players'), page.click('button[type=submit]')]);
    ok((await txt(page, 'main')).includes('Eve'), 'Eve is on the players page');
    await page.goto(L.APP + '/add-players-to-game/g-1');
    ok((await txt(page, 'main')).includes('Playing now'), 'add players shows who is playing');
    await page.click('label.chip:has-text("Eve")');
    await Promise.all([page.waitForURL('**/home'), page.click('button:has-text("Add players")')]);
    st = await L.get('/__state');
    ok(st.assigned['g-1'].some(a => a.category.name === 'Eve'), 'Eve added to the game');
    await page.goto(L.APP + '/new-game');
    await page.click('button[type=submit]', { force: true }).catch(() => {});

    console.log('Errors: ' + errors.length);
    errors.forEach(e => console.log('  ' + e));
    ok(errors.length === 0, 'no script errors on these pages');
    await b.close();
    console.log(`\n${pass} passed, ${fail} failed`);
    process.exit(fail ? 1 : 0);
})().catch(e => { console.error(e); process.exit(1); });
