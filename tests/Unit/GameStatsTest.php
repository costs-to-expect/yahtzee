<?php

declare(strict_types=1);

namespace Tests\Unit;

use App\Support\GameStats;
use Carbon\CarbonImmutable;
use PHPUnit\Framework\TestCase;

class GameStatsTest extends TestCase
{
    /**
     * The rows of one game: the player => score, or player => [score, Yahtzees]. The game is created on that day of
     * January at 18:00.
     *
     * @param array<string, int|array{int, int}> $scores
     * @return list<array<string, mixed>>
     */
    private function game(string $id, int $day, array $scores): array
    {
        $rows = [];

        foreach ($scores as $player => $result) {
            [$score, $yahtzees] = is_array($result) ? $result : [$result, 0];

            $rows[] = [
                'game_id' => $id,
                'player_id' => 'p-' . $player,
                'player_name' => ucfirst($player),
                'score' => $score,
                'yahtzees' => $yahtzees,
                'game_created_at' => sprintf('2026-01-%02d 18:00:00', $day),
            ];
        }

        return $rows;
    }

    /**
     * @param list<array<string, mixed>> ...$games
     * @return list<array<string, mixed>>
     */
    private function rows(array ...$games): array
    {
        return array_merge(...$games);
    }

    /**
     * Who holds a record and the record, null when nobody does
     *
     * @param array{value: int, holders: list<array<string, mixed>>}|null $record
     * @return array<string, int>|null
     */
    private function holders(?array $record): ?array
    {
        if ($record === null) {
            return null;
        }

        $holders = [];
        foreach ($record['holders'] as $holder) {
            $holders[$holder['player_name']] = $record['value'];
        }

        return $holders;
    }

    /**
     * @param list<array<string, mixed>> $rows
     * @return array<string, mixed>
     */
    private function player(array $rows, string $name): array
    {
        foreach (GameStats::summarise($rows)['players'] as $player) {
            if ($player['player_name'] === $name) {
                return $player;
            }
        }

        $this->fail('There is no player called ' . $name);
    }

    /**
     * @param list<array<string, mixed>> $rows
     * @return array{wins: int, losses: int}
     */
    private function wonAndLost(array $rows, string $name): array
    {
        $player = $this->player($rows, $name);

        return ['wins' => $player['wins'], 'losses' => $player['losses']];
    }

    // The shape

    public function test_there_is_nothing_to_report_without_games(): void
    {
        $stats = GameStats::summarise([]);

        self::assertSame(0, $stats['games']);
        self::assertSame([], $stats['players']);
        self::assertSame(
            ['highest_score', 'most_wins', 'most_consecutive_wins', 'lowest_score', 'most_consecutive_losses', 'most_yahtzees_in_a_game', 'most_consecutive_games_with_a_yahtzee'],
            array_keys($stats['records'])
        );

        foreach ($stats['records'] as $name => $record) {
            self::assertNull($record, $name);
        }
    }

    public function test_a_game_is_counted_once_however_many_players_it_has(): void
    {
        $stats = GameStats::summarise($this->rows(
            $this->game('g-1', 1, ['ada' => 200, 'ben' => 150, 'cleo' => 100]),
            $this->game('g-2', 2, ['ada' => 180])
        ));

        self::assertSame(2, $stats['games']);
        self::assertCount(3, $stats['players']);
    }

    // Highest and lowest score

    public function test_the_highest_and_lowest_scores_say_who_got_them_and_when(): void
    {
        $stats = GameStats::summarise($this->rows(
            $this->game('g-1', 1, ['ada' => 250, 'ben' => 140]),
            $this->game('g-2', 2, ['ada' => 310, 'ben' => 95]),
            $this->game('g-3', 3, ['ada' => 200, 'ben' => 180])
        ));

        self::assertSame(
            ['value' => 310, 'holders' => [['player_id' => 'p-ada', 'player_name' => 'Ada', 'game_id' => 'g-2', 'from' => '2026-01-02 18:00:00', 'to' => '2026-01-02 18:00:00']]],
            $stats['records']['highest_score']
        );
        self::assertSame(
            ['value' => 95, 'holders' => [['player_id' => 'p-ben', 'player_name' => 'Ben', 'game_id' => 'g-2', 'from' => '2026-01-02 18:00:00', 'to' => '2026-01-02 18:00:00']]],
            $stats['records']['lowest_score']
        );
    }

