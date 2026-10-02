<?php

declare(strict_types=1);

namespace Tests\Feature;

use Tests\Feature\Concerns\FakesTheApi;
use Tests\TestCase;

class LandingPageTest extends TestCase
{
    use FakesTheApi;

    public function test_guests_see_the_landing_page_with_a_way_to_sign_in_or_register(): void
    {
        $this->get('/')
            ->assertOk()
            ->assertSee('Yahtzee Game Scorer')
            ->assertSee(route('sign-in.view'), false)
            ->assertSee(route('register.view'), false);
    }

    public function test_the_landing_page_is_built_to_be_found_and_shared(): void
    {
        $this->get('/')
            ->assertOk()
            ->assertSee('<link rel="canonical" href="'.url('/').'">', false)
            ->assertSee('<meta property="og:title"', false)
            ->assertSee('<meta name="twitter:card" content="summary_large_image">', false)
            ->assertDontSee('noindex');
    }

    public function test_the_landing_page_footer_shows_the_app_version(): void
    {
        $this->get('/')
            ->assertOk()
            ->assertSee('v'.config('app.version.app'));
    }

    public function test_the_authentication_pages_are_not_indexed(): void
    {
        foreach (['/sign-in', '/register'] as $uri) {
            $this->get($uri)
                ->assertOk()
                ->assertSee('<meta name="robots" content="noindex, nofollow">', false);
        }
    }
}
