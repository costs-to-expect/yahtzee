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
        <x-offcanvas active="players" />

        <div class="col-lg-8 mx-auto p-3 py-md-5">
            <main>
                <h2>Players</h2>

                <p class="lead">Add a new <a href="{{ route('player.create.view') }}">player</a>.</p>

                <p>Select a player for a detailed breakdown of their Yahtzee games.</p>

                @if (count($players) > 0)
                    <ul class="list-unstyled">
                        @foreach ($players as $__player)
                            <li>
                                <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" fill="currentColor" class="bi bi-person-fill" viewBox="0 0 16 16">
                                    <path d="M3 14s-1 0-1-1 1-4 6-4 6 3 6 4-1 1-1 1H3zm5-6a3 3 0 1 0 0-6 3 3 0 0 0 0 6z"/>
                                </svg>
                                <a href="" class="ps-2">{{ $__player['name'] }}</a>
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
            <footer class="container py-5">
                <div class="row">
                    <div class="col-12 col-md">
                        <small class="d-block mb-3 text-muted">&copy; 2022</small>
                        <small class="d-block mb-3 text-muted">v{{ $config['version'] }} - Released {{ $config['release_date'] }}</small>
                    </div>
                    <div class="col-6 col-md">
                        <h5>Game Scorers</h5>
                        <ul class="list-unstyled text-small">
                            <li><a class="link-secondary" href="https://yahtzee.game-scorer.com">Yahtzee</a></li>
                            <li><a class="link-secondary" href="#">Yatzy (Coming soon)</a></li>
                        </ul>
                    </div>
                    <div class="col-6 col-md">
                        <h5>Costs to Expect</h5>
                        <ul class="list-unstyled text-small">
                            <li><a class="link-secondary" href="https://api.costs-to-expect.com">The API</a></li>
                            <li><a class="link-secondary" href="https://github.com/costs-to-expect">GitHub</a></li>
                            <li><a class="link-secondary" href="https://www.costs-to-expect.com">Social Experiment</a></li>
                            <li><a class="link-secondary" href="https://www.deanblackborough.com">Dean Blackborough</a></li>
                        </ul>
                    </div>
                </div>
            </footer>
        </div>
        <script src="{{ asset('node_modules/bootstrap/dist/js/bootstrap.js') }}" defer></script>
    </body>
</html>
