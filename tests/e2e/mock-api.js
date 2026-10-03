// A small in-memory stand-in for the Costs to Expect API, only what the Yahtzee app calls, so the real app can be
// driven in a browser (tests/e2e/run.sh). It is not the API: it keeps everything in memory, accepts one token and the
// scenarios below decide what is in it. node mock-api.js [port]
//
// Test controls: /__scenario?name=busy resets to a scenario, /__state shows everything it holds, /__calls lists the
// requests it has had and /__fail?pattern=/data/p-1 makes writes to matching addresses fail with a 503 (no pattern
// puts it right).
const http = require('http');
const url = require('url');

const PORT = Number(process.argv[2] || 8099);
const TOKEN = 'mock-token';
let seq = 100;
let state;
let calls = [];

function iso(minutesAgo) { return new Date(Date.now() - minutesAgo * 60000).toISOString().replace('T', ' ').slice(0, 19); }

function blank() {
    return { players: [], games: [], sheets: {}, assigned: {}, logs: [], hasResource: true, revoked: 0 };
}

function sheet(upper, lower) {
    const u = Object.values(upper || {}).reduce((a, b) => a + b, 0);
    const l = Object.values(lower || {}).reduce((a, b) => a + b, 0);
    const bonus = u >= 63 ? 35 : 0;
    return { 'upper-section': upper || {}, 'lower-section': lower || {}, score: { upper: u, bonus, lower: l, total: u + bonus + l } };
}

function addGame(s, id, playerIds, opts = {}) {
    s.games.push({ id, name: 'Yahtzee game', description: 'Yahtzee game create via the Yahtzee app', complete: opts.complete ? 1 : 0, created_at: opts.created === undefined ? iso(40) : opts.created, game: opts.game || null });
    s.assigned[id] = playerIds.map(p => ({ id: 'ga-' + id + '-' + p, category: { id: p, name: s.players.find(x => x.id === p).name } }));
    s.sheets[id] = {};
}

const SCENARIOS = {
    first() { return blank(); },
    idle() {
        const s = blank();
        s.players = [{ id: 'p-1', name: 'Ada' }, { id: 'p-2', name: 'Ben' }, { id: 'p-3', name: 'Cleo' }, { id: 'p-4', name: 'Dev' }];
        addGame(s, 'g-old', ['p-1', 'p-2', 'p-3'], { complete: true, created: iso(60 * 24 * 2), game: { scores: [{ player_id: 'p-1', player_name: 'Ada', score: 241 }, { player_id: 'p-2', player_name: 'Ben', score: 198 }, { player_id: 'p-3', player_name: 'Cleo', score: 176 }], winner: { player_id: 'p-1', player_name: 'Ada', score: 241 } } });
        addGame(s, 'g-old2', ['p-2', 'p-4'], { complete: true, created: iso(60 * 24 * 9), game: { scores: [{ player_id: 'p-2', player_name: 'Ben', score: 223 }, { player_id: 'p-4', player_name: 'Dev', score: 210 }], winner: { player_id: 'p-2', player_name: 'Ben', score: 223 } } });
        return s;
    },
    busy() {
        const s = SCENARIOS.idle();
        addGame(s, 'g-1', ['p-1', 'p-2', 'p-3'], { created: iso(40) });
        s.sheets['g-1']['p-1'] = sheet({ ones: 3, twos: 6, threes: 9, fours: 12, fives: 15 }, { three_of_a_kind: 22, full_house: 25, yahtzee: 50 });
        s.sheets['g-1']['p-2'] = sheet({ ones: 2, twos: 6, threes: 9, fours: 12 }, { chance: 20, full_house: 25, small_straight: 30 });
        s.sheets['g-1']['p-3'] = sheet({ ones: 1, twos: 4, threes: 6 }, { three_of_a_kind: 18, chance: 15 });
        return s;
    },
    two() {
        const s = SCENARIOS.busy();
        addGame(s, 'g-2', ['p-2', 'p-4'], { created: iso(60 * 20) });
        s.sheets['g-2']['p-4'] = sheet({ ones: 1 }, {});
        return s;
    },
    nodates() {
        const s = SCENARIOS.busy();
        s.games.forEach(g => { delete g.created_at; });
        return s;
    },
    done() {
        const s = SCENARIOS.idle();
        addGame(s, 'g-1', ['p-1', 'p-2'], { created: iso(90) });
        s.sheets['g-1']['p-1'] = sheet(
            { ones: 3, twos: 6, threes: 9, fours: 12, fives: 15, sixes: 18 },
            { three_of_a_kind: 22, four_of_a_kind: 0, full_house: 25, small_straight: 30, large_straight: 0, yahtzee: 50, chance: 22 });
        s.sheets['g-1']['p-2'] = sheet({ ones: 2, twos: 4, threes: 6, fours: 8, fives: 10 }, { chance: 20 });
        return s;
    },
    nearly() {
        const s = SCENARIOS.idle();
        addGame(s, 'g-1', ['p-1', 'p-2', 'p-3'], { created: iso(90) });
        s.sheets['g-1']['p-1'] = sheet(
            { ones: 3, twos: 6, threes: 9, fours: 12, fives: 20, sixes: 18 },
            { three_of_a_kind: 22, four_of_a_kind: 0, full_house: 25, small_straight: 30, large_straight: 0, yahtzee: 50 });
        s.sheets['g-1']['p-2'] = sheet({ ones: 2, twos: 6, threes: 9, fours: 12 }, { chance: 20, full_house: 25 });
        s.sheets['g-1']['p-3'] = sheet({ ones: 1, twos: 4 }, {});
        return s;
    }
};

