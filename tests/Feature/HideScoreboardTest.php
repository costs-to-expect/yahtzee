<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Models\ShareToken;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\Client\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Http;
use Tests\Feature\Concerns\FakesTheApi;
use Tests\TestCase;

/**
 * The person running a game can switch off the public scoreboard (the "Everyone" panel) on its public score sheets. The
 * choice is made when the game is started and kept as a column on every share token of the game, a game that was in
 * progress before the setting existed keeps showing the scoreboard.
 */
class HideScoreboardTest extends TestCase
{
    use FakesTheApi;
    use RefreshDatabase;

    private const PLAYER_NAMES = ['p-1' => 'Ada', 'p-2' => 'Ben', 'p-3' => 'Cleo'];

    private function fakeCreatingGames(array $overrides = []): void
    {
        $this->fakeApi($overrides + [
            $this->items() => Http::response(['id' => 'g-new'], 201),
            $this->items('/g-new/categories') => fn (Request $request) => Http::response([
                'category' => ['id' => $request['category_id'], 'name' => self::PLAYER_NAMES[$request['category_id']] ?? 'Name of '.$request['category_id']],
            ], 201),
        ]);
    }

    /**
     * The tokens of a game as player id => hidden, so one assertion shows every player of the game has the same value
     *
     * @return array<string, bool>
     */
    private function hiddenFor(string $game_id): array
    {
        $hidden = [];
        foreach (ShareToken::query()->where('game_id', $game_id)->orderBy('player_id')->get() as $token) {
            $hidden[$token->player_id] = $token->hide_scoreboard;
        }

        return $hidden;
    }

    /**
     * @param list<string> $player_ids
     */
    private function issueTokens(string $game_id, array $player_ids, bool $hide_scoreboard): void
    {
        foreach ($player_ids as $player_id) {
            ShareToken::issue('rt-1', 'r-1', $game_id, $player_id, self::PLAYER_NAMES[$player_id], 'owner-bearer', $hide_scoreboard);
        }
    }

    /**
     * @return array<string, mixed> the settings the score sheet script reads, written into the page as JSON
     */
    private function sheetConfig(string $html): array
    {
        self::assertSame(1, preg_match('#<script type="application/json" id="sheet-config">(.*?)</script>#s', $html, $matches), 'the page carries its settings');

        return json_decode($matches[1], true, 512, JSON_THROW_ON_ERROR);
    }

    private function fakeSharedGame(): void
    {
        $this->fakeApi([
            $this->items('/g-1') => Http::response($this->game('g-1', ['p-1' => 'Ada', 'p-2' => 'Ben']), 200),
            $this->items('/g-1/data/p-1') => Http::response(['key' => 'p-1', 'value' => $this->scoreSheet(['ones' => 3], ['chance' => 20])], 200),
            $this->items('/g-1/categories') => Http::response($this->assignedPlayers(['p-1' => 'Ada', 'p-2' => 'Ben']), 200),
            $this->items('/g-1/data') => Http::response($this->scoreSheets([
                'p-1' => $this->scoreSheet(['ones' => 3], ['chance' => 20]),
                'p-2' => $this->scoreSheet(['sixes' => 24], []),
            ]), 200),
        ]);
    }

    // The checkbox

    public function test_the_new_game_form_offers_to_hide_the_scoreboard_and_it_is_not_ticked(): void
    {
        $response = $this->signedIn()->get('/new-game')
            ->assertOk()
            ->assertSee('name="hide_scoreboard" value="1"', false)
            ->assertSee('Hide the scoreboard on players&rsquo; score sheets', false);

        self::assertDoesNotMatchRegularExpression('/name="hide_scoreboard"[^>]*\schecked/', $response->getContent());
    }

