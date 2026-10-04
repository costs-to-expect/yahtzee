<x-layouts.app :title="config('app.game.name').' Game Scorer: Stats'" active="stats">
    <div>
        <h1 class="text-3xl font-extrabold tracking-tight sm:text-4xl">Stats</h1>
        <p class="mt-1 text-stone-600">
            @if ($games > 0)
                {{ $games }} {{ $games === 1 ? 'game' : 'games' }} played to the end. A game only counts when everyone played all {{ $turns }} turns.
            @else
                Records, and how everyone is doing, from the games you have played to the end.
            @endif
        </p>
    </div>

    @if ($backfill !== null && $backfill['kind'] === 'skipped')
        <p class="mt-3 text-sm text-stone-600" data-backfill="skipped">{{ $backfill['text'] }}</p>
    @elseif ($backfill !== null)
        <div @class(['alert mt-5', 'alert-info' => $backfill['kind'] === 'counting', 'alert-warning' => $backfill['kind'] === 'failed']) role="status" data-backfill="{{ $backfill['kind'] }}">
            <x-icon :name="$backfill['kind'] === 'failed' ? 'alert' : 'info'" class="mt-0.5 h-5 w-5 shrink-0" />
            <div class="min-w-0">
                <p class="font-bold">{{ $backfill['title'] }}</p>
                <p class="mt-0.5">{{ $backfill['text'] }}</p>
                @if ($backfill['kind'] === 'counting')
                    <a href="{{ route('stats') }}" class="btn-link mt-1">Refresh</a>
                @endif
            </div>
        </div>
    @endif

    @if ($games === 0)
        {{-- While the older games are being counted there is nothing to show yet, and nothing to say there are no stats --}}
        @if (($backfill['kind'] ?? null) !== 'counting')
            <div class="card mt-6 text-center">
                <div class="flex justify-center"><x-art /></div>
                <h2 class="mt-4 text-xl font-extrabold tracking-tight">No stats yet.</h2>
                <p class="mt-1 text-stone-600">They appear when you finish a game that everyone played to the end, all {{ $turns }} turns.</p>
                <a href="{{ route('game.create.view') }}" class="btn btn-primary mt-5">Start a game</a>
            </div>
        @endif
    @else
        <section class="mt-6" aria-labelledby="records-heading">
            <h2 id="records-heading" class="text-lg font-extrabold tracking-tight">Records</h2>

            <ul class="mt-3 grid gap-3 sm:grid-cols-2">
                @foreach ($cards as $card)
                    <li class="card p-5 sm:p-5" data-record="{{ $card['key'] }}">
                        <div class="flex items-start gap-3">
                            <span @class([
                                'inline-flex h-9 w-9 shrink-0 items-center justify-center rounded-full',
                                'bg-amber-100 text-amber-700' => $card['gold'],
                                'bg-stone-100 text-stone-600' => ! $card['gold'],
                            ])><x-icon :name="$card['icon']" class="h-5 w-5" /></span>
                            <div class="min-w-0 flex-1">
                                <h3 class="text-sm font-bold text-stone-600">{{ $card['title'] }}</h3>
                                @if ($card['value'] !== null)
                                    <p class="mt-0.5"><span class="text-3xl font-extrabold tabular-nums">{{ $card['value'] }}</span> <span class="text-sm font-semibold text-stone-600">{{ $card['unit'] }}</span></p>
                                @else
                                    <p class="mt-1 text-sm font-semibold text-stone-600">{{ $card['none'] }}</p>
                                @endif
                            </div>
                        </div>

                        @if ($card['holders'] !== [])
                            <ul class="mt-3 divide-y divide-stone-100">
                                @foreach ($card['holders'] as $holder)
                                    <li class="flex items-center gap-2.5 py-1.5">
                                        <x-avatar :name="$holder['name']" :index="$tones[$holder['player_id']] ?? 0" class="h-8 w-8 text-sm" />
                                        <span class="min-w-0 flex-1 truncate text-sm font-bold">{{ $holder['name'] }}</span>
                                        @if ($holder['game_id'] !== null)
                                            <a href="{{ route('game.show', ['game_id' => $holder['game_id']]) }}" class="-mr-2 inline-flex min-h-11 shrink-0 items-center rounded-xl px-2 text-xs font-bold text-brand-700 hover:bg-brand-50 hover:text-brand-900 focus-visible:outline-2 focus-visible:outline-brand-600">{{ $holder['detail'] }}<span class="sr-only">, open the game</span></a>
                                        @elseif ($holder['detail'] !== null)
                                            <span class="shrink-0 text-xs font-semibold text-stone-600">{{ $holder['detail'] }}</span>
                                        @endif
                                    </li>
                                @endforeach
                            </ul>

                            @if ($card['more'] > 0)
                                <p class="mt-1 text-xs font-semibold text-stone-600">and {{ $card['more'] }} more</p>
                            @endif
                        @endif
                    </li>
                @endforeach
            </ul>
        </section>

        <section class="mt-8" aria-labelledby="players-heading">
            <h2 id="players-heading" class="text-lg font-extrabold tracking-tight">Players</h2>

            <ul class="mt-3 grid gap-3 lg:grid-cols-2">
                @foreach ($players as $player)
                    <li class="card p-5 sm:p-5" data-player="{{ $player['player_id'] }}">
                        <div class="flex items-center gap-3">
                            <x-avatar :name="$player['player_name']" :index="$tones[$player['player_id']] ?? 0" class="h-10 w-10 text-base" />
                            <div class="min-w-0 flex-1">
                                <h3 class="truncate text-base font-extrabold">{{ $player['player_name'] }}</h3>
                                <p class="text-sm text-stone-600">
                                    {{ $player['games'] }} {{ $player['games'] === 1 ? 'game' : 'games' }}
                                    &middot; {{ $player['wins'] }} {{ $player['wins'] === 1 ? 'win' : 'wins' }}@if ($player['win_rate'] !== null) ({{ $player['win_rate'] }}%)@endif
                                </p>
                            </div>
                        </div>

                        <dl class="mt-4 grid grid-cols-2 gap-x-4 gap-y-3 sm:grid-cols-4">
                            @foreach ([
                                ['Average score', $player['average']],
                                ['Highest score', $player['highest_score']],
                                ['Lowest score', $player['lowest_score']],
                                ['Yahtzees', $player['yahtzees']],
                                ['Most Yahtzees in a game', $player['most_yahtzees_in_a_game']],
                                ['Longest winning streak', $player['longest_win_streak']],
                                ['Longest losing streak', $player['longest_loss_streak']],
                                ['Longest Yahtzee streak', $player['longest_yahtzee_streak']],
                            ] as [$label, $value])
                                <div>
                                    <dt class="text-xs text-stone-600">{{ $label }}</dt>
                                    <dd class="text-lg font-extrabold tabular-nums">{{ $value }}</dd>
                                </div>
                            @endforeach
                        </dl>
                    </li>
                @endforeach
            </ul>
        </section>
    @endif
</x-layouts.app>
