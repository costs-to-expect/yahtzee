// The stats of the player the mock API signs in (user-1), run by run.sh once the database is migrated.
//
// The player's older games have been collected, so the home page does not start the job (on the sync queue the job
// would run inside the request and read every game from the mock API) and /stats has records to draw: three finished
// games, one of them tied, a Yahtzee and a game with someone missing, so the cards, the holders and the players are
// all on the page.
\App\Models\StatsBackfill::create([
    'user_id' => 'user-1',
    'state' => \App\Models\StatsBackfill::COMPLETE,
    'games_total' => 3,
    'games_seen' => 3,
    'games_counted' => 3,
]);

$games = [
    ['g-stats-1', 21, ['p-1' => ['Ada', 285, 1], 'p-2' => ['Ben', 105, 0], 'p-3' => ['Cleo', 190, 0]]],
    ['g-stats-2', 14, ['p-1' => ['Ada', 200, 0], 'p-2' => ['Ben', 215, 2], 'p-3' => ['Cleo', 215, 0]]],
    ['g-stats-3', 7, ['p-1' => ['Ada', 310, 1], 'p-2' => ['Ben', 90, 0]]],
];

foreach ($games as [$game_id, $days_ago, $players]) {
    foreach ($players as $player_id => [$name, $score, $yahtzees]) {
        \App\Models\GameStat::create([
            'user_id' => 'user-1',
            'game_id' => $game_id,
            'player_id' => $player_id,
            'player_name' => $name,
            'players_in_game' => count($players),
            'score' => $score,
            'upper' => 60,
            'upper_bonus' => 0,
            'lower' => $score - 60,
            'yahtzees' => $yahtzees,
            'sheet' => [],
            'game_created_at' => now()->subDays($days_ago)->setTime(18, 30),
            'game_completed_at' => now()->subDays($days_ago)->setTime(19, 10),
        ]);
    }
}

echo "Seeded the stats of user-1\n";
