@php
    use App\Support\GameBoard;

    $game = config('app.game');
    $has_players = count($players) > 0;
    $player_errors = $errors['players']['errors'] ?? [];

    // First visit: nobody has been added yet. A failed start (a name that was taken) shows the form again.
    $state = match (true) {
        ! $has_players => 'first',
        count($boards) > 0 => 'busy',
        $player_errors !== [] && count($boards) === 0 => 'first',
        default => 'idle',
    };

    $board = $boards[$selected] ?? null;
    $standings = $board['players'] ?? [];
    $leader = $standings[0] ?? null;
    $gap = count($standings) > 1 ? $standings[0]['score'] - $standings[1]['score'] : 0;
    $unfinished = collect($standings)->contains(fn ($player) => $player['turns'] < $game['turns']);

    // The players the next game starts with: whoever played the last game
    $picked = array_column($last_game['players'] ?? [], 'id');
@endphp
<x-layouts.app title="{{ $game['name'] }} Game Scorer: Home" active="home">
    <div @class(['grid gap-6', 'lg:grid-cols-5' => $state !== 'first'])>

        <div @class(['lg:col-span-3' => $state !== 'first', 'mx-auto w-full max-w-xl' => $state === 'first'])>

            @if ($state === 'busy')
                {{-- ============ Game night ============ --}}
                <section aria-labelledby="busy-heading">
                    <div class="card">
                        <div class="flex flex-wrap items-center justify-between gap-3">
                            <p class="inline-flex items-center gap-2.5 text-sm font-semibold text-emerald-700">
                                <span class="relative flex h-2.5 w-2.5" aria-hidden="true"><span class="absolute inline-flex h-full w-full rounded-full bg-emerald-400 opacity-75 motion-safe:animate-ping"></span><span class="relative inline-flex h-2.5 w-2.5 rounded-full bg-emerald-500"></span></span>
                                <span>Game in progress @if ($board['since']) &middot; {{ $board['since'] }}@endif</span>
                            </p>

                            @if (count($boards) > 1)
                                <nav class="flex gap-1 rounded-full bg-stone-100 p-1 text-sm font-bold" aria-label="Open games">
                                    @foreach ($boards as $position => $open)
                                        <a href="{{ route('home', ['game' => $open['id']]) }}" @if ($position === $selected) aria-current="true" @endif
                                           @class([
                                               'inline-flex min-h-10 items-center rounded-full px-4 focus-visible:outline-2 focus-visible:outline-brand-600',
                                               'bg-white text-stone-900 shadow-sm' => $position === $selected,
                                               'text-stone-600 hover:text-stone-900' => $position !== $selected,
                                           ])>{{ $open['when'] ?? 'Game '.($position + 1) }}</a>
                                    @endforeach
                                </nav>
                            @endif
                        </div>

                        <h1 id="busy-heading" class="mt-4 text-3xl font-extrabold tracking-tight sm:text-4xl">Who&rsquo;s scoring?</h1>
                        <p class="mt-1.5 text-stone-600">
                            Tap a player to carry on scoring.
                            @if ($leader && $leader['leader'])
                                {{ $leader['name'] }} is ahead by {{ $gap }}.
                            @endif
                        </p>

                        <div @class(['mt-6 grid gap-3', 'sm:grid-cols-2' => in_array(count($standings), [2, 4], true), 'sm:grid-cols-3' => ! in_array(count($standings), [2, 4], true)])>
                            @foreach ($standings as $player)
                                <a href="{{ route('game.score-sheet', ['game_id' => $board['id'], 'player_id' => $player['id']]) }}"
                                   @class([
                                       'group relative grid grid-cols-[auto_1fr_auto_auto] items-center gap-x-3.5 rounded-2xl p-4 shadow-card transition hover:shadow-lift focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-brand-600 motion-reduce:transition-none sm:flex sm:flex-col sm:gap-x-0 sm:px-4 sm:py-6 sm:text-center',
                                       'bg-gradient-to-b from-white to-brand-50 ring-2 ring-brand-300 hover:ring-brand-400' => $player['leader'],
                                       'bg-white ring-1 ring-stone-200 hover:ring-brand-300' => ! $player['leader'],
                                   ])>
                                    <span class="relative row-span-2 h-14 w-14 shrink-0 sm:order-1 sm:row-span-1 sm:h-19 sm:w-19">
                                        <x-ring :fraction="$player['progress']" class="absolute inset-0 h-full w-full" />
                                        <x-avatar :name="$player['name']" :index="$player['tone']" class="absolute inset-1.25 text-xl sm:inset-1.75 sm:text-2xl" />
                                        @if ($player['leader'])
                                            <span class="absolute -right-1.5 -top-1.5 flex h-6 w-6 items-center justify-center rounded-full bg-amber-400 text-amber-950 ring-2 ring-white"><x-icon name="crown" class="h-3.5 w-3.5" /><span class="sr-only">Leading</span></span>
                                        @endif
                                    </span>
                                    <span class="col-start-2 truncate text-base font-bold sm:order-2 sm:mt-3 sm:text-lg">{{ $player['name'] }}</span>
                                    <span class="col-start-2 text-xs text-stone-600 sm:order-4 sm:mt-1">{{ $player['turns'] >= $game['turns'] ? 'All '.$game['turns'].' turns played' : $player['turns'].' of '.$game['turns'].' turns' }}</span>
                                    <span class="col-start-3 row-span-2 row-start-1 text-3xl font-extrabold tabular-nums sm:order-3 sm:mt-0.5 sm:text-4xl">{{ $player['score'] }}</span>
                                    <span class="col-start-4 row-span-2 row-start-1 text-stone-400 sm:hidden"><x-icon name="chevron-right" class="h-5 w-5" /></span>
                                    <span class="mt-5 hidden w-full rounded-xl bg-brand-50 py-2.5 text-sm font-bold text-brand-800 transition group-hover:bg-brand-700 group-hover:text-white sm:order-5 sm:block motion-reduce:transition-none">{{ $game['action'] }}</span>
                                </a>
                            @endforeach
                        </div>

                        <div class="mt-6 flex flex-wrap items-center gap-x-1 gap-y-3 border-t border-stone-100 pt-4 text-sm">
                            <button type="button" data-dialog-open="share-dialog" class="btn-link"><x-icon name="share" class="h-5 w-5" />Share links</button>
                            <a href="{{ route('game.add-players.view', ['game_id' => $board['id']]) }}" class="btn-link"><x-icon name="plus" class="h-5 w-5" />Add player</a>

                            <div class="flex w-full items-center gap-2 sm:ml-auto sm:w-auto">
                                <button type="button" data-dialog-open="finish-dialog" class="btn btn-secondary min-h-11 flex-1 sm:flex-none">Finish game</button>

                                <details data-menu class="relative">
                                    <summary class="flex h-11 w-11 cursor-pointer list-none items-center justify-center rounded-xl text-stone-500 hover:bg-stone-100 hover:text-stone-800 focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-brand-600 [&::-webkit-details-marker]:hidden" aria-label="More game options"><x-icon name="more" class="h-6 w-6" /></summary>
                                    <div class="absolute bottom-full right-0 z-10 mb-2 w-56 rounded-2xl bg-white p-1.5 shadow-lift ring-1 ring-stone-200">
                                        <button type="button" data-dialog-open="remove-dialog" class="flex min-h-11 w-full items-center gap-2.5 rounded-xl px-3 text-left font-semibold text-stone-800 hover:bg-stone-100"><x-icon name="user-minus" class="h-5 w-5" />Remove a player&hellip;</button>
                                        <button type="button" data-dialog-open="delete-dialog" class="flex min-h-11 w-full items-center gap-2.5 rounded-xl px-3 text-left font-semibold text-red-700 hover:bg-red-50"><x-icon name="trash" class="h-5 w-5" />Delete game&hellip;</button>
                                    </div>
                                </details>
                            </div>
                        </div>
                    </div>
                </section>

            @elseif ($state === 'idle')
                {{-- ============ No game running ============ --}}
                <section aria-labelledby="idle-heading">
                    <div class="card text-center sm:p-10">
                        <div class="flex justify-center"><x-art /></div>
                        <h1 id="idle-heading" class="mt-4 text-3xl font-extrabold tracking-tight sm:text-4xl">{{ $last_game ? 'Ready for another game?' : 'Ready for the first game?' }}</h1>

                        @if ($last_game)
                            @php($last_names = array_column($last_game['players'], 'name'))
                            @php($last_when = $last_game['when'] === null ? null : (in_array($last_game['when'], ['Today', 'Yesterday'], true) ? strtolower($last_game['when']) : 'on '.$last_game['when']))
                            <p class="mt-1.5 text-stone-600">Last played @if ($last_when){{ $last_when }} @endif with {{ GameBoard::names($last_names) }}.</p>

                            <form action="{{ route('game.create.action') }}" method="POST">
                                @csrf
                                <input type="hidden" name="name" value="{{ $game['game_name'] }}">
                                <input type="hidden" name="description" value="{{ $game['game_description'] }}">
                                @foreach ($last_game['players'] as $last_player)
                                    <input type="hidden" name="players[]" value="{{ $last_player['id'] }}">
                                @endforeach
                                <button type="submit" class="btn btn-primary btn-block mt-6 py-4 text-base sm:w-auto sm:min-w-80">Play again with {{ GameBoard::ampersands($last_names) }}</button>
                            </form>
                            <p class="mt-3 text-sm text-stone-600">Same players, fresh score sheets.</p>
                        @else
                            <p class="mt-1.5 text-stone-600">Choose who is playing in the next game and start scoring.</p>
                        @endif
                    </div>
                </section>

            @else
                {{-- ============ First visit ============ --}}
                <section aria-labelledby="first-heading">
                    <div class="card sm:p-10">
                        <div class="flex justify-center"><x-art /></div>
                        <h1 id="first-heading" class="mt-4 text-center text-3xl font-extrabold tracking-tight sm:text-4xl">Let&rsquo;s get started!</h1>
                        <p class="mt-1.5 text-center text-stone-600">Enter each of your players on a new line in the box below. You can add more players whenever you like.</p>

                        <form action="{{ route('start') }}" method="POST" class="mt-6">
                            @csrf
                            <label for="players" class="sr-only">Players</label>
                            <textarea name="players" id="players" rows="4" required
                                      @class(['form-control', 'form-control-error' => $player_errors !== []])
                                      placeholder="Ada&#10;Ben&#10;Cleo"
                                      aria-describedby="players-help @if ($player_errors !== []) players-error @endif"
                                      @if ($player_errors !== []) aria-invalid="true" @endif>{{ old('players') }}</textarea>
                            <p id="players-help" class="form-help">Please enter all players, one name per line.</p>
                            @if ($player_errors !== [])
                                <p id="players-error" class="form-error"><x-icon name="alert" class="mt-0.5 h-4 w-4 shrink-0" /><span>{{ implode(' ', $player_errors) }}</span></p>
                            @endif
                            <button type="submit" class="btn btn-primary btn-block mt-4 py-4 text-base">Start the first game</button>
                        </form>
                    </div>
                </section>
            @endif
        </div>

        @if ($state !== 'first')
            <aside class="space-y-6 lg:col-span-2">
                <section class="card p-5 sm:p-5" aria-labelledby="next-heading">
                    <h2 id="next-heading" class="text-lg font-extrabold tracking-tight">Next game</h2>
                    <p class="mt-0.5 text-sm text-stone-600">Pick who&rsquo;s playing.</p>

                    <form action="{{ route('game.create.action') }}" method="POST"
                          data-picker data-picker-min="{{ $game['min_players'] }}" data-picker-label="Start game with">
                        @csrf
                        <input type="hidden" name="name" value="{{ $game['game_name'] }}">
                        <input type="hidden" name="description" value="{{ $game['game_description'] }}">

                        <fieldset class="mt-4">
                            <legend class="sr-only">Players in the next game</legend>
                            <div class="flex flex-wrap gap-2">
                                @foreach ($players as $player)
                                    <label class="chip has-checked:bg-brand-700 has-checked:text-white has-checked:ring-brand-700 has-checked:hover:bg-brand-800 has-focus-visible:outline-2 has-focus-visible:outline-offset-2 has-focus-visible:outline-brand-600">
                                        <input type="checkbox" name="players[]" value="{{ $player['id'] }}" class="peer sr-only" @checked(in_array($player['id'], $picked, true))>
                                        {{-- A chosen player's avatar becomes a tick, so the choice never relies on colour alone --}}
                                        <span class="hidden h-7 w-7 shrink-0 items-center justify-center rounded-full bg-white text-brand-700 peer-checked:inline-flex"><x-icon name="check" class="h-4 w-4" stroke-width="3" /></span>
                                        <x-avatar :name="$player['name']" :index="$tones[$player['id']] ?? 0" class="h-7 w-7 text-xs peer-checked:hidden" />
                                        {{ $player['name'] }}
                                    </label>
                                @endforeach
                                <a href="{{ route('player.create.view') }}" class="inline-flex min-h-11 items-center gap-1.5 rounded-full border border-dashed border-stone-400 px-4 text-sm font-bold text-brand-700 hover:bg-brand-50 focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-brand-600"><x-icon name="plus" class="h-4 w-4" />New player</a>
                            </div>
                        </fieldset>

                        @if ($player_errors !== [])
                            <p class="form-error"><x-icon name="alert" class="mt-0.5 h-4 w-4 shrink-0" /><span>{{ implode(' ', $player_errors) }}</span></p>
                        @endif

                        @php($count = count($picked))
                        <button type="submit" data-picker-submit class="btn btn-primary btn-block mt-5" @disabled($count < $game['min_players'])>
                            @if ($count < $game['min_players'])
                                {{ $game['min_players'] === 1 ? 'Choose the players to start' : 'Choose at least '.$game['min_players'].' players' }}
                            @else
                                Start game with {{ $count }} {{ $count === 1 ? 'player' : 'players' }}
                            @endif
                        </button>
                    </form>
                </section>

                <section aria-labelledby="history-heading">
                    <div class="flex items-baseline justify-between">
                        <h2 id="history-heading" class="text-lg font-extrabold tracking-tight">Last games</h2>
                        <a href="{{ route('games') }}" class="-mr-2 inline-flex min-h-11 items-center rounded-xl px-2 text-sm font-bold text-brand-700 hover:text-brand-900">All games</a>
                    </div>

                    @if (count($history) > 0)
                        <ul class="card-list mt-3">
                            @foreach ($history as $item)
                                <li>
                                    <a href="{{ route('game.show', ['game_id' => $item['id']]) }}" class="flex items-center gap-3 px-4 py-3.5 hover:bg-stone-50 focus-visible:bg-stone-50 focus-visible:outline-2 focus-visible:-outline-offset-2 focus-visible:outline-brand-600">
                                        <span class="inline-flex h-9 w-9 shrink-0 items-center justify-center rounded-full bg-amber-100 text-amber-700"><x-icon name="trophy" class="h-5 w-5" /></span>
                                        <span class="min-w-0 flex-1">
                                            <span class="block truncate text-sm font-bold">{{ $item['winner'] }} <span class="font-medium text-stone-600">won with</span> {{ $item['score'] }}</span>
                                            <span class="block truncate text-xs text-stone-600">@if ($item['when']){{ $item['when'] }} &middot; @endif{{ $item['others'] !== '' ? $item['others'] : 'Played on their own' }}</span>
                                        </span>
                                        <span class="text-stone-400"><x-icon name="chevron-right" class="h-5 w-5" /></span>
                                    </a>
                                </li>
                            @endforeach
                        </ul>
                    @else
                        <div class="card-list mt-3 px-4 py-5 text-sm text-stone-600">
                            <p class="font-bold text-stone-800">No finished games yet.</p>
                            <p class="mt-1">As soon as you finish a game it shows up here, with who won.</p>
                        </div>
                    @endif
                </section>
            </aside>
        @endif
    </div>

    @if ($state === 'busy')
        {{-- ============ Dialogs for the open game ============ --}}
        <x-sheet id="share-dialog" title="Share score sheets" subtitle="Each player has their own link and anyone who has it can score for them, so only send it to that player.">
            <ul class="divide-y divide-stone-100 rounded-2xl ring-1 ring-stone-200">
                @foreach ($standings as $player)
                    @php($token = $share_tokens[$board['id']][$player['id']] ?? null)
                    <li class="flex items-center gap-3 p-3">
                        <x-avatar :name="$player['name']" :index="$player['tone']" class="h-10 w-10 text-base" />
                        <span class="min-w-0 flex-1 truncate font-bold">{{ $player['name'] }}</span>
                        @if ($token)
                            <button type="button" data-copy="{{ route('public.score-sheet', ['token' => $token]) }}" @if ($loop->first) data-autofocus @endif
                                    class="inline-flex min-h-11 min-w-32 items-center justify-center gap-1.5 rounded-xl bg-brand-50 px-3.5 py-2.5 text-sm font-bold text-brand-800 hover:bg-brand-100 focus-visible:outline-2 focus-visible:outline-brand-600">
                                <x-icon name="link" class="h-4 w-4" /><span data-copy-label>Copy link</span>
                            </button>
                        @else
                            <span class="text-xs text-stone-600">No link</span>
                        @endif
                    </li>
                @endforeach
            </ul>
            <button type="button" data-dialog-close class="btn btn-quiet btn-block mt-4">Done</button>
        </x-sheet>

        <x-sheet id="finish-dialog" title="Finish this game?"
                 :subtitle="($leader && $leader['leader'] ? $leader['name'].' is in the lead with '.$leader['score'].'. ' : '').'Scores are locked once the game is finished.'">
            <ul class="divide-y divide-stone-100 rounded-2xl ring-1 ring-stone-200">
                @foreach ($standings as $rank => $player)
                    <li class="flex items-center gap-3 px-3 py-2.5">
                        <span class="w-5 text-center text-sm font-bold text-stone-500">{{ $rank + 1 }}</span>
                        <x-avatar :name="$player['name']" :index="$player['tone']" class="h-8 w-8 text-sm" />
                        <span class="flex-1 font-semibold">{{ $player['name'] }}</span>
                        <span class="text-lg font-extrabold tabular-nums">{{ $player['score'] }}</span>
                    </li>
                @endforeach
            </ul>

            @if ($unfinished)
                <x-alert type="warning" class="mt-3">Not everyone has scored all {{ $game['turns'] }} turns yet.</x-alert>
            @endif

            <div class="mt-5 grid gap-2.5">
                <form action="{{ route('game.complete.action', ['game_id' => $board['id']]) }}" method="POST">
                    @csrf
                    <button type="submit" class="btn btn-primary btn-block">Finish game</button>
                </form>
                <form action="{{ route('game.complete.play-again.action', ['game_id' => $board['id']]) }}" method="POST">
                    @csrf
                    <button type="submit" class="btn btn-soft btn-block">Finish and play again</button>
                </form>
                <button type="button" data-dialog-close data-autofocus class="btn btn-quiet btn-block">Keep playing</button>
            </div>
        </x-sheet>

        <x-sheet id="remove-dialog" title="Remove a player" subtitle="Their score sheet and share link are removed straight away, it can&rsquo;t be undone.">
            <ul class="divide-y divide-stone-100 rounded-2xl ring-1 ring-stone-200">
                @foreach ($standings as $player)
                    <li class="flex items-center gap-3 p-3">
                        <x-avatar :name="$player['name']" :index="$player['tone']" class="h-10 w-10 text-base" />
                        <span class="min-w-0 flex-1 truncate font-bold">{{ $player['name'] }}</span>
                        <form action="{{ route('game.player.delete', ['game_id' => $board['id'], 'player_id' => $player['id']]) }}" method="POST">
                            @csrf
                            <button type="submit" class="btn btn-danger-soft min-h-11 px-4 py-2" aria-label="Remove {{ $player['name'] }} from the game">Remove</button>
                        </form>
                    </li>
                @endforeach
            </ul>
            <button type="button" data-dialog-close data-autofocus class="btn btn-quiet btn-block mt-4">Cancel</button>
        </x-sheet>

        <x-sheet id="delete-dialog" title="Delete this game?" subtitle="This removes the game and every score in it. It can&rsquo;t be undone.">
            <div class="grid gap-2.5">
                <form action="{{ route('game.delete.action', ['game_id' => $board['id']]) }}" method="POST">
                    @csrf
                    <button type="submit" class="btn btn-danger btn-block">Delete game</button>
                </form>
                <button type="button" data-dialog-close data-autofocus class="btn btn-quiet btn-block">Cancel</button>
            </div>
        </x-sheet>
    @endif
</x-layouts.app>
