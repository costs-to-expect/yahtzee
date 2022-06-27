<!doctype html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
    <head>
        <meta charset="utf-8">
        <meta name="viewport" content="width=device-width, initial-scale=1">
        <meta name="description" content="Yahtzee Game Score by Costs to Expect">
        <meta name="author" content="Dean Blackborough">
        <title>Yahtzee Game Scorer: Home</title>
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
                <h2>Open Games</h2>
                <p class="fs-5 col-md-8">Resume your open games...</p>

                <hr class="col-12 col-md-6 mb-4">

                <div class="row g-4">
                    <div class="col-md-6">
                        <h3>Recent games</h3>
                        <p>View your recent games, open a game to see all the statistics.</p>
                        <p><a type="submit" class="btn btn-primary">New Game</a></p>
                        <ul class="icon-list ps-0">
                            <li class="d-flex align-items-start mb-1">Game 1</li>
                            <li class="d-flex align-items-start mb-1">Game 2</li>
                            <li class="d-flex align-items-start mb-1">Game 3</li>
                            <li class="d-flex align-items-start mb-1">Game 4</li>
                            <li class="d-flex align-items-start mb-1">Game 5</li>
                        </ul>
                    </div>

                    <div class="col-md-6">
                        <h3>Players</h3>
                        <p>Select a player for a detailed breakdown of their Yahtzee games.</p>
                        <p><a type="submit" class="btn btn-primary">New Player</a></p>
                        <ul class="icon-list ps-0">
                            <li class="d-flex align-items-start mb-1">Player 1</li>
                            <li class="d-flex align-items-start mb-1">Player 2</li>
                            <li class="d-flex align-items-start mb-1">Player 3</li>
                            <li class="d-flex align-items-start mb-1">Player 4</li>
                            <li class="d-flex align-items-start mb-1">Player 5</li>
                        </ul>
                    </div>
                </div>
            </main>
            <footer class="pt-5 my-5 text-muted border-top text-center">
                Created by <a href="https://twitter.com/DBlackborough">Dean Blackborough</a> &
                powered by the <a href="https://api.costs-to-expect.com">Costs to Expect API</a>
            </footer>
        </div>
    </body>
</html>