    public function test_a_score_two_players_share_has_two_holders_each_with_the_first_game_they_did_it_in(): void
    {
        $stats = GameStats::summarise($this->rows(
            $this->game('g-1', 1, ['ben' => 300, 'ada' => 120]),
            $this->game('g-2', 2, ['ada' => 300, 'ben' => 300]),
            $this->game('g-3', 3, ['ada' => 300, 'ben' => 100])
        ));

        $holders = $stats['records']['highest_score']['holders'];

        self::assertSame(300, $stats['records']['highest_score']['value']);
        self::assertSame(['Ben', 'Ada'], array_column($holders, 'player_name'), 'In the order they first did it');
        self::assertSame(['g-1', 'g-2'], array_column($holders, 'game_id'), 'The first game each of them did it in');
    }

    public function test_the_same_score_again_by_the_same_player_is_not_another_holder(): void
    {
        $stats = GameStats::summarise($this->rows(
            $this->game('g-1', 1, ['ada' => 90, 'ben' => 200]),
            $this->game('g-2', 2, ['ada' => 90, 'ben' => 210])
        ));

        self::assertCount(1, $stats['records']['lowest_score']['holders']);
        self::assertSame('g-1', $stats['records']['lowest_score']['holders'][0]['game_id']);
    }

    // Wins and losses

    public function test_most_wins_counts_the_games_a_player_won(): void
    {
        $rows = $this->rows(
            $this->game('g-1', 1, ['ada' => 200, 'ben' => 150]),
            $this->game('g-2', 2, ['ada' => 100, 'ben' => 150]),
            $this->game('g-3', 3, ['ada' => 220, 'ben' => 150]),
            $this->game('g-4', 4, ['ada' => 230, 'ben' => 150])
        );

        $record = GameStats::summarise($rows)['records']['most_wins'];

        self::assertSame(['Ada' => 3], $this->holders($record));
        self::assertSame(['wins' => 3, 'losses' => 1], $this->wonAndLost($rows, 'Ada'));
        self::assertSame(['wins' => 1, 'losses' => 3], $this->wonAndLost($rows, 'Ben'));
        self::assertNull($record['holders'][0]['game_id']);
        self::assertNull($record['holders'][0]['from']);
        self::assertNull($record['holders'][0]['to']);
    }

    public function test_players_with_the_same_number_of_wins_share_the_record(): void
    {
        $stats = GameStats::summarise($this->rows(
            $this->game('g-1', 1, ['ada' => 200, 'ben' => 150]),
            $this->game('g-2', 2, ['ada' => 100, 'ben' => 150])
        ));

        self::assertSame(['Ada' => 1, 'Ben' => 1], $this->holders($stats['records']['most_wins']));
    }

    public function test_a_tie_for_the_top_score_is_a_win_for_everyone_who_tied_and_a_loss_for_nobody(): void
    {
        $rows = $this->game('g-1', 1, ['ada' => 200, 'ben' => 200, 'cleo' => 190]);

        self::assertSame(['wins' => 1, 'losses' => 0], $this->wonAndLost($rows, 'Ada'));
        self::assertSame(['wins' => 1, 'losses' => 0], $this->wonAndLost($rows, 'Ben'));
        self::assertSame(['wins' => 0, 'losses' => 1], $this->wonAndLost($rows, 'Cleo'));
    }

    public function test_a_tie_continues_a_winning_streak_and_ends_a_losing_one(): void
    {
        $stats = GameStats::summarise($this->rows(
            $this->game('g-1', 1, ['ada' => 200, 'ben' => 100]),
            $this->game('g-2', 2, ['ada' => 150, 'ben' => 150]),
            $this->game('g-3', 3, ['ada' => 200, 'ben' => 100])
        ));

        // Ada: win, win (shared), win. Ben: loss, win (shared), loss
        self::assertSame(['Ada' => 3], $this->holders($stats['records']['most_consecutive_wins']));
        self::assertSame(['Ben' => 1], $this->holders($stats['records']['most_consecutive_losses']));
    }

