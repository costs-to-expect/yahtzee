<?php

declare(strict_types=1);

namespace Tests\Unit;

use App\Models\GameStat;
use App\Support\GameStatBuilder;
use App\Support\GameStatResult;
use App\Support\ScoreRules;
use Carbon\Carbon;
use Carbon\CarbonImmutable;
use Carbon\CarbonInterface;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;
use Tests\Support\BuildsApiFixtures;

class GameStatBuilderTest extends TestCase
{
    use BuildsApiFixtures;

    private const PLAYERS = ['p-ada' => 'Ada', 'p-ben' => 'Ben'];

    /**
     * Ada: upper 63 earns the bonus, lower 187 with a Yahtzee, 285 in all
     */
    private function adaSheet(): array
    {
        return $this->scoreSheet(
            ['ones' => 3, 'twos' => 6, 'threes' => 9, 'fours' => 12, 'fives' => 15, 'sixes' => 18],
            ['three_of_a_kind' => 20, 'four_of_a_kind' => 0, 'full_house' => 25, 'small_straight' => 30, 'large_straight' => 40, 'yahtzee' => 50, 'chance' => 22]
        );
    }

    /**
     * Ben: upper 42 and no bonus, lower 63 with a scratched Yahtzee, 105 in all
     */
    private function benSheet(): array
    {
        return $this->scoreSheet(
            ['ones' => 2, 'twos' => 4, 'threes' => 6, 'fours' => 8, 'fives' => 10, 'sixes' => 12],
            ['three_of_a_kind' => 18, 'four_of_a_kind' => 0, 'full_house' => 0, 'small_straight' => 30, 'large_straight' => 0, 'yahtzee' => 0, 'chance' => 15]
        );
    }

    /**
     * @param array<string, mixed> $override
     * @return array<string, mixed>
     */
    private function apiGame(array $override = []): array
    {
        return array_merge(['id' => 'g-1', 'created' => '2026-10-01 18:30:00', 'updated' => '2026-10-01 19:10:00'], $override);
    }

    /**
     * Builds Ada and Ben's finished game, with whatever the test needs to change
     *
     * @param array<string, mixed>|null $game
     * @param array<int, mixed>|null $players
     * @param array<int, mixed>|null $sheets
     */
    private function build(?array $game = null, ?array $players = null, ?array $sheets = null, ?CarbonInterface $completed_at = null): GameStatResult
    {
        return GameStatBuilder::build(
            'u-1',
            $game ?? $this->apiGame(),
            $players ?? $this->playerCollection(self::PLAYERS),
            $sheets ?? $this->scoreSheets(['p-ada' => $this->adaSheet(), 'p-ben' => $this->benSheet()]),
            $completed_at
        );
    }

    /**
     * Ada's and Ben's finished game, with Ada's sheet changed
     *
     * @param mixed $ada
     * @return array<int, mixed>
     */
    private function sheetsWithAda(mixed $ada): array
    {
        return $this->scoreSheets(['p-ada' => $ada, 'p-ben' => $this->benSheet()]);
    }

    /**
     * Carbon raises a PHP warning before it throws for a date it can't parse. Laravel turns warnings into exceptions,
     * so that is what the app sees, and a test that parses a bad date has to do the same or it is only a warning
     * in the report.
     */
    private function whereWarningsAreExceptions(callable $test): void
    {
        set_error_handler(static function (int $severity, string $message, string $file, int $line): never {
            throw new \ErrorException($message, 0, $severity, $file, $line);
        });

        try {
            $test();
        } finally {
            restore_error_handler();
        }
    }

    private function assertSkipped(string $reason, GameStatResult $result): void
    {
        self::assertFalse($result->isCounted());
        self::assertSame($reason, $result->reason);
        self::assertSame([], $result->rows, 'A skipped game never has rows');
    }

