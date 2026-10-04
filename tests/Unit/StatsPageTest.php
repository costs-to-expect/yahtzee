<?php

declare(strict_types=1);

namespace Tests\Unit;

use App\Models\StatsBackfill;
use App\Support\GameStats;
use App\Support\StatsPage;
use Carbon\CarbonImmutable;
use PHPUnit\Framework\TestCase;

class StatsPageTest extends TestCase
{
    private function now(): CarbonImmutable
    {
        return CarbonImmutable::parse('2026-10-05 12:00:00', 'UTC');
    }

    /**
     * @param array<string, mixed> $override
     * @return array<string, mixed>
     */
    private function holder(string $name, array $override = []): array
    {
        return array_merge([
            'player_id' => 'p-' . strtolower($name),
            'player_name' => $name,
            'game_id' => 'g-1',
            'from' => '2026-09-12 18:00:00',
            'to' => '2026-09-12 18:00:00',
        ], $override);
    }

    /**
     * @param array<string, array{value: int, holders: list<array<string, mixed>>}|null> $records
     * @return array<string, array<string, mixed>> the cards by the record they are for
     */
    private function cards(array $records): array
    {
        $cards = [];

        foreach (StatsPage::records($records, $this->now()) as $card) {
            $cards[$card['key']] = $card;
        }

        return $cards;
    }

    public function test_there_is_a_card_for_each_record_in_the_order_they_are_asked_for(): void
    {
        $cards = StatsPage::records(GameStats::summarise([])['records'], $this->now());

        self::assertSame(
            ['highest_score', 'most_wins', 'most_consecutive_wins', 'lowest_score', 'most_consecutive_losses', 'most_yahtzees_in_a_game', 'most_consecutive_games_with_a_yahtzee'],
            array_column($cards, 'key')
        );
        self::assertSame(
            ['Highest score', 'Most wins', 'Most consecutive wins', 'Lowest score', 'Most consecutive losses', 'Most Yahtzees in a game', 'Most consecutive games with a Yahtzee'],
            array_column($cards, 'title')
        );
    }

    public function test_a_record_nobody_holds_says_so_and_has_no_value_or_holders(): void
    {
        foreach (StatsPage::records(GameStats::summarise([])['records'], $this->now()) as $card) {
            self::assertNull($card['value'], $card['key']);
            self::assertNull($card['unit'], $card['key']);
            self::assertSame([], $card['holders'], $card['key']);
            self::assertSame(0, $card['more'], $card['key']);
            self::assertNotSame('', $card['none'], $card['key']);
        }
    }

    public function test_the_unit_is_singular_for_one(): void
    {
        $cards = $this->cards([
            'most_wins' => ['value' => 1, 'holders' => [$this->holder('Ada', ['game_id' => null, 'from' => null, 'to' => null])]],
            'most_consecutive_wins' => ['value' => 1, 'holders' => [$this->holder('Ada')]],
            'most_consecutive_losses' => ['value' => 1, 'holders' => [$this->holder('Ada')]],
            'most_yahtzees_in_a_game' => ['value' => 1, 'holders' => [$this->holder('Ada')]],
            'most_consecutive_games_with_a_yahtzee' => ['value' => 1, 'holders' => [$this->holder('Ada')]],
            'highest_score' => ['value' => 1, 'holders' => [$this->holder('Ada')]],
        ]);

        self::assertSame(
            ['win', 'win in a row', 'loss in a row', 'Yahtzee', 'game in a row', 'point'],
            [$cards['most_wins']['unit'], $cards['most_consecutive_wins']['unit'], $cards['most_consecutive_losses']['unit'], $cards['most_yahtzees_in_a_game']['unit'], $cards['most_consecutive_games_with_a_yahtzee']['unit'], $cards['highest_score']['unit']]
        );
    }