    public function test_second_place_is_a_loss_however_close_it_was(): void
    {
        $rows = $this->game('g-1', 1, ['ada' => 200, 'ben' => 199]);

        self::assertSame(['wins' => 0, 'losses' => 1], $this->wonAndLost($rows, 'Ben'));
    }

    // Games with one player

    public function test_a_game_with_one_player_counts_for_scores_and_yahtzees_but_is_not_a_win_or_a_loss(): void
    {
        $stats = GameStats::summarise($this->game('g-1', 1, ['ada' => [320, 2]]));

        self::assertSame(['Ada' => 320], $this->holders($stats['records']['highest_score']));
        self::assertSame(['Ada' => 320], $this->holders($stats['records']['lowest_score']));
        self::assertSame(['Ada' => 2], $this->holders($stats['records']['most_yahtzees_in_a_game']));
        self::assertSame(['Ada' => 1], $this->holders($stats['records']['most_consecutive_games_with_a_yahtzee']));

        self::assertNull($stats['records']['most_wins']);
        self::assertNull($stats['records']['most_consecutive_wins']);
        self::assertNull($stats['records']['most_consecutive_losses']);
        self::assertSame(['games' => 1, 'wins' => 0, 'losses' => 0], array_intersect_key($stats['players'][0], ['games' => 0, 'wins' => 0, 'losses' => 0]));
    }

    public function test_a_game_with_one_player_neither_breaks_nor_extends_a_streak(): void
    {
        // Ada wins, plays on her own, wins, plays on her own, loses, wins. If a game on her own broke the streak it
        // would be 1, if it counted as a win it would be 3
        $stats = GameStats::summarise($this->rows(
            $this->game('g-1', 1, ['ada' => 200, 'ben' => 100]),
            $this->game('g-2', 2, ['ada' => 90]),
            $this->game('g-3', 3, ['ada' => 200, 'ben' => 100]),
            $this->game('g-4', 4, ['ada' => 95]),
            $this->game('g-5', 5, ['ada' => 100, 'ben' => 120]),
            $this->game('g-6', 6, ['ada' => 210, 'ben' => 100])
        ));

        $holder = $stats['records']['most_consecutive_wins']['holders'][0];

        self::assertSame(['Ada' => 2], $this->holders($stats['records']['most_consecutive_wins']));
        self::assertSame('g-3', $holder['game_id']);
        self::assertSame('2026-01-01 18:00:00', $holder['from']);
        self::assertSame('2026-01-03 18:00:00', $holder['to']);
    }

    public function test_a_game_with_one_player_is_not_a_loss_and_does_not_end_a_run_of_losses(): void
    {
        $rows = $this->rows(
            $this->game('g-1', 1, ['ada' => 100, 'ben' => 200]),
            $this->game('g-2', 2, ['ada' => 90]),
            $this->game('g-3', 3, ['ada' => 100, 'ben' => 200])
        );

        // Two losses with a game on her own between them: one if it ended the run, three if it was a loss
        self::assertSame(2, $this->player($rows, 'Ada')['longest_loss_streak']);
        self::assertSame(2, $this->player($rows, 'Ada')['losses']);
    }

    // Streaks

    public function test_consecutive_wins_run_through_the_games_a_player_took_part_in(): void
    {
        // Ben misses the second game, it ends neither Ada's wins nor Ben's losses
        $stats = GameStats::summarise($this->rows(
            $this->game('g-1', 1, ['ada' => 200, 'ben' => 100]),
            $this->game('g-2', 2, ['ada' => 200, 'cleo' => 100]),
            $this->game('g-3', 3, ['ada' => 200, 'ben' => 100])
        ));

        self::assertSame(['Ada' => 3], $this->holders($stats['records']['most_consecutive_wins']));
        self::assertSame(['Ben' => 2], $this->holders($stats['records']['most_consecutive_losses']));
    }

