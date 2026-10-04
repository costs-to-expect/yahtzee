<?php

declare(strict_types=1);

namespace Tests\Support;

/**
 * Builders for the data shapes the Costs to Expect API returns for Yahtzee, shared by the
 * tests that fake the API. The score sheet builder applies the same rules as the app: the
 * upper section scores a 35 point bonus from 63, the total is upper + bonus + lower.
 */
trait BuildsApiFixtures
{
    /**
     * @param array<string, int> $upper e.g. ['ones' => 3, 'twos' => 0]
     * @param array<string, int> $lower e.g. ['full_house' => 25]
     */
    protected function scoreSheet(array $upper = [], array $lower = []): array
    {
        $upper_total = array_sum($upper);
        $bonus = $upper_total >= 63 ? 35 : 0;
        $lower_total = array_sum($lower);

        return [
            'upper-section' => $upper,
            'lower-section' => $lower,
            'score' => [
                'upper' => $upper_total,
                'bonus' => $bonus,
                'lower' => $lower_total,
                'total' => $upper_total + $bonus + $lower_total,
            ],
        ];
    }

    /**
     * A score sheet for a game that has been played to the end, every combination is scored (285 points with the
     * upper bonus and a Yahtzee). The overrides change the score of a combination, they can't add one.
     *
     * @param array<string, int> $upper e.g. ['ones' => 0]
     * @param array<string, int> $lower e.g. ['yahtzee' => 0]
     */
    protected function finishedScoreSheet(array $upper = [], array $lower = []): array
    {
        return $this->scoreSheet(
            array_replace(['ones' => 3, 'twos' => 6, 'threes' => 9, 'fours' => 12, 'fives' => 15, 'sixes' => 18], $upper),
            array_replace(
                ['three_of_a_kind' => 20, 'four_of_a_kind' => 0, 'full_house' => 25, 'small_straight' => 30, 'large_straight' => 40, 'yahtzee' => 50, 'chance' => 22],
                $lower
            )
        );
    }

    /**
     * A game as returned when requesting items with include-players.
     *
     * @param array<string, string> $players player id => name
     * @param list<array{player_id: string, player_name: string, score: int}> $scores only for a complete game
     */
    protected function game(string $id, array $players, bool $complete = false, array $scores = []): array
    {
        $collection = [];
        foreach ($players as $player_id => $name) {
            $collection[] = ['id' => $player_id, 'name' => $name];
        }

        $game = [
            'id' => $id,
            'name' => 'Yahtzee game',
            'description' => 'Yahtzee game create via the Yahtzee app',
            'complete' => $complete ? 1 : 0,
            'players' => ['collection' => $collection],
        ];

        if ($complete) {
            $game['game'] = ['scores' => $scores, 'winner' => $scores[0] ?? null];
        }

        return $game;
    }

    /**
     * The players assigned to a game, as returned by the game's categories collection. Each
     * assignment has its own id (ga-p-1 for player p-1), it is the assignment that is deleted
     * to remove a player from a game.
     *
     * @param array<string, string> $players player id => name
     */
    protected function assignedPlayers(array $players): array
    {
        $assigned = [];
        foreach ($players as $player_id => $name) {
            $assigned[] = ['id' => 'ga-'.$player_id, 'category' => ['id' => $player_id, 'name' => $name]];
        }

        return $assigned;
    }

    /**
     * The players collection, as returned by the resource type's categories.
     *
     * @param array<string, string> $players player id => name
     */
    protected function playerCollection(array $players): array
    {
        $collection = [];
        foreach ($players as $player_id => $name) {
            $collection[] = ['id' => $player_id, 'name' => $name];
        }

        return $collection;
    }

    /**
     * A game's score sheets, one per player.
     *
     * @param array<string, array> $sheets player id => score sheet
     */
    protected function scoreSheets(array $sheets): array
    {
        $collection = [];
        foreach ($sheets as $player_id => $sheet) {
            $collection[] = ['key' => $player_id, 'value' => $sheet];
        }

        return $collection;
    }
}
