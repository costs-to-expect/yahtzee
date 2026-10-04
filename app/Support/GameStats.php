<?php

declare(strict_types=1);

namespace App\Support;

use Carbon\CarbonImmutable;

/**
 * The stats page: the records and a table of the players, worked out from the rows of the game_stat table (one for each
 * player in each game that was played to the end). Nothing is read from the API or the database, the caller hands the
 * rows over, which is also why wins, ranks and streaks are not stored: the rules below can change and the numbers with
 * them, without the games being collected again.
 *
 * The rules:
 * - Games are taken in the order they were created, games created at the same moment in the order of their ids.
 * - The winner of a game is the player with the top score, a tie for the top is a win for everyone who tied, and
 *   nobody lost. Everyone else lost, however close it was.
 * - A game with one player is not a win or a loss, it counts for scores and Yahtzees only. It does not break a streak of
 *   wins or losses either, it is not part of them.
 * - A streak runs through the games a player took part in, a game they missed does not break it.
 * - A record is held by everyone who has it. A record for one game (a score, the Yahtzees in a game) is held with
 *   the first game the player did it in. A record nobody has is null, nobody wins a game that is not a win, and
 *   there is no record for Yahtzees if there has never been one.
 *
 * The Yahtzees are the Yahtzees scored, see ScoreRules::yahtzees().
 */
final class GameStats
{
    private const WON = 'won';

    private const LOST = 'lost';

    private const DATE_FORMAT = 'Y-m-d H:i:s';

    /**
     * @param list<array<string, mixed>> $rows One for each player in a game, `game_id`, `player_id`, `player_name`, `score`,
     *      `yahtzees` and `game_created_at` (a date or the text of one), the numbers can be numbers in text
     * @return array{
     *     games: int,
     *     players: list<array{
     *         player_id: string, player_name: string, games: int, wins: int, losses: int, average_score: float,
     *         highest_score: int, lowest_score: int, yahtzees: int, most_yahtzees_in_a_game: int,
     *         longest_win_streak: int, longest_loss_streak: int, longest_yahtzee_streak: int
     *     }>,
     *     records: array{
     *         highest_score: array{value: int, holders: list<array<string, mixed>>}|null,
     *         most_wins: array{value: int, holders: list<array<string, mixed>>}|null,
     *         most_consecutive_wins: array{value: int, holders: list<array<string, mixed>>}|null,
     *         lowest_score: array{value: int, holders: list<array<string, mixed>>}|null,
     *         most_consecutive_losses: array{value: int, holders: list<array<string, mixed>>}|null,
     *         most_yahtzees_in_a_game: array{value: int, holders: list<array<string, mixed>>}|null,
     *         most_consecutive_games_with_a_yahtzee: array{value: int, holders: list<array<string, mixed>>}|null
     *     }
     * }
     *
     * The `players` are the best first: most wins, then most games, then by name. A record's `holders` each have
     * `player_id`, `player_name` and three that are null when they don't apply: `game_id` (for a streak, its last
     * game), and `from` and `to`, when the game was created (for a streak, its first and last games, as `Y-m-d H:i:s` in UTC).
     */
    public static function summarise(array $rows): array
    {
        $games = self::games($rows);
        $players = self::players($games);

        $stats = [];
        foreach ($players as $player) {
            $stats[] = self::statsFor($player);
        }

        usort($stats, static fn (array $a, array $b): int => [$b['row']['wins'], $b['row']['games'], strtolower($a['row']['player_name']), $a['row']['player_id']]
            <=> [$a['row']['wins'], $a['row']['games'], strtolower($b['row']['player_name']), $b['row']['player_id']]);

        return [
            'games' => count($games),
            'players' => array_column($stats, 'row'),
            'records' => [
                'highest_score' => self::bestInAGame($games, $players, 'score', true),
                'most_wins' => self::bestPlayers($stats, static fn (array $stat): int => $stat['row']['wins'], null),
                'most_consecutive_wins' => self::bestPlayers($stats, static fn (array $stat): int => $stat['row']['longest_win_streak'], 'win'),
                'lowest_score' => self::bestInAGame($games, $players, 'score', false),
                'most_consecutive_losses' => self::bestPlayers($stats, static fn (array $stat): int => $stat['row']['longest_loss_streak'], 'loss'),
                'most_yahtzees_in_a_game' => self::positive(self::bestInAGame($games, $players, 'yahtzees', true)),
                'most_consecutive_games_with_a_yahtzee' => self::bestPlayers($stats, static fn (array $stat): int => $stat['row']['longest_yahtzee_streak'], 'yahtzee'),
            ],
        ];
    }

