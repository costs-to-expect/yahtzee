<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Models\GameStat;
use App\Support\GameStatBuilder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\Support\BuildsApiFixtures;
use Tests\TestCase;

/**
 * The rows the builder returns are the rows of the stats table, the recording of a completed game and the backfill job
 * store them without changing anything.
 */
class GameStatBuilderStorageTest extends TestCase
{
    use BuildsApiFixtures;
    use RefreshDatabase;

    public function test_the_rows_are_stored_as_they_are_and_read_back_the_same(): void
    {
        $ada = $this->scoreSheet(
            ['ones' => 3, 'twos' => 6, 'threes' => 9, 'fours' => 12, 'fives' => 15, 'sixes' => 18],
            ['three_of_a_kind' => 20, 'four_of_a_kind' => 0, 'full_house' => 25, 'small_straight' => 30, 'large_straight' => 40, 'yahtzee' => 50, 'chance' => 22, 'yahtzee_bonus_one' => 100]
        );
        $ben = $this->scoreSheet(
            ['ones' => 2, 'twos' => 4, 'threes' => 6, 'fours' => 8, 'fives' => 10, 'sixes' => 12],
            ['three_of_a_kind' => 18, 'four_of_a_kind' => 0, 'full_house' => 0, 'small_straight' => 30, 'large_straight' => 0, 'yahtzee' => 0, 'chance' => 15]
        );

        $result = GameStatBuilder::build(
            'u-1',
            ['id' => 'g-1', 'created' => '2026-10-01 18:30:00', 'updated' => '2026-10-01 19:10:00'],
            $this->playerCollection(['p-ada' => 'Ada', 'p-ben' => 'Ben']),
            $this->scoreSheets(['p-ada' => $ada, 'p-ben' => $ben])
        );

        self::assertTrue($result->isCounted());

        foreach ($result->rows as $row) {
            GameStat::create($row);
        }

        $stored = GameStat::query()->where('game_id', 'g-1')->orderBy('id')->get();

        self::assertCount(2, $stored);

        foreach ($result->rows as $position => $row) {
            $stat = $stored[$position];

            self::assertSame($row['user_id'], $stat->user_id);
            self::assertSame($row['player_id'], $stat->player_id);
            self::assertSame($row['player_name'], $stat->player_name);
            self::assertSame($row['players_in_game'], $stat->players_in_game);
            self::assertSame($row['score'], $stat->score);
            self::assertSame($row['upper'], $stat->upper);
            self::assertSame($row['upper_bonus'], $stat->upper_bonus);
            self::assertSame($row['lower'], $stat->lower);
            self::assertSame($row['yahtzees'], $stat->yahtzees);
            self::assertSame($row['sheet'], $stat->sheet);
            self::assertSame($row['game_created_at'], $stat->game_created_at->toDateTimeString());
            self::assertSame($row['game_completed_at'], $stat->game_completed_at?->toDateTimeString());
        }

        self::assertSame(385, $stored[0]->score);
        self::assertSame(2, $stored[0]->yahtzees);
    }
}
