<?php

declare(strict_types=1);

namespace App\Support;

use Carbon\CarbonImmutable;
use Carbon\CarbonInterface;

/**
 * Turns a finished game and its score sheets into the rows of the game_stat table, one for each player, or says why
 * the game can't be counted. Nothing is read from the API and nothing is written to the database, the caller hands over
 * what it has and stores what comes back, so the game that has just been completed and the old games the backfill job
 * collects follow exactly the same rules.
 *
 * A game is counted only when every player in it has played all thirteen turns, and only when its sheets can be
 * trusted: until 1.13.0 the app stored whatever score it was sent, so an old sheet can hold a score its combination can't
 * produce. One player that fails is enough to skip the whole game, there are never rows for some of its players.
 *
 * The reasons a game is skipped, checked in this order:
 * - unreadable: the data isn't what the app writes, there is no id or start time, no players, a sheet with a
 *   combination that doesn't exist or a score that isn't a whole number
 * - unfinished: a player has no score sheet or has not scored every combination (a scratched one, 0, is scored)
 * - mismatch: a finished sheet that breaks the rules, a score its combination can't produce, a Yahtzee bonus with no
 *   Yahtzee, or stored totals that aren't the totals of its scores
 *
 * It does not decide who won, a tie, a streak or whether a solo game counts, those are worked out from the rows when the
 * stats are read. The caller only offers games that are complete.
 */
final class GameStatBuilder
{
    public const UNREADABLE = 'unreadable';

    public const UNFINISHED = 'unfinished';

    public const MISMATCH = 'mismatch';

    private const DATE_FORMAT = 'Y-m-d H:i:s';

    private const SECTIONS = [
        ScoreRules::UPPER_SECTION => 'upper-section',
        ScoreRules::LOWER_SECTION => 'lower-section',
    ];

    /**
     * @param string $user_id The Costs to Expect user the game belongs to
     * @param array<string, mixed> $game As the API returns a game, it needs an id and when it was created, `updated`
     *      is when it was completed
     * @param array<int, mixed> $players The players in the game, each one `['id' => ..., 'name' => ...]`
     * @param array<int, mixed> $sheets The game's score sheets as the API returns them, `['key' => player id, 'value' => sheet]`,
     *      a sheet of someone who is not in the game is ignored
     * @param CarbonInterface|null $completed_at When the game was completed, for a game that has only just been
     *      completed and whose `updated` doesn't say so yet
     */
    public static function build(
        string $user_id,
        array $game,
        array $players,
        array $sheets,
        ?CarbonInterface $completed_at = null
    ): GameStatResult
    {
        $game_id = $game['id'] ?? null;
        $created = GameBoard::startedAt($game);
        $roster = self::roster($players);
        $found = self::sheetsByPlayer($sheets);

        if (is_string($game_id) === false || $game_id === '' || $created === null || $roster === null || $found === null) {
            return GameStatResult::skipped(self::UNREADABLE);
        }

        $unfinished = false;
        foreach (array_keys($roster) as $player_id) {
            if (array_key_exists($player_id, $found) === false) {
                $unfinished = true;
                continue;
            }

            if (self::isReadable($found[$player_id]) === false) {
                return GameStatResult::skipped(self::UNREADABLE);
            }

            if (ScoreRules::isFinished($found[$player_id]) === false) {
                $unfinished = true;
            }
        }

        if ($unfinished === true) {
            return GameStatResult::skipped(self::UNFINISHED);
        }

        foreach (array_keys($roster) as $player_id) {
            if (self::followsTheRules($found[$player_id]) === false) {
                return GameStatResult::skipped(self::MISMATCH);
            }
        }

        $completed = $completed_at !== null ? CarbonImmutable::instance($completed_at) : self::completedAt($game);

        $rows = [];
        foreach ($roster as $player_id => $name) {
            $sheet = $found[$player_id];
            $totals = ScoreRules::totals($sheet);

            $rows[] = [
                'user_id' => $user_id,
                'game_id' => $game_id,
                'player_id' => $player_id,
                'player_name' => $name,
                'players_in_game' => count($roster),
                'score' => $totals['total'],
                'upper' => $totals['upper'],
                'upper_bonus' => $totals['bonus'],
                'lower' => $totals['lower'],
                'yahtzees' => ScoreRules::yahtzees($sheet),
                'sheet' => $sheet,
                'game_created_at' => $created->utc()->format(self::DATE_FORMAT),
                'game_completed_at' => $completed?->utc()->format(self::DATE_FORMAT),
            ];
        }

        return GameStatResult::counted($rows);
    }

