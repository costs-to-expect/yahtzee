<?php

declare(strict_types=1);

namespace Tests\Unit;

use App\Support\ScoreRules;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;

class ScoreRulesTest extends TestCase
{
    private function sheet(array $upper = [], array $lower = []): array
    {
        return ['upper-section' => $upper, 'lower-section' => $lower, 'score' => ScoreRules::totals(['upper-section' => $upper, 'lower-section' => $lower])];
    }

    public function test_the_totals_add_the_upper_bonus_from_sixty_three(): void
    {
        self::assertSame(['upper' => 62, 'bonus' => 0, 'lower' => 25, 'total' => 87], ScoreRules::totals($this->sheet(
            ['ones' => 2, 'twos' => 6, 'threes' => 9, 'fours' => 12, 'fives' => 15, 'sixes' => 18],
            ['full_house' => 25]
        )));
        self::assertSame(['upper' => 63, 'bonus' => 35, 'lower' => 0, 'total' => 98], ScoreRules::totals($this->sheet(
            ['ones' => 3, 'twos' => 6, 'threes' => 9, 'fours' => 12, 'fives' => 15, 'sixes' => 18]
        )));
    }

    public function test_the_totals_of_an_empty_sheet_are_zero(): void
    {
        self::assertSame(['upper' => 0, 'bonus' => 0, 'lower' => 0, 'total' => 0], ScoreRules::totals([]));
    }

    public function test_a_yahtzee_bonus_is_points_but_not_a_turn(): void
    {
        $sheet = $this->sheet(['ones' => 3], ['yahtzee' => 50, 'yahtzee_bonus_one' => 100, 'yahtzee_bonus_two' => 100]);

        self::assertSame(2, ScoreRules::turns($sheet));
        self::assertSame(['upper' => 3, 'bonus' => 0, 'lower' => 250, 'total' => 253], ScoreRules::totals($sheet));
        self::assertSame(0, ScoreRules::turns([]));
    }

    /**
     * @return array<string, array{string, string, mixed, bool}>
     */
    public static function scores(): array
    {
        return [
            'no ones' => ['upper', 'ones', 0, true],
            'five sixes' => ['upper', 'sixes', 30, true],
            'six sixes' => ['upper', 'sixes', 36, false],
            'a score that is not a multiple' => ['upper', 'threes', 10, false],
            'an unknown dice' => ['upper', 'sevens', 7, false],
            'a string of digits' => ['upper', 'fours', '12', true],
            'a decimal' => ['upper', 'fours', 12.0, false],
            'text' => ['upper', 'fours', 'twelve', false],
            'null' => ['upper', 'fours', null, false],
            'negative' => ['upper', 'fours', -4, false],
            'three of a kind' => ['lower', 'three_of_a_kind', 22, true],
            'three of a kind scratched' => ['lower', 'three_of_a_kind', 0, true],
            'three of a kind too low' => ['lower', 'three_of_a_kind', 4, false],
            'four of a kind too high' => ['lower', 'four_of_a_kind', 31, false],
            'chance' => ['lower', 'chance', 5, true],
            'chance at the top' => ['lower', 'chance', 30, true],
            'chance cannot be scratched' => ['lower', 'chance', 0, false],
            'full house' => ['lower', 'full_house', 25, true],
            'full house scratched' => ['lower', 'full_house', 0, true],
            'full house for the wrong points' => ['lower', 'full_house', 24, false],
            'small straight' => ['lower', 'small_straight', 30, true],
            'large straight' => ['lower', 'large_straight', 40, true],
            'large straight for a small straight' => ['lower', 'large_straight', 30, false],
            'yahtzee' => ['lower', 'yahtzee', 50, true],
            'yahtzee scratched' => ['lower', 'yahtzee', 0, true],
            'the upper section is not a lower combination' => ['lower', 'ones', 3, false],
            'the lower section is not an upper combination' => ['upper', 'chance', 20, false],
            'an unknown section' => ['middle', 'ones', 3, false],
        ];
    }

