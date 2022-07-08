<!doctype html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
    <head>
        <meta charset="utf-8">
        <meta name="viewport" content="width=device-width, initial-scale=1">
        <meta name="description" content="Yahtzee Game Score by Costs to Expect">
        <meta name="author" content="Dean Blackborough">
        <title>Yahtzee Game Scorer: Players</title>
        <link rel="icon" sizes="48x48" href="{{ asset('images/favicon.ico') }}">
        <link rel="icon" type="image/png" sizes="192x192" href="{{ asset('images/favicon.png') }}">
        <link href="{{ asset('css/theme.css') }}" rel="stylesheet" />
    </head>
    <body>
        <div class="col-lg-8 mx-auto p-3 py-md-5">
            <x-layout.header />

            <nav class="nav nav-fill my-4 border-bottom border-top">
                <a class="nav-link" href="{{ route('home') }}">Home</a>
                <a class="nav-link" href="#">Games</a>
                <a class="nav-link active" href="{{ route('players') }}">Players</a>
                <a class="nav-link" href="{{ route('sign-out') }}">Sign-out</a>
            </nav>

            <main>
                <h2>Players</h2>

                <p class="lead">Add a new <a href="{{ route('player.create.view') }}">player</a>.</p>

                <p>Select a player for a detailed breakdown of their Yahtzee games.</p>

                @if (count($players) > 0)
                    <ul class="icon-list ps-0">
                        @foreach ($players as $__player)
                            <li class="d-flex align-items-start mb-1">
                                {{ $__player['name'] }} - <a href="">[Statistics]</a>
                            </li>
                        @endforeach
                    </ul>
                @else
                    <p class="text-primary">You haven't added any players yet, you need to
                        <a href="#">add</a>
                        some players before you can start a game.
                    </p>
                @endif

            </main>
            <footer class="pt-4 my-4 text-muted border-top text-center">
                Created by <a href="https://twitter.com/DBlackborough">Dean Blackborough</a><br />
                powered by the <a href="https://api.costs-to-expect.com">Costs to Expect API</a>

                <div class="mt-3 small">
                    v{{ $config['version'] }} - Released {{ $config['release_date'] }}
                </div>
            </footer>
        </div>
    </body>
</html>
