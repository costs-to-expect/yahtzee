@props(['id' => 'hide-scoreboard', 'checked' => false])
{{-- A setting for the game that is about to start: hide the public scoreboard (the "Everyone" panel) on the players' score
     sheets. It is a plain checkbox, so it works without JavaScript and an unchecked box sends nothing, which means the
     scoreboard is shown. The id is a prop because the home page has two forms that start a game. --}}
<label for="{{ $id }}" {{ $attributes->merge(['class' => 'toggle']) }}>
    <input type="checkbox" name="hide_scoreboard" value="1" id="{{ $id }}" class="toggle-box"
           aria-labelledby="{{ $id }}-label" aria-describedby="{{ $id }}-help" @checked($checked)>
    <span class="min-w-0">
        <span id="{{ $id }}-label" class="toggle-title">Hide the scoreboard on players&rsquo; score sheets</span>
        <span id="{{ $id }}-help" class="toggle-help">Players won&rsquo;t see how everyone else is doing. You can still see every score.</span>
    </span>
</label>