    public function test_both_forms_that_start_a_game_on_the_home_page_offer_it_with_ids_of_their_own(): void
    {
        $this->fakeApi([
            $this->items('?complete=0&include-players=1') => Http::response([], 200),
            $this->items('?complete=1&limit=5&include-players=1') => Http::response([
                $this->game('g-9', ['p-1' => 'Ada', 'p-2' => 'Ben'], true, [
                    ['player_id' => 'p-2', 'player_name' => 'Ben', 'score' => 211],
                    ['player_id' => 'p-1', 'player_name' => 'Ada', 'score' => 187],
                ]),
            ], 200),
        ]);

        $response = $this->signedIn()->get('/home')
            ->assertOk()
            ->assertSee('Play again with')
            ->assertSee('id="hide-scoreboard-next"', false)
            ->assertSee('id="hide-scoreboard-again"', false);

        self::assertSame(2, substr_count($response->getContent(), 'name="hide_scoreboard"'));
        self::assertDoesNotMatchRegularExpression('/name="hide_scoreboard"[^>]*\schecked/', $response->getContent());
        // The label points at its own checkbox, two with the same id would tick the wrong one
        self::assertSame(1, substr_count($response->getContent(), 'id="hide-scoreboard-next"'));
        self::assertSame(1, substr_count($response->getContent(), 'id="hide-scoreboard-again"'));
    }

    public function test_the_first_game_form_has_no_checkbox(): void
    {
        $this->fakeApi([
            'api.test/v3/resource-types/rt-1/categories?collection=1' => Http::response([], 200),
            $this->items('?complete=0&include-players=1') => Http::response([], 200),
            $this->items('?complete=1&limit=5&include-players=1') => Http::response([], 200),
        ]);

        $this->signedIn()->get('/home')
            ->assertOk()
            ->assertSee('Let&rsquo;s get started!', false)
            ->assertDontSee('hide_scoreboard', false);
    }

    public function test_the_ticked_box_comes_back_when_the_game_is_refused(): void
    {
        $this->fakeApi();

        $response = $this->signedIn()->followingRedirects()
            ->post('/new-game', ['name' => 'Yahtzee game', 'description' => 'A game', 'hide_scoreboard' => '1']);

        $response->assertOk()->assertSee('Please select your players');
        self::assertMatchesRegularExpression('/name="hide_scoreboard"[^>]*\schecked/', $response->getContent());
    }

    // Creating a game

    public function test_a_game_shows_the_scoreboard_unless_the_box_is_ticked(): void
    {
        $this->fakeCreatingGames();

        $this->signedIn()
            ->post('/new-game', ['name' => 'Yahtzee game', 'description' => 'A game', 'players' => ['p-1', 'p-2']])
            ->assertRedirect(route('game.show', ['game_id' => 'g-new']));

        self::assertSame(['p-1' => false, 'p-2' => false], $this->hiddenFor('g-new'));
    }

    public function test_a_ticked_box_hides_the_scoreboard_on_every_players_token(): void
    {
        $this->fakeCreatingGames();

        $this->signedIn()
            ->post('/new-game', ['name' => 'Yahtzee game', 'description' => 'A game', 'players' => ['p-1', 'p-2', 'p-3'], 'hide_scoreboard' => '1'])
            ->assertRedirect(route('game.show', ['game_id' => 'g-new']));

        self::assertSame(['p-1' => true, 'p-2' => true, 'p-3' => true], $this->hiddenFor('g-new'));
        self::assertTrue((new ShareToken())->scoreboardHiddenFor('g-new'));
    }

    public function test_the_setting_is_not_sent_to_the_api(): void
    {
        $this->fakeCreatingGames();

        $this->signedIn()
            ->post('/new-game', ['name' => 'Yahtzee game', 'description' => 'A game', 'players' => ['p-1'], 'hide_scoreboard' => '1']);

        Http::assertSent(fn (Request $request) => $request->method() === 'POST'
            && $request->url() === 'http://api.test/v3/resource-types/rt-1/resources/r-1/items'
            && $request->data() === ['name' => 'Yahtzee game', 'description' => 'A game']);
    }

    public function test_the_setting_is_not_one_of_the_encrypted_parameters(): void
    {
        $this->fakeCreatingGames();

        $this->signedIn()
            ->post('/new-game', ['name' => 'Yahtzee game', 'description' => 'A game', 'players' => ['p-1'], 'hide_scoreboard' => '1']);

        $token = ShareToken::query()->where('game_id', 'g-new')->firstOrFail();

        self::assertSame(
            ['resource_type_id', 'resource_id', 'game_id', 'player_id', 'player_name', 'owner_bearer'],
            array_keys($token->parameters)
        );
    }

