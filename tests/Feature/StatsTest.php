<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Jobs\BackfillStats;
use App\Models\GameStat;
use App\Models\StatsBackfill;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Bus;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Http;
use Tests\Feature\Concerns\FakesTheApi;
use Tests\TestCase;

class StatsTest extends TestCase
{
    use FakesTheApi;
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->travelTo(Carbon::parse('2026-10-05 12:00:00', 'UTC'));

        // These are about a player whose older games have been collected, the tests of the job and of the notices
        // about it say otherwise
        StatsBackfill::create(['user_id' => self::USER_ID, 'state' => StatsBackfill::COMPLETE]);
    }

    private function stat(string $game_id, string $created, string $player_id, string $name, int $score, int $yahtzees = 0, string $user_id = self::USER_ID, int $players_in_game = 2): void
    {
        GameStat::create([
            'user_id' => $user_id,
            'game_id' => $game_id,
            'player_id' => $player_id,
            'player_name' => $name,
            'players_in_game' => $players_in_game,
            'score' => $score,
            'upper' => 60,
            'upper_bonus' => 0,
            'lower' => $score - 60,
            'yahtzees' => $yahtzees,
            'sheet' => [],
            'game_created_at' => $created,
            'game_completed_at' => $created,
        ]);
    }

    /**
     * Ada wins the first and the third, Ben the second
     */
    private function threeGames(): void
    {
        $this->stat('g-1', '2026-09-12 18:00:00', 'p-1', 'Ada', 285, 1);
        $this->stat('g-1', '2026-09-12 18:00:00', 'p-2', 'Ben', 105);
        $this->stat('g-2', '2026-09-20 18:00:00', 'p-1', 'Ada', 200);
        $this->stat('g-2', '2026-09-20 18:00:00', 'p-2', 'Ben', 215, 2);
        $this->stat('g-3', '2026-09-28 18:00:00', 'p-1', 'Ada', 310, 1);
        $this->stat('g-3', '2026-09-28 18:00:00', 'p-2', 'Ben', 90);
    }

    /**
     * The html of one record's card
     */
    private function card(string $html, string $record): string
    {
        self::assertSame(1, preg_match('/data-record="' . $record . '">(.*?)(?=data-record="|<\/section>)/s', $html, $matches), 'No card for ' . $record);

        return $matches[1];
    }

    /**
     * The html of one player's card
     */
    private function playerCard(string $html, string $player_id): string
    {
        self::assertSame(1, preg_match('/<li class="card[^"]*" data-player="' . $player_id . '">(.*?)<\/dl>/s', $html, $matches), 'No card for ' . $player_id);

        return $matches[1];
    }

    public function test_the_page_says_when_there_are_no_stats_yet(): void
    {
        $this->signedIn()->get('/stats')
            ->assertOk()
            ->assertSee('Stats')
            ->assertSee('No stats yet.')
            ->assertSee('all 13 turns')
            ->assertSee(route('game.create.view'), false)
            ->assertDontSee('records-heading', false)
            ->assertDontSee('data-record', false);
    }

    public function test_the_page_shows_a_card_for_each_record(): void
    {
        $this->threeGames();

        $html = $this->signedIn()->get('/stats')->assertOk()->assertSee('3 games played to the end')->getContent();

        foreach (['highest_score', 'most_wins', 'most_consecutive_wins', 'lowest_score', 'most_consecutive_losses', 'most_yahtzees_in_a_game', 'most_consecutive_games_with_a_yahtzee'] as $record) {
            self::assertStringContainsString('data-record="' . $record . '"', $html);
        }

        preg_match_all('/data-record="([a-z_]+)"/', $html, $matches);

        self::assertSame(
            ['highest_score', 'most_wins', 'most_consecutive_wins', 'lowest_score', 'most_consecutive_losses', 'most_yahtzees_in_a_game', 'most_consecutive_games_with_a_yahtzee'],
            $matches[1]
        );
    }

    public function test_a_record_says_its_value_who_holds_it_and_when(): void
    {
        $this->threeGames();

        $html = $this->signedIn()->get('/stats')->getContent();

        $highest = $this->card($html, 'highest_score');
        self::assertStringContainsString('Highest score', $highest);
        self::assertStringContainsString('>310<', $highest);
        self::assertStringContainsString('points', $highest);
        self::assertStringContainsString('Ada', $highest);
        self::assertStringContainsString('28 Sep', $highest);
        self::assertStringContainsString(route('game.show', ['game_id' => 'g-3']), $highest);

        $lowest = $this->card($html, 'lowest_score');
        self::assertStringContainsString('>90<', $lowest);
        self::assertStringContainsString('Ben', $lowest);
        self::assertStringNotContainsString('Ada', $lowest);

        $yahtzees = $this->card($html, 'most_yahtzees_in_a_game');
        self::assertStringContainsString('>2<', $yahtzees);
        self::assertStringContainsString('Yahtzees', $yahtzees);
        self::assertStringContainsString('Ben', $yahtzees);
        self::assertStringContainsString('20 Sep', $yahtzees);
    }

    public function test_the_most_wins_is_a_total_with_nothing_to_open(): void
    {
        $this->threeGames();

        $wins = $this->card($this->signedIn()->get('/stats')->getContent(), 'most_wins');

        self::assertStringContainsString('>2<', $wins);
        self::assertStringContainsString('wins', $wins);
        self::assertStringContainsString('Ada', $wins);
        self::assertStringNotContainsString('Ben', $wins);
        self::assertStringNotContainsString('<a ', $wins);
    }

    public function test_a_streak_is_a_run_of_days_that_opens_the_last_game(): void
    {
        // Ada wins three in a row, Ben never wins twice running
        $this->stat('g-1', '2026-09-03 18:00:00', 'p-1', 'Ada', 200);
        $this->stat('g-1', '2026-09-03 18:00:00', 'p-2', 'Ben', 100);
        $this->stat('g-2', '2026-09-06 18:00:00', 'p-1', 'Ada', 200);
        $this->stat('g-2', '2026-09-06 18:00:00', 'p-2', 'Ben', 100);
        $this->stat('g-3', '2026-09-09 18:00:00', 'p-1', 'Ada', 200);
        $this->stat('g-3', '2026-09-09 18:00:00', 'p-2', 'Ben', 100);

        $html = $this->signedIn()->get('/stats')->getContent();

        $streak = $this->card($html, 'most_consecutive_wins');
        self::assertStringContainsString('>3<', $streak);
        self::assertStringContainsString('wins in a row', $streak);
        self::assertStringContainsString('3 Sep to 9 Sep', $streak);
        self::assertStringContainsString(route('game.show', ['game_id' => 'g-3']), $streak);

        $losses = $this->card($html, 'most_consecutive_losses');
        self::assertStringContainsString('Ben', $losses);
        self::assertStringContainsString('losses in a row', $losses);
    }

    public function test_a_record_nobody_has_says_so(): void
    {
        // On their own: no wins or losses, and no Yahtzees
        $this->stat('g-1', '2026-09-12 18:00:00', 'p-1', 'Ada', 285, 0, self::USER_ID, 1);

        $html = $this->signedIn()->get('/stats')->getContent();

        self::assertStringContainsString('Nobody has won a game yet', $this->card($html, 'most_wins'));
        self::assertStringContainsString('Nobody has lost a game yet', $this->card($html, 'most_consecutive_losses'));
        self::assertStringContainsString('No Yahtzees yet', $this->card($html, 'most_yahtzees_in_a_game'));
        self::assertStringContainsString('>285<', $this->card($html, 'highest_score'));
    }

    public function test_a_record_with_a_lot_of_holders_names_a_few_and_counts_the_rest(): void
    {
        foreach (['p-1' => 'Ada', 'p-2' => 'Ben', 'p-3' => 'Cleo', 'p-4' => 'Dan', 'p-5' => 'Eve'] as $player_id => $name) {
            $this->stat('g-1', '2026-09-12 18:00:00', $player_id, $name, 100 + strlen($name), 1, self::USER_ID, 5);
        }

        $card = $this->card($this->signedIn()->get('/stats')->getContent(), 'most_yahtzees_in_a_game');

        self::assertStringContainsString('and 2 more', $card);
        self::assertStringNotContainsString('Dan', $card);
        self::assertStringNotContainsString('Eve', $card);
    }

    public function test_each_player_has_a_card_with_their_numbers(): void
    {
        $this->threeGames();

        $html = $this->signedIn()->get('/stats')->getContent();

        $ada = $this->playerCard($html, 'p-1');
        self::assertStringContainsString('Ada', $ada);
        self::assertStringContainsString('3 games', $ada);
        self::assertStringContainsString('2 wins (67%)', $ada);

        $ben = $this->playerCard($html, 'p-2');
        self::assertStringContainsString('1 win (33%)', $ben);

        // Ada: average 265, highest 310, lowest 200, 2 Yahtzees
        self::assertSame(1, preg_match('/Average score<\/dt>\s*<dd[^>]*>265</', $html));
        self::assertSame(1, preg_match('/Highest score<\/dt>\s*<dd[^>]*>310</', $html));
        self::assertSame(1, preg_match('/Lowest score<\/dt>\s*<dd[^>]*>200</', $html));
    }

    public function test_the_players_are_best_first(): void
    {
        $this->threeGames();

        $html = $this->signedIn()->get('/stats')->getContent();

        self::assertLessThan(strpos($html, 'data-player="p-2"'), strpos($html, 'data-player="p-1"'));
    }

    public function test_only_the_signed_in_players_games_are_shown(): void
    {
        $this->threeGames();
        $this->stat('g-other', '2026-09-30 18:00:00', 'p-9', 'Mallory', 999, 4, 'someone-else');
        $this->stat('g-other', '2026-09-30 18:00:00', 'p-8', 'Trent', 1, 0, 'someone-else');

        $this->signedIn()->get('/stats')
            ->assertOk()
            ->assertSee('3 games played to the end')
            ->assertDontSee('Mallory')
            ->assertDontSee('Trent')
            ->assertDontSee('>999<', false);
    }

    public function test_one_game_is_a_game_not_games(): void
    {
        $this->stat('g-1', '2026-09-12 18:00:00', 'p-1', 'Ada', 285);
        $this->stat('g-1', '2026-09-12 18:00:00', 'p-2', 'Ben', 105);

        $this->signedIn()->get('/stats')
            ->assertSee('1 game played to the end')
            ->assertDontSee('1 games');
    }

    public function test_the_players_keep_the_colour_of_their_place_in_the_players_list(): void
    {
        $this->threeGames();

        // The players list is Ada then Ben, the colours of the first two places
        $html = $this->signedIn()->get('/stats')->getContent();

        self::assertSame(1, preg_match('/<span class="[^"]*bg-rose-100[^"]*"[^>]*>A<\/span>/', $this->playerCard($html, 'p-1')));
        self::assertSame(1, preg_match('/<span class="[^"]*bg-sky-100[^"]*"[^>]*>B<\/span>/', $this->playerCard($html, 'p-2')));
    }

    public function test_the_page_still_works_when_the_api_will_not_say_who_the_players_are(): void
    {
        $this->threeGames();
        $this->fakeApi(['api.test/v3/resource-types/rt-1/categories?collection=1' => Http::response(['message' => 'Down'], 503)]);

        $this->signedIn()->get('/stats')
            ->assertOk()
            ->assertSee('3 games played to the end')
            ->assertSee('Ada');
    }

    public function test_the_games_are_not_read_from_the_api(): void
    {
        $this->threeGames();

        $this->signedIn()->get('/stats')->assertOk();

        Http::assertNotSent(fn ($request) => str_contains($request->url(), '/items'));
    }

    public function test_a_token_the_api_no_longer_accepts_stops_the_page_like_every_other_signed_in_page(): void
    {
        $this->threeGames();
        $this->fakeApi(['api.test/v3/resource-types?item-type=item-type-id' => Http::response(['message' => 'Unauthenticated.'], 401)]);

        $this->signedIn()->get('/stats')
            ->assertStatus(401)
            ->assertDontSee('Ada');
    }

    public function test_stats_is_a_tab_in_both_navigations_and_is_the_current_one_on_its_page(): void
    {
        $html = $this->signedIn()->get('/stats')->getContent();

        // The laptop navigation and the phone tab bar, and only on this page
        self::assertSame(2, substr_count($html, 'href="' . route('stats') . '"'));
        self::assertSame(2, preg_match_all('/<a href="' . preg_quote(route('stats'), '/') . '"\s+aria-current="page"/', $html));
        self::assertSame(0, preg_match_all('/<a href="' . preg_quote(route('home'), '/') . '"\s+aria-current/', $html), 'Only one tab is current');
    }

    public function test_the_other_pages_link_to_the_stats_without_marking_them_current(): void
    {
        $html = $this->signedIn()->get('/players')->getContent();

        self::assertGreaterThanOrEqual(2, substr_count($html, 'href="' . route('stats') . '"'));
        self::assertSame(0, preg_match('/<a href="' . preg_quote(route('stats'), '/') . '"\s+aria-current/', $html));
    }

    public function test_the_players_page_points_to_the_stats(): void
    {
        $this->signedIn()->get('/players')
            ->assertOk()
            ->assertDontSee('coming soon')
            ->assertSee('Stats</a> page', false);
    }

    // The job that collects the older games

    private function backfill(string $state, array $attributes = []): void
    {
        StatsBackfill::query()->delete();
        StatsBackfill::create(['user_id' => self::USER_ID, 'state' => $state] + $attributes);
    }

    public function test_the_first_visit_starts_the_job_and_says_the_games_are_being_counted(): void
    {
        StatsBackfill::query()->delete();

        $this->signedIn()->get('/stats')
            ->assertOk()
            ->assertSee('data-backfill="counting"', false)
            ->assertSee('Counting your older games')
            ->assertSee('We’re getting started')
            ->assertDontSee('No stats yet.');

        Bus::assertDispatchedTimes(BackfillStats::class, 1);
        $this->assertDatabaseHas('stats_backfill', ['user_id' => self::USER_ID, 'state' => StatsBackfill::QUEUED]);
    }

    public function test_a_second_visit_does_not_start_it_again(): void
    {
        StatsBackfill::query()->delete();

        $this->signedIn()->get('/stats')->assertOk();
        $this->signedIn()->get('/stats')->assertOk();

        Bus::assertDispatchedTimes(BackfillStats::class, 1);
    }

    public function test_a_player_whose_games_have_been_collected_does_not_start_it_again(): void
    {
        $this->signedIn()->get('/stats')->assertOk()->assertDontSee('data-backfill', false);

        Bus::assertNotDispatched(BackfillStats::class);
    }

    public function test_while_it_runs_the_page_says_how_far_it_has_got_and_offers_a_refresh(): void
    {
        $this->backfill(StatsBackfill::RUNNING, ['games_total' => 160, 'games_seen' => 42, 'games_counted' => 40, 'games_skipped' => 2]);
        $this->threeGames();

        $this->signedIn()->get('/stats')
            ->assertOk()
            ->assertSee('data-backfill="counting"', false)
            ->assertSee('Counting your older games')
            ->assertSee('42 of 160 games checked so far')
            ->assertSee('class="btn-link mt-1">Refresh</a>', false)
            ->assertSee('Highest score')
            ->assertSee('records-heading', false);
    }

    public function test_while_it_runs_and_nothing_is_counted_yet_the_page_is_not_a_claim_there_are_no_stats(): void
    {
        $this->backfill(StatsBackfill::RUNNING, ['games_total' => 160, 'games_seen' => 3]);

        $this->signedIn()->get('/stats')
            ->assertOk()
            ->assertSee('3 of 160 games checked so far')
            ->assertDontSee('No stats yet.')
            ->assertDontSee('Start a game')
            // Not a page of records that all say nobody has done anything
            ->assertDontSee('records-heading', false)
            ->assertDontSee('players-heading', false)
            ->assertDontSee('data-record', false)
            ->assertDontSee('Nobody has won a game yet');
    }

    public function test_a_job_that_gave_up_says_so_and_the_stats_so_far_are_still_there(): void
    {
        $this->backfill(StatsBackfill::FAILED, ['last_error' => 'The API answered 500']);
        $this->threeGames();

        $this->signedIn()->get('/stats')
            ->assertOk()
            ->assertSee('data-backfill="failed"', false)
            ->assertSee('We couldn’t count all your older games')
            ->assertSee('alert-warning', false)
            ->assertDontSee('The API answered 500')
            ->assertSee('Highest score');

        Bus::assertNotDispatched(BackfillStats::class);
    }

    public function test_a_job_that_gave_up_does_not_hide_that_there_are_no_stats(): void
    {
        $this->backfill(StatsBackfill::FAILED);

        $this->signedIn()->get('/stats')
            ->assertOk()
            ->assertSee('We couldn’t count all your older games')
            ->assertSee('No stats yet.');
    }

    public function test_when_it_has_finished_the_page_says_how_many_games_were_not_counted_and_why(): void
    {
        $this->backfill(StatsBackfill::COMPLETE, ['games_total' => 160, 'games_seen' => 160, 'games_counted' => 154, 'games_skipped' => 6, 'skipped' => ['unfinished' => 4, 'mismatch' => 2]]);
        $this->threeGames();

        $this->signedIn()->get('/stats')
            ->assertOk()
            ->assertSee('data-backfill="skipped"', false)
            ->assertSee('6 older games weren’t counted: 4 weren’t played to the end and 2 had scores that didn’t add up.')
            ->assertDontSee('Counting your older games');
    }

    public function test_when_it_has_finished_and_counted_everything_there_is_nothing_to_say(): void
    {
        $this->backfill(StatsBackfill::COMPLETE, ['games_total' => 3, 'games_seen' => 3, 'games_counted' => 3]);
        $this->threeGames();

        $this->signedIn()->get('/stats')->assertOk()->assertDontSee('data-backfill', false);
    }

    public function test_only_the_signed_in_players_job_is_described(): void
    {
        StatsBackfill::create(['user_id' => 'someone-else', 'state' => StatsBackfill::FAILED]);

        $this->signedIn()->get('/stats')->assertOk()->assertDontSee('data-backfill', false);
    }
}