    #[DataProvider('scores')]
    public function test_a_score_is_only_allowed_when_the_combination_can_produce_it(string $section, string $combination, mixed $score, bool $allowed): void
    {
        $problem = ScoreRules::problem($this->sheet(), $section, $combination, $score);

        self::assertSame($allowed, $problem === null, (string) $problem);
    }

    public function test_a_yahtzee_bonus_needs_a_yahtzee_and_a_turn_left_to_play(): void
    {
        self::assertSame('Score a Yahtzee before a Yahtzee bonus', ScoreRules::problem($this->sheet(), 'lower', 'yahtzee_bonus_one', 100));
        self::assertSame('Score a Yahtzee before a Yahtzee bonus', ScoreRules::problem($this->sheet([], ['yahtzee' => 0]), 'lower', 'yahtzee_bonus_one', 100));
        self::assertNull(ScoreRules::problem($this->sheet([], ['yahtzee' => 50]), 'lower', 'yahtzee_bonus_two', 100));
        self::assertNotNull(ScoreRules::problem($this->sheet([], ['yahtzee' => 50]), 'lower', 'yahtzee_bonus_two', 50));

        $finished = $this->sheet(
            ['ones' => 3, 'twos' => 6, 'threes' => 9, 'fours' => 12, 'fives' => 15, 'sixes' => 18],
            ['three_of_a_kind' => 20, 'four_of_a_kind' => 0, 'full_house' => 25, 'small_straight' => 30, 'large_straight' => 40, 'yahtzee' => 50, 'chance' => 22]
        );
        self::assertSame(13, ScoreRules::turns($finished));
        self::assertStringContainsString('no Yahtzee bonus', (string) ScoreRules::problem($finished, 'lower', 'yahtzee_bonus_one', 100));
    }

    public function test_the_yahtzee_cannot_be_changed_away_from_fifty_while_there_are_bonuses(): void
    {
        $sheet = $this->sheet([], ['yahtzee' => 50, 'yahtzee_bonus_one' => 100]);

        self::assertStringContainsString('Remove the Yahtzee bonuses', (string) ScoreRules::problem($sheet, 'lower', 'yahtzee', 0));
        self::assertStringContainsString('Remove the Yahtzee bonuses', (string) ScoreRules::clearProblem($sheet, 'lower', 'yahtzee'));
        self::assertNull(ScoreRules::clearProblem($sheet, 'lower', 'yahtzee_bonus_one'));
    }

    public function test_only_a_scored_combination_can_be_cleared(): void
    {
        $sheet = $this->sheet(['ones' => 0]);

        self::assertNull(ScoreRules::clearProblem($sheet, 'upper', 'ones'));
        self::assertSame('That combination has not been scored', ScoreRules::clearProblem($sheet, 'upper', 'twos'));
        self::assertSame('That is not a combination on the score sheet', ScoreRules::clearProblem($sheet, 'upper', 'chance'));
    }

    public function test_adding_and_removing_a_score_works_the_totals_out_again(): void
    {
        $sheet = ScoreRules::with($this->sheet(['ones' => 3]), 'lower', 'chance', 20);
        self::assertSame(['upper' => 3, 'bonus' => 0, 'lower' => 20, 'total' => 23], $sheet['score']);

        $sheet = ScoreRules::with($sheet, 'lower', 'chance', 11);
        self::assertSame(['chance' => 11], $sheet['lower-section']);
        self::assertSame(14, $sheet['score']['total']);

        $sheet = ScoreRules::without($sheet, 'upper', 'ones');
        self::assertSame([], $sheet['upper-section']);
        self::assertSame(11, $sheet['score']['total']);
    }

    public function test_every_combination_has_a_readable_label(): void
    {
        self::assertSame('Three of a kind', ScoreRules::label('three_of_a_kind'));
        self::assertSame('Ones', ScoreRules::label('ones'));
    }
}