    public function test_the_first_game_started_from_typed_names_shows_the_scoreboard(): void
    {
        $this->fakeCreatingGames([
            'api.test/v3/resource-types/rt-1/categories' => fn (Request $request) => Http::response(['id' => 'new-'.strtolower($request['name'])], 201),
            $this->items('/g-new/categories') => fn (Request $request) => Http::response([
                'category' => ['id' => $request['category_id'], 'name' => ucfirst(substr($request['category_id'], 4))],
            ], 201),
        ]);

        $this->signedIn()->post('/start', ['players' => "Ada\nBen", 'hide_scoreboard' => '1']);

        self::assertSame(['new-ada' => false, 'new-ben' => false], $this->hiddenFor('g-new'));
    }

    // Adding players to a game

    public function test_a_player_added_to_a_game_with_a_hidden_scoreboard_gets_a_hidden_token(): void
    {
        $this->issueTokens('g-1', ['p-1'], true);
        $this->fakeApi([
            $this->items('/g-1/categories') => fn (Request $request) => Http::response([
                'category' => ['id' => $request['category_id'], 'name' => self::PLAYER_NAMES[$request['category_id']]],
            ], 201),
        ]);

        $this->signedIn()
            ->post('/add-players-to-game/g-1', ['players' => ['p-2', 'p-3']])
            ->assertRedirect(route('home'));

        self::assertSame(['p-1' => true, 'p-2' => true, 'p-3' => true], $this->hiddenFor('g-1'));
    }

    public function test_a_player_added_to_a_game_with_a_visible_scoreboard_gets_a_visible_token(): void
    {
        $this->issueTokens('g-1', ['p-1'], false);
        $this->issueTokens('g-other', ['p-1'], true);
        $this->fakeApi([
            $this->items('/g-1/categories') => fn (Request $request) => Http::response([
                'category' => ['id' => $request['category_id'], 'name' => self::PLAYER_NAMES[$request['category_id']]],
            ], 201),
        ]);

        $this->signedIn()->post('/add-players-to-game/g-1', ['players' => ['p-2']]);

        // Another game's setting is not the game's
        self::assertSame(['p-1' => false, 'p-2' => false], $this->hiddenFor('g-1'));
    }

    // Finishing a game and playing again

    private function fakeFinishingAndPlayingAgain(): void
    {
        $players = ['p-1' => 'Ada', 'p-2' => 'Ben'];
        $sheets = [];
        foreach (array_keys($players) as $player_id) {
            $sheets[$player_id] = $this->scoreSheet([], []);
        }

        $this->fakeApi([
            $this->items('/g-1?include-players=1') => Http::response($this->game('g-1', $players), 200),
            $this->items('/g-1/categories') => Http::response($this->assignedPlayers($players), 200),
            $this->items('/g-1/data') => Http::response($this->scoreSheets($sheets), 200),
            $this->items('/g-1') => Http::response(null, 204),
            $this->items() => Http::response(['id' => 'g-new'], 201),
            $this->items('/g-new/categories') => fn (Request $request) => Http::response([
                'category' => ['id' => $request['category_id'], 'name' => self::PLAYER_NAMES[$request['category_id']]],
            ], 201),
        ]);
    }

    public function test_finishing_and_playing_again_keeps_a_hidden_scoreboard_hidden(): void
    {
        $this->issueTokens('g-1', ['p-1', 'p-2'], true);
        $this->fakeFinishingAndPlayingAgain();

        $this->signedIn()
            ->post('/game/g-1/complete-and-play-again')
            ->assertRedirect(route('game.show', ['game_id' => 'g-new']));

        // The old game's tokens went with the game, the new game has the setting
        self::assertSame([], $this->hiddenFor('g-1'));
        self::assertSame(['p-1' => true, 'p-2' => true], $this->hiddenFor('g-new'));
    }

    public function test_finishing_and_playing_again_keeps_a_visible_scoreboard_visible(): void
    {
        $this->issueTokens('g-1', ['p-1', 'p-2'], false);
        $this->fakeFinishingAndPlayingAgain();

        $this->signedIn()->post('/game/g-1/complete-and-play-again');

        self::assertSame(['p-1' => false, 'p-2' => false], $this->hiddenFor('g-new'));
    }

    public function test_finishing_and_playing_again_does_not_hide_the_scoreboard_for_a_game_from_before_the_setting_existed(): void
    {
        // A token row written before the column was added, so it has the column's default
        DB::table('share_token')->insert(['token' => 'old-token', 'game_id' => 'g-1', 'player_id' => 'p-1', 'parameters' => '{}']);
        $this->fakeFinishingAndPlayingAgain();

        $this->signedIn()->post('/game/g-1/complete-and-play-again');

        self::assertSame(['p-1' => false, 'p-2' => false], $this->hiddenFor('g-new'));
    }