    public function test_a_loss_ends_a_winning_streak_and_the_longest_is_the_record(): void
    {
        // Ada: win win loss win win win loss
        $rows = $this->rows(
            $this->game('g-1', 1, ['ada' => 200, 'ben' => 100]),
            $this->game('g-2', 2, ['ada' => 200, 'ben' => 100]),
            $this->game('g-3', 3, ['ada' => 100, 'ben' => 200]),
            $this->game('g-4', 4, ['ada' => 200, 'ben' => 100]),
            $this->game('g-5', 5, ['ada' => 200, 'ben' => 100]),
            $this->game('g-6', 6, ['ada' => 200, 'ben' => 100]),
            $this->game('g-7', 7, ['ada' => 100, 'ben' => 200])
        );

        $ada = $this->player($rows, 'Ada');

        self::assertSame(3, $ada['longest_win_streak']);
        self::assertSame(1, $ada['longest_loss_streak']);
        self::assertSame(['Ada' => 3], $this->holders(GameStats::summarise($rows)['records']['most_consecutive_wins']));
    }

    public function test_a_streak_says_which_games_it_was_and_when(): void
    {
        $stats = GameStats::summarise($this->rows(
            $this->game('g-1', 1, ['ada' => 100, 'ben' => 200]),
            $this->game('g-2', 3, ['ada' => 200, 'ben' => 100]),
            $this->game('g-3', 5, ['ada' => 200, 'ben' => 100]),
            $this->game('g-4', 9, ['ada' => 200, 'ben' => 100]),
            $this->game('g-5', 12, ['ada' => 100, 'ben' => 200])
        ));

        self::assertSame(
            ['value' => 3, 'holders' => [['player_id' => 'p-ada', 'player_name' => 'Ada', 'game_id' => 'g-4', 'from' => '2026-01-03 18:00:00', 'to' => '2026-01-09 18:00:00']]],
            $stats['records']['most_consecutive_wins']
        );
    }

    public function test_the_first_streak_is_kept_when_two_are_as_long(): void
    {
        // Ada: win win loss win win
        $stats = GameStats::summarise($this->rows(
            $this->game('g-1', 1, ['ada' => 200, 'ben' => 100]),
            $this->game('g-2', 2, ['ada' => 200, 'ben' => 100]),
            $this->game('g-3', 3, ['ada' => 100, 'ben' => 200]),
            $this->game('g-4', 4, ['ada' => 200, 'ben' => 100]),
            $this->game('g-5', 5, ['ada' => 200, 'ben' => 100])
        ));

        $holder = $stats['records']['most_consecutive_wins']['holders'][0];

        self::assertSame(2, $stats['records']['most_consecutive_wins']['value']);
        self::assertSame('g-2', $holder['game_id']);
        self::assertSame('2026-01-01 18:00:00', $holder['from']);
        self::assertSame('2026-01-02 18:00:00', $holder['to']);
    }

    public function test_players_with_equally_long_streaks_share_the_record(): void
    {
        $stats = GameStats::summarise($this->rows(
            $this->game('g-1', 1, ['ada' => 200, 'ben' => 100, 'cleo' => 50]),
            $this->game('g-2', 2, ['ada' => 200, 'ben' => 100, 'cleo' => 50])
        ));

        self::assertSame(['Ben' => 2, 'Cleo' => 2], $this->holders($stats['records']['most_consecutive_losses']));
    }

    public function test_consecutive_losses(): void
    {
        $stats = GameStats::summarise($this->rows(
            $this->game('g-1', 1, ['ada' => 200, 'ben' => 100]),
            $this->game('g-2', 2, ['ada' => 200, 'ben' => 100]),
            $this->game('g-3', 3, ['ada' => 200, 'ben' => 100]),
            $this->game('g-4', 4, ['ada' => 100, 'ben' => 200]),
            $this->game('g-5', 5, ['ada' => 200, 'ben' => 100])
        ));

        self::assertSame(
            ['value' => 3, 'holders' => [['player_id' => 'p-ben', 'player_name' => 'Ben', 'game_id' => 'g-3', 'from' => '2026-01-01 18:00:00', 'to' => '2026-01-03 18:00:00']]],
            $stats['records']['most_consecutive_losses']
        );
    }