state = SCENARIOS.busy();

function send(res, status, body, headers) {
    const data = body === undefined || body === null ? '' : JSON.stringify(body);
    res.writeHead(status, Object.assign({ 'Content-Type': 'application/json' }, headers || {}));
    res.end(data);
}

function readBody(req) {
    return new Promise(resolve => {
        let raw = '';
        req.on('data', c => raw += c);
        req.on('end', () => { try { resolve(raw ? JSON.parse(raw) : {}); } catch (e) { resolve({}); } });
    });
}

function gameView(g, withPlayers) {
    const out = Object.assign({}, g);
    if (g.created_at === undefined) { delete out.created_at; }
    if (withPlayers) { out.players = { collection: (state.assigned[g.id] || []).map(a => ({ id: a.category.id, name: a.category.name })) }; }
    return out;
}

http.createServer(async (req, res) => {
    const u = url.parse(req.url, true);
    const path = u.pathname;
    const q = u.query;
    const body = ['POST', 'PATCH', 'PUT'].includes(req.method) ? await readBody(req) : {};
    calls.push({ method: req.method, path: req.url, body });

    // Test controls
    if (path === '/__scenario') { state = (SCENARIOS[q.name] || SCENARIOS.busy)(); calls = []; return send(res, 200, { ok: true }); }
    if (path === '/__state') { return send(res, 200, state); }
    if (path === '/__calls') { return send(res, 200, calls); }
    if (path === '/__fail') { state.fail = q.pattern || null; return send(res, 200, { fail: state.fail }); }

    if (state.fail && req.url.includes(state.fail) && ['PATCH', 'POST'].includes(req.method)) { return send(res, 503, { message: 'The API is down (simulated)' }); }

    const auth = req.headers.authorization || '';
    const authed = auth === 'Bearer ' + TOKEN;
    const p = path.replace(/^\/v3/, '');

    if (p === '/auth/login' && req.method === 'POST') {
        if (body.email === 'ada@example.test' && body.password === 'correct horse battery') { return send(res, 201, { token: TOKEN, id: 'user-1' }); }
        return send(res, 401, { message: 'Unauthorised: those credentials do not match' });
    }
    if (p === '/auth/register' && req.method === 'POST') { return send(res, 201, { uris: { 'create-password': { parameters: { token: 'tok-1', email: body.email } } } }); }
    if (p === '/auth/forgot-password' && req.method === 'POST') { return send(res, 201, { uris: { 'create-new-password': { parameters: { email: body.email, encrypted_token: 'enc-1' } } } }); }
    if (p === '/auth/create-password' && req.method === 'POST') { return send(res, 204); }
    if (p === '/auth/create-new-password' && req.method === 'POST') { return send(res, 204); }

    if (!authed) { return send(res, 401, { message: 'Unauthenticated' }); }

    if (p === '/auth/logout') { state.revoked++; return send(res, 200, { message: 'Account signed out' }); }
    if (p === '/auth/user') { return send(res, 200, { id: 'user-1', name: 'Ada Lovelace', email: 'ada@example.test' }); }
    if (p === '/auth/user/request-delete' || /request-delete$/.test(p)) { return send(res, 201, { message: 'Request received' }); }

    if (p === '/resource-types' && req.method === 'GET') { return send(res, 200, [{ id: 'rt-1' }]); }
    if (p === '/resource-types/rt-1/resources' && req.method === 'GET') { return send(res, 200, state.hasResource ? [{ id: 'r-1' }] : []); }

    if (p === '/resource-types/rt-1/categories') {
        if (req.method === 'GET') { return send(res, 200, state.players); }
        if (req.method === 'POST') {
            if (state.players.some(x => x.name.toLowerCase() === String(body.name).toLowerCase())) { return send(res, 422, { message: 'Validation error', fields: { name: { errors: ['The name has already been taken'] } } }); }
            const player = { id: 'p-' + (++seq), name: body.name };
            state.players.push(player);
            return send(res, 201, player);
        }
    }

    const base = '/resource-types/rt-1/resources/r-1/items';
    if (p === base) {
        if (req.method === 'GET') {
            let list = state.games.filter(g => String(g.complete) === String(q.complete === undefined ? g.complete : q.complete));
            // Newest first, the order the app assumes (the last game is the first one it is given)
            list = list.slice().sort((a, b) => String(b.created_at || '').localeCompare(String(a.created_at || '')));
            const total = list.length;
            const offset = Number(q.offset || 0);
            const limit = Number(q.limit || 25);
            list = list.slice(offset, offset + limit);
            return send(res, 200, list.map(g => gameView(g, q['include-players'])), {
                'X-Link-Previous': offset > 0 ? 'prev' : '', 'X-Link-Next': offset + limit < total ? 'next' : '',
                'X-Offset': String(offset), 'X-Limit': String(limit), 'X-Total-Count': String(total)
            });
        }
        if (req.method === 'POST') {
            const id = 'g-' + (++seq);
            state.games.push({ id, name: body.name, description: body.description, complete: 0, created_at: iso(0), game: null });
            state.assigned[id] = [];
            state.sheets[id] = {};
            return send(res, 201, { id });
        }
    }

    let m = p.match(new RegExp('^' + base + '/([^/]+)$'));
    if (m) {
        const g = state.games.find(x => x.id === m[1]);
        if (!g) { return send(res, 404, { message: 'Not found' }); }
        if (req.method === 'GET') { return send(res, 200, gameView(g, q['include-players'])); }
        if (req.method === 'PATCH') {
            if (body.game !== undefined) { g.game = JSON.parse(body.game); }
            if (body.complete !== undefined) { g.complete = Number(body.complete); }
            return send(res, 204);
        }
        if (req.method === 'DELETE') { state.games = state.games.filter(x => x.id !== g.id); return send(res, 204); }
    }

    m = p.match(new RegExp('^' + base + '/([^/]+)/categories$'));
    if (m) {
        if (req.method === 'GET') { return send(res, 200, state.assigned[m[1]] || []); }
        if (req.method === 'POST') {
            const player = state.players.find(x => x.id === body.category_id);
            if (!player) { return send(res, 422, { message: 'Validation error', fields: { category_id: { errors: ['Unknown player'] } } }); }
            const a = { id: 'ga-' + m[1] + '-' + player.id, category: { id: player.id, name: player.name } };
            state.assigned[m[1]].push(a);
            return send(res, 201, a);
        }
    }
    m = p.match(new RegExp('^' + base + '/([^/]+)/categories/([^/]+)$'));
    if (m && req.method === 'DELETE') { state.assigned[m[1]] = (state.assigned[m[1]] || []).filter(a => a.id !== m[2]); return send(res, 204); }

    m = p.match(new RegExp('^' + base + '/([^/]+)/data$'));
    if (m) {
        if (req.method === 'GET') { return send(res, 200, Object.entries(state.sheets[m[1]] || {}).map(([key, value]) => ({ key, value }))); }
        if (req.method === 'POST') { state.sheets[m[1]][body.key] = JSON.parse(body.value); return send(res, 201, { key: body.key }); }
    }
    m = p.match(new RegExp('^' + base + '/([^/]+)/data/([^/]+)$'));
    if (m) {
        const sheets = state.sheets[m[1]] || {};
        if (req.method === 'GET') { return sheets[m[2]] ? send(res, 200, { key: m[2], value: sheets[m[2]] }) : send(res, 404, { message: 'Not found' }); }
        if (req.method === 'PATCH') { sheets[m[2]] = JSON.parse(body.value); return send(res, 204); }
        if (req.method === 'DELETE') { delete sheets[m[2]]; return send(res, 204); }
    }

    m = p.match(new RegExp('^' + base + '/([^/]+)/log$'));
    if (m && req.method === 'POST') { state.logs.push(Object.assign({ game: m[1] }, body)); return send(res, 201, { id: 'log-' + (++seq) }); }

    send(res, 404, { message: 'The mock API does not know ' + req.method + ' ' + req.url });
}).listen(PORT, () => console.log('mock api on ' + PORT));