    public function test_the_unit_is_plural_for_anything_else(): void
    {
        $cards = $this->cards([
            'most_wins' => ['value' => 12, 'holders' => [$this->holder('Ada', ['game_id' => null, 'from' => null, 'to' => null])]],
            'most_consecutive_wins' => ['value' => 6, 'holders' => [$this->holder('Ada')]],
            'most_consecutive_losses' => ['value' => 4, 'holders' => [$this->holder('Ada')]],
            'most_yahtzees_in_a_game' => ['value' => 2, 'holders' => [$this->holder('Ada')]],
            'most_consecutive_games_with_a_yahtzee' => ['value' => 3, 'holders' => [$this->holder('Ada')]],
            'lowest_score' => ['value' => 95, 'holders' => [$this->holder('Ada')]],
        ]);

        self::assertSame(
            ['wins', 'wins in a row', 'losses in a row', 'Yahtzees', 'games in a row', 'points'],
            [$cards['most_wins']['unit'], $cards['most_consecutive_wins']['unit'], $cards['most_consecutive_losses']['unit'], $cards['most_yahtzees_in_a_game']['unit'], $cards['most_consecutive_games_with_a_yahtzee']['unit'], $cards['lowest_score']['unit']]
        );
        self::assertSame(12, $cards['most_wins']['value']);
    }

    public function test_a_record_for_one_game_says_the_day_and_which_game_to_open(): void
    {
        $cards = $this->cards(['highest_score' => ['value' => 310, 'holders' => [$this->holder('Ada', ['game_id' => 'g-3', 'from' => '2026-09-28 18:00:00', 'to' => '2026-09-28 18:00:00'])]]]);

        self::assertSame(
            [['player_id' => 'p-ada', 'name' => 'Ada', 'detail' => '28 Sep', 'game_id' => 'g-3']],
            $cards['highest_score']['holders']
        );
    }

    public function test_a_game_from_the_last_few_days_says_the_day_of_the_week_or_today_or_yesterday(): void
    {
        $detail = fn (string $at): ?string => $this->cards(['highest_score' => ['value' => 300, 'holders' => [$this->holder('Ada', ['from' => $at, 'to' => $at])]]])['highest_score']['holders'][0]['detail'];

        self::assertSame('Today', $detail('2026-10-05 09:00:00'));
        self::assertSame('Yesterday', $detail('2026-10-04 20:00:00'));
        self::assertSame('Thursday', $detail('2026-10-01 20:00:00'));
        self::assertSame('12 Sep 2025', $detail('2025-09-12 20:00:00'));
    }

    public function test_a_streak_says_its_first_and_last_days_and_opens_its_last_game(): void
    {
        $cards = $this->cards(['most_consecutive_wins' => ['value' => 3, 'holders' => [$this->holder('Ada', ['game_id' => 'g-4', 'from' => '2026-09-03 18:00:00', 'to' => '2026-09-09 18:00:00'])]]]);

        self::assertSame('3 Sep to 9 Sep', $cards['most_consecutive_wins']['holders'][0]['detail']);
        self::assertSame('g-4', $cards['most_consecutive_wins']['holders'][0]['game_id']);
    }

    public function test_a_streak_that_began_and_ended_on_one_day_says_the_day_once(): void
    {
        $cards = $this->cards(['most_consecutive_wins' => ['value' => 3, 'holders' => [$this->holder('Ada', ['from' => '2026-09-03 12:00:00', 'to' => '2026-09-03 21:00:00'])]]]);

        self::assertSame('3 Sep', $cards['most_consecutive_wins']['holders'][0]['detail']);
    }

    public function test_a_total_has_no_day_and_no_game_to_open(): void
    {
        $cards = $this->cards(['most_wins' => ['value' => 12, 'holders' => [$this->holder('Ada', ['game_id' => null, 'from' => null, 'to' => null])]]]);

        self::assertSame(
            [['player_id' => 'p-ada', 'name' => 'Ada', 'detail' => null, 'game_id' => null]],
            $cards['most_wins']['holders']
        );
    }