    public function test_a_finished_game_has_a_row_for_each_player(): void
    {
        $result = $this->build();

        self::assertTrue($result->isCounted());
        self::assertNull($result->reason);
        self::assertSame([
            [
                'user_id' => 'u-1',
                'game_id' => 'g-1',
                'player_id' => 'p-ada',
                'player_name' => 'Ada',
                'players_in_game' => 2,
                'score' => 285,
                'upper' => 63,
                'upper_bonus' => 35,
                'lower' => 187,
                'yahtzees' => 1,
                'sheet' => $this->adaSheet(),
                'game_created_at' => '2026-10-01 18:30:00',
                'game_completed_at' => '2026-10-01 19:10:00',
            ],
            [
                'user_id' => 'u-1',
                'game_id' => 'g-1',
                'player_id' => 'p-ben',
                'player_name' => 'Ben',
                'players_in_game' => 2,
                'score' => 105,
                'upper' => 42,
                'upper_bonus' => 0,
                'lower' => 63,
                'yahtzees' => 0,
                'sheet' => $this->benSheet(),
                'game_created_at' => '2026-10-01 18:30:00',
                'game_completed_at' => '2026-10-01 19:10:00',
            ],
        ], $result->rows);
    }

    public function test_the_rows_have_the_columns_of_the_stats_table(): void
    {
        $columns = (new GameStat())->getFillable();

        foreach ($this->build()->rows as $row) {
            self::assertEqualsCanonicalizing($columns, array_keys($row));
        }
    }

    public function test_the_rows_follow_the_order_of_the_players_not_the_order_of_the_sheets(): void
    {
        $result = $this->build(sheets: $this->scoreSheets(['p-ben' => $this->benSheet(), 'p-ada' => $this->adaSheet()]));

        self::assertSame(['p-ada', 'p-ben'], array_column($result->rows, 'player_id'));
    }

    public function test_the_yahtzee_and_its_bonuses_are_counted(): void
    {
        $ada = $this->scoreSheet(
            ['ones' => 3, 'twos' => 6, 'threes' => 9, 'fours' => 12, 'fives' => 15, 'sixes' => 18],
            ['three_of_a_kind' => 20, 'four_of_a_kind' => 0, 'full_house' => 25, 'small_straight' => 30, 'large_straight' => 40, 'yahtzee' => 50, 'chance' => 22, 'yahtzee_bonus_one' => 100, 'yahtzee_bonus_two' => 100]
        );

        $result = $this->build(sheets: $this->sheetsWithAda($ada));

        self::assertTrue($result->isCounted());
        self::assertSame(3, $result->rows[0]['yahtzees']);
        self::assertSame(0, $result->rows[1]['yahtzees']);
        self::assertSame(485, $result->rows[0]['score']);
    }

    public function test_the_sheet_is_kept_as_the_api_returned_it(): void
    {
        $sheet = $this->adaSheet();
        $sheet['something-the-app-may-add-later'] = ['kept' => true];

        $result = $this->build(sheets: $this->sheetsWithAda($sheet));

        self::assertTrue($result->isCounted());
        self::assertSame($sheet, $result->rows[0]['sheet']);
    }

    public function test_a_game_with_one_player_is_counted(): void
    {
        $result = $this->build(
            players: $this->playerCollection(['p-ada' => 'Ada']),
            sheets: $this->scoreSheets(['p-ada' => $this->adaSheet()])
        );

        self::assertTrue($result->isCounted());
        self::assertCount(1, $result->rows);
        self::assertSame(1, $result->rows[0]['players_in_game']);
    }

    public function test_a_scratched_combination_is_a_turn_played(): void
    {
        $sheet = $this->scoreSheet(
            ['ones' => 0, 'twos' => 0, 'threes' => 0, 'fours' => 0, 'fives' => 0, 'sixes' => 0],
            ['three_of_a_kind' => 0, 'four_of_a_kind' => 0, 'full_house' => 0, 'small_straight' => 0, 'large_straight' => 0, 'yahtzee' => 0, 'chance' => 5]
        );

        $result = $this->build(sheets: $this->sheetsWithAda($sheet));

        self::assertTrue($result->isCounted());
        self::assertSame(5, $result->rows[0]['score']);
    }