    public function test_there_are_no_streaks_while_nobody_has_won_or_lost_a_game(): void
    {
        $stats = GameStats::summarise($this->rows(
            $this->game('g-1', 1, ['ada' => 200]),
            $this->game('g-2', 2, ['ben' => 100])
        ));

        self::assertNull($stats['records']['most_wins']);
        self::assertNull($stats['records']['most_consecutive_wins']);
        self::assertNull($stats['records']['most_consecutive_losses']);
    }

    // The order games are taken in

    public function test_games_are_taken_in_the_order_they_were_created_not_the_order_given(): void
    {
        // Ada wins, wins, loses
        $in_order = $this->rows(
            $this->game('g-1', 1, ['ada' => 200, 'ben' => 100]),
            $this->game('g-2', 2, ['ada' => 200, 'ben' => 100]),
            $this->game('g-3', 3, ['ada' => 100, 'ben' => 200])
        );

        $shuffled = [$in_order[5], $in_order[0], $in_order[3], $in_order[4], $in_order[2], $in_order[1]];

        self::assertSame(GameStats::summarise($in_order), GameStats::summarise($shuffled));
        self::assertSame(2, $this->player($shuffled, 'Ada')['longest_win_streak']);
    }

    public function test_games_created_at_the_same_moment_are_taken_in_the_order_of_their_ids(): void
    {
        // In the order of their ids Ada wins, loses, wins
        $rows = $this->rows(
            $this->game('g-b', 1, ['ada' => 100, 'ben' => 200]),
            $this->game('g-a', 1, ['ada' => 200, 'ben' => 100]),
            $this->game('g-c', 1, ['ada' => 200, 'ben' => 100])
        );

        self::assertSame(GameStats::summarise($rows), GameStats::summarise(array_reverse($rows)));
        self::assertSame(1, $this->player($rows, 'Ada')['longest_win_streak']);
    }

    // Yahtzees

    public function test_most_yahtzees_in_a_game(): void
    {
        $stats = GameStats::summarise($this->rows(
            $this->game('g-1', 1, ['ada' => [300, 1], 'ben' => [100, 0]]),
            $this->game('g-2', 2, ['ada' => [250, 2], 'ben' => [100, 2]]),
            $this->game('g-3', 3, ['ada' => [250, 2], 'ben' => [100, 1]])
        ));

        self::assertSame(
            ['value' => 2, 'holders' => [
                ['player_id' => 'p-ada', 'player_name' => 'Ada', 'game_id' => 'g-2', 'from' => '2026-01-02 18:00:00', 'to' => '2026-01-02 18:00:00'],
                ['player_id' => 'p-ben', 'player_name' => 'Ben', 'game_id' => 'g-2', 'from' => '2026-01-02 18:00:00', 'to' => '2026-01-02 18:00:00'],
            ]],
            $stats['records']['most_yahtzees_in_a_game']
        );
    }

    public function test_consecutive_games_with_a_yahtzee_run_through_the_games_a_player_took_part_in(): void
    {
        // Ada has a Yahtzee in g-1 and g-2, none in g-3, then g-4 and g-5. Ben was not in g-2 and has one in g-3, g-4
        // and g-5, the game he missed does not matter to him
        $rows = $this->rows(
            $this->game('g-1', 1, ['ada' => [200, 1], 'ben' => [100, 0]]),
            $this->game('g-2', 2, ['ada' => [200, 3], 'cleo' => [100, 0]]),
            $this->game('g-3', 3, ['ada' => [100, 0], 'ben' => [200, 1]]),
            $this->game('g-4', 4, ['ada' => [200, 1], 'ben' => [100, 1]]),
            $this->game('g-5', 5, ['ada' => [200, 1], 'ben' => [100, 1]])
        );

        $record = GameStats::summarise($rows)['records']['most_consecutive_games_with_a_yahtzee'];

        self::assertSame(['Ben' => 3], $this->holders($record));
        self::assertSame('g-5', $record['holders'][0]['game_id']);
        self::assertSame('2026-01-03 18:00:00', $record['holders'][0]['from']);
        self::assertSame('2026-01-05 18:00:00', $record['holders'][0]['to']);
        self::assertSame(2, $this->player($rows, 'Ada')['longest_yahtzee_streak']);
    }