    // The public score sheet

    public function test_a_public_score_sheet_with_the_scoreboard_hidden_has_no_everyone_panel(): void
    {
        $this->issueTokens('g-1', ['p-1', 'p-2'], true);
        $token = (string) ShareToken::query()->where('player_id', 'p-1')->value('token');
        $this->fakeSharedGame();

        $response = $this->get('/public/score-sheet/'.$token)
            ->assertOk()
            ->assertSee('Player: Ada')
            ->assertSee('id="total"', false)
            ->assertDontSee('id="everyone"', false)
            ->assertDontSee('Everyone')
            ->assertDontSee('<aside', false)
            ->assertDontSee('Scores update by themselves')
            // A single, narrower column, not the sheet next to an empty gutter
            ->assertDontSee('lg:grid-cols-[minmax(0,1fr)_20rem]', false)
            ->assertSee('max-w-3xl', false);

        // The other players are not on the page at all
        self::assertStringNotContainsString('Ben', $response->getContent());

        $config = $this->sheetConfig($response->getContent());
        self::assertFalse($config['scoreboard']);
        self::assertSame(['upper', 'lower', 'clear'], array_keys($config['urls']));
        self::assertStringNotContainsString('player-scores', $response->getContent());
    }

    public function test_a_public_score_sheet_shows_the_scoreboard_by_default(): void
    {
        $this->issueTokens('g-1', ['p-1', 'p-2'], false);
        $token = (string) ShareToken::query()->where('player_id', 'p-1')->value('token');
        $this->fakeSharedGame();

        $response = $this->get('/public/score-sheet/'.$token)
            ->assertOk()
            ->assertSee('id="everyone"', false)
            ->assertSee('Everyone')
            ->assertSee('lg:grid-cols-[minmax(0,1fr)_20rem]', false)
            ->assertSee('max-w-5xl', false);

        $config = $this->sheetConfig($response->getContent());
        self::assertTrue($config['scoreboard']);
        self::assertSame(route('public.player-scores', ['token' => $token]), $config['urls']['players']);
    }

    public function test_a_link_from_before_the_setting_existed_still_shows_the_scoreboard(): void
    {
        // Written before the column was added, so it has the column's default
        DB::table('share_token')->insert(['token' => 'old-token', 'game_id' => 'g-1', 'player_id' => 'p-1', 'parameters' => '{}']);

        self::assertSame(0, (int) DB::table('share_token')->where('token', 'old-token')->value('hide_scoreboard'));
        self::assertFalse((new ShareToken())->scoreboardHiddenFor('g-1'));
        self::assertSame([], (new ShareToken())->hiddenScoreboards(['g-1']));
    }

    public function test_everyones_scores_are_refused_when_the_scoreboard_is_hidden(): void
    {
        $this->issueTokens('g-1', ['p-1', 'p-2'], true);
        $token = (string) ShareToken::query()->where('player_id', 'p-1')->value('token');
        $this->fakeSharedGame();

        $this->get('/public/game/'.$token.'/player-scores')->assertForbidden();

        // Refused before the API is asked for anything
        Http::assertNothingSent();
    }

    public function test_everyones_scores_are_still_served_when_the_scoreboard_is_shown(): void
    {
        $this->issueTokens('g-1', ['p-1', 'p-2'], false);
        $token = (string) ShareToken::query()->where('player_id', 'p-1')->value('token');
        $this->fakeSharedGame();

        $this->get('/public/game/'.$token.'/player-scores')
            ->assertOk()
            ->assertJsonCount(2, 'players');
    }

    public function test_hiding_the_scoreboard_does_not_stop_a_player_scoring(): void
    {
        $this->issueTokens('g-1', ['p-1'], true);
        $token = (string) ShareToken::query()->value('token');
        $this->fakeApi([
            $this->items('/g-1/data/p-1') => $this->byMethod([
                'GET' => Http::response(['key' => 'p-1', 'value' => $this->scoreSheet()], 200),
                'PATCH' => Http::response(null, 204),
            ]),
            $this->items('/g-1/log') => Http::response(['id' => 'log-1'], 201),
        ]);

        $this->postJson('/public/score-sheet/'.$token.'/score-upper', ['dice' => 'ones', 'score' => 3])
            ->assertStatus(200)
            ->assertJsonPath('score.upper', 3);
    }