    /**
     * The players in the order they are listed, id => name, null when there are none or one can't be understood
     *
     * @param array<int, mixed> $players
     * @return array<string, string>|null
     */
    private static function roster(array $players): ?array
    {
        $roster = [];

        foreach ($players as $player) {
            if (
                is_array($player) === false
                || is_string($player['id'] ?? null) === false || $player['id'] === ''
                || is_string($player['name'] ?? null) === false || $player['name'] === ''
                || array_key_exists($player['id'], $roster)
            ) {
                return null;
            }

            $roster[$player['id']] = $player['name'];
        }

        return $roster === [] ? null : $roster;
    }

    /**
     * The sheets by the id of the player they belong to, null when a sheet can't be understood or a player has two
     *
     * @param array<int, mixed> $sheets
     * @return array<string, mixed>|null
     */
    private static function sheetsByPlayer(array $sheets): ?array
    {
        $found = [];

        foreach ($sheets as $sheet) {
            if (
                is_array($sheet) === false
                || is_string($sheet['key'] ?? null) === false || $sheet['key'] === ''
                || array_key_exists('value', $sheet) === false
                || array_key_exists($sheet['key'], $found)
            ) {
                return null;
            }

            $found[$sheet['key']] = $sheet['value'];
        }

        return $found;
    }

    /**
     * Whether the sheet is made of combinations that exist and whole number scores, however many it has played
     */
    private static function isReadable(mixed $sheet): bool
    {
        if (is_array($sheet) === false) {
            return false;
        }

        foreach (self::SECTIONS as $section => $key) {
            $scores = $sheet[$key] ?? [];

            if (is_array($scores) === false) {
                return false;
            }

            foreach ($scores as $combination => $points) {
                if (
                    is_string($combination) === false
                    || ScoreRules::isCombination($section, $combination) === false
                    || is_int($points) === false
                ) {
                    return false;
                }
            }
        }

        return true;
    }

    /**
     * Whether every score is one its combination can produce, a Yahtzee bonus has a Yahtzee behind it and the totals
     * stored on the sheet are the totals of its scores. A sheet with no stored totals can't be checked.
     *
     * @param array<string, mixed> $sheet A readable sheet
     */
    private static function followsTheRules(array $sheet): bool
    {
        foreach (self::SECTIONS as $section => $key) {
            foreach ($sheet[$key] ?? [] as $combination => $points) {
                if (in_array($points, ScoreRules::allowed($section, $combination), true) === false) {
                    return false;
                }
            }
        }

        $has_bonus = array_intersect(ScoreRules::BONUSES, array_keys($sheet['lower-section'] ?? [])) !== [];
        if ($has_bonus && ($sheet['lower-section']['yahtzee'] ?? null) !== ScoreRules::FIXED['yahtzee']) {
            return false;
        }

        $stored = $sheet['score'] ?? null;
        if (is_array($stored) === false) {
            return false;
        }

        foreach (ScoreRules::totals($sheet) as $total => $points) {
            if (($stored[$total] ?? null) !== $points) {
                return false;
            }
        }

        return true;
    }

    /**
     * @param array<string, mixed> $game
     */
    private static function completedAt(array $game): ?CarbonImmutable
    {
        foreach (['updated', 'updated_at'] as $key) {
            if (isset($game[$key]) && is_string($game[$key]) && $game[$key] !== '') {
                try {
                    return CarbonImmutable::parse($game[$key]);
                } catch (\Throwable) {
                    return null;
                }
            }
        }

        return null;
    }
}