    public function test_a_game_with_one_player_is_part_of_a_run_of_games_with_a_yahtzee(): void
    {
        $stats = GameStats::summarise($this->rows(
            $this->game('g-1', 1, ['ada' => [200, 1], 'ben' => [100, 0]]),
            $this->game('g-2', 2, ['ada' => [200, 1]]),
            $this->game('g-3', 3, ['ada' => [200, 1], 'ben' => [100, 0]])
        ));

        self::assertSame(['Ada' => 3], $this->holders($stats['records']['most_consecutive_games_with_a_yahtzee']));
    }

    public function test_a_game_without_a_yahtzee_ends_the_run(): void
    {
        $stats = GameStats::summarise($this->rows(
            $this->game('g-1', 1, ['ada' => [200, 1], 'ben' => [100, 0]]),
            $this->game('g-2', 2, ['ada' => [200, 0], 'ben' => [100, 0]]),
            $this->game('g-3', 3, ['ada' => [200, 4], 'ben' => [100, 0]])
        ));

        self::assertSame(['Ada' => 1], $this->holders($stats['records']['most_consecutive_games_with_a_yahtzee']));
        self::assertSame('g-1', $stats['records']['most_consecutive_games_with_a_yahtzee']['holders'][0]['game_id']);
        self::assertSame(['Ada' => 4], $this->holders($stats['records']['most_yahtzees_in_a_game']));
    }

    public function test_there_are_no_yahtzee_records_until_there_has_been_a_yahtzee(): void
    {
        $stats = GameStats::summarise($this->game('g-1', 1, ['ada' => [200, 0], 'ben' => [100, 0]]));

        self::assertNull($stats['records']['most_yahtzees_in_a_game']);
        self::assertNull($stats['records']['most_consecutive_games_with_a_yahtzee']);
        self::assertNotNull($stats['records']['highest_score']);
    }

    // The table of players

    public function test_the_table_has_a_row_for_each_player(): void
    {
        $rows = $this->rows(
            $this->game('g-1', 1, ['ada' => [285, 1], 'ben' => [105, 0]]),
            $this->game('g-2', 2, ['ada' => [200, 0], 'ben' => [215, 2]]),
            $this->game('g-3', 3, ['ada' => [310, 1], 'ben' => [90, 0]])
        );

        self::assertSame(
            [
                'player_id' => 'p-ada',
                'player_name' => 'Ada',
                'games' => 3,
                'wins' => 2,
                'losses' => 1,
                'average_score' => 265.0,
                'highest_score' => 310,
                'lowest_score' => 200,
                'yahtzees' => 2,
                'most_yahtzees_in_a_game' => 1,
                'longest_win_streak' => 1,
                'longest_loss_streak' => 1,
                'longest_yahtzee_streak' => 1,
            ],
            $this->player($rows, 'Ada')
        );
        self::assertSame(
            [
                'player_id' => 'p-ben',
                'player_name' => 'Ben',
                'games' => 3,
                'wins' => 1,
                'losses' => 2,
                'average_score' => 136.7,
                'highest_score' => 215,
                'lowest_score' => 90,
                'yahtzees' => 2,
                'most_yahtzees_in_a_game' => 2,
                'longest_win_streak' => 1,
                'longest_loss_streak' => 1,
                'longest_yahtzee_streak' => 1,
            ],
            $this->player($rows, 'Ben')
        );
    }

    public function test_a_player_who_has_never_won_or_had_a_yahtzee_has_zeros_not_nulls(): void
    {
        $ben = $this->player($this->game('g-1', 1, ['ada' => 200, 'ben' => 100]), 'Ben');

        self::assertSame(0, $ben['wins']);
        self::assertSame(0, $ben['yahtzees']);
        self::assertSame(0, $ben['most_yahtzees_in_a_game']);
        self::assertSame(0, $ben['longest_win_streak']);
        self::assertSame(0, $ben['longest_yahtzee_streak']);
        self::assertSame(1, $ben['longest_loss_streak']);
    }

