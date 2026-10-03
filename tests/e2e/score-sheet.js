// The score sheet script: every kind of entry, the number pad, failed saves and retry, the order of saves.
// CORRECTIONS=1 (with SCORE_CORRECTIONS=true on the app) also runs undo, change and clear.
const L = require('./lib');
const SHOTS = process.env.E2E_SHOTS || require('os').tmpdir();
let pass = 0, fail = 0;
function ok(cond, msg) { if (cond) { pass++; console.log('  ok   ' + msg); } else { fail++; console.log('  FAIL ' + msg); } }
const txt = async (page, sel) => ((await page.textContent(sel)) || '').replace(/\s+/g, ' ').trim();
const CORRECTIONS = process.env.CORRECTIONS === '1';
const row = (s, i) => `[data-section=${s}][data-id=${i}]`;
const sheet = async () => (await L.get('/__state')).sheets['g-1']['p-1'];
const settle = (page, ms = 500) => page.waitForTimeout(ms);

(async () => {
    const b = await L.browser();
    const ctx = await L.context(b, 'phone');
    const page = await ctx.newPage();
    const errors = [];
    page.on('pageerror', e => errors.push('pageerror ' + e.message));
    page.on('console', m => { if (m.type() === 'error' && !/status of (4|5)\d\d/.test(m.text())) errors.push('console ' + m.text()); });

    await L.scenario('nearly');
    await L.login(page);
    await page.goto(L.APP + '/game/g-1/player/p-1/score-sheet');
    await page.waitForSelector('#upper-list');

    console.log('Score sheet, corrections ' + (CORRECTIONS ? 'ON' : 'off'));
    ok((await txt(page, '#total')) === '230', 'starts at the stored total ' + await txt(page, '#total'));
    ok((await txt(page, '#turn-label')).includes('12 of 13'), 'twelve turns played');
    ok(!(await page.isVisible('#done')), 'the done card is hidden');

    // A scored row
    const scoredIsButton = (await page.$(row('upper', 'ones'))) !== null;
    ok(scoredIsButton === CORRECTIONS, 'a scored row is ' + (CORRECTIONS ? 'a button' : 'not a button') + ' (corrections ' + (CORRECTIONS ? 'on' : 'off') + ')');
    ok((await txt(page, '#bonus-count')) === '63', 'bonus counter capped at 63');
    ok((await txt(page, '#tip-text')).includes('35 extra points'), 'tip says the bonus is scored: ' + await txt(page, '#tip-text'));

    // The last turn: chance 26 via the pad, typed with the keyboard, finishes the game
    await page.click(row('lower', 'chance'));
    await page.keyboard.type('4');
    ok(await page.isDisabled('#pad-score'), 'Score is disabled below five');
    await page.keyboard.type('0');
    ok((await txt(page, '#pad-note')).includes('more than 30'), 'pad warns above thirty');
    await page.keyboard.press('Backspace');
    await page.keyboard.press('Backspace');
    await page.keyboard.type('26');
    await page.keyboard.press('Enter');
    await settle(page);
    ok((await txt(page, '#total')) === '256', 'total updated to 256 (' + await txt(page, '#total') + ')');
    ok(await page.isVisible('#done'), 'all thirteen turns shows the done card');
    ok((await txt(page, '#done-waiting')).includes('waiting for'), 'it says who it is waiting for: ' + await txt(page, '#done-waiting'));
    ok((await page.isVisible('#complete')), 'the owner can complete the game');
    ok((await txt(page, '#status')).includes('Saved'), 'status says Saved');
    ok((await sheet())['lower-section'].chance === 26, 'the server has chance = 26');
    ok((await L.get('/__state')).logs.some(l => l.message === 'Scored 26 in Chance'), 'and logged it');

    // Yahtzee bonus is locked once every turn is played
    ok(await page.isDisabled('[data-bonus=yahtzee_bonus_two]'), 'a new Yahtzee bonus is locked after thirteen turns');

    if (CORRECTIONS) {
        console.log('Corrections');
        await page.click(row('lower', 'chance'));
        ok((await txt(page, 'dialog[open]')).includes('Change score') && (await txt(page, 'dialog[open]')).includes('Clear score'), 'tapping a score offers Change and Clear');
        await page.click('[data-action=clear]');
        await settle(page);
        ok(!(await sheet())['lower-section'].chance && (await txt(page, '#total')) === '230', 'Clear removed the score on the server and the total went back');
        ok(!(await page.isVisible('#done')), 'the done card went away');
        ok((await page.isVisible('[data-snack-action]')), 'Undo is offered');
        await page.click('[data-snack-action]');
        await settle(page);
        ok((await sheet())['lower-section'].chance === 26, 'Undo put it back on the server');
        await page.click(row('lower', 'chance'));
        await page.click('[data-action=change]');
        await page.keyboard.type('18');
        await page.keyboard.press('Enter');
        await settle(page);
        ok((await sheet())['lower-section'].chance === 18 && (await txt(page, '#total')) === '248', 'Change replaced it with 18 (' + await txt(page, '#total') + ')');
        ok((await L.get('/__state')).logs.some(l => l.message === 'Changed their Chance from 26 to 18'), 'and logged the change');
        await page.click('[data-snack-action]');
        await settle(page);
        ok((await sheet())['lower-section'].chance === 26, 'Undo of a change restores 26');
    }

    console.log('Entry sheets');
    await L.scenario('busy');
    await page.goto(L.APP + '/game/g-1/player/p-1/score-sheet');
    await page.waitForSelector('#upper-list');
    // upper: scratch
    await page.click(row('upper', 'sixes'));
    await page.click('[data-count="0"]');
    await settle(page);
    ok((await sheet())['upper-section'].sixes === 0, 'scratching an upper combination stores 0');
    ok((await txt(page, row('upper', 'sixes') + ', #upper-list li:last-child')).includes('Scratched'), 'the row says Scratched');
    // fixed: score
    await page.click(row('lower', 'small_straight'));
    await page.click('[data-score="30"]');
    await settle(page);
    ok((await sheet())['lower-section'].small_straight === 30, 'a fixed combination scores 30');
    // fixed: scratch
    await page.click(row('lower', 'large_straight'));
    await page.click('[data-score="0"]');
    await settle(page);
    ok((await sheet())['lower-section'].large_straight === 0, 'a fixed combination scratches');
    // sum with scratch available
    await page.click(row('lower', 'four_of_a_kind'));
    ok(await page.isVisible('dialog[open] [data-score="0"]'), 'four of a kind can be scratched');
    await page.keyboard.press('Escape');
    await page.click(row('lower', 'chance'));
    ok(!(await page.isVisible('dialog[open] [data-score="0"]')), 'chance cannot be scratched');
    await page.keyboard.press('Escape');
    // Yahtzee bonus
    ok(!(await page.isDisabled('[data-bonus=yahtzee_bonus_one]')), 'a Yahtzee bonus is available after a Yahtzee');
    await page.click('[data-bonus=yahtzee_bonus_one]');
    await settle(page);
    ok((await sheet())['lower-section'].yahtzee_bonus_one === 100, 'the bonus is stored as 100');
    ok(await page.isDisabled('[data-bonus=yahtzee_bonus_one]') === !CORRECTIONS, 'the bonus is ' + (CORRECTIONS ? 'togglable' : 'locked') + ' once on');

    console.log('Bonus tracker, just over and just under');
    for (const [scenario, expected] of [['busy', 'Exactly 63'], ['upper64', 'Just over the line'], ['upper62', 'One point short'], ['upper61', 'Two points short']]) {
        await L.scenario(scenario);
        await page.goto(L.APP + '/game/g-1/player/p-1/score-sheet');
        await page.waitForSelector('#upper-list');
        ok((await txt(page, '#tip-text')).includes('would get you the bonus') || scenario.startsWith('upper6'), scenario + ': before: ' + await txt(page, '#tip-text'));
        await page.click(row('upper', 'sixes'));
        await page.click('[data-count="3"]');
        await settle(page);
        ok((await txt(page, '#tip-text')).includes(expected), scenario + ': "' + expected + '" -> ' + await txt(page, '#tip-text'));
    }
    await L.scenario('busy');

    console.log('Failed saves');
    await L.get('/__fail?pattern=/data/p-1');
    await page.click(row('lower', 'chance'));
    await page.keyboard.type('22');
    await page.keyboard.press('Enter');
    await settle(page, 900);
    ok((await txt(page, '#status')).includes('1 not saved'), 'status says 1 not saved: ' + await txt(page, '#status'));
    ok(await page.isVisible('#banner'), 'the banner shows');
    ok((await txt(page, row('lower', 'chance'))).includes('Not saved'), 'the row says Not saved');
    const shown = await txt(page, '#total');
    ok(shown !== '', 'the score stays on screen (total ' + shown + ')');
    ok(!(await sheet())['lower-section'].chance, 'and the server does not have it');
    await page.screenshot({ path: SHOTS + '/sheet-failed.png' });
    await page.click('#banner-retry');
    await settle(page, 900);
    ok((await txt(page, '#status')).includes('1 not saved'), 'retry fails while the API is down');
    await L.get('/__fail');
    await page.click('#banner-retry');
    await settle(page, 900);
    ok((await sheet())['lower-section'].chance === 22, 'retry saves it once the API is back');
    ok(!(await page.isVisible('#banner')) && (await txt(page, '#status')).includes('Saved'), 'banner gone, status Saved');

    console.log('Order of saves');
    await L.scenario('busy');
    await page.goto(L.APP + '/game/g-1/player/p-1/score-sheet');
    await page.waitForSelector('#upper-list');
    for (const [r, t] of [['sixes', '[data-count="2"]'], ['four_of_a_kind', null]]) {
        if (r === 'sixes') { await page.click(row('upper', 'sixes')); await page.click(t); }
        else { await page.click(row('lower', r)); await page.keyboard.type('19'); await page.keyboard.press('Enter'); }
    }
    await page.click(row('lower', 'chance')); await page.keyboard.type('17'); await page.keyboard.press('Enter');
    await settle(page, 1200);
    const s = await sheet();
    ok(s['upper-section'].sixes === 12 && s['lower-section'].four_of_a_kind === 19 && s['lower-section'].chance === 17, 'three quick scores are all saved (none lost to a race)');
    ok(s.score.total === 142 + 12 + 19 + 17, 'and the stored total is right ' + s.score.total);

    console.log('Everyone');
    ok((await txt(page, '#everyone')).includes('Ben') && (await txt(page, '#everyone')).includes('Cleo'), 'everyone shows the other players');
    ok((await txt(page, '#everyone')).includes('you'), 'and marks you');
    await L.get('/__scenario?name=busy');

    console.log('Errors: ' + errors.length);
    errors.forEach(e => console.log('  ' + e));
    ok(errors.length === 0, 'no script errors');
    await b.close();
    console.log(`\n${pass} passed, ${fail} failed`);
    process.exit(fail ? 1 : 0);
})().catch(e => { console.error(e); process.exit(1); });
