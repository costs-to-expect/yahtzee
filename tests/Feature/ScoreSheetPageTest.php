<?php

declare(strict_types=1);

namespace Tests\Feature;

use Illuminate\Http\Client\Request;
use Illuminate\Support\Facades\Http;
use PHPUnit\Framework\Attributes\DataProvider;
use Tests\Feature\Concerns\FakesTheApi;
use Tests\TestCase;

class ScoreSheetPageTest extends TestCase
{
    use FakesTheApi;

    private function fakeSheet(array $sheet, bool $complete = false, array $overrides = []): void
    {
        $this->fakeApi($overrides + [
            $this->items('/g-1?include-players=1') => Http::response($this->game('g-1', ['p-1' => 'Ada', 'p-2' => 'Ben'], $complete), 200),
            $this->items('/g-1/data/p-1') => Http::response(['key' => 'p-1', 'value' => $sheet], 200),
        ]);
    }

    public function test_the_score_sheet_belongs_to_the_player_and_game(): void
    {
        $this->fakeSheet($this->scoreSheet());

        $this->signedIn()->get('/game/g-1/player/p-1/score-sheet')
            ->assertOk()
            ->assertSee('Player: Ada')
            ->assertSee('id="game_id" name="game_id" value="g-1"', false)
            ->assertSee('id="player_id" name="player_id" value="p-1"', false);
    }

    public function test_scored_combinations_are_locked_with_their_score_and_the_rest_are_open(): void
    {
        $this->fakeSheet($this->scoreSheet(['ones' => 3, 'twos' => 0], ['three_of_a_kind' => 22, 'yahtzee' => 50]));

        $html = $this->signedIn()->get('/game/g-1/player/p-1/score-sheet')->assertOk()->getContent();

        // Scored ones: the score is shown in a disabled input, twos was scratched.
        self::assertMatchesRegularExpression('/<input type="number"[^>]*name="ones"[^>]*disabled="disabled" value="3"/', $html);
        self::assertMatchesRegularExpression('/<input type="number"[^>]*name="twos"[^>]*disabled="disabled" value="0"/', $html);
        self::assertMatchesRegularExpression('/id="scratch_twos"[^>]*checked="checked"/', $html);
        self::assertDoesNotMatchRegularExpression('/id="scratch_ones"[^>]*checked="checked"/', $html);

        // Nothing has been scored for threes, it is open.
        self::assertMatchesRegularExpression('/<input type="number"[^>]*class="[^"]*\bactive\b[^"]*"[^>]*name="threes"/', $html);
        self::assertDoesNotMatchRegularExpression('/<input type="number"[^>]*name="threes"[^>]*disabled/', $html);

        // Lower section, a typed score and a ticked fixed score.
        self::assertMatchesRegularExpression('/id="three_of_a_kind"[^>]*disabled="disabled" value="22"/', $html);
        self::assertMatchesRegularExpression('/id="yahtzee" value="50"[^>]*disabled="disabled"[^>]*checked="checked"/', $html);
        self::assertDoesNotMatchRegularExpression('/id="large_straight"[^>]*disabled/', $html);
    }

    public function test_the_totals_are_those_of_the_stored_score_sheet(): void
    {
        $sheet = $this->scoreSheet(
            ['ones' => 3, 'twos' => 6, 'threes' => 9, 'fours' => 12, 'fives' => 15, 'sixes' => 18],
            ['full_house' => 25]
        );

        $this->fakeSheet($sheet);

        $response = $this->signedIn()->get('/game/g-1/player/p-1/score-sheet')->assertOk();

        $response->assertSee('id="upper-score">63<', false)
            ->assertSee('id="upper-bonus">35<', false)
            ->assertSee('id="upper-total">98<', false)
            ->assertSee('id="lower-score">25<', false)
            ->assertSee('id="total">123<', false);
    }

