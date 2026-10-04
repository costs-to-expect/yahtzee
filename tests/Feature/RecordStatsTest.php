<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Actions\Game\RecordStats;
use App\Models\GameStat;
use App\Support\GameStatBuilder;
use Carbon\CarbonImmutable;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\Support\BuildsApiFixtures;
use Tests\TestCase;

/**
 * Recording a game makes the stats table say what the game's score sheets say, and only that, however many times the
 * game is recorded.
 */
class RecordStatsTest extends TestCase
{
    use BuildsApiFixtures;
    use RefreshDatabase;

    private const PLAYERS = ['p-1' => 'Ada', 'p-2' => 'Ben'];

    /**
     * @return array<string, mixed>
     */
    private function game(string $id = 'g-1'): array
    {
        return ['id' => $id, 'created' => '2026-10-01 18:30:00', 'updated' => '2026-10-01 19:10:00'];
    }

    /**
     * Ada has 285 with a Yahtzee, Ben has 105
     *
     * @return array<int, mixed>
     */
    private function sheets(): array
    {
        return $this->scoreSheets([
            'p-1' => $this->finishedScoreSheet(),
            'p-2' => $this->finishedScoreSheet(
                ['ones' => 2, 'twos' => 4, 'threes' => 6, 'fours' => 8, 'fives' => 10, 'sixes' => 12],
                ['three_of_a_kind' => 18, 'full_house' => 0, 'large_straight' => 0, 'yahtzee' => 0, 'chance' => 15]
            ),
        ]);
    }

    /**
     * @param array<string, mixed>|null $game
     * @param array<string, string>|null $players
     * @param array<int, mixed>|null $sheets
     */
    private function record(string $user_id = 'u-1', ?array $game = null, ?array $players = null, ?array $sheets = null, ?CarbonImmutable $completed_at = null): \App\Support\GameStatResult
    {
        return (new RecordStats())(
            $user_id,
            $game ?? $this->game(),
            $this->playerCollection($players ?? self::PLAYERS),
            $sheets ?? $this->sheets(),
            $completed_at
        );
    }

    /**
     * @return array<string, array{int, int, int}> player id => score, yahtzees, players in the game
     */
    private function stored(string $user_id = 'u-1', string $game_id = 'g-1'): array
    {
        $stored = [];

        foreach (GameStat::query()->where('user_id', $user_id)->where('game_id', $game_id)->orderBy('player_id')->get() as $stat) {
            $stored[$stat->player_id] = [$stat->score, $stat->yahtzees, $stat->players_in_game];
        }

        return $stored;
    }

    public function test_a_finished_game_is_recorded_for_each_of_its_players(): void
    {
        $result = $this->record();

        self::assertTrue($result->isCounted());
        self::assertSame(['p-1' => [285, 1, 2], 'p-2' => [105, 0, 2]], $this->stored());
    }

    public function test_the_sheet_and_the_times_are_stored(): void
    {
        $this->record();

        $stat = GameStat::query()->where('player_id', 'p-1')->firstOrFail();

        self::assertSame($this->finishedScoreSheet(), $stat->sheet);
        self::assertSame('Ada', $stat->player_name);
        self::assertSame('u-1', $stat->user_id);
        self::assertSame('2026-10-01 18:30:00', $stat->game_created_at->toDateTimeString());
        self::assertSame('2026-10-01 19:10:00', $stat->game_completed_at?->toDateTimeString());
        self::assertNotNull($stat->created_at);
    }

    public function test_the_time_a_game_is_given_is_the_time_it_was_completed(): void
    {
        $this->record(game: ['id' => 'g-1', 'created' => '2026-10-01 18:30:00'], completed_at: CarbonImmutable::parse('2026-10-04 21:15:30', 'UTC'));

        self::assertSame('2026-10-04 21:15:30', GameStat::query()->firstOrFail()->game_completed_at?->toDateTimeString());
    }

    public function test_recording_a_game_again_replaces_it_and_does_not_count_it_twice(): void
    {
        $this->record();
        $first_id = GameStat::query()->where('player_id', 'p-1')->value('id');

        $sheets = $this->scoreSheets([
            'p-1' => $this->finishedScoreSheet([], ['yahtzee' => 50, 'yahtzee_bonus_one' => 100]),
            'p-2' => $this->finishedScoreSheet(),
        ]);

        $this->record(sheets: $sheets);

        self::assertSame(2, GameStat::query()->count());
        self::assertSame(['p-1' => [385, 2, 2], 'p-2' => [285, 1, 2]], $this->stored());
        self::assertSame($first_id, GameStat::query()->where('player_id', 'p-1')->value('id'), 'The row is updated, not replaced');
    }

    public function test_a_player_who_is_no_longer_in_the_game_loses_their_row(): void
    {
        $this->record();

        $this->record(players: ['p-1' => 'Ada'], sheets: $this->scoreSheets(['p-1' => $this->finishedScoreSheet()]));

        self::assertSame(['p-1' => [285, 1, 1]], $this->stored());
    }

    public function test_a_game_that_can_no_longer_be_counted_loses_its_rows(): void
    {
        $this->record();

        $unfinished = $this->finishedScoreSheet();
        unset($unfinished['lower-section']['chance']);

        $result = $this->record(sheets: $this->scoreSheets(['p-1' => $unfinished, 'p-2' => $this->finishedScoreSheet()]));

        self::assertSame(GameStatBuilder::UNFINISHED, $result->reason);
        self::assertSame([], $this->stored());
    }

    public function test_a_game_that_cannot_be_counted_leaves_no_rows(): void
    {
        $result = $this->record(sheets: $this->scoreSheets(['p-1' => $this->finishedScoreSheet()]));

        self::assertFalse($result->isCounted());
        self::assertSame(GameStatBuilder::UNFINISHED, $result->reason);
        self::assertSame(0, GameStat::query()->count());
    }

    public function test_other_games_and_other_users_are_left_alone(): void
    {
        $this->record('u-1', $this->game('g-other'));
        $this->record('u-2', $this->game('g-1'));
        $this->record('u-1', $this->game('g-1'));

        // Replace, then lose, the game of the first user
        $this->record('u-1', $this->game('g-1'), sheets: $this->scoreSheets(['p-1' => $this->finishedScoreSheet(['ones' => 0])]));
        $this->record('u-1', $this->game('g-1'), sheets: []);

        self::assertSame([], $this->stored('u-1', 'g-1'));
        self::assertSame(['p-1' => [285, 1, 2], 'p-2' => [105, 0, 2]], $this->stored('u-1', 'g-other'));
        self::assertSame(['p-1' => [285, 1, 2], 'p-2' => [105, 0, 2]], $this->stored('u-2', 'g-1'));
    }

    public function test_a_game_with_no_id_cannot_be_recorded_and_nothing_is_removed(): void
    {
        $this->record();

        $result = $this->record(game: ['created' => '2026-10-01 18:30:00']);

        self::assertSame(GameStatBuilder::UNREADABLE, $result->reason);
        self::assertSame(2, GameStat::query()->count());
    }

    public function test_a_game_that_is_unreadable_for_another_reason_loses_its_rows(): void
    {
        $this->record();

        $result = $this->record(game: ['id' => 'g-1']);

        self::assertSame(GameStatBuilder::UNREADABLE, $result->reason);
        self::assertSame([], $this->stored());
    }
}
