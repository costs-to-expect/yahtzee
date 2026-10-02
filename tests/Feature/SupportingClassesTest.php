<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Models\ShareToken;
use App\Notifications\ApiError;
use App\Notifications\Bye;
use App\Notifications\ByeBye;
use App\Notifications\Registered;
use App\View\Components\Toast;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class SupportingClassesTest extends TestCase
{
    use RefreshDatabase;

    public function test_share_tokens_are_grouped_by_game_and_player(): void
    {
        foreach ([['t-1', 'g-1', 'p-1'], ['t-2', 'g-1', 'p-2'], ['t-3', 'g-2', 'p-1']] as [$token, $game, $player]) {
            $share = new ShareToken();
            $share->token = $token;
            $share->game_id = $game;
            $share->player_id = $player;
            $share->parameters = '{}';
            $share->save();
        }

        self::assertSame(
            ['g-1' => ['p-1' => 't-1', 'p-2' => 't-2'], 'g-2' => ['p-1' => 't-3']],
            (new ShareToken())->getShareTokens()
        );
    }

    public function test_no_share_tokens_is_an_empty_list(): void
    {
        self::assertSame([], (new ShareToken())->getShareTokens());
    }

    public function test_the_toast_has_a_message_for_every_celebration_picked_from_its_own_list(): void
    {
        $messages = (new Toast())->messages;

        $view = $this->blade('<x-toast />');

        foreach (['yahtzee', 'yahtzee_scratch', 'yahtzee_bonus_one', 'yahtzee_bonus_two', 'yahtzee_bonus_three', 'done'] as $toast) {
            $view->assertSee('id="toast_'.$toast.'"', false);
        }

        $view->assertSee('id="final-score"', false);

        foreach (['toast_yahtzee', 'toast_yahtzee_scratch', 'toast_yahtzee_bonus_one', 'toast_yahtzee_bonus_two', 'toast_yahtzee_bonus_three'] as $key) {
            $shown = array_filter(
                $messages[$key],
                fn (array $message) => str_contains((string) $view, e($message['heading'])) && str_contains((string) $view, e($message['message']))
            );

            self::assertNotEmpty($shown, "{$key} should show one of its own messages");
        }
    }

    public function test_the_footer_shows_the_version_and_how_to_get_support(): void
    {
        $this->blade('<x-footer />')
            ->assertSee('v'.config('app.version.app').' - '.config('app.version.date'))
            ->assertSee('support@costs-to-expect.com');
    }

    public function test_the_navigation_marks_the_current_page(): void
    {
        $html = (string) $this->blade('<x-offcanvas active="games" />');

        self::assertMatchesRegularExpression('/<a class="nav-link\s+active\s*"\s+aria-current="page"\s+href="[^"]*\/games">Games<\/a>/', $html);
        self::assertDoesNotMatchRegularExpression('/nav-link\s+active\s*"[^>]*>Home</', $html);
    }

    public function test_guests_see_the_navigation_brand_linking_to_the_landing_page(): void
    {
        $this->blade('<x-offcanvas active="home" />')->assertSee('<a class="navbar-brand" href="/">', false);
    }

    public function test_the_registered_email_invites_the_player_to_sign_in(): void
    {
        $mail = (new Registered())->toMail(new \stdClass());

        self::assertSame('Yahtzee Game Scorer: Registered', $mail->subject);
        self::assertSame('Sign-in', $mail->actionText);
        self::assertSame(url('/sign-in'), $mail->actionUrl);
    }

    public function test_the_goodbye_emails_say_which_account_was_deleted(): void
    {
        $yahtzee = (new Bye())->toMail(new \stdClass());
        $everything = (new ByeBye())->toMail(new \stdClass());

        self::assertSame('Yahtzee Game Scorer: Bye', $yahtzee->subject);
        self::assertContains('Your Yahtzee account has been deleted.', $yahtzee->introLines);

        self::assertSame('Yahtzee Game Scorer: Bye', $everything->subject);
        self::assertContains('Your account has been deleted.', $everything->introLines);
    }

    public function test_the_error_email_carries_the_error_and_the_message(): void
    {
        $notification = new ApiError('Unable to log the score', 'The log is down');
        $mail = $notification->toMail(new \stdClass());

        self::assertSame('Yahtzee Game Scorer: Error', $mail->subject);
        self::assertContains('Error: Unable to log the score', $mail->introLines);
        self::assertContains('Message: The log is down', $mail->introLines);
        self::assertSame(['mail'], $notification->via(new \stdClass()));
        self::assertSame(['error' => 'Unable to log the score', 'message' => 'The log is down'], $notification->toArray(new \stdClass()));
    }

    public function test_a_page_that_does_not_exist_is_a_plain_not_found_page(): void
    {
        $this->get('/not-a-page')
            ->assertNotFound()
            ->assertSee('Not Found');
    }
}
