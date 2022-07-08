<!doctype html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
    <head>
        <meta charset="utf-8">
        <meta name="viewport" content="width=device-width, initial-scale=1">
        <meta name="description" content="Yahtzee Game Score by Costs to Expect">
        <meta name="author" content="Dean Blackborough">
        <title>Yahtzee Game Scorer: Add Players to Game</title>
        <link rel="icon" sizes="48x48" href="{{ asset('images/favicon.ico') }}">
        <link rel="icon" type="image/png" sizes="192x192" href="{{ asset('images/favicon.png') }}">
        <link href="{{ asset('css/theme.css') }}" rel="stylesheet" />
    </head>
    <body>
        <div class="col-lg-8 mx-auto p-3 py-md-5">
            <x-layout.header />

            <nav class="nav nav-fill my-4 border-bottom border-top">
                <a class="nav-link" href="{{ route('home') }}">Home</a>
                <a class="nav-link active" href="{{ route('games') }}">Games</a>
                <a class="nav-link" href="{{ route('players') }}">Players</a>
                <a class="nav-link" href="{{ route('sign-out') }}">Sign-out</a>
            </nav>

            <main>
                <form action="{{ route('add-players-to-game.create.process', ['game_id' => $game_id]) }}" method="POST" class="col-12 col-md-4 col-lg-4 mx-auto p-2">

                    @csrf

                    <div class="mb-3">
                        <h2>Add Players</h2>
                        <p>Select any additional players to add to the game.</p>

                        @if (count($game_players) > 0)
                            <h3>Current Players</h3>

                            <ul class="list-unstyled">
                                @foreach ($game_players as $__game_player)
                                <li>
                                    <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" fill="currentColor" class="bi bi-person-fill" viewBox="0 0 16 16">
                                        <path d="M3 14s-1 0-1-1 1-4 6-4 6 3 6 4-1 1-1 1H3zm5-6a3 3 0 1 0 0-6 3 3 0 0 0 0 6z"/>
                                    </svg>
                                    {{ $__game_player }}
                                </li>
                                @endforeach
                            </ul>
                        @endif

                        <h3>New Players</h3>

                        @foreach ($players as $__player)
                        <div class="form-check">
                            <input class="form-check-input @if($errors !== null && array_key_exists('players', $errors)) is-invalid @endif" type="checkbox" value="{{ $__player['id'] }}" name="players[]" id="players_{{ $__player['id'] }}" />
                            <label class="form-check-label" for="players_{{ $__player['id'] }}">
                                {{ $__player['name'] }}
                            </label>
                            @if($errors !== null && array_key_exists('players', $errors))
                                <div class="invalid-feedback">
                                    @foreach ($errors['players']['errors'] as $error)
                                        {{ $error }}
                                    @endforeach
                                </div>
                            @endif
                        </div>
                        @endforeach
                    </div>

                    @if (count($players) > 0)
                    <button type="submit" class="btn btn-primary w-100">Add Players</button>
                    @else
                    <span class="text-primary">
                        There are no more players to add to the game, check the
                        <a href="{{ route('players') }}">players</a> list, you might need to add one.
                    </span>
                    @endif
                </form>
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
