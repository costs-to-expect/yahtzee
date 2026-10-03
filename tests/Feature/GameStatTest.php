<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Models\GameStat;
use Illuminate\Database\UniqueConstraintViolationException;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * The table the stats page reads: a row for each player in each finished game.
 */
class GameStatTest extends TestCase
{
    use RefreshDatabase;

    /**
     * @param array<string, mixed> $override
     * @return array<string, mixed>
     */
    private function row(array $override = []): array
    {
        return array_merge([
            'user_id' => 'u-1',
            'game_id' => 'g-1',
            'player_id' => 'p-1',
            'player_name' => 'Ada',
            'players_in_game' => 2,
            'score' => 287,
            'upper' => 80,
            'upper_bonus' => 35,
            'lower' => 172,
            'yahtzees' => 1,
            'sheet' => [
                'upper-section' => ['ones' => 3, 'twos' => 6],
                'lower-section' => ['yahtzee' => 50],
                'score' => ['upper' => 9, 'bonus' => 0, 'lower' => 50, 'total' => 59],
            ],
            'game_created_at' => '2026-10-01 18:30:00',
            'game_completed_at' => '2026-10-01 19:10:00',
        ], $override);
    }

    public function test_a_player_has_one_row_for_each_game_of_a_user(): void
    {
        GameStat::create($this->row());

        $this->expectException(UniqueConstraintViolationException::class);

        GameStat::create($this->row(['score' => 300]));
    }

    public function test_the_same_game_and_player_can_be_recorded_for_another_user(): void
    {
        GameStat::create($this->row());
        GameStat::create($this->row(['user_id' => 'u-2']));

        self::assertSame(2, GameStat::query()->count());
    }

    public function test_a_game_has_a_row_for_each_of_its_players(): void
    {
        GameStat::create($this->row());
        GameStat::create($this->row(['player_id' => 'p-2', 'player_name' => 'Ben', 'score' => 201]));

        self::assertSame(2, GameStat::query()->where('game_id', 'g-1')->count());
    }

    public function test_ids_that_only_differ_in_case_are_different_ids(): void
    {
        // The API's ids are case sensitive hashes. SQLite compares strings that way already, the table's utf8mb4_bin
        // collation is what makes MySQL do the same, so this is only a real check when the tests run against MySQL.
        GameStat::create($this->row(['user_id' => 'aBc', 'game_id' => 'xYz', 'player_id' => 'pQr']));
        GameStat::create($this->row(['user_id' => 'abc', 'game_id' => 'xyz', 'player_id' => 'pqr']));

        self::assertSame(2, GameStat::query()->count());
        self::assertSame(1, GameStat::query()->where('user_id', 'aBc')->count());
    }

    public function test_the_sheet_and_the_dates_come_back_as_they_went_in(): void
    {
        $row = $this->row();
        $id = GameStat::create($row)->id;

        $stat = GameStat::query()->findOrFail($id);

        self::assertSame($row['sheet'], $stat->sheet);
        self::assertSame('2026-10-01 18:30:00', $stat->game_created_at->toDateTimeString());
        self::assertSame('2026-10-01 19:10:00', $stat->game_completed_at?->toDateTimeString());
    }

    public function test_the_numbers_come_back_as_integers(): void
    {
        $stat = GameStat::query()->findOrFail(GameStat::create($this->row())->id);

        foreach (['players_in_game', 'score', 'upper', 'upper_bonus', 'lower', 'yahtzees'] as $column) {
            self::assertIsInt($stat->{$column}, $column);
        }
    }

    public function test_the_completed_time_is_optional_the_created_time_is_not(): void
    {
        $stat = GameStat::query()->findOrFail(GameStat::create($this->row(['game_completed_at' => null]))->id);

        self::assertNull($stat->game_completed_at);

        $this->expectException(\Illuminate\Database\QueryException::class);

        GameStat::create($this->row(['game_id' => 'g-2', 'game_created_at' => null]));
    }
}