    public function test_the_first_three_holders_are_shown_and_the_rest_are_counted(): void
    {
        $holders = array_map(fn (string $name): array => $this->holder($name), ['Ada', 'Ben', 'Cleo', 'Dan', 'Eve']);

        $card = $this->cards(['most_yahtzees_in_a_game' => ['value' => 1, 'holders' => $holders]])['most_yahtzees_in_a_game'];

        self::assertSame(['Ada', 'Ben', 'Cleo'], array_column($card['holders'], 'name'));
        self::assertSame(2, $card['more']);
        self::assertSame(1, $card['value'], 'The value is the record, however many hold it');
    }

    public function test_three_holders_or_fewer_leave_nothing_to_count(): void
    {
        $holders = array_map(fn (string $name): array => $this->holder($name), ['Ada', 'Ben', 'Cleo']);

        $card = $this->cards(['most_yahtzees_in_a_game' => ['value' => 1, 'holders' => $holders]])['most_yahtzees_in_a_game'];

        self::assertCount(3, $card['holders']);
        self::assertSame(0, $card['more']);
    }

    public function test_the_good_records_are_gold_and_the_lows_are_not(): void
    {
        $cards = StatsPage::records(GameStats::summarise([])['records'], $this->now());

        self::assertSame(
            ['highest_score' => true, 'most_wins' => true, 'most_consecutive_wins' => true, 'lowest_score' => false, 'most_consecutive_losses' => false, 'most_yahtzees_in_a_game' => true, 'most_consecutive_games_with_a_yahtzee' => true],
            array_column($cards, 'gold', 'key')
        );
    }

    public function test_a_players_win_rate_is_the_share_of_the_games_with_a_winner_they_won(): void
    {
        $players = StatsPage::players([
            ['player_id' => 'p-1', 'wins' => 2, 'losses' => 1, 'average_score' => 265.0],
            ['player_id' => 'p-2', 'wins' => 1, 'losses' => 2, 'average_score' => 136.66],
            ['player_id' => 'p-3', 'wins' => 0, 'losses' => 0, 'average_score' => 320.0],
            ['player_id' => 'p-4', 'wins' => 0, 'losses' => 5, 'average_score' => 100.0],
        ]);

        self::assertSame([67, 33, null, 0], array_column($players, 'win_rate'));
    }

    public function test_an_average_is_a_whole_number_when_it_is_one(): void
    {
        $players = StatsPage::players([
            ['player_id' => 'p-1', 'wins' => 0, 'losses' => 0, 'average_score' => 265.0],
            ['player_id' => 'p-2', 'wins' => 0, 'losses' => 0, 'average_score' => 136.7],
            ['player_id' => 'p-3', 'wins' => 0, 'losses' => 0, 'average_score' => 100.0],
            ['player_id' => 'p-4', 'wins' => 0, 'losses' => 0, 'average_score' => 99.5],
        ]);

        self::assertSame(['265', '136.7', '100', '99.5'], array_column($players, 'average'));
    }

    public function test_a_player_keeps_everything_gamestats_said_about_them(): void
    {
        $player = ['player_id' => 'p-1', 'player_name' => 'Ada', 'games' => 3, 'wins' => 2, 'losses' => 1, 'average_score' => 265.0, 'yahtzees' => 2];

        $card = StatsPage::players([$player])[0];

        foreach ($player as $key => $value) {
            self::assertSame($value, $card[$key], $key);
        }
    }

    // What it says about the job that collects the older games

    /**
     * @param array<string, mixed> $attributes
     */
    private function backfill(string $state, array $attributes = []): ?array
    {
        return StatsPage::backfill(new StatsBackfill(['state' => $state] + $attributes));
    }

    public function test_a_player_who_has_never_had_the_job_is_told_nothing(): void
    {
        self::assertNull(StatsPage::backfill(null));
    }