    public function test_the_sheet_of_someone_who_is_not_in_the_game_is_ignored(): void
    {
        $sheets = $this->scoreSheets(['p-ada' => $this->adaSheet(), 'p-ben' => $this->benSheet(), 'p-gone' => $this->scoreSheet(['ones' => 1])]);

        $result = $this->build(sheets: $sheets);

        self::assertTrue($result->isCounted());
        self::assertSame(['p-ada', 'p-ben'], array_column($result->rows, 'player_id'));
    }

    public function test_the_game_is_completed_when_the_api_says_it_was_updated(): void
    {
        self::assertSame('2026-10-01 19:10:00', $this->build()->rows[0]['game_completed_at']);
        self::assertSame('2026-10-01 19:10:00', $this->build($this->apiGame(['updated' => null, 'updated_at' => '2026-10-01 19:10:00']))->rows[0]['game_completed_at']);
    }

    public function test_the_game_is_not_completed_when_the_api_does_not_say_when(): void
    {
        $this->whereWarningsAreExceptions(function (): void {
            foreach ([null, '', '2026-13-45 99:99:99', 12345] as $updated) {
                $result = $this->build($this->apiGame(['updated' => $updated]));

                self::assertTrue($result->isCounted());
                self::assertNull($result->rows[0]['game_completed_at']);
            }
        });

        $game = $this->apiGame();
        unset($game['updated']);

        self::assertNull($this->build($game)->rows[0]['game_completed_at']);
    }

    public function test_a_game_that_has_only_just_been_completed_uses_the_time_it_is_given(): void
    {
        $completed = Carbon::parse('2026-10-04 21:15:30', 'UTC');

        $result = $this->build($this->apiGame(['updated' => null]), completed_at: $completed);

        self::assertSame('2026-10-04 21:15:30', $result->rows[0]['game_completed_at']);
        self::assertSame('2026-10-01 18:30:00', $result->rows[0]['game_created_at']);
        self::assertSame('UTC', $completed->getTimezone()->getName(), 'The time that is given is left alone');
    }

    public function test_the_times_are_stored_in_utc(): void
    {
        $result = $this->build(
            $this->apiGame(['created' => '2026-10-01T20:30:00+02:00', 'updated' => '2026-10-01T21:10:00+02:00']),
            completed_at: CarbonImmutable::parse('2026-10-04T23:15:30+02:00')
        );

        self::assertSame('2026-10-01 18:30:00', $result->rows[0]['game_created_at']);
        self::assertSame('2026-10-04 21:15:30', $result->rows[0]['game_completed_at']);

        $result = $this->build($this->apiGame(['created' => '2026-10-01T20:30:00+02:00', 'updated' => '2026-10-01T21:10:00+02:00']));

        self::assertSame('2026-10-01 19:10:00', $result->rows[0]['game_completed_at']);
    }

    public function test_the_reasons_are_the_names_the_backfill_counts_them_under(): void
    {
        self::assertSame('unreadable', GameStatBuilder::UNREADABLE);
        self::assertSame('unfinished', GameStatBuilder::UNFINISHED);
        self::assertSame('mismatch', GameStatBuilder::MISMATCH);
    }

    public function test_a_game_where_a_player_has_no_score_sheet_is_unfinished(): void
    {
        $this->assertSkipped(
            GameStatBuilder::UNFINISHED,
            $this->build(sheets: $this->scoreSheets(['p-ada' => $this->adaSheet()]))
        );
        $this->assertSkipped(GameStatBuilder::UNFINISHED, $this->build(sheets: []));
    }

    public function test_a_game_where_a_player_has_a_turn_left_is_unfinished_whoever_it_is(): void
    {
        $ada = $this->adaSheet();
        unset($ada['lower-section']['chance']);
        $ada['score'] = ScoreRules::totals($ada);

        $this->assertSkipped(GameStatBuilder::UNFINISHED, $this->build(sheets: $this->sheetsWithAda($ada)));

        $ben = $this->benSheet();
        unset($ben['upper-section']['ones']);
        $ben['score'] = ScoreRules::totals($ben);

        $this->assertSkipped(
            GameStatBuilder::UNFINISHED,
            $this->build(sheets: $this->scoreSheets(['p-ada' => $this->adaSheet(), 'p-ben' => $ben]))
        );
    }

