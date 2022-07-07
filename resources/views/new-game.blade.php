<!doctype html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
    <head>
        <meta charset="utf-8">
        <meta name="viewport" content="width=device-width, initial-scale=1">
        <meta name="description" content="Yahtzee Game Score by Costs to Expect">
        <meta name="author" content="Dean Blackborough">
        <title>Yahtzee Game Scorer: New Game</title>
        <link rel="icon" sizes="48x48" href="{{ asset('images/favicon.ico') }}">
        <link rel="icon" type="image/png" sizes="192x192" href="{{ asset('images/favicon.png') }}">
        <link href="{{ asset('css/theme.css') }}" rel="stylesheet" />
    </head>
    <body>
        <div class="col-lg-8 mx-auto p-3 py-md-5">
            <x-layout.header />

            <nav class="nav nav-fill my-4 border-bottom border-top">
                <a class="nav-link" href="{{ route('home') }}">Home</a>
                <a class="nav-link active" href="#">Games</a>
                <a class="nav-link" href="#">Players</a>
                <a class="nav-link" href="{{ route('sign-out') }}">Sign-out</a>
            </nav>

            <main>
                <form class="col-12 col-md-4 col-lg-3 mx-auto p-2">
                    <div class="mb-3">
                        <h2>New Game</h2>
                        <p>Select the players.</p>

                        <div class="form-check">
                            <input class="form-check-input" type="checkbox" value="hash" name="category_id_for_player_1" id="category_id_for_player_1" />
                            <label class="form-check-label" for="category_id_for_player_1">
                                Name of Player 1
                            </label>
                        </div>
                        <div class="form-check">
                            <input class="form-check-input" type="checkbox" value="hash" name="category_id_for_player_2" id="category_id_for_player_2" />
                            <label class="form-check-label" for="category_id_for_player_2">
                                Name of Player 2
                            </label>
                        </div>
                        <div class="form-check">
                            <input class="form-check-input" type="checkbox" value="hash" name="category_id_for_player_3" id="category_id_for_player_3">
                            <label class="form-check-label" for="category_id_for_player_3">
                                Name of Player 3
                            </label>
                            <div id="help" class="form-text">
                                You can add <a href="#">new</a> players on the player management screen.
                            </div>
                        </div>
                    </div>

                    <button type="submit" class="btn btn-primary w-100">Start Game</button>
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
