<!doctype html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
    <head>
        <meta charset="utf-8">
        <meta name="viewport" content="width=device-width, initial-scale=1">
        <meta name="description" content="Yahtzee Game Score by Costs to Expect">
        <meta name="author" content="Dean Blackborough">
        <title>Yahtzee Game Scorer: Home</title>
        <link rel="icon" sizes="48x48" href="{{ asset('images/favicon.ico') }}">
        <link rel="icon" type="image/png" sizes="192x192" href="{{ asset('images/favicon.png') }}">
        <link href="{{ asset('css/theme.css') }}" rel="stylesheet" />
    </head>
    <body>
        <div class="col-lg-8 mx-auto p-3 py-md-5">
            <x-layout.header />

            <nav class="nav nav-fill my-4 border-bottom border-top">
                <a class="nav-link active" href="{{ route('home') }}">Home</a>
                <a class="nav-link" href="{{ route('games') }}">Games</a>
                <a class="nav-link" href="{{ route('players') }}">Players</a>
                <a class="nav-link" href="{{ route('sign-out') }}">Sign-out</a>
            </nav>

            <main>
                @if (count($open_games) > 0)
                    <h2>Open Games</h2>
                    <p class="fs-5 col-md-8">Resume your open games...</p>

                    <ul class="list-unstyled">
                        @foreach ($open_games as $game)
                            <li>
                                <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" fill="currentColor" class="bi bi-controller" viewBox="0 0 16 16">
                                    <path d="M11.5 6.027a.5.5 0 1 1-1 0 .5.5 0 0 1 1 0zm-1.5 1.5a.5.5 0 1 0 0-1 .5.5 0 0 0 0 1zm2.5-.5a.5.5 0 1 1-1 0 .5.5 0 0 1 1 0zm-1.5 1.5a.5.5 0 1 0 0-1 .5.5 0 0 0 0 1zm-6.5-3h1v1h1v1h-1v1h-1v-1h-1v-1h1v-1z"/>
                                    <path d="M3.051 3.26a.5.5 0 0 1 .354-.613l1.932-.518a.5.5 0 0 1 .62.39c.655-.079 1.35-.117 2.043-.117.72 0 1.443.041 2.12.126a.5.5 0 0 1 .622-.399l1.932.518a.5.5 0 0 1 .306.729c.14.09.266.19.373.297.408.408.78 1.05 1.095 1.772.32.733.599 1.591.805 2.466.206.875.34 1.78.364 2.606.024.816-.059 1.602-.328 2.21a1.42 1.42 0 0 1-1.445.83c-.636-.067-1.115-.394-1.513-.773-.245-.232-.496-.526-.739-.808-.126-.148-.25-.292-.368-.423-.728-.804-1.597-1.527-3.224-1.527-1.627 0-2.496.723-3.224 1.527-.119.131-.242.275-.368.423-.243.282-.494.575-.739.808-.398.38-.877.706-1.513.773a1.42 1.42 0 0 1-1.445-.83c-.27-.608-.352-1.395-.329-2.21.024-.826.16-1.73.365-2.606.206-.875.486-1.733.805-2.466.315-.722.687-1.364 1.094-1.772a2.34 2.34 0 0 1 .433-.335.504.504 0 0 1-.028-.079zm2.036.412c-.877.185-1.469.443-1.733.708-.276.276-.587.783-.885 1.465a13.748 13.748 0 0 0-.748 2.295 12.351 12.351 0 0 0-.339 2.406c-.022.755.062 1.368.243 1.776a.42.42 0 0 0 .426.24c.327-.034.61-.199.929-.502.212-.202.4-.423.615-.674.133-.156.276-.323.44-.504C4.861 9.969 5.978 9.027 8 9.027s3.139.942 3.965 1.855c.164.181.307.348.44.504.214.251.403.472.615.674.318.303.601.468.929.503a.42.42 0 0 0 .426-.241c.18-.408.265-1.02.243-1.776a12.354 12.354 0 0 0-.339-2.406 13.753 13.753 0 0 0-.748-2.295c-.298-.682-.61-1.19-.885-1.465-.264-.265-.856-.523-1.733-.708-.85-.179-1.877-.27-2.913-.27-1.036 0-2.063.091-2.913.27z"/>
                                </svg>
                                <a href="{{ route('game') }}" class="ps-2">
                                    Yahtzee Game
                                </a>
                                <br />
                                [Players] [<a href="{{ route('add-players-to-game.create.view', ['game_id' => $game['id']]) }}">Add Players</a>]
                            </li>
                        @endforeach
                    </ul>

                    <hr class="col-12 col-md-6 mb-4">
                @endif

                <h2>New game</h2>

                <p class="fs-5 col-md-8">Start a new <a href="{{ route('game.create.view') }}">game</a></p>

                <hr class="col-12 col-md-6 mb-4">

                <div class="row g-4">
                    <div class="col-md-6">
                        <h3>Recent Games</h3>
                        <p>View your recent games, open a game to see all the statistics.</p>

                        @if (count($closed_games) > 0)
                        <ul class="list-unstyled">
                            @foreach ($closed_games as $__closed_game)
                                <li>
                                    <a href="#">
                                        Yahtzee Game
                                    </a>
                                    &nbsp; [Show players]
                                </li>
                            @endforeach
                        </ul>
                        @else
                        <p class="text-primary">You haven't finished any games yet, once you do,
                            they will show up here.
                        </p>
                        @endif
                    </div>

                    <div class="col-md-6">
                        <h3>Players</h3>
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
                            <a href="{{ route('player.create.view') }}">add</a>
                            some players before you can start a game.
                        </p>
                        @endif
                    </div>
                </div>
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