    /**
     * The games in the order they were played, each with its rows and, for a game with more than one player, who won
     * and who lost
     *
     * @param list<array<string, mixed>> $rows
     * @return list<array{id: string, created: string, rows: list<array{player_id: string, player_name: string, score: int, yahtzees: int, result: string|null}>}>
     */
    private static function games(array $rows): array
    {
        $games = [];

        foreach ($rows as $row) {
            $id = (string) $row['game_id'];

            $games[$id]['id'] = $id;
            $games[$id]['created'] ??= CarbonImmutable::parse($row['game_created_at'], 'UTC')->utc()->format(self::DATE_FORMAT);
            $games[$id]['rows'][] = [
                'player_id' => (string) $row['player_id'],
                'player_name' => (string) $row['player_name'],
                'score' => (int) $row['score'],
                'yahtzees' => (int) $row['yahtzees'],
                'result' => null,
            ];
        }

        $games = array_values($games);

        usort($games, static fn (array $a, array $b): int => [$a['created'], $a['id']] <=> [$b['created'], $b['id']]);

        foreach ($games as $position => $game) {
            if (count($game['rows']) < 2) {
                continue;
            }

            $top = max(array_column($game['rows'], 'score'));

            foreach ($game['rows'] as $row_position => $row) {
                $games[$position]['rows'][$row_position]['result'] = $row['score'] === $top ? self::WON : self::LOST;
            }
        }

        return $games;
    }

    /**
     * Each player's games, in the order they were played. A player has the name they had in their latest game.
     *
     * @param list<array<string, mixed>> $games
     * @return list<array{id: string, name: string, timeline: list<array<string, mixed>>}>
     */
    private static function players(array $games): array
    {
        $players = [];

        foreach ($games as $game) {
            foreach ($game['rows'] as $row) {
                $id = $row['player_id'];

                $players[$id]['id'] = $id;
                $players[$id]['name'] = $row['player_name'];
                $players[$id]['timeline'][] = [
                    'game_id' => $game['id'],
                    'created' => $game['created'],
                    'score' => $row['score'],
                    'yahtzees' => $row['yahtzees'],
                    'result' => $row['result'],
                ];
            }
        }

        return array_values($players);
    }

    /**
     * @param array{id: string, name: string, timeline: list<array<string, mixed>>} $player
     * @return array{row: array<string, mixed>, streaks: array<string, array<string, mixed>|null>}
     */
    private static function statsFor(array $player): array
    {
        $timeline = $player['timeline'];
        $scores = array_column($timeline, 'score');
        $yahtzees = array_column($timeline, 'yahtzees');
        $results = array_column($timeline, 'result');

        $streaks = [
            'win' => self::longestStreak($timeline, static fn (array $game): ?bool => match ($game['result']) {
                self::WON => true,
                self::LOST => false,
                default => null,
            }),
            'loss' => self::longestStreak($timeline, static fn (array $game): ?bool => match ($game['result']) {
                self::LOST => true,
                self::WON => false,
                default => null,
            }),
            'yahtzee' => self::longestStreak($timeline, static fn (array $game): bool => $game['yahtzees'] > 0),
        ];

        return [
            'row' => [
                'player_id' => $player['id'],
                'player_name' => $player['name'],
                'games' => count($timeline),
                'wins' => count(array_keys($results, self::WON, true)),
                'losses' => count(array_keys($results, self::LOST, true)),
                'average_score' => round(array_sum($scores) / count($scores), 1),
                'highest_score' => max($scores),
                'lowest_score' => min($scores),
                'yahtzees' => array_sum($yahtzees),
                'most_yahtzees_in_a_game' => max($yahtzees),
                'longest_win_streak' => $streaks['win']['length'] ?? 0,
                'longest_loss_streak' => $streaks['loss']['length'] ?? 0,
                'longest_yahtzee_streak' => $streaks['yahtzee']['length'] ?? 0,
            ],
            'streaks' => $streaks,
        ];
    }