    public function test_while_it_runs_the_player_is_told_how_far_it_has_got(): void
    {
        $notice = $this->backfill(StatsBackfill::RUNNING, ['games_total' => 160, 'games_seen' => 42]);

        self::assertSame('counting', $notice['kind']);
        self::assertSame('Counting your older games', $notice['title']);
        self::assertSame('42 of 160 games checked so far. Refresh to see how far it has got.', $notice['text']);
    }

    public function test_a_job_that_has_not_read_the_list_yet_says_it_is_getting_started(): void
    {
        foreach ([StatsBackfill::QUEUED, StatsBackfill::RUNNING, StatsBackfill::PAUSED] as $state) {
            foreach ([['games_total' => null], ['games_total' => 0]] as $attributes) {
                $notice = $this->backfill($state, $attributes);

                self::assertSame('counting', $notice['kind'], $state);
                self::assertSame('We’re getting started, it takes a minute or two. Refresh to see how far it has got.', $notice['text'], $state);
            }
        }
    }

    public function test_a_job_that_gave_up_says_so_without_the_error(): void
    {
        $notice = $this->backfill(StatsBackfill::FAILED, ['last_error' => 'The API answered 503 with a secret']);

        self::assertSame('failed', $notice['kind']);
        self::assertSame('We couldn’t count all your older games', $notice['title']);
        self::assertStringNotContainsString('503', $notice['text']);
        self::assertStringNotContainsString('secret', $notice['text']);
    }

    public function test_a_job_that_counted_everything_has_nothing_to_say(): void
    {
        self::assertNull($this->backfill(StatsBackfill::COMPLETE, ['games_total' => 12, 'games_seen' => 12, 'games_counted' => 12, 'games_skipped' => 0]));
    }

    public function test_the_games_that_were_not_counted_are_added_up_with_the_reasons(): void
    {
        $notice = $this->backfill(StatsBackfill::COMPLETE, ['games_skipped' => 6, 'skipped' => ['unfinished' => 4, 'mismatch' => 2]]);

        self::assertSame('skipped', $notice['kind']);
        self::assertNull($notice['title']);
        self::assertSame('6 older games weren’t counted: 4 weren’t played to the end and 2 had scores that didn’t add up.', $notice['text']);
    }

    public function test_one_game_that_was_not_counted_is_a_game_not_games(): void
    {
        self::assertSame(
            '1 older game wasn’t counted: 1 wasn’t played to the end.',
            $this->backfill(StatsBackfill::COMPLETE, ['games_skipped' => 1, 'skipped' => ['unfinished' => 1]])['text']
        );
        self::assertSame(
            '1 older game wasn’t counted: 1 couldn’t be read.',
            $this->backfill(StatsBackfill::COMPLETE, ['games_skipped' => 1, 'skipped' => ['unreadable' => 1]])['text']
        );
    }

    public function test_all_three_reasons_are_listed_in_the_same_order_whatever_order_they_were_found(): void
    {
        $notice = $this->backfill(StatsBackfill::COMPLETE, ['games_skipped' => 7, 'skipped' => ['unreadable' => 1, 'mismatch' => 2, 'unfinished' => 4]]);

        self::assertSame('7 older games weren’t counted: 4 weren’t played to the end, 2 had scores that didn’t add up and 1 couldn’t be read.', $notice['text']);
    }

    public function test_a_reason_nobody_hit_is_not_mentioned(): void
    {
        $notice = $this->backfill(StatsBackfill::COMPLETE, ['games_skipped' => 3, 'skipped' => ['unfinished' => 3, 'mismatch' => 0]]);

        self::assertSame('3 older games weren’t counted: 3 weren’t played to the end.', $notice['text']);
    }

    public function test_skipped_games_with_no_reasons_recorded_are_still_counted(): void
    {
        self::assertSame('2 older games weren’t counted.', $this->backfill(StatsBackfill::COMPLETE, ['games_skipped' => 2])['text']);
    }
}
