<!doctype html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
    <head>
        <meta charset="utf-8">
        <meta name="viewport" content="width=device-width, initial-scale=1">
        <meta name="description" content="Yahtzee Game Score by Costs to Expect">
        <meta name="author" content="Dean Blackborough">
        <title>Yahtzee Game Scorer: New Game</title>
        <link href="{{ asset('css/theme.css') }}" rel="stylesheet" />
    </head>
    <body>
        <div class="col-lg-8 mx-auto p-3 py-md-5">
            <div class="header">
                <h1 class="display-1">
                    <a href="{{ route('home') }}">
                        <img src="{{ asset('images/logo.png') }}" width="64" height="64" alt="Costs to Expect Logo" title="Powered by Costs to Expect API">
                    </a>
                    Yahtzee
                </h1>
            </div>

            <nav class="nav nav-fill my-4 border-bottom border-top">
                <a class="nav-link" aria-current="page" href="#">Home</a>
                <a class="nav-link active" href="#">Games</a>
                <a class="nav-link" href="#">Players</a>
                <a class="nav-link" href="#">Sign-out</a>
            </nav>

            <main>
                <form class="col-12 col-md-4 col-lg-3 mx-auto p-2">
                    <input name="name" type="hidden" value="Yahtzee [date]" />
                    <input name="description" type="hidden" value="Yahtzee game started @ [date]" />

                    <div class="mb-3">
                        <h2>New Game</h2>
                        <p>Select the players.</p>

                        <div class="form-check">
                            <input class="form-check-input" type="checkbox" value="hash" name="player_1" id="player_1">
                            <label class="form-check-label" for="player_">
                                Player 1
                            </label>
                        </div>
                        <div class="form-check">
                            <input class="form-check-input" type="checkbox" value="hash" name="player_1" id="player_1">
                            <label class="form-check-label" for="player_">
                                Player 2
                            </label>
                        </div>
                        <div class="form-check">
                            <input class="form-check-input" type="checkbox" value="hash" name="player_1" id="player_1">
                            <label class="form-check-label" for="player_">
                                Player 3
                            </label>
                            <div id="help" class="form-text">
                                You can add <a href="#">new</a> players on the player management screen.
                            </div>
                        </div>
                    </div>

                    <a type="submit" class="btn btn-primary w-100">Start Game</a>
                </form>
            </main>
            <footer class="pt-4 my-4 text-muted border-top text-center">
                Created by <a href="https://twitter.com/DBlackborough">Dean Blackborough</a><br />
                powered by the <a href="https://api.costs-to-expect.com">Costs to Expect API</a>

                <div class="mt-3 small">
                    v0.01 - Released ##ordinal ## {{ date('Y') }}
                </div>
            </footer>
        </div>
    </body>
</html>