    public function test_a_new_sheet_with_nothing_scored_is_unfinished(): void
    {
        $new = ['upper-section' => [], 'lower-section' => [], 'score' => ['upper' => 0, 'bonus' => 0, 'lower' => 0, 'total' => 0]];

        $this->assertSkipped(GameStatBuilder::UNFINISHED, $this->build(sheets: $this->sheetsWithAda($new)));
        $this->assertSkipped(GameStatBuilder::UNFINISHED, $this->build(sheets: $this->sheetsWithAda([])));
        $this->assertSkipped(GameStatBuilder::UNFINISHED, $this->build(sheets: $this->sheetsWithAda(['upper-section' => null, 'lower-section' => null])));
    }

    public function test_the_yahtzee_bonuses_do_not_make_up_for_a_turn_that_was_not_played(): void
    {
        $ada = $this->adaSheet();
        unset($ada['upper-section']['sixes']);
        $ada['lower-section'] += ['yahtzee_bonus_one' => 100, 'yahtzee_bonus_two' => 100, 'yahtzee_bonus_three' => 100];
        $ada['score'] = ScoreRules::totals($ada);

        $this->assertSkipped(GameStatBuilder::UNFINISHED, $this->build(sheets: $this->sheetsWithAda($ada)));
    }

    /**
     * @return array<string, array{array<string, mixed>}>
     */
    public static function unreadableGames(): array
    {
        return [
            'no id' => [['id' => null]],
            'an id that is not text' => [['id' => 17]],
            'an empty id' => [['id' => '']],
            'no created time' => [['created' => null]],
            'a created time that is not text' => [['created' => 20261001]],
            'a created time that is not a date' => [['created' => '2026-13-45 99:99:99']],
        ];
    }

    /**
     * @param array<string, mixed> $override
     */
    #[DataProvider('unreadableGames')]
    public function test_a_game_without_an_id_or_a_start_time_is_unreadable(array $override): void
    {
        $this->whereWarningsAreExceptions(function () use ($override): void {
            $this->assertSkipped(GameStatBuilder::UNREADABLE, $this->build($this->apiGame($override)));
        });
    }

    public function test_a_game_with_no_start_time_at_all_is_unreadable(): void
    {
        $game = $this->apiGame();
        unset($game['created']);

        $this->assertSkipped(GameStatBuilder::UNREADABLE, $this->build($game));
    }

    /**
     * @return array<string, array{array<int, mixed>}>
     */
    public static function unreadablePlayers(): array
    {
        return [
            'no players' => [[]],
            'a player that is not a list' => [['Ada']],
            'a player with no id' => [[['name' => 'Ada']]],
            'a player with an id that is not text' => [[['id' => 5, 'name' => 'Ada']]],
            'a player with an empty id' => [[['id' => '', 'name' => 'Ada']]],
            'a player with no name' => [[['id' => 'p-ada'], ['id' => 'p-ben', 'name' => 'Ben']]],
            'a player with an empty name' => [[['id' => 'p-ada', 'name' => ''], ['id' => 'p-ben', 'name' => 'Ben']]],
            'the same player twice' => [[['id' => 'p-ada', 'name' => 'Ada'], ['id' => 'p-ada', 'name' => 'Ada'], ['id' => 'p-ben', 'name' => 'Ben']]],
        ];
    }

    /**
     * @param array<int, mixed> $players
     */
    #[DataProvider('unreadablePlayers')]
    public function test_a_game_with_no_players_or_players_that_cannot_be_understood_is_unreadable(array $players): void
    {
        $this->assertSkipped(GameStatBuilder::UNREADABLE, $this->build(players: $players));
    }