    /**
     * The longest run of games a player's games make, the first one when there are two as long. null when there is none.
     *
     * @param list<array<string, mixed>> $timeline
     * @param callable(array<string, mixed>): ?bool $step true when the game is part of the streak, false when it ends
     *      it and null for a game that is neither, which is skipped
     * @return array{length: int, first_game_id: string, last_game_id: string, from: string, to: string}|null
     */
    private static function longestStreak(array $timeline, callable $step): ?array
    {
        $best = null;
        $current = null;

        foreach ($timeline as $game) {
            $counts = $step($game);

            if ($counts === null) {
                continue;
            }

            if ($counts === false) {
                $current = null;

                continue;
            }

            $current ??= ['length' => 0, 'first' => $game];
            $current['length']++;

            if ($best === null || $current['length'] > $best['length']) {
                $best = [
                    'length' => $current['length'],
                    'first_game_id' => $current['first']['game_id'],
                    'last_game_id' => $game['game_id'],
                    'from' => $current['first']['created'],
                    'to' => $game['created'],
                ];
            }
        }

        return $best;
    }

    /**
     * The record for the best, or the worst, a player has done in one game, held by everyone who did it with the
     * first game they did it in. null when there are no games.
     *
     * @param list<array<string, mixed>> $games
     * @param list<array<string, mixed>> $players
     * @return array{value: int, holders: list<array<string, mixed>>}|null
     */
    private static function bestInAGame(array $games, array $players, string $field, bool $highest): ?array
    {
        $values = [];
        foreach ($games as $game) {
            array_push($values, ...array_column($game['rows'], $field));
        }

        if ($values === []) {
            return null;
        }

        $value = $highest ? max($values) : min($values);

        $names = array_column($players, 'name', 'id');

        $holders = [];
        $seen = [];
        foreach ($games as $game) {
            foreach ($game['rows'] as $row) {
                if ($row[$field] !== $value || array_key_exists($row['player_id'], $seen)) {
                    continue;
                }

                $seen[$row['player_id']] = true;
                $holders[] = [
                    'player_id' => $row['player_id'],
                    'player_name' => $names[$row['player_id']],
                    'game_id' => $game['id'],
                    'from' => $game['created'],
                    'to' => $game['created'],
                ];
            }
        }

        return ['value' => $value, 'holders' => $holders];
    }

    /**
     * The record for the most of something a player has, held by every player who has that many. null when nobody has any.
     *
     * @param list<array{row: array<string, mixed>, streaks: array<string, array<string, mixed>|null>}> $stats The best player first
     * @param callable(array<string, mixed>): int $value
     * @param string|null $streak The streak that says which games it was, null for a total
     * @return array{value: int, holders: list<array<string, mixed>>}|null
     */
    private static function bestPlayers(array $stats, callable $value, ?string $streak): ?array
    {
        $values = array_map($value, $stats);

        $best = $values === [] ? 0 : max($values);
        if ($best <= 0) {
            return null;
        }

        $holders = [];
        foreach ($stats as $position => $stat) {
            if ($values[$position] !== $best) {
                continue;
            }

            $games = $streak !== null ? $stat['streaks'][$streak] : null;

            $holders[] = [
                'player_id' => $stat['row']['player_id'],
                'player_name' => $stat['row']['player_name'],
                'game_id' => $games['last_game_id'] ?? null,
                'from' => $games['from'] ?? null,
                'to' => $games['to'] ?? null,
            ];
        }

        return ['value' => $best, 'holders' => $holders];
    }

    /**
     * @param array{value: int, holders: list<array<string, mixed>>}|null $record
     * @return array{value: int, holders: list<array<string, mixed>>}|null
     */
    private static function positive(?array $record): ?array
    {
        return $record !== null && $record['value'] > 0 ? $record : null;
    }
}
