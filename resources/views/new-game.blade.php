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
        <x-offcanvas active="games" />

        <div class="col-lg-8 mx-auto p-3 py-md-5">
            <main>
                <form action="{{ route('game.create.process') }}" method="POST" class="col-12 col-md-4 col-lg-4 mx-auto p-2">

                    @csrf

                    <div class="mb-3">
                        <h2>New Game</h2>
                        <p>Select the players.</p>

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

                    <input type="hidden" name="name" value="Yahtzee game" />
                    <input type="hidden" name="description" value="Yahtzee game create via the Yahtzee app" />

                    <button type="submit" class="btn btn-primary w-100">Start Game</button>
                    @else
                    <span class="text-primary">
                        You can't start a game without players.
                    </span>
                    @endif
                </form>
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
