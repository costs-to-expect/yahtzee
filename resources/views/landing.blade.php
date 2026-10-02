<x-layouts.guest title="Yahtzee Game Scorer — Online Score Sheets for Game Night"
                 description="Score Yahtzee online with friends and family. Shareable live score sheets, no designated scorer, no app to install — powered by the Costs to Expect API."
                 width="max-w-5xl">
    <x-slot:head>
        <link rel="canonical" href="{{ url('/') }}">

        <meta property="og:type" content="website">
        <meta property="og:site_name" content="Yahtzee Game Scorer">
        <meta property="og:title" content="Yahtzee Game Scorer — Online Score Sheets for Game Night">
        <meta property="og:description" content="Score Yahtzee online with friends and family. Shareable live score sheets, no designated scorer, no app to install.">
        <meta property="og:url" content="{{ url('/') }}">
        <meta property="og:image" content="{{ asset('images/card.png') }}">
        <meta name="twitter:card" content="summary_large_image">
        <meta name="twitter:title" content="Yahtzee Game Scorer — Online Score Sheets for Game Night">
        <meta name="twitter:description" content="Score Yahtzee online with friends and family. Shareable live score sheets, no designated scorer, no app to install.">
        <meta name="twitter:image" content="{{ asset('images/card.png') }}">
    </x-slot:head>

    <section class="text-center" aria-labelledby="hero-heading">
        <div class="flex justify-center"><x-art /></div>
        <h1 id="hero-heading" class="mt-4 text-4xl font-extrabold tracking-tight sm:text-5xl">Yahtzee Game Scorer</h1>
        <p class="mx-auto mt-3 max-w-xl text-lg text-stone-700">A game scorer powered by the Costs to Expect API.</p>
        <p class="mx-auto mt-2 max-w-xl text-stone-600">Yep, you read that right, a game scorer, you still need to be social and play the game.</p>
        <div class="mt-7 flex flex-col items-center justify-center gap-3 sm:flex-row">
            <a href="{{ route('register.view') }}" class="btn btn-primary w-full sm:w-auto sm:min-w-44">Register</a>
            <a href="{{ route('sign-in.view') }}" class="btn btn-secondary w-full sm:w-auto sm:min-w-44">Sign in</a>
        </div>
    </section>

    <div class="mt-14 grid gap-6 md:grid-cols-2">
        <section class="card flex flex-col overflow-hidden p-0 sm:p-0" aria-labelledby="scorer-heading">
            <div class="p-6 sm:p-8">
                <h2 id="scorer-heading" class="text-2xl font-extrabold tracking-tight">No designated scorer</h2>
                <p class="mt-2 text-stone-700">No more designated scorers, no more, &ldquo;Do I have my threes?&rdquo;, everyone gets and updates their own score sheet.</p>
            </div>
            <div class="mt-auto flex justify-center bg-brand-50 px-6 pt-6">
                <img src="{{ asset('images/score-sheet.png') }}" width="300" height="600" loading="lazy" alt="A screen shot of the score sheet for Yahtzee" class="h-auto w-64 rounded-t-3xl shadow-lift ring-1 ring-stone-200">
            </div>
        </section>

        <section class="card flex flex-col overflow-hidden p-0 sm:p-0" aria-labelledby="scores-heading">
            <div class="p-6 sm:p-8">
                <h2 id="scores-heading" class="text-2xl font-extrabold tracking-tight">All scores</h2>
                <p class="mt-2 text-stone-700">You can see all the scores, all the time, no need to bug the designated scorer.</p>
            </div>
            <div class="mt-auto flex justify-center bg-brand-50 px-6 pt-6">
                <img src="{{ asset('images/player-scores.png') }}" width="300" height="600" loading="lazy" alt="A screen shot of the home page showing every player's score in the game" class="h-auto w-64 rounded-t-3xl shadow-lift ring-1 ring-stone-200">
            </div>
        </section>

        <section class="card flex flex-col overflow-hidden p-0 sm:p-0" aria-labelledby="account-heading">
            <div class="p-6 sm:p-8">
                <h2 id="account-heading" class="text-2xl font-extrabold tracking-tight">One account</h2>
                <p class="mt-2 text-stone-700">Only one player needs an account, sharable public score sheets for each player, tokens valid for the life of the game.</p>
            </div>
            <div class="mt-auto flex justify-center bg-brand-50 px-6 pt-6">
                <img src="{{ asset('images/management.png') }}" width="300" height="600" loading="lazy" alt="A screen shot of sharing a score sheet link with each player" class="h-auto w-64 rounded-t-3xl shadow-lift ring-1 ring-stone-200">
            </div>
        </section>

        <section class="card flex flex-col justify-center text-center" aria-labelledby="stats-heading">
            <h2 id="stats-heading" class="text-2xl font-extrabold tracking-tight">Stats <span class="font-semibold text-stone-500">(coming soon)</span></h2>
            <p class="mt-2 text-stone-700">All the stats you could possibly want are coming soon, we are working out the best way to visualise everything.</p>
        </section>
    </div>
</x-layouts.guest>
