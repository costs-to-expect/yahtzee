<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Models\ShareToken;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\Client\Request;
use Illuminate\Support\Facades\Http;
use Tests\Feature\Concerns\FakesTheApi;
use Tests\TestCase;

class HomeTest extends TestCase
{
    use FakesTheApi;
    use RefreshDatabase;

    /**
     * @param list<array> $open
     * @param list<array> $closed
     * @param array<string, array> $sheets game id => score sheets
     */
    private function fakeHome(array $open = [], array $closed = [], array $sheets = [], ?array $players = null, array $overrides = []): void
    {
        $fakes = [
            $this->items('?complete=0&include-players=1') => Http::response($open, 200),
            $this->items('?complete=1&limit=5&include-players=1') => Http::response($closed, 200),
        ];

        foreach ($sheets as $game_id => $game_sheets) {
            $fakes[$this->items("/{$game_id}/data")] = Http::response($game_sheets, 200);
        }

        if ($players !== null) {
            $fakes['api.test/v3/resource-types/rt-1/categories?collection=1'] = Http::response($players, 200);
        }

        $this->fakeApi($overrides + $fakes);
    }

    private function shareToken(string $token, string $game_id, string $player_id): void
    {
        $share = new ShareToken();
        $share->token = $token;
        $share->game_id = $game_id;
        $share->player_id = $player_id;
        $share->parameters = json_encode(['game_id' => $game_id, 'player_id' => $player_id]);
        $share->save();
    }

    public function test_a_new_player_is_invited_to_enter_their_players_and_start_a_game(): void
    {
        $this->fakeHome(players: []);

        $this->signedIn()->get('/home')
            ->assertOk()
            ->assertSee("Let's get started!", false)
            ->assertSee('action="'.route('start').'"', false)
            ->assertSee('name="players"', false)
            ->assertSee('No finished games!')
            ->assertSee('No Players!')
            ->assertDontSee('Open Games');
    }

    public function test_a_player_with_players_is_offered_a_new_game_not_the_getting_started_form(): void
    {
        $this->fakeHome();

        $this->signedIn()->get('/home')
            ->assertOk()
            ->assertSee('New game')
            ->assertSee(route('game.create.view'), false)
            ->assertDontSee("Let's get started!", false)
            ->assertSee('Ada')
            ->assertSee('Ben');
    }

    public function test_open_games_show_each_players_score_with_the_links_to_play(): void
    {
        $this->fakeHome(
            open: [$this->game('g-1', ['p-1' => 'Ada', 'p-2' => 'Ben'])],
            sheets: ['g-1' => $this->scoreSheets(['p-1' => $this->scoreSheet(['ones' => 3], ['full_house' => 25])])]
        );

        $this->signedIn()->get('/home')
            ->assertOk()
            ->assertSee('Open Games')
            ->assertSee('Ada')
            ->assertSee('(28 pts)')
            ->assertSee('Ben')
            ->assertSee('(0 pts)')
            ->assertSee(route('game.show', ['game_id' => 'g-1']), false)
            ->assertSee(route('game.score-sheet', ['game_id' => 'g-1', 'player_id' => 'p-1']), false)
            ->assertSee(route('game.score-sheet', ['game_id' => 'g-1', 'player_id' => 'p-2']), false)
            ->assertSee(route('game.player.delete', ['game_id' => 'g-1', 'player_id' => 'p-1']), false)
            ->assertSee(route('game.add-players.view', ['game_id' => 'g-1']), false)
            ->assertSee(route('game.complete.action', ['game_id' => 'g-1']), false)
            ->assertSee(route('game.complete.play-again.action', ['game_id' => 'g-1']), false)
            ->assertSee(route('game.delete.action', ['game_id' => 'g-1']), false);
    }

    public function test_a_public_score_sheet_link_is_only_offered_to_players_with_a_share_token(): void
    {
        $this->shareToken('token-for-ada', 'g-1', 'p-1');
        $this->fakeHome(open: [$this->game('g-1', ['p-1' => 'Ada', 'p-2' => 'Ben'])], sheets: ['g-1' => []]);

        $response = $this->signedIn()->get('/home')
            ->assertOk()
            ->assertSee(route('public.score-sheet', ['token' => 'token-for-ada']), false);

        // Ben has no token, so there is a single public link on the page.
        self::assertSame(1, substr_count($response->getContent(), '/public/score-sheet/'));
    }

    public function test_recent_games_show_the_final_scores_with_a_link_to_each_score_sheet(): void
    {
        $this->fakeHome(closed: [
            $this->game('g-9', ['p-1' => 'Ada', 'p-2' => 'Ben'], true, [
                ['player_id' => 'p-1', 'player_name' => 'Ada', 'score' => 211],
                ['player_id' => 'p-2', 'player_name' => 'Ben', 'score' => 187],
            ]),
        ]);

        $this->signedIn()->get('/home')
            ->assertOk()
            ->assertSee('Recent Games')
            ->assertSee('(211 pts)')
            ->assertSee('(187 pts)')
            ->assertSee(route('game.score-sheet', ['game_id' => 'g-9', 'player_id' => 'p-2']), false);
    }

    public function test_every_api_request_is_made_with_the_players_bearer_token(): void
    {
        $this->fakeHome(open: [$this->game('g-1', ['p-1' => 'Ada'])], sheets: ['g-1' => []]);

        $this->signedIn()->get('/home')->assertOk();

        Http::assertSent(fn (Request $request) => $request->header('Authorization') === ['Bearer test-bearer-token']);
        Http::assertNotSent(fn (Request $request) => $request->header('Authorization') !== ['Bearer test-bearer-token']);
        Http::assertNotSent(fn (Request $request) => $request->hasHeader('X-Internal-Api-Key'));
    }

    public function test_the_lookups_for_the_players_resource_type_and_resource_always_skip_the_api_cache(): void
    {
        $this->fakeHome();

        $this->signedIn()->get('/home')->assertOk();

        Http::assertSent(fn (Request $request) => str_contains($request->url(), '/v3/resource-types?item-type=item-type-id')
            && $request->hasHeader('X-Skip-Cache'));
        Http::assertSent(fn (Request $request) => str_contains($request->url(), '/resources?item-subtype=item-subtype-id')
            && $request->hasHeader('X-Skip-Cache'));
    }

    public function test_the_home_page_is_a_404_when_the_account_cannot_be_fetched(): void
    {
        $this->fakeHome(overrides: ['api.test/v3/auth/user' => Http::response(['message' => 'Unauthenticated'], 401)]);

        $this->signedIn()->get('/home')->assertNotFound();
    }

    public function test_failed_game_lookups_show_as_empty_lists_rather_than_an_error(): void
    {
        $this->fakeApi([
            $this->items('?complete=0&include-players=1') => Http::response(['message' => 'Broken'], 500),
            $this->items('?complete=1&limit=5&include-players=1') => Http::response(['message' => 'Broken'], 500),
        ]);

        $this->signedIn()->get('/home')
            ->assertOk()
            ->assertDontSee('Open Games')
            ->assertSee('No finished games!');
    }
}