    /**
     * @return array<string, array{array<int, mixed>}>
     */
    public static function unreadableSheetLists(): array
    {
        $sheet = ['upper-section' => [], 'lower-section' => []];

        return [
            'a sheet that is not a list' => [['nope']],
            'a sheet with no key' => [[['value' => $sheet]]],
            'a sheet with a key that is not text' => [[['key' => 4, 'value' => $sheet]]],
            'a sheet with an empty key' => [[['key' => '', 'value' => $sheet]]],
            'a sheet with no value' => [[['key' => 'p-ada']]],
            'two sheets for one player' => [[['key' => 'p-ada', 'value' => $sheet], ['key' => 'p-ada', 'value' => $sheet]]],
        ];
    }

    /**
     * @param array<int, mixed> $sheets
     */
    #[DataProvider('unreadableSheetLists')]
    public function test_a_list_of_sheets_that_cannot_be_understood_is_unreadable(array $sheets): void
    {
        $this->assertSkipped(GameStatBuilder::UNREADABLE, $this->build(sheets: $sheets));
    }

    /**
     * Each one changes Ada's finished sheet into one the app could never have written
     *
     * @return array<string, array{callable(array): mixed}>
     */
    public static function unreadableSheets(): array
    {
        return [
            'a sheet that is not an array' => [static fn (array $sheet): string => 'a sheet'],
            'a sheet with nothing in it' => [static fn (array $sheet): mixed => null],
            'a combination that does not exist' => [static function (array $sheet): array {
                $sheet['upper-section']['sevens'] = 7;

                return $sheet;
            }],
            'a lower combination in the upper section' => [static function (array $sheet): array {
                $sheet['upper-section']['chance'] = 20;

                return $sheet;
            }],
            'an upper combination in the lower section' => [static function (array $sheet): array {
                $sheet['lower-section']['ones'] = 3;

                return $sheet;
            }],
            'a score that is text' => [static function (array $sheet): array {
                $sheet['upper-section']['ones'] = '3';

                return $sheet;
            }],
            'a score that is a decimal' => [static function (array $sheet): array {
                $sheet['upper-section']['ones'] = 3.0;

                return $sheet;
            }],
            'a score that is empty' => [static function (array $sheet): array {
                $sheet['lower-section']['chance'] = null;

                return $sheet;
            }],
            'an upper section that is text' => [static function (array $sheet): array {
                $sheet['upper-section'] = 'ones';

                return $sheet;
            }],
        ];
    }

    /**
     * @param callable(array): mixed $break
     */
    #[DataProvider('unreadableSheets')]
    public function test_a_sheet_the_app_could_not_have_written_is_unreadable(callable $break): void
    {
        $this->assertSkipped(GameStatBuilder::UNREADABLE, $this->build(sheets: $this->sheetsWithAda($break($this->adaSheet()))));
    }

