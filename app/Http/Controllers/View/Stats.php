<?php
declare(strict_types=1);

namespace App\Http\Controllers\View;

use App\Http\Controllers\Controller;
use App\Models\GameStat;
use App\Models\StatsBackfill;
use App\Support\GameBoard;
use App\Support\GameStats;
use App\Support\StatsPage;
use Illuminate\Http\Request;

/**
 * The records and the players' stats, worked out from the stats of the user's finished games (the game_stat table).
 * The games themselves are not read from the API, it is only asked who the players are, so they keep their colours.
 */
class Stats extends Controller
{
    public function index(Request $request)
    {
        // Like every signed-in page, this asks the API for the resource, so a token that has been revoked stops here
        $this->bootstrap($request);

        // A player can come straight here, a bookmark, before they have been to the home page
        $this->startStatsBackfill($request);

        $user_id = $this->userId($request);

        $rows = GameStat::query()
            ->where('user_id', $user_id)
            ->get(['game_id', 'player_id', 'player_name', 'score', 'yahtzees', 'game_created_at'])
            ->toArray();

        $stats = GameStats::summarise($rows);

        $players_response = $this->api->getPlayers($this->resource_type_id, ['collection' => true]);

        $players = [];
        if ($players_response['status'] === 200) {
            foreach ($players_response['content'] as $player) {
                $players[] = ['id' => $player['id'], 'name' => $player['name']];
            }
        }

        return view(
            'stats',
            [
                'games' => $stats['games'],
                'backfill' => StatsPage::backfill(StatsBackfill::query()->where('user_id', $user_id)->first()),
                'cards' => StatsPage::records($stats['records']),
                'players' => StatsPage::players($stats['players']),
                'tones' => GameBoard::tones($players),
                'turns' => (int) config('app.game.turns'),
            ]
        );
    }
}
