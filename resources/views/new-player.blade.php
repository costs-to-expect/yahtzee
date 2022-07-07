<!doctype html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
    <head>
        <meta charset="utf-8">
        <meta name="viewport" content="width=device-width, initial-scale=1">
        <meta name="description" content="Yahtzee Game Score by Costs to Expect">
        <meta name="author" content="Dean Blackborough">
        <title>Yahtzee Game Scorer: New Player</title>
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
                <form class="col-12 col-md-4 col-lg-4 mx-auto p-2">
                    <div class="mb-3">
                        <h2>New Player</h2>
                        <p>Add a new player, they will be selectable as a player in all new games.</p>

                        @csrf

                        <div class="mt-3 mb-3">
                            <label for="name" class="form-label">Name</label>
                            <input type="text" name="name" class="form-control @if($errors !== null && array_key_exists('name', $errors)) is-invalid @endif" id="name" aria-describedby="name-help" required value="{{ old('name') }}" />
                            <div id="name-help" class="form-text">Please enter the name of the new player.</div>
                            @if($errors !== null && array_key_exists('name', $errors))
                                <div class="invalid-feedback">
                                    @foreach ($errors['name'] as $error)
                                        {{ $error }}
                                    @endforeach
                                </div>
                            @endif
                        </div>
                    </div>

                    <input type="hidden" name="description" value="{{ old('description', 'New player - Added via the Yahtzee App') }}" />
                    <button type="submit" class="btn btn-primary w-100">Add Player</button>
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