    /**
     * Each one changes Ada's finished sheet into one that breaks the rules of the game
     *
     * @return array<string, array{callable(array): array}>
     */
    public static function mismatchedSheets(): array
    {
        return [
            'a total that is not the total of the scores' => [static function (array $sheet): array {
                $sheet['score']['total']++;

                return $sheet;
            }],
            'an upper total that is wrong' => [static function (array $sheet): array {
                $sheet['score']['upper'] = 60;

                return $sheet;
            }],
            'a bonus that was earned and not stored' => [static function (array $sheet): array {
                $sheet['score']['bonus'] = 0;

                return $sheet;
            }],
            'a bonus that was not earned and is stored' => [static function (array $sheet): array {
                $sheet['upper-section']['sixes'] = 6;
                $sheet['score'] = ['upper' => 51, 'bonus' => 35, 'lower' => 187, 'total' => 273];

                return $sheet;
            }],
            'a lower total that is wrong' => [static function (array $sheet): array {
                $sheet['score']['lower'] = 0;

                return $sheet;
            }],
            'totals that are numbers in text' => [static function (array $sheet): array {
                $sheet['score'] = array_map('strval', $sheet['score']);

                return $sheet;
            }],
            'no totals' => [static function (array $sheet): array {
                unset($sheet['score']);

                return $sheet;
            }],
            'totals that are not an array' => [static function (array $sheet): array {
                $sheet['score'] = 'lots';

                return $sheet;
            }],
            'a score the combination can not produce' => [static function (array $sheet): array {
                // The totals are the totals of the scores, only the full house is impossible
                $sheet['lower-section']['full_house'] = 24;
                $sheet['score'] = ScoreRules::totals($sheet);

                return $sheet;
            }],
            'a score that is too many of a number' => [static function (array $sheet): array {
                $sheet['upper-section']['ones'] = 6;
                $sheet['score'] = ScoreRules::totals($sheet);

                return $sheet;
            }],
            'a chance that was scratched' => [static function (array $sheet): array {
                $sheet['lower-section']['chance'] = 0;
                $sheet['score'] = ScoreRules::totals($sheet);

                return $sheet;
            }],
            'a score that is negative' => [static function (array $sheet): array {
                $sheet['lower-section']['four_of_a_kind'] = -5;
                $sheet['score'] = ScoreRules::totals($sheet);

                return $sheet;
            }],
            'a yahtzee bonus that is not worth a hundred' => [static function (array $sheet): array {
                $sheet['lower-section']['yahtzee_bonus_one'] = 50;
                $sheet['score'] = ScoreRules::totals($sheet);

                return $sheet;
            }],
            'a yahtzee bonus with no yahtzee' => [static function (array $sheet): array {
                $sheet['lower-section']['yahtzee'] = 0;
                $sheet['lower-section']['yahtzee_bonus_one'] = 100;
                $sheet['score'] = ScoreRules::totals($sheet);

                return $sheet;
            }],
        ];
    }

    /**
     * @param callable(array): array $break
     */
    #[DataProvider('mismatchedSheets')]
    public function test_a_finished_sheet_that_breaks_the_rules_is_a_mismatch(callable $break): void
    {
        $this->assertSkipped(GameStatBuilder::MISMATCH, $this->build(sheets: $this->sheetsWithAda($break($this->adaSheet()))));
    }

    public function test_one_bad_player_skips_the_whole_game(): void
    {
        $ben = $this->benSheet();
        $ben['score']['total']++;

        $result = $this->build(sheets: $this->scoreSheets(['p-ada' => $this->adaSheet(), 'p-ben' => $ben]));

        $this->assertSkipped(GameStatBuilder::MISMATCH, $result);
    }

    public function test_a_sheet_that_cannot_be_read_is_unreadable_before_it_is_unfinished(): void
    {
        $bad = $this->adaSheet();
        $bad['upper-section']['sevens'] = 7;

        // Ben has a turn left, Ada's sheet is the worse problem
        $ben = $this->benSheet();
        unset($ben['lower-section']['chance']);

        $this->assertSkipped(
            GameStatBuilder::UNREADABLE,
            $this->build(sheets: $this->scoreSheets(['p-ada' => $bad, 'p-ben' => $ben]))
        );
    }

    public function test_an_unfinished_sheet_is_not_checked_against_the_rules(): void
    {
        // Half a game with totals that are wrong is unfinished, there is nothing to check yet
        $ada = $this->adaSheet();
        unset($ada['lower-section']['chance']);
        $ada['score']['total'] = 999;

        $this->assertSkipped(GameStatBuilder::UNFINISHED, $this->build(sheets: $this->sheetsWithAda($ada)));
    }

    public function test_a_game_is_unfinished_before_it_is_a_mismatch(): void
    {
        $ben = $this->benSheet();
        unset($ben['lower-section']['chance']);

        $ada = $this->adaSheet();
        $ada['score']['total']++;

        $this->assertSkipped(
            GameStatBuilder::UNFINISHED,
            $this->build(sheets: $this->scoreSheets(['p-ada' => $ada, 'p-ben' => $ben]))
        );
    }
}