    // The host

    public function test_the_signed_in_score_sheet_always_has_the_everyone_panel(): void
    {
        $this->issueTokens('g-1', ['p-1', 'p-2'], true);
        $this->fakeApi([
            $this->items('/g-1?include-players=1') => Http::response($this->game('g-1', ['p-1' => 'Ada', 'p-2' => 'Ben']), 200),
            $this->items('/g-1/data/p-1') => Http::response(['key' => 'p-1', 'value' => $this->scoreSheet()], 200),
        ]);

        $response = $this->signedIn()->get('/game/g-1/player/p-1/score-sheet')
            ->assertOk()
            ->assertSee('id="everyone"', false)
            ->assertSee('max-w-5xl', false);

        $config = $this->sheetConfig($response->getContent());
        self::assertTrue($config['scoreboard']);
        self::assertArrayHasKey('players', $config['urls']);
    }

    public function test_the_game_overview_says_when_the_scoreboard_is_hidden_on_the_share_links(): void
    {
        $this->issueTokens('g-1', ['p-1'], true);
        $this->fakeApi([
            $this->items('/g-1?include-players=1') => Http::response($this->game('g-1', ['p-1' => 'Ada']), 200),
            $this->items('/g-1/data') => Http::response($this->scoreSheets([]), 200),
        ]);

        $this->signedIn()->get('/games/g-1')
            ->assertOk()
            ->assertSee('The scoreboard is hidden on these links');
    }

    public function test_the_game_overview_says_nothing_when_the_scoreboard_is_shown(): void
    {
        $this->issueTokens('g-1', ['p-1'], false);
        $this->issueTokens('g-other', ['p-1'], true);
        $this->fakeApi([
            $this->items('/g-1?include-players=1') => Http::response($this->game('g-1', ['p-1' => 'Ada']), 200),
            $this->items('/g-1/data') => Http::response($this->scoreSheets([]), 200),
        ]);

        // Another game's setting is not this game's
        $this->signedIn()->get('/games/g-1')
            ->assertOk()
            ->assertDontSee('The scoreboard is hidden on these links');
    }

    public function test_the_home_page_says_when_the_scoreboard_of_the_open_game_is_hidden(): void
    {
        $this->issueTokens('g-1', ['p-1', 'p-2'], true);
        $this->fakeApi([
            $this->items('?complete=0&include-players=1') => Http::response([$this->game('g-1', ['p-1' => 'Ada', 'p-2' => 'Ben'])], 200),
            $this->items('?complete=1&limit=5&include-players=1') => Http::response([], 200),
            $this->items('/g-1/data') => Http::response($this->scoreSheets([]), 200),
        ]);

        $this->signedIn()->get('/home')
            ->assertOk()
            ->assertSee('Game in progress')
            ->assertSee('The scoreboard is hidden on these links');
    }

    // The model

    public function test_the_games_with_a_hidden_scoreboard_are_found_in_one_go(): void
    {
        $this->issueTokens('g-1', ['p-1', 'p-2'], true);
        $this->issueTokens('g-2', ['p-1'], false);
        $this->issueTokens('g-3', ['p-1'], true);

        self::assertSame(['g-1' => true, 'g-3' => true], (new ShareToken())->hiddenScoreboards(['g-1', 'g-2', 'g-3', 'g-4']));
        self::assertSame(['g-1' => true], (new ShareToken())->hiddenScoreboards(['g-1', 'g-2']));
        self::assertSame([], (new ShareToken())->hiddenScoreboards([]));
        self::assertTrue((new ShareToken())->scoreboardHiddenFor('g-3'));
        self::assertFalse((new ShareToken())->scoreboardHiddenFor('g-2'));
        self::assertFalse((new ShareToken())->scoreboardHiddenFor('g-4'));
    }

    public function test_a_token_issued_without_the_setting_shows_the_scoreboard(): void
    {
        ShareToken::issue('rt-1', 'r-1', 'g-1', 'p-1', 'Ada', 'owner-bearer');

        self::assertFalse(ShareToken::query()->firstOrFail()->hide_scoreboard);
        $this->assertDatabaseHas('share_token', ['game_id' => 'g-1', 'hide_scoreboard' => 0]);
    }
}
