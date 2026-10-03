// A public link, bad requests, an expired session, finishing a game, a finished sheet, sign out and account deletion
const L = require('./lib');
const SHOTS = process.env.E2E_SHOTS || require('os').tmpdir();
let pass = 0, fail = 0;
function ok(cond, msg) { if (cond) { pass++; console.log('  ok   ' + msg); } else { fail++; console.log('  FAIL ' + msg); } }
const txt = async (page, sel) => ((await page.textContent(sel)) || '').replace(/\s+/g, ' ').trim();
const row = (s, i) => `[data-section=${s}][data-id=${i}]`;
const settle = (page, ms = 600) => page.waitForTimeout(ms);

(async () => {
    const b = await L.browser();
    const errors = [];
    const watch = p => { p.on('pageerror', e => errors.push('pageerror ' + e.message)); p.on('console', m => { if (m.type() === 'error' && !/status of (4|5)\d\d/.test(m.text())) errors.push('console ' + m.text()); }); };

    const ownerCtx = await L.context(b, 'laptop');
    const owner = await ownerCtx.newPage(); watch(owner);
    await L.scenario('busy');
    L.seedTokens('g-1', [['p-1', 'Ada'], ['p-2', 'Ben'], ['p-3', 'Cleo']]);
    await L.login(owner);

    console.log('Public score sheet');
    await owner.click('[data-dialog-open=share-dialog]');
    const links = await owner.$$eval('#share-dialog [data-copy]', els => els.map(e => e.dataset.copy));
    ok(links.length === 3, 'three links');
    // Ben is the second tile in standings (104 points), find Ben's link by name order in dialog
    const names = await owner.$$eval('#share-dialog li', els => els.map(e => e.textContent.replace(/\s+/g, ' ').trim()));
    const benLink = links[names.findIndex(n => n.startsWith('B'))];

    const guestCtx = await L.context(b, 'phone');
    const guest = await guestCtx.newPage(); watch(guest);
    await guest.goto(benLink);
    await guest.waitForSelector('#upper-list');
    ok((await guest.title()) === 'Hey Ben, play Yahtzee with us!', 'title: ' + await guest.title());
    ok((await txt(guest, 'h1')) === 'Player: Ben', 'heading says whose sheet it is');
    ok(!(await guest.$('a[href$="/home"]')) && !(await guest.$('a[href*="sign-out"]')), 'no account navigation');
    ok((await guest.$('meta[name=robots]')) !== null, 'kept out of search engines');
    ok((await txt(guest, '#total')) === '104', 'shows Ben\'s total');
    const html = await guest.content();
    ok(!html.includes('mock-token'), 'the owner\'s bearer token never reaches the page');
    await guest.click(row('upper', 'fives'));
    await guest.click('[data-count="2"]');
    await settle(guest);
    let st = await L.get('/__state');
    ok(st.sheets['g-1']['p-2']['upper-section'].fives === 10, 'scored through the link, saved to Ben\'s sheet');
    ok(st.sheets['g-1']['p-1']['upper-section'].fives === 15, 'and Ada\'s sheet is untouched');
    ok((await txt(guest, '#total')) === '114', 'total 114');
    // The owner's home shows it after a reload, the live panel on Ada's sheet shows it without
    await owner.goto(L.APP + '/game/g-1/player/p-1/score-sheet');
    await owner.waitForSelector('#everyone li');
    await settle(owner, 800);
    ok((await txt(owner, '#everyone')).includes('114'), 'Ada\'s Everyone panel shows Ben on 114');
    await guest.click(row('upper', 'sixes'));
    await guest.click('[data-count="1"]');
    await settle(guest);
    await owner.evaluate(() => document.dispatchEvent(new Event('visibilitychange')));
    await settle(owner, 800);
    ok((await txt(owner, '#everyone')).includes('120'), 'the panel catches up without a reload (120)');
    ok((await guest.$(row('upper', 'ones'))) === null, 'a scored row is locked for the player too');

    console.log('A bad request cannot corrupt a sheet');
    const bad = await guest.evaluate(async () => {
        const token = document.querySelector('meta[name=csrf-token]').content;
        const url = JSON.parse(document.getElementById('sheet-config').textContent).urls.upper;
        const post = body => fetch(url, { method: 'POST', headers: { 'Content-Type': 'application/json', 'X-CSRF-TOKEN': token, Accept: 'application/json' }, body: JSON.stringify(body) }).then(r => r.status);
        return [await post({ dice: 'twos', score: 999 }), await post({ dice: 'bogus', score: 3 }), await post({ dice: 'ones', score: 1 })];
    });
    ok(bad[0] === 422 && bad[1] === 422 && bad[2] === 409, 'impossible scores are refused 422/422/409: ' + bad);
    st = await L.get('/__state');
    ok(st.sheets['g-1']['p-2']['upper-section'].twos === 6 && !st.sheets['g-1']['p-2']['upper-section'].bogus, 'the stored sheet is unchanged');

    console.log('Session ended');
    await guestCtx.clearCookies();
    await guest.click(row('lower', 'large_straight'));
    await guest.click('[data-score="40"]');
    await settle(guest, 900);
    ok((await txt(guest, '#banner')).includes('signed out') || (await txt(guest, '#banner')).includes('not saved'), 'an expired page says the score is not saved: ' + (await txt(guest, '#banner')).slice(0, 90));
    await guestCtx.close();

    console.log('Done, finishing the game');
    await L.scenario('done');
    await owner.goto(L.APP + '/game/g-1/player/p-1/score-sheet');
    await owner.waitForSelector('#done:not([hidden])');
    ok(await owner.isVisible('#done'), 'the done card shows at thirteen turns');
    ok((await txt(owner, '#done-score')) === '247', 'it has the score');
    await owner.screenshot({ path: SHOTS + '/sheet-done-laptop.png' });
    await Promise.all([owner.waitForURL('**/home'), owner.click('#complete')]);
    st = await L.get('/__state');
    ok(st.games.find(g => g.id === 'g-1').complete === 1, 'Complete the game finished it');

    console.log('Read only sheet');
    await owner.goto(L.APP + '/game/g-1/player/p-1/score-sheet');
    await owner.waitForSelector('#upper-list li');
    ok((await owner.$('#upper-list button')) === null && (await owner.$('#lower-list button')) === null, 'no row can be tapped on a finished game');
    ok((await txt(owner, 'main')).includes('This game is finished'), 'it says the game is finished');
    ok(await owner.isDisabled('[data-bonus=yahtzee_bonus_one]'), 'the bonus buttons are disabled');
    await owner.screenshot({ path: SHOTS + '/sheet-readonly-laptop.png', fullPage: true });

    console.log('Sign out revokes the token');
    const before = (await L.get('/__state')).revoked;
    await owner.goto(L.APP + '/account');
    await owner.screenshot({ path: SHOTS + '/account-laptop.png' });
    await Promise.all([owner.waitForURL(L.APP + '/'), owner.click('header a:has-text("Sign out")')]);
    ok((await L.get('/__state')).revoked === before + 1, 'the API was asked to revoke the token');
    await owner.goto(L.APP + '/home');
    ok(owner.url().includes('/sign-in'), 'signed out, /home goes to sign in');

    console.log('Account deletion revokes the token');
    for (const [url, button] of [['/account/confirm-delete-yahtzee-account', 'Confirm delete'], ['/account/confirm-delete-account', 'Confirm delete']]) {
        await L.login(owner);
        const r0 = (await L.get('/__state')).revoked;
        await owner.goto(L.APP + url);
        await Promise.all([owner.waitForURL('**/account?job=*'), owner.click(`button:has-text("${button}")`)]);
        ok((await txt(owner, 'main')).includes('Delete started'), url + ': says the delete started');
        const calls = await L.get('/__calls');
        const deleteCall = calls.filter(c => /request-delete/.test(c.path)).length;
        ok(deleteCall >= 1, url + ': asked the API to delete');
        ok((await L.get('/__state')).revoked === r0 + 1, url + ': and revoked the token afterwards (' + ((await L.get('/__state')).revoked - r0) + ')');
        const order = calls.map(c => c.path).filter(p => /request-delete|auth\/logout/.test(p));
        ok(order.lastIndexOf('/v3/auth/logout') > order.findIndex(p => /request-delete/.test(p)), url + ': revoked after the delete request ' + JSON.stringify(order.slice(-2)));
        await owner.goto(L.APP + '/home');
        ok(owner.url().includes('/sign-in'), url + ': the player is signed out');
    }

    console.log('Errors: ' + errors.length);
    errors.forEach(e => console.log('  ' + e));
    ok(errors.length === 0, 'no script errors');
    await b.close();
    console.log(`\n${pass} passed, ${fail} failed`);
    process.exit(fail ? 1 : 0);
})().catch(e => { console.error(e); process.exit(1); });
