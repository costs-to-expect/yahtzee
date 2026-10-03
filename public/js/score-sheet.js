/*
 * The score sheet. The page (resources/views/components/score-sheet.blade.php) holds the totals bar and the places to
 * draw into, the sheet itself and the settings come from the JSON in #sheet-config. This script draws every row, takes
 * the scores, saves them and keeps "Everyone" up to date.
 *
 * Scores are saved one at a time, in the order they were tapped: the server reads the whole sheet, adds the score and
 * writes it back, so two saves at once would lose one of them. The screen updates straight away and a pill says
 * Saving, Saved or Not saved. A save that fails keeps its score on the screen, with a Retry, nothing is lost.
 */
(function () {
    'use strict';

    var UI = window.UI;
    var config = JSON.parse(document.getElementById('sheet-config').textContent);

    // ---- The rules, the server checks the same ones (App\Support\ScoreRules) ------------------------------------------

    var UPPER = [
        {id: 'ones', label: 'Ones', singular: 'one', face: 1}, {id: 'twos', label: 'Twos', singular: 'two', face: 2},
        {id: 'threes', label: 'Threes', singular: 'three', face: 3}, {id: 'fours', label: 'Fours', singular: 'four', face: 4},
        {id: 'fives', label: 'Fives', singular: 'five', face: 5}, {id: 'sixes', label: 'Sixes', singular: 'six', face: 6}
    ];

    var LOWER = [
        {id: 'three_of_a_kind', label: 'Three of a kind', hint: 'Add up all five dice', kind: 'sum', badge: '3&times;'},
        {id: 'four_of_a_kind', label: 'Four of a kind', hint: 'Add up all five dice', kind: 'sum', badge: '4&times;'},
        {id: 'full_house', label: 'Full house', hint: 'Three of one and two of another', kind: 'fixed', points: 25, badge: 'FH'},
        {id: 'small_straight', label: 'Small straight', hint: 'Four in a row', kind: 'fixed', points: 30, badge: 'SS'},
        {id: 'large_straight', label: 'Large straight', hint: 'Five in a row', kind: 'fixed', points: 40, badge: 'LS'},
        {id: 'yahtzee', label: 'Yahtzee', hint: 'Five of a kind', kind: 'fixed', points: 50, badge: '5&times;'},
        {id: 'chance', label: 'Chance', hint: 'Add up all five dice', kind: 'sum', badge: '?', noScratch: true}
    ];

    var BONUSES = ['yahtzee_bonus_one', 'yahtzee_bonus_two', 'yahtzee_bonus_three'];
    var MIN_SUM = 5, MAX_SUM = 30, TURNS = config.turns || 13;

    // ---- State -------------------------------------------------------------------------------------------------------

    var readOnly = config.complete === true;
    var corrections = config.corrections === true && !readOnly;

    var state = fromSheet(config.sheet);
    var queue = [];            // saves waiting to be sent, in the order they were tapped
    var unsaved = {};          // key -> the save that could not be sent
    var inFlight = null;       // the save being sent now
    var last = null;           // the last change, for Undo
    var target = null;         // the combination the entry sheet is open for
    var pad = null;            // the digits typed on the number pad
    var returnFocus = null;    // the row to give focus back to after the lists are redrawn
    var previousTotal = null;
    var everyone = [];         // the players, as last read from the server
    var sessionEnded = false;

    function fromSheet(sheet) {
        return {upper: Object.assign({}, sheet['upper-section'] || {}), lower: Object.assign({}, sheet['lower-section'] || {})};
    }

    function $(id) { return document.getElementById(id); }
    function sum(object) { return Object.keys(object).reduce(function (total, key) { return total + object[key]; }, 0); }
    function find(list, id) { return list.filter(function (item) { return item.id === id; })[0]; }
    function listFor(section) { return section === 'upper' ? UPPER : LOWER; }
    function key(section, id) { return section + ':' + id; }
    function unsavedCount() { return Object.keys(unsaved).length; }

    function upperScore() { return sum(state.upper); }
    function bonusScore() { return upperScore() >= 63 ? 35 : 0; }
    function lowerScore() { return sum(state.lower); }
    function totalScore() { return upperScore() + bonusScore() + lowerScore(); }

    function turns() {
        var count = Object.keys(state.upper).length;
        Object.keys(state.lower).forEach(function (id) { if (BONUSES.indexOf(id) === -1) { count++; } });
        return count;
    }

    function bonusesOn() { return BONUSES.filter(function (id) { return state.lower[id] !== undefined; }); }
    function toneOf(id) { return config.tones && config.tones[id] !== undefined ? config.tones[id] : 0; }

    // ---- Rows --------------------------------------------------------------------------------------------------------

    function badge(text) {
        return '<span class="inline-flex h-10 w-10 shrink-0 items-center justify-center rounded-xl bg-brand-50 text-xs font-extrabold text-brand-800 ring-1 ring-brand-100" aria-hidden="true">' + text + '</span>';
    }

    function points(value) { return value + (value === 1 ? ' point' : ' points'); }

    function rowHtml(section, item, lead) {
        var value = state[section][item.id];
        var scored = value !== undefined;
        var failed = unsaved[key(section, item.id)] !== undefined;
        // A scored row is only something to tap when a score can be changed (or when it has not been saved)
        var interactive = !readOnly && (!scored || corrections || failed);
        var right;

        if (failed) {
            right = '<span class="inline-flex items-center gap-1.5 rounded-full bg-amber-100 px-3 py-1.5 text-xs font-bold text-amber-900">' + UI.icon('alert', 'h-4 w-4') + 'Not saved &middot; Retry</span>';
        } else if (!scored) {
            right = readOnly ? '' : '<span class="rounded-full bg-brand-50 px-4 py-1.5 text-sm font-bold text-brand-800 ring-1 ring-brand-100">Score</span>';
        } else if (value === 0) {
            right = '<span class="text-xs font-semibold text-stone-600">Scratched</span><span class="w-9 text-right text-xl font-extrabold tabular-nums text-stone-400">0</span>';
        } else {
            right = UI.icon('check-circle', 'h-5 w-5 text-emerald-600') + '<span class="sr-only">Scored</span><span class="w-9 text-right text-xl font-extrabold tabular-nums">' + value + '</span>';
        }

        var hint = section === 'upper'
            ? item.face + (item.face === 1 ? ' point' : ' points') + ' each'
            : item.hint + (item.points ? ' &middot; ' + item.points + ' points' : '');

        var inner = lead +
            '<span class="min-w-0 flex-1"><span class="block font-bold ' + (scored && value === 0 ? 'text-stone-600' : '') + '">' + item.label + '</span><span class="block text-xs text-stone-600">' + hint + '</span></span>' +
            '<span class="flex shrink-0 items-center gap-2">' + right + '</span>';

        if (!interactive) {
            return '<li><div class="flex min-h-16 w-full items-center gap-3.5 px-4 py-2.5">' + inner + '</div></li>';
        }

        return '<li><button type="button" data-section="' + section + '" data-id="' + item.id + '" class="flex min-h-16 w-full items-center gap-3.5 px-4 py-2.5 text-left transition hover:bg-stone-50 active:bg-stone-100 focus-visible:bg-stone-50 focus-visible:outline-2 focus-visible:-outline-offset-2 focus-visible:outline-brand-600 motion-reduce:transition-none">' +
            inner + '</button></li>';
    }

    // What the upper bonus needs, in words (the words live in ui.js, the landing page demo uses them too)
    function tip(need) {
        return UI.bonusTip(need, UPPER.filter(function (item) { return state.upper[item.id] === undefined; }));
    }

    // ---- Drawing -----------------------------------------------------------------------------------------------------

    function pop(element) {
        if (!element.animate || UI.reducedMotion()) { return; }
        element.animate([{transform: 'scale(1.16)'}, {transform: 'scale(1)'}], {duration: 260, easing: 'ease-out'});
    }

    function renderStatus() {
        var button = $('status');
        var failed = unsavedCount();
        var saving = inFlight !== null || queue.length > 0;
        var base = '-mr-1 inline-flex h-11 min-w-11 shrink-0 items-center justify-center gap-1.5 rounded-full px-2.5 text-xs font-bold focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-brand-600 ';

        if (failed > 0) {
            button.className = base + 'bg-amber-100 text-amber-900';
            button.innerHTML = UI.icon('alert', 'h-5 w-5') + '<span class="sr-only sm:not-sr-only">' + failed + ' not saved</span>';
        } else if (saving) {
            button.className = base + 'text-stone-600';
            button.innerHTML = UI.icon('refresh', 'h-5 w-5 motion-safe:animate-spin') + '<span class="sr-only sm:not-sr-only">Saving</span>';
        } else {
            button.className = base + 'text-emerald-700';
            button.innerHTML = UI.icon('check-circle', 'h-5 w-5') + '<span class="sr-only sm:not-sr-only">Saved</span>';
        }

        $('banner').hidden = failed === 0;
        if (sessionEnded) {
            $('banner-text').textContent = 'You have been signed out, reload the page to carry on. Your scores are safe on this screen until you do.';
            $('banner-retry').textContent = 'Reload';
        } else {
            $('banner-text').textContent = failed === 1 ? '1 score isn’t saved yet, it’s safe on this screen.' : failed + ' scores aren’t saved yet, they’re safe on this screen.';
            $('banner-retry').textContent = 'Retry';
        }
    }

    function renderEveryone() {
        var me = {id: config.player.id, name: config.player.name, total: totalScore(), turns: turns(), you: true};
        var people = everyone.filter(function (person) { return person.id !== me.id; }).map(function (person) {
            return {id: person.id, name: person.name, total: person.total, turns: person.turns, you: false};
        });

        // Until the first read from the server there is only the player whose sheet this is
        people.push(me);
        people.sort(function (a, b) { return b.total - a.total; });

        // A crown is for being ahead: someone has scored and someone else has scored less
        var best = people[0].total, worst = people[people.length - 1].total;
        var crowned = people.length > 1 && best > 0 && best > worst;

        $('everyone').innerHTML = people.map(function (person) {
            var leader = crowned && person.total === best;

            return '<li class="flex items-center gap-3 px-4 py-3 ' + (person.you ? 'bg-brand-50/70' : '') + '">' +
                '<span class="relative h-11 w-11 shrink-0">' + UI.ring(person.turns / TURNS, 'absolute inset-0 h-full w-full') + UI.avatar(person.name, toneOf(person.id), 'absolute inset-1 text-sm') +
                (leader ? '<span class="absolute -right-1 -top-1 flex h-4.5 w-4.5 items-center justify-center rounded-full bg-amber-400 text-amber-950 ring-2 ring-white">' + UI.icon('crown', 'h-2.5 w-2.5') + '<span class="sr-only">Leading</span></span>' : '') + '</span>' +
                '<span class="min-w-0 flex-1"><span class="block truncate font-bold">' + UI.escape(person.name) + (person.you ? ' <span class="text-xs font-semibold text-brand-800">you</span>' : '') + '</span>' +
                '<span class="block text-xs text-stone-600">' + person.turns + ' of ' + TURNS + ' turns</span></span>' +
                '<span class="text-2xl font-extrabold tabular-nums">' + person.total + '</span></li>';
        }).join('');

        var waiting = people.filter(function (person) { return !person.you && person.turns < TURNS; }).map(function (person) { return person.name; });
        var note = $('done-waiting');
        if (everyone.length > 0) {
            note.textContent = waiting.length === 0
                ? (people.length > 1 ? 'Everyone has finished.' : '')
                : 'We’re waiting for ' + (waiting.length === 1 ? waiting[0] : waiting.slice(0, -1).join(', ') + ' and ' + waiting[waiting.length - 1]) + ' to finish.';
        }
    }

    // The lists are rebuilt on every change, so remember which control had focus and give it back afterwards
    function focusSelector(element) {
        if (!element || !element.dataset) { return null; }
        if (element.dataset.section) { return '[data-section="' + element.dataset.section + '"][data-id="' + element.dataset.id + '"]'; }
        if (element.dataset.bonus !== undefined) { return '[data-bonus="' + element.dataset.bonus + '"]'; }

        return element.id === 'status' ? '#status' : null;
    }

    function render() {
        var keep = focusSelector(document.activeElement);

        $('upper-list').innerHTML = UPPER.map(function (item) { return rowHtml('upper', item, UI.die(item.face)); }).join('');
        $('lower-list').innerHTML = LOWER.map(function (item) { return rowHtml('lower', item, badge(item.badge)); }).join('');

        var upper = upperScore(), bonus = bonusScore(), lower = lowerScore(), total = totalScore(), done = turns(), need = 63 - upper;

        $('total').textContent = total;
        if (previousTotal !== null && previousTotal !== total) { pop($('total')); }
        previousTotal = total;
        $('upper').textContent = upper;
        $('bonus').textContent = bonus;
        $('lower').textContent = lower;
        $('turn-label').textContent = '· ' + done + ' of ' + TURNS + ' turns';
        $('turn-bar').innerHTML = Array.apply(null, Array(TURNS)).map(function (_, index) {
            return '<span class="h-[3px] flex-1 ' + (index < done ? 'bg-brand-600' : 'bg-stone-200') + '"></span>';
        }).join('');

        $('bonus-count').textContent = Math.min(upper, 63);
        $('bonus-progress').setAttribute('aria-valuenow', Math.min(upper, 63));
        $('bonus-bar').style.width = Math.min(100, Math.round(upper / 63 * 100)) + '%';
        $('bonus-bar').className = 'h-full rounded-full transition-[width] duration-500 motion-reduce:transition-none ' + (need <= 0 ? 'bg-emerald-500' : 'bg-brand-600');
        $('tip-icon').innerHTML = UI.icon(need <= 0 ? 'sparkles' : 'bulb', 'h-5 w-5');
        $('tip-text').textContent = tip(need);

        var yahtzee = state.lower.yahtzee === 50;
        $('bonus-hint').textContent = yahtzee ? '+100 for every extra Yahtzee' : 'Score a Yahtzee first, then every extra one is worth 100';
        $('bonus-buttons').innerHTML = BONUSES.map(function (id, index) {
            var on = state.lower[id] !== undefined;
            // Once on, a bonus stays unless scores can be corrected. A new one needs a Yahtzee and a turn left to play.
            var disabled = readOnly || (on ? !corrections : (!yahtzee || done >= TURNS));

            return '<button type="button" data-bonus="' + id + '" aria-pressed="' + on + '" ' + (disabled ? 'disabled' : '') + ' class="h-11 w-14 rounded-xl text-sm font-extrabold ring-1 ring-inset transition focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-brand-600 motion-reduce:transition-none ' +
                (on ? 'bg-emerald-600 text-white ring-emerald-600 ' + (disabled ? '' : 'hover:bg-emerald-700') : (disabled ? 'bg-stone-100 text-stone-500 ring-stone-100' : 'bg-white text-stone-800 ring-stone-300 hover:bg-stone-50')) +
                '" aria-label="Extra Yahtzee ' + (index + 1) + ', 100 points">+100</button>';
        }).join('');

        $('done').hidden = done < TURNS;
        $('done-score').textContent = total;

        renderStatus();
        renderEveryone();

        var selector = returnFocus ? '[data-section="' + returnFocus.section + '"][data-id="' + returnFocus.id + '"]' : keep;
        if (selector && !document.querySelector('dialog[open]')) {
            var again = document.querySelector(selector);
            if (again) { again.focus({preventScroll: true}); }
            returnFocus = null;
        }
    }

    // ---- Saving ------------------------------------------------------------------------------------------------------

    function csrf() {
        var meta = document.querySelector('meta[name="csrf-token"]');
        return meta ? meta.content : '';
    }

    function payloadFor(operation) {
        var payload = Object.assign({}, config.ids);

        if (operation.type === 'clear') {
            payload.section = operation.section;
            payload.combo = operation.id;
        } else {
            payload[operation.section === 'upper' ? 'dice' : 'combo'] = operation.id;
            payload.score = operation.value;
            if (operation.replace) { payload.replace = true; }
        }

        return payload;
    }

    function addressFor(operation) {
        return operation.type === 'clear' ? config.urls.clear : config.urls[operation.section];
    }

    function enqueue(operation) {
        queue.push(operation);
        delete unsaved[key(operation.section, operation.id)];
        pump();
    }

    function pump() {
        if (inFlight !== null) { return; }

        var operation = queue.shift();
        if (!operation) { renderStatus(); return; }

        inFlight = operation;
        renderStatus();

        fetch(addressFor(operation), {
            method: 'POST',
            credentials: 'same-origin',
            headers: {'Content-Type': 'application/json', 'Accept': 'application/json', 'X-CSRF-TOKEN': csrf(), 'X-Requested-With': 'XMLHttpRequest'},
            body: JSON.stringify(payloadFor(operation))
        }).then(function (response) {
            return response.json().catch(function () { return {}; }).then(function (body) { return {status: response.status, body: body}; });
        }).catch(function () {
            return {status: 0, body: {}};
        }).then(function (result) {
            finished(operation, result);
        });
    }

    function finished(operation, result) {
        inFlight = null;
        var status = result.status, body = result.body;

        if (status === 200) {
            delete unsaved[key(operation.section, operation.id)];
        } else if ((status === 409 || status === 422 || status === 403) && body.sheet) {
            // The server will not take it and says what the sheet really is, show that and say why
            delete unsaved[key(operation.section, operation.id)];
            catchUp(body.sheet);
            UI.Snack.show(body.message || 'That score could not be saved', {duration: 8000});
        } else {
            // No connection, the API is down or the session ended: keep the score on the screen and offer a retry
            if (status === 419 || status === 401) { sessionEnded = true; }
            unsaved[key(operation.section, operation.id)] = operation;
            UI.Snack.show(sessionEnded ? 'You have been signed out, the score is safe on this screen' : 'Not saved, we\u2019ll keep it here until it is', sessionEnded ? {duration: 8000} : {action: 'Retry', onAction: retryAll, duration: 8000});
        }

        render();
        pump();
    }

    // Take the server's sheet, then lay what has not reached the server yet back on top of it
    function catchUp(sheet) {
        state = fromSheet(sheet);

        var pending = queue.slice();
        Object.keys(unsaved).forEach(function (id) { pending.push(unsaved[id]); });
        pending.forEach(function (operation) {
            if (operation.type === 'clear') { delete state[operation.section][operation.id]; } else { state[operation.section][operation.id] = operation.value; }
        });
    }

    function retryAll() {
        if (sessionEnded) { window.location.reload(); return; }

        var failed = Object.keys(unsaved).map(function (id) { return unsaved[id]; });
        unsaved = {};
        failed.forEach(function (operation) { queue.push(operation); });
        render();
        pump();
    }

    // ---- Changing the sheet ------------------------------------------------------------------------------------------

    function commit(section, id, value, message) {
        var existing = state[section][id];
        var bonusBefore = bonusScore();

        last = {section: section, id: id, previous: existing};
        state[section][id] = value;
        returnFocus = {section: section, id: id};

        if (section === 'lower' && id === 'yahtzee' && value === 50) { message += ' · Yahtzee!'; UI.confetti(); }
        if (bonusBefore === 0 && bonusScore() === 35) { message += ' · Upper bonus +35'; }

        UI.Sheet.close();
        enqueue({type: 'score', section: section, id: id, value: value, replace: existing !== undefined});
        render();
        UI.Snack.show(message, corrections ? {action: 'Undo', onAction: undo} : {});
    }

    function clearScore(section, id) {
        last = {section: section, id: id, previous: state[section][id]};
        delete state[section][id];
        returnFocus = {section: section, id: id};

        UI.Sheet.close();
        enqueue({type: 'clear', section: section, id: id});
        render();
        UI.Snack.show('Score cleared', {action: 'Undo', onAction: undo});
    }

    function undo() {
        if (!last) { return; }

        var undoing = last;
        last = null;
        returnFocus = {section: undoing.section, id: undoing.id};

        if (undoing.previous === undefined) {
            delete state[undoing.section][undoing.id];
            enqueue({type: 'clear', section: undoing.section, id: undoing.id});
        } else {
            state[undoing.section][undoing.id] = undoing.previous;
            enqueue({type: 'score', section: undoing.section, id: undoing.id, value: undoing.previous, replace: true});
        }

        render();
    }

    // ---- The entry sheet ---------------------------------------------------------------------------------------------

    function button(text, attributes, style) {
        var styles = {primary: 'btn-primary', secondary: 'btn-quiet', danger: 'btn-danger-soft'};

        return '<button type="button" ' + attributes + ' class="btn btn-block h-14 text-base font-extrabold ' + styles[style] + '">' + text + '</button>';
    }

    function describe(value) { return value === 0 ? 'scratched' : points(value); }

    function openSheet(section, id, again) {
        var item = find(listFor(section), id);
        var value = state[section][id];
        var failed = unsaved[key(section, id)] !== undefined;
        var body, subtitle;

        target = {section: section, id: id};
        pad = null;

        if (failed) {
            subtitle = 'This score is on your screen but we couldn’t save it.';
            body = '<p class="flex items-start gap-2 rounded-xl bg-amber-50 px-3.5 py-3 text-sm text-amber-900">' + UI.icon('alert', 'mt-0.5 h-4 w-4 shrink-0') +
                (sessionEnded ? 'You have been signed out. Reload the page, then score it again.' : 'Check your connection. Nothing is lost, it stays here until it has been saved.') + '</p>' +
                '<div class="mt-4">' + button(sessionEnded ? 'Reload the page' : 'Try saving again', 'data-action="retry" data-autofocus', 'primary') + '</div>';
        } else if (value !== undefined && !again) {
            subtitle = 'Currently ' + describe(value) + '.';
            if (section === 'lower' && id === 'yahtzee' && bonusesOn().length > 0) {
                body = '<p class="flex items-start gap-2 rounded-xl bg-amber-50 px-3.5 py-3 text-sm text-amber-900">' + UI.icon('alert', 'mt-0.5 h-4 w-4 shrink-0') + 'Remove the Yahtzee bonuses before changing the Yahtzee.</p>';
            } else {
                body = '<div class="grid gap-2.5">' + button('Change score', 'data-action="change" data-autofocus', 'primary') + button('Clear score', 'data-action="clear"', 'danger') + '</div>';
            }
        } else if (section === 'upper') {
            subtitle = again ? 'Currently ' + describe(value) + '. How many ' + item.label.toLowerCase() + ' now?' : 'How many ' + item.label.toLowerCase() + ' did you roll?';
            body = '<div class="grid grid-cols-3 gap-2.5" role="group" aria-label="Number of ' + item.label.toLowerCase() + '">' + [0, 1, 2, 3, 4, 5].map(function (count) {
                return '<button type="button" data-count="' + count + '" ' + (count === 0 ? 'data-autofocus' : '') + ' aria-label="' + (count === 0 ? 'Scratch' : count + ' ' + item.label.toLowerCase() + ', ' + count * item.face + ' points') + '" class="flex h-[4.5rem] flex-col items-center justify-center rounded-2xl bg-white ring-1 ring-inset ring-stone-300 transition hover:bg-brand-50 hover:ring-brand-300 active:bg-brand-100 focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-brand-600 motion-reduce:transition-none">' +
                    '<span class="text-2xl font-extrabold leading-none tabular-nums">' + count + '</span><span class="mt-1 text-xs font-semibold text-stone-600">' + (count === 0 ? 'Scratch' : '= ' + count * item.face) + '</span></button>';
            }).join('') + '</div>' +
                '<p class="mt-3.5 text-center text-sm text-stone-600">Each ' + item.singular + ' is worth ' + points(item.face) + '. Tap 0 to scratch it.</p>';
        } else if (item.kind === 'fixed') {
            subtitle = (again ? 'Currently ' + describe(value) + '. ' : '') + item.hint + ', worth ' + item.points + ' points.';
            body = '<div class="grid grid-cols-2 gap-2.5">' + button('Score ' + item.points, 'data-score="' + item.points + '" data-autofocus', 'primary') + button('Scratch', 'data-score="0"', 'secondary') + '</div>';
        } else {
            subtitle = (again ? 'Currently ' + describe(value) + '. ' : '') + item.hint + '.';
            pad = '';
            body = '<div data-autofocus tabindex="-1" class="rounded-2xl bg-stone-50 px-4 py-3 text-center ring-1 ring-stone-200 outline-none">' +
                '<p class="text-xs font-bold uppercase tracking-wider text-stone-600">Total of the five dice</p>' +
                '<p id="pad-value" class="mt-1 text-5xl font-extrabold leading-none tabular-nums text-stone-300" aria-live="polite">&ndash;</p>' +
                '<p id="pad-note" class="mt-1.5 h-5 text-sm text-stone-600">Between ' + MIN_SUM + ' and ' + MAX_SUM + '</p></div>' +
                '<div class="mt-3 grid grid-cols-3 gap-2" role="group" aria-label="Number pad">' +
                [1, 2, 3, 4, 5, 6, 7, 8, 9].map(function (digit) { return padKey(String(digit), 'data-key="' + digit + '"'); }).join('') +
                '<span aria-hidden="true"></span>' + padKey('0', 'data-key="0"') + padKey(UI.icon('backspace', 'mx-auto h-6 w-6'), 'data-key="back" aria-label="Delete the last digit"') + '</div>' +
                '<div class="mt-3 grid gap-2.5 ' + (item.noScratch ? '' : 'grid-cols-2') + '">' + button('Score', 'id="pad-score" data-sum disabled', 'primary') + (item.noScratch ? '' : button('Scratch', 'data-score="0"', 'secondary')) + '</div>';
        }

        UI.Sheet.open({
            title: item.label,
            subtitle: subtitle,
            body: body,
            onClose: function () { pad = null; if (returnFocus) { render(); } }
        });
    }

    function padKey(content, attributes) {
        return '<button type="button" ' + attributes + ' class="h-14 rounded-2xl bg-stone-100 text-xl font-bold transition hover:bg-stone-200 active:bg-stone-300 focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-brand-600 motion-reduce:transition-none">' + content + '</button>';
    }

    function updatePad() {
        var value = pad === '' ? null : Number(pad);
        var valid = value !== null && value >= MIN_SUM && value <= MAX_SUM;
        var display = $('pad-value');

        display.textContent = pad === '' ? '–' : pad;
        display.className = 'mt-1 text-5xl font-extrabold leading-none tabular-nums ' + (pad === '' ? 'text-stone-300' : (value > MAX_SUM ? 'text-red-700' : 'text-stone-900'));
        $('pad-note').textContent = value > MAX_SUM ? 'Five dice can’t add up to more than ' + MAX_SUM : 'Between ' + MIN_SUM + ' and ' + MAX_SUM;
        $('pad-note').className = 'mt-1.5 h-5 text-sm ' + (value > MAX_SUM ? 'font-semibold text-red-700' : 'text-stone-600');

        var score = $('pad-score');
        score.disabled = !valid;
        score.textContent = valid ? 'Score ' + value : 'Score';
    }

    function press(digit) {
        if (pad === null) { return; }

        if (digit === 'back') { pad = pad.slice(0, -1); } else if (pad.length < 2) { pad = (pad + digit).replace(/^0+(?=\d)/, ''); }
        updatePad();
    }

    // ---- Events ------------------------------------------------------------------------------------------------------

    document.addEventListener('click', function (event) {
        var t = event.target, element;

        if ((element = t.closest('[data-section]'))) { openSheet(element.dataset.section, element.dataset.id, false); return; }

        if ((element = t.closest('[data-bonus]'))) {
            var id = element.dataset.bonus;
            var on = state.lower[id] !== undefined;

            last = {section: 'lower', id: id, previous: state.lower[id]};
            if (on) { delete state.lower[id]; } else { state.lower[id] = 100; }

            enqueue(on ? {type: 'clear', section: 'lower', id: id} : {type: 'score', section: 'lower', id: id, value: 100, replace: false});
            render();

            if (!on) { UI.confetti(); }
            UI.Snack.show(on ? 'Yahtzee bonus removed' : 'Yahtzee bonus, +100', corrections ? {action: 'Undo', onAction: undo} : {});
            return;
        }

        if ((element = t.closest('[data-count]'))) {
            var item = find(UPPER, target.id), count = Number(element.dataset.count);
            commit('upper', item.id, count * item.face, count === 0 ? 'Scratched ' + item.label : 'Scored ' + count * item.face + ' in ' + item.label);
            return;
        }

        if ((element = t.closest('[data-score]'))) {
            var lowerItem = find(LOWER, target.id), score = Number(element.dataset.score);
            commit('lower', lowerItem.id, score, (score === 0 ? 'Scratched ' : 'Scored ' + score + ' in ') + lowerItem.label);
            return;
        }

        if ((element = t.closest('[data-key]'))) { press(element.dataset.key); return; }

        if (t.closest('[data-sum]')) {
            var chosen = Number(pad);
            if (pad !== '' && chosen >= MIN_SUM && chosen <= MAX_SUM) { commit('lower', target.id, chosen, 'Scored ' + chosen + ' in ' + find(LOWER, target.id).label); }
            return;
        }

        if ((element = t.closest('[data-action]'))) {
            if (element.dataset.action === 'change') {
                openSheet(target.section, target.id, true);
            } else if (element.dataset.action === 'clear') {
                clearScore(target.section, target.id);
            } else {
                // Try saving again
                var failedOperation = unsaved[key(target.section, target.id)];
                UI.Sheet.close();
                if (sessionEnded) { window.location.reload(); return; }
                if (failedOperation) {
                    delete unsaved[key(target.section, target.id)];
                    enqueue(failedOperation);
                    render();
                }
            }
            return;
        }

        if (t.closest('#status, #banner-retry') && unsavedCount() > 0) { retryAll(); return; }

        if ((element = t.closest('#help-toggle'))) {
            var help = $('help'), open = help.hidden;
            help.hidden = !open;
            element.setAttribute('aria-expanded', open);
        }
    });

    // Type the total on a laptop, the number pad is for fingers
    document.addEventListener('keydown', function (event) {
        if (pad === null || event.ctrlKey || event.metaKey || event.altKey) { return; }

        if (/^\d$/.test(event.key)) { press(event.key); event.preventDefault(); }
        else if (event.key === 'Backspace') { press('back'); event.preventDefault(); }
        else if (event.key === 'Enter' && event.target.tagName !== 'BUTTON') {
            var score = $('pad-score');
            if (score && !score.disabled) { score.click(); }
            event.preventDefault();
        }
    });

    // Leaving with scores that are not saved asks first
    window.addEventListener('beforeunload', function (event) {
        if (unsavedCount() > 0 || queue.length > 0 || inFlight !== null) {
            event.preventDefault();
            event.returnValue = '';
        }
    });

    // ---- Everyone ----------------------------------------------------------------------------------------------------

    function readEveryone() {
        if (document.hidden) { return; }

        fetch(config.urls.players, {credentials: 'same-origin', headers: {'Accept': 'application/json'}})
            .then(function (response) { return response.ok ? response.json() : null; })
            .then(function (data) {
                if (data && Array.isArray(data.players)) {
                    everyone = data.players;
                    renderEveryone();
                }
            })
            .catch(function () { /* the panel keeps what it has, the next read tries again */ });
    }

    render();
    readEveryone();
    window.setInterval(readEveryone, 10000);
    document.addEventListener('visibilitychange', readEveryone);
})();