    public function test_the_table_is_best_first_most_wins_then_most_games_then_name(): void
    {
        $stats = GameStats::summarise($this->rows(
            $this->game('g-1', 1, ['zoe' => 200, 'ada' => 100, 'ben' => 100, 'cleo' => 100]),
            $this->game('g-2', 2, ['zoe' => 200, 'ben' => 100, 'cleo' => 100]),
            $this->game('g-3', 3, ['cleo' => 200, 'ben' => 100]),
            $this->game('g-4', 4, ['ben' => 200, 'ada' => 100])
        ));

        // Zoe has 2 wins, Ben 1 win in 4 games, Cleo 1 win in 3 games, Ada none
        self::assertSame(['Zoe', 'Ben', 'Cleo', 'Ada'], array_column($stats['players'], 'player_name'));

        $everyone_tied = GameStats::summarise($this->game('g-1', 1, ['ben' => 100, 'ada' => 100, 'cleo' => 100]));

        // A win and a game each, so the names decide
        self::assertSame(['Ada', 'Ben', 'Cleo'], array_column($everyone_tied['players'], 'player_name'));
    }

    public function test_the_holders_of_a_total_or_a_streak_are_in_the_order_of_the_table(): void
    {
        // Ada and Ben both win twice in a row, Ben has played more games so he is first in the table
        $stats = GameStats::summarise($this->rows(
            $this->game('g-1', 1, ['ada' => 200, 'cleo' => 100]),
            $this->game('g-2', 2, ['ada' => 200, 'cleo' => 100]),
            $this->game('g-3', 3, ['ben' => 100, 'cleo' => 200]),
            $this->game('g-4', 4, ['ben' => 200, 'cleo' => 100]),
            $this->game('g-5', 5, ['ben' => 200, 'cleo' => 100])
        ));

        self::assertSame(['Ben', 'Ada'], array_column($stats['records']['most_consecutive_wins']['holders'], 'player_name'));
    }

    public function test_a_player_has_the_name_they_had_in_their_latest_game(): void
    {
        $second = $this->game('g-2', 2, ['ada' => 200, 'ben' => 100]);
        $second[0]['player_name'] = 'Ada B';

        $stats = GameStats::summarise($this->rows($this->game('g-1', 1, ['ada' => 200, 'ben' => 100]), $second));

        self::assertContains('Ada B', array_column($stats['players'], 'player_name'));
        self::assertNotContains('Ada', array_column($stats['players'], 'player_name'));
        self::assertSame('Ada B', $stats['records']['most_wins']['holders'][0]['player_name']);
    }

    // What goes in

    public function test_the_times_are_in_utc_and_the_numbers_may_be_text(): void
    {
        $stats = GameStats::summarise([
            [
                'game_id' => 'g-1',
                'player_id' => 'p-ada',
                'player_name' => 'Ada',
                'score' => '285',
                'yahtzees' => '1',
                'game_created_at' => CarbonImmutable::parse('2026-01-01 20:00:00+02:00'),
            ],
            [
                'game_id' => 'g-1',
                'player_id' => 'p-ben',
                'player_name' => 'Ben',
                'score' => '105',
                'yahtzees' => '0',
                'game_created_at' => '2026-01-01T20:00:00+02:00',
            ],
        ]);

        self::assertSame(285, $stats['records']['highest_score']['value']);
        self::assertSame('2026-01-01 18:00:00', $stats['records']['highest_score']['holders'][0]['from']);
        self::assertSame(['Ada' => 1], $this->holders($stats['records']['most_wins']));
        self::assertSame(['Ada' => 1], $this->holders($stats['records']['most_yahtzees_in_a_game']));
    }

    public function test_ids_that_look_like_numbers_are_still_text(): void
    {
        $stats = GameStats::summarise([
            ['game_id' => '10', 'player_id' => '7', 'player_name' => 'Ada', 'score' => 200, 'yahtzees' => 0, 'game_created_at' => '2026-01-01 18:00:00'],
            ['game_id' => '10', 'player_id' => '8', 'player_name' => 'Ben', 'score' => 100, 'yahtzees' => 0, 'game_created_at' => '2026-01-01 18:00:00'],
        ]);

        self::assertSame('7', $stats['players'][0]['player_id']);
        self::assertSame('10', $stats['records']['highest_score']['holders'][0]['game_id']);
        self::assertSame('8', $stats['records']['lowest_score']['holders'][0]['player_id']);
    }
}
