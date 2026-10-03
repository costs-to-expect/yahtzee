<?php

declare(strict_types=1);

namespace App\Support;

/**
 * The rules of a Yahtzee score sheet, in one place: which combinations there are, what each one can score, the upper
 * bonus and how the totals are worked out.
 *
 * A score sheet is the array the API stores for a player, `upper-section` and `lower-section` map a combination to the
 * points scored (a scratched combination is 0) and `score` holds the totals.
 */
final class ScoreRules
{
    /** @var array<string, int> The upper combinations and the points one die of that number is worth */
    public const UPPER = ['ones' => 1, 'twos' => 2, 'threes' => 3, 'fours' => 4, 'fives' => 5, 'sixes' => 6];

    /** @var array<string, int> The lower combinations that always score the same */
    public const FIXED = ['full_house' => 25, 'small_straight' => 30, 'large_straight' => 40, 'yahtzee' => 50];

    /** @var list<string> The lower combinations that score the total of the five dice (Chance cannot be scratched) */
    public const SUMS = ['three_of_a_kind', 'four_of_a_kind', 'chance'];

    /** @var list<string> One slot for each extra Yahtzee, worth 100 and not a turn */
    public const BONUSES = ['yahtzee_bonus_one', 'yahtzee_bonus_two', 'yahtzee_bonus_three'];

    public const TURNS = 13;

    public const UPPER_BONUS_FROM = 63;

    public const UPPER_BONUS = 35;

    public const YAHTZEE_BONUS = 100;

    public const MIN_SUM = 5;

    public const MAX_SUM = 30;

    public const UPPER_SECTION = 'upper';

    public const LOWER_SECTION = 'lower';

    /**
     * The turns a player has played, the Yahtzee bonuses are not a turn
     */
    public static function turns(array $sheet): int
    {
        $turns = isset($sheet['upper-section']) ? count($sheet['upper-section']) : 0;

        foreach ($sheet['lower-section'] ?? [] as $combination => $score) {
            if (in_array($combination, self::BONUSES, true) === false) {
                $turns++;
            }
        }

        return $turns;
    }

    /**
     * @return array{upper: int, bonus: int, lower: int, total: int}
     */
    public static function totals(array $sheet): array
    {
        $upper = (int) array_sum($sheet['upper-section'] ?? []);
        $bonus = $upper >= self::UPPER_BONUS_FROM ? self::UPPER_BONUS : 0;
        $lower = (int) array_sum($sheet['lower-section'] ?? []);

        return ['upper' => $upper, 'bonus' => $bonus, 'lower' => $lower, 'total' => $upper + $bonus + $lower];
    }

    public static function isCombination(string $section, string $combination): bool
    {
        return match ($section) {
            self::UPPER_SECTION => array_key_exists($combination, self::UPPER),
            self::LOWER_SECTION => array_key_exists($combination, self::FIXED)
                || in_array($combination, self::SUMS, true)
                || in_array($combination, self::BONUSES, true),
            default => false,
        };
    }

    /**
     * The scores a combination can take
     *
     * @return list<int>
     */
    public static function allowed(string $section, string $combination): array
    {
        if ($section === self::UPPER_SECTION) {
            return array_map(static fn (int $count): int => $count * self::UPPER[$combination], range(0, 5));
        }

        if (array_key_exists($combination, self::FIXED)) {
            return [0, self::FIXED[$combination]];
        }

        if (in_array($combination, self::BONUSES, true)) {
            return [self::YAHTZEE_BONUS];
        }

        // The total of the dice, three and four of a kind can be scratched, Chance never
        $allowed = range(self::MIN_SUM, self::MAX_SUM);

        return $combination === 'chance' ? $allowed : [0, ...$allowed];
    }

    /**
     * Why a score cannot go on the sheet, null when it can
     *
     * @param mixed $score as sent by the browser, the JSON number or a string of digits
     */
    public static function problem(array $sheet, string $section, string $combination, mixed $score): ?string
    {
        if (self::isCombination($section, $combination) === false) {
            return 'That is not a combination on the score sheet';
        }

        $points = self::integer($score);
        if ($points === null) {
            return 'The score has to be a whole number';
        }

        if (in_array($points, self::allowed($section, $combination), true) === false) {
            return 'That score is not possible for ' . self::label($combination);
        }

        if (in_array($combination, self::BONUSES, true)) {
            if (($sheet['lower-section']['yahtzee'] ?? null) !== self::FIXED['yahtzee']) {
                return 'Score a Yahtzee before a Yahtzee bonus';
            }

            if (self::turns($sheet) >= self::TURNS) {
                return 'All the turns have been played, there is no Yahtzee bonus left to score';
            }
        }

        if ($section === self::LOWER_SECTION && $combination === 'yahtzee' && $points !== self::FIXED['yahtzee']
            && self::hasBonuses($sheet)) {
            return 'Remove the Yahtzee bonuses before changing the Yahtzee';
        }

        return null;
    }

    /**
     * Why a score cannot be taken off the sheet, null when it can
     */
    public static function clearProblem(array $sheet, string $section, string $combination): ?string
    {
        if (self::isCombination($section, $combination) === false) {
            return 'That is not a combination on the score sheet';
        }

        if (self::value($sheet, $section, $combination) === null) {
            return 'That combination has not been scored';
        }

        if ($section === self::LOWER_SECTION && $combination === 'yahtzee' && self::hasBonuses($sheet)) {
            return 'Remove the Yahtzee bonuses before clearing the Yahtzee';
        }

        return null;
    }

    public static function value(array $sheet, string $section, string $combination): ?int
    {
        return $sheet[$section . '-section'][$combination] ?? null;
    }

    /**
     * The sheet with a score added, or replaced, and the totals worked out again
     */
    public static function with(array $sheet, string $section, string $combination, int $points): array
    {
        $sheet['upper-section'] ??= [];
        $sheet['lower-section'] ??= [];
        $sheet[$section . '-section'][$combination] = $points;

        $sheet['score'] = self::totals($sheet);

        return $sheet;
    }

    /**
     * The sheet with a score taken off and the totals worked out again
     */
    public static function without(array $sheet, string $section, string $combination): array
    {
        $sheet['upper-section'] ??= [];
        $sheet['lower-section'] ??= [];
        unset($sheet[$section . '-section'][$combination]);

        $sheet['score'] = self::totals($sheet);

        return $sheet;
    }

    public static function integer(mixed $score): ?int
    {
        if (is_int($score)) {
            return $score;
        }

        if (is_string($score) && preg_match('/^\d{1,3}$/', $score) === 1) {
            return (int) $score;
        }

        return null;
    }

    /**
     * Three of a kind, the readable name of a combination
     */
    public static function label(string $combination): string
    {
        return ucfirst(str_replace('_', ' ', $combination));
    }

    private static function hasBonuses(array $sheet): bool
    {
        foreach (self::BONUSES as $bonus) {
            if (array_key_exists($bonus, $sheet['lower-section'] ?? [])) {
                return true;
            }
        }

        return false;
    }
}