    /**
     * @return array<string, array{array, bool}> the sheet and whether the yahtzee bonus is locked
     */
    public static function yahtzeeBonusStates(): array
    {
        $finished_upper = ['ones' => 3, 'twos' => 6, 'threes' => 9, 'fours' => 12, 'fives' => 15, 'sixes' => 18];
        $lower_without_yahtzee = ['three_of_a_kind' => 20, 'four_of_a_kind' => 0, 'full_house' => 25, 'small_straight' => 30, 'large_straight' => 40, 'chance' => 22];

        return [
            'yahtzee not scored yet' => [['upper' => [], 'lower' => []], false],
            'yahtzee scored' => [['upper' => [], 'lower' => ['yahtzee' => 50]], false],
            'yahtzee scratched, nothing to bonus' => [['upper' => [], 'lower' => ['yahtzee' => 0]], true],
            'every turn taken' => [['upper' => $finished_upper, 'lower' => $lower_without_yahtzee + ['yahtzee' => 50]], true],
        ];
    }

    #[DataProvider('yahtzeeBonusStates')]
    public function test_the_yahtzee_bonus_is_locked_when_the_yahtzee_was_scratched_or_every_turn_is_taken(array $sheet, bool $locked): void
    {
        $this->fakeSheet($this->scoreSheet($sheet['upper'], $sheet['lower']));

        $html = $this->signedIn()->get('/game/g-1/player/p-1/score-sheet')->assertOk()->getContent();

        $pattern = '/id="yahtzee_bonus_one"[^>]*disabled="disabled"/';

        $locked
            ? self::assertMatchesRegularExpression($pattern, $html)
            : self::assertDoesNotMatchRegularExpression($pattern, $html);
    }

    public function test_the_scoring_scripts_are_only_loaded_for_an_open_game(): void
    {
        $this->fakeSheet($this->scoreSheet(), complete: false);
        $this->signedIn()->get('/game/g-1/player/p-1/score-sheet')
            ->assertSee('js/score-sheet.js', false)
            ->assertSee('js/player-scores.js', false);
    }

    public function test_a_complete_game_is_a_read_only_score_sheet(): void
    {
        $this->fakeSheet($this->scoreSheet(['ones' => 3]), complete: true);

        $this->signedIn()->get('/game/g-1/player/p-1/score-sheet')
            ->assertOk()
            ->assertDontSee('js/score-sheet.js', false)
            ->assertDontSee('js/player-scores.js', false);
    }

    public function test_a_player_without_a_score_sheet_is_given_an_empty_one(): void
    {
        $this->fakeApi([
            $this->items('/g-1?include-players=1') => Http::response($this->game('g-1', ['p-1' => 'Ada']), 200),
            $this->items('/g-1/data/p-1') => Http::response(['message' => 'Not found'], 404),
            $this->items('/g-1/data') => Http::response(['id' => 'ds-1'], 201),
        ]);

        $this->signedIn()->get('/game/g-1/player/p-1/score-sheet')
            ->assertRedirect(route('game.score-sheet', ['game_id' => 'g-1', 'player_id' => 'p-1']));

        Http::assertSent(fn (Request $request) => $request->method() === 'POST'
            && $request->url() === 'http://api.test/v3/resource-types/rt-1/resources/r-1/items/g-1/data'
            && $request['key'] === 'p-1'
            && json_decode($request['value'], true) === $this->scoreSheet());
    }

    public function test_a_failure_creating_the_score_sheet_is_passed_on(): void
    {
        $this->fakeApi([
            $this->items('/g-1?include-players=1') => Http::response($this->game('g-1', ['p-1' => 'Ada']), 200),
            $this->items('/g-1/data/p-1') => Http::response(['message' => 'Not found'], 404),
            $this->items('/g-1/data') => Http::response(['message' => 'The API is down'], 503),
        ]);

        $this->signedIn()->get('/game/g-1/player/p-1/score-sheet')->assertStatus(503);
    }

    public function test_a_game_that_cannot_be_found_is_passed_on(): void
    {
        $this->fakeApi([$this->items('/g-404?include-players=1') => Http::response(['message' => 'Not found'], 404)]);

        $this->signedIn()->get('/game/g-404/player/p-1/score-sheet')->assertNotFound();
    }

    public function test_a_failure_reading_the_score_sheet_is_passed_on(): void
    {
        $this->fakeApi([
            $this->items('/g-1?include-players=1') => Http::response($this->game('g-1', ['p-1' => 'Ada']), 200),
            $this->items('/g-1/data/p-1') => Http::response(['message' => 'The API is down'], 503),
        ]);

        $this->signedIn()->get('/game/g-1/player/p-1/score-sheet')->assertStatus(503);
    }
}
