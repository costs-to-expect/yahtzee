<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Models\ShareToken;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;
use PHPUnit\Framework\Attributes\DataProvider;
use Tests\Feature\Concerns\FakesTheApi;
use Tests\TestCase;

/**
 * After each upper section score the score sheet asks for a message about the player's chances
 * of scoring the 35 point bonus (63 or more in the upper section).
 */
class BonusMessageTest extends TestCase
{
    use FakesTheApi;
    use RefreshDatabase;

    /**
     * @return array<string, array{array<string, int>, string}> the upper section and the message
     */
    public static function messages(): array
    {
        return [
            'nothing scored yet' => [[], "Looking good, you haven't messed up yet!"],

            // Scored the bonus
            'bonus reached before every dice is scored' => [
                ['ones' => 5, 'twos' => 10, 'threes' => 15, 'fours' => 20, 'fives' => 25],
                'OK, OK, you have the bonus without even finishing!',
            ],
            'exactly sixty three with every dice scored' => [
                ['ones' => 3, 'twos' => 6, 'threes' => 9, 'fours' => 12, 'fives' => 15, 'sixes' => 18],
                'Damn, that was close, next time, give yourself a little breathing room!',
            ],
            'a little over sixty three' => [
                ['ones' => 4, 'twos' => 6, 'threes' => 9, 'fours' => 12, 'fives' => 15, 'sixes' => 18],
                'Awesome, made it with a little breathing room!',
            ],
            'plenty of breathing room' => [
                ['ones' => 3, 'twos' => 6, 'threes' => 12, 'fours' => 12, 'fives' => 20, 'sixes' => 18],
                'Awesome, plenty of breathing room!',
            ],
            'showing off' => [
                ['ones' => 4, 'twos' => 8, 'threes' => 12, 'fours' => 16, 'fives' => 20, 'sixes' => 18],
                'WOW, someone is showing off!',
            ],
            'the maximum upper section' => [
                ['ones' => 5, 'twos' => 10, 'threes' => 15, 'fours' => 20, 'fives' => 25, 'sixes' => 30],
                "Umm!, I wasn't even sure you could score this much!",
            ],

            // Missed the bonus
            'one point short' => [
                ['ones' => 2, 'twos' => 6, 'threes' => 9, 'fours' => 12, 'fives' => 15, 'sixes' => 18],
                'You were robbed! You needed one point, anyone got one spare?',
            ],
            'missed it by a distance' => [
                ['ones' => 3, 'twos' => 6, 'threes' => 9, 'fours' => 12, 'fives' => 15, 'sixes' => 0],
                'Oh! Time to play the tiny little violin just for you!',
            ],

            // Still to play for, what the dice left need to score
            'one of every dice left is enough' => [
                ['ones' => 5, 'twos' => 10, 'threes' => 12, 'fours' => 20, 'fives' => 10],
                'Easy, one of everything left will do!',
            ],
            'one of the last dice is enough' => [
                ['ones' => 5, 'twos' => 10, 'threes' => 15, 'fours' => 20, 'fives' => 10],
                'Easy, one of the last dice please!',
            ],
            'two of every dice left is enough' => [
                ['ones' => 5, 'twos' => 8, 'threes' => 12, 'fours' => 16],
                'Looking good, two of everything left will do!',
            ],
            'two of the last dice is enough' => [
                ['ones' => 5, 'twos' => 10, 'threes' => 15, 'fours' => 20, 'fives' => 5],
                'You can still easily get the bonus, two of the last dice please!',
            ],
            'three of every dice left is enough' => [
                ['ones' => 3],
                "Looking good, you haven't messed up yet, three of everything left will do!",
            ],
            'plenty of dice left to get the bonus' => [
                ['ones' => 5],
                'You can still easily get the bonus',
            ],
            'four of every dice left is enough' => [
                ['ones' => 5, 'twos' => 6, 'threes' => 6, 'fours' => 12, 'fives' => 10],
                "Looking good, you haven't messed up yet, you can still score the bonus without a Yahtzee!",
            ],
            'four of the last dice is enough' => [
                ['ones' => 3, 'twos' => 8, 'threes' => 9, 'fours' => 12, 'fives' => 10],
                'You can still get the bonus, four of the last dice please!',
            ],
            'four of the dice left is enough' => [
                ['ones' => 4, 'twos' => 6, 'threes' => 6, 'fours' => 8],
                'You can still get the bonus, you need four of something',
            ],
            'even four of every dice left is not enough' => [
                ['ones' => 0, 'twos' => 0, 'threes' => 0, 'fours' => 0],
                "Scoring four of everything won't help you!",
            ],
        ];
    }

    private function fakeSheet(array $upper): void
    {
        $this->fakeApi([
            $this->items('/g-1') => Http::response($this->game('g-1', ['p-1' => 'Ada']), 200),
            $this->items('/g-1/data/p-1') => Http::response(['key' => 'p-1', 'value' => $this->scoreSheet($upper)], 200),
        ]);
    }

    #[DataProvider('messages')]
    public function test_the_message_reflects_the_chances_of_scoring_the_bonus(array $upper, string $message): void
    {
        $this->fakeSheet($upper);

        $this->signedIn()->get('/game/g-1/player/p-1/bonus')
            ->assertOk()
            ->assertSee($message);
    }

    #[DataProvider('messages')]
    public function test_the_public_score_sheet_gets_the_same_messages(array $upper, string $message): void
    {
        $this->fakeSheet($upper);

        $share = new ShareToken();
        $share->token = 'public-token';
        $share->game_id = 'g-1';
        $share->player_id = 'p-1';
        $share->parameters = json_encode([
            'resource_type_id' => 'rt-1',
            'resource_id' => 'r-1',
            'game_id' => 'g-1',
            'player_id' => 'p-1',
            'player_name' => 'Ada',
            'owner_bearer' => 'owner-bearer',
        ]);
        $share->save();

        $this->get('/public/game/public-token/bonus')
            ->assertOk()
            ->assertSee($message);
    }

    public function test_the_message_is_a_fragment_for_the_score_sheet_not_a_page(): void
    {
        $this->fakeSheet([]);

        $this->signedIn()->get('/game/g-1/player/p-1/bonus')
            ->assertOk()
            ->assertDontSee('<html', false)
            ->assertSee('<p class="p-2">', false);
    }

    public function test_there_is_no_message_for_a_game_or_score_sheet_that_does_not_exist(): void
    {
        $this->fakeApi([$this->items('/g-404') => Http::response(['message' => 'Not found'], 404)]);
        $this->signedIn()->get('/game/g-404/player/p-1/bonus')->assertNotFound();

        $this->fakeApi([
            $this->items('/g-1') => Http::response($this->game('g-1', ['p-1' => 'Ada']), 200),
            $this->items('/g-1/data/p-9') => Http::response(['message' => 'Not found'], 404),
        ]);
        $this->signedIn()->get('/game/g-1/player/p-9/bonus')->assertNotFound();
    }
}
