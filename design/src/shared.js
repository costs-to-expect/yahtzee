/*
 * Pieces every game scorer shares: icons, player avatars, the sheet (a native <dialog>), the snackbar and the
 * confetti. Mockup code only, the app would use Blade components and a small amount of JavaScript for these.
 */
(function () {
    'use strict';

    // ---- Icons (24px, outline) -------------------------------------------------------------------------------------

    var OUTLINE = {
        home: '<path d="M2.25 12l8.954-8.955a1.126 1.126 0 011.591 0L21.75 12M4.5 9.75v10.125c0 .621.504 1.125 1.125 1.125H9.75v-4.875c0-.621.504-1.125 1.125-1.125h2.25c.621 0 1.125.504 1.125 1.125V21h4.125c.621 0 1.125-.504 1.125-1.125V9.75M8.25 21h8.25"/>',
        games: '<path d="M3.75 6A2.25 2.25 0 016 3.75h2.25A2.25 2.25 0 0110.5 6v2.25a2.25 2.25 0 01-2.25 2.25H6a2.25 2.25 0 01-2.25-2.25V6zM3.75 15.75A2.25 2.25 0 016 13.5h2.25a2.25 2.25 0 012.25 2.25V18a2.25 2.25 0 01-2.25 2.25H6A2.25 2.25 0 013.75 18v-2.25zM13.5 6a2.25 2.25 0 012.25-2.25H18A2.25 2.25 0 0120.25 6v2.25A2.25 2.25 0 0118 10.5h-2.25a2.25 2.25 0 01-2.25-2.25V6zM13.5 15.75a2.25 2.25 0 012.25-2.25H18a2.25 2.25 0 012.25 2.25V18A2.25 2.25 0 0118 20.25h-2.25A2.25 2.25 0 0113.5 18v-2.25z"/>',
        players: '<path d="M15 19.128a9.38 9.38 0 002.625.372 9.337 9.337 0 004.121-.952 4.125 4.125 0 00-7.533-2.493M15 19.128v-.003c0-1.113-.285-2.16-.786-3.07M15 19.128v.106A12.318 12.318 0 018.624 21c-2.331 0-4.512-.645-6.374-1.766l-.001-.109a6.375 6.375 0 0111.964-3.07M12 6.375a3.375 3.375 0 11-6.75 0 3.375 3.375 0 016.75 0zm8.25 2.25a2.625 2.625 0 11-5.25 0 2.625 2.625 0 015.25 0z"/>',
        account: '<path d="M17.982 18.725A7.488 7.488 0 0012 15.75a7.488 7.488 0 00-5.982 2.975m11.963 0a9 9 0 10-11.963 0m11.963 0A8.966 8.966 0 0112 21a8.966 8.966 0 01-5.982-2.275M15 9.75a3 3 0 11-6 0 3 3 0 016 0z"/>',
        share: '<path d="M7.217 10.907a2.25 2.25 0 100 2.186m0-2.186c.18.324.283.696.283 1.093s-.103.77-.283 1.093m0-2.186l9.566-5.314m-9.566 7.5l9.566 5.314m0 0a2.25 2.25 0 103.935 2.186 2.25 2.25 0 00-3.935-2.186zm0-12.814a2.25 2.25 0 103.933-2.185 2.25 2.25 0 00-3.933 2.185z"/>',
        plus: '<path d="M12 4.5v15m7.5-7.5h-15"/>',
        check: '<path d="M4.5 12.75l6 6 9-13.5"/>',
        close: '<path d="M6 18L18 6M6 6l12 12"/>',
        'chevron-right': '<path d="M8.25 4.5l7.5 7.5-7.5 7.5"/>',
        'chevron-left': '<path d="M15.75 19.5L8.25 12l7.5-7.5"/>',
        'chevron-down': '<path d="M19.5 8.25l-7.5 7.5-7.5-7.5"/>',
        more: '<path d="M12 6.75a.75.75 0 110-1.5.75.75 0 010 1.5zM12 12.75a.75.75 0 110-1.5.75.75 0 010 1.5zM12 18.75a.75.75 0 110-1.5.75.75 0 010 1.5z"/>',
        help: '<path d="M9.879 7.519c1.171-1.025 3.071-1.025 4.242 0 1.172 1.025 1.172 2.687 0 3.712-.203.179-.43.326-.67.442-.745.361-1.45.999-1.45 1.827v.75M21 12a9 9 0 11-18 0 9 9 0 0118 0zm-9 5.25h.008v.008H12v-.008z"/>',
        undo: '<path d="M9 15L3 9m0 0l6-6M3 9h12a6 6 0 010 12h-3"/>',
        bulb: '<path d="M12 18v-5.25m0 0a6.01 6.01 0 001.5-.189m-1.5.189a6.01 6.01 0 01-1.5-.189m3.75 7.478a12.06 12.06 0 01-4.5 0m3.75 2.383a14.406 14.406 0 01-3 0M14.25 18v-.192c0-.983.658-1.823 1.508-2.316a7.5 7.5 0 10-7.517 0c.85.493 1.509 1.333 1.509 2.316V18"/>',
        sparkles: '<path d="M9.813 15.904L9 18.75l-.813-2.846a4.5 4.5 0 00-3.09-3.09L2.25 12l2.846-.813a4.5 4.5 0 003.09-3.09L9 5.25l.813 2.846a4.5 4.5 0 003.09 3.09L15.75 12l-2.846.813a4.5 4.5 0 00-3.09 3.09zM18.259 8.715L18 9.75l-.259-1.035a3.375 3.375 0 00-2.455-2.456L14.25 6l1.036-.259a3.375 3.375 0 002.455-2.456L18 2.25l.259 1.035a3.375 3.375 0 002.456 2.456L21.75 6l-1.035.259a3.375 3.375 0 00-2.456 2.456zM16.894 20.567L16.5 21.75l-.394-1.183a2.25 2.25 0 00-1.423-1.423L13.5 18.75l1.183-.394a2.25 2.25 0 001.423-1.423l.394-1.183.394 1.183a2.25 2.25 0 001.423 1.423l1.183.394-1.183.394a2.25 2.25 0 00-1.423 1.423z"/>',
        trophy: '<path d="M16.5 18.75h-9m9 0a3 3 0 013 3h-15a3 3 0 013-3m9 0v-3.375c0-.621-.503-1.125-1.125-1.125h-.871M7.5 18.75v-3.375c0-.621.504-1.125 1.125-1.125h.872m5.007 0H9.497m5.007 0a7.454 7.454 0 01-.982-3.172M9.497 14.25a7.454 7.454 0 00.981-3.172M5.25 4.236c-.982.143-1.954.317-2.916.52A6.003 6.003 0 007.73 9.728M5.25 4.236V4.5c0 2.108.966 3.99 2.48 5.228M5.25 4.236V2.721C7.456 2.41 9.71 2.25 12 2.25c2.291 0 4.545.16 6.75.47v1.516M7.73 9.728a6.726 6.726 0 002.748 1.35m8.272-6.842V4.5c0 2.108-.966 3.99-2.48 5.228m2.48-5.492a46.32 46.32 0 012.916.52 6.003 6.003 0 01-5.395 4.972m0 0a6.726 6.726 0 01-2.749 1.35m0 0a6.772 6.772 0 01-3.044 0"/>',
        alert: '<path d="M12 9v3.75m-9.303 3.376c-.866 1.5.217 3.374 1.948 3.374h14.71c1.73 0 2.813-1.874 1.948-3.374L13.949 3.378c-.866-1.5-3.032-1.5-3.898 0L2.697 16.126zM12 15.75h.007v.008H12v-.008z"/>',
        link: '<path d="M13.19 8.688a4.5 4.5 0 011.242 7.244l-4.5 4.5a4.5 4.5 0 01-6.364-6.364l1.757-1.757m13.35-.622l1.757-1.757a4.5 4.5 0 00-6.364-6.364l-4.5 4.5a4.5 4.5 0 001.242 7.244"/>',
        refresh: '<path d="M16.023 9.348h4.992v-.001M2.985 19.644v-4.992m0 0h4.992m-4.993 0l3.181 3.183a8.25 8.25 0 0013.803-3.7M4.031 9.865a8.25 8.25 0 0113.803-3.7l3.181 3.182m0-4.991v4.99"/>',
        backspace: '<path d="M12 9.75L14.25 12m0 0l2.25 2.25M14.25 12l2.25-2.25M14.25 12L12 14.25m-2.58 4.92l-6.375-6.375a1.125 1.125 0 010-1.59L9.42 4.83c.211-.211.498-.33.796-.33H19.5a2.25 2.25 0 012.25 2.25v10.5a2.25 2.25 0 01-2.25 2.25h-9.284c-.298 0-.585-.119-.796-.33z"/>',
        trash: '<path d="M14.74 9l-.346 9m-4.788 0L9.26 9m9.968-3.21c.342.052.682.107 1.022.166m-1.022-.165L18.16 19.673a2.25 2.25 0 01-2.244 2.077H8.084a2.25 2.25 0 01-2.244-2.077L4.772 5.79m14.456 0a48.108 48.108 0 00-3.478-.397m-12 .562c.34-.059.68-.114 1.022-.165m0 0a48.11 48.11 0 013.478-.397m7.5 0v-.916c0-1.18-.91-2.164-2.09-2.201a51.964 51.964 0 00-3.32 0c-1.18.037-2.09 1.022-2.09 2.201v.916m7.5 0a48.667 48.667 0 00-7.5 0"/>',
        flag: '<path d="M3 3v1.5M3 21v-6m0 0l2.77-.693a9 9 0 016.208.682l.108.054a9 9 0 006.086.71l3.114-.732a48.524 48.524 0 01-.005-10.499l-3.11.732a9 9 0 01-6.085-.711l-.108-.054a9 9 0 00-6.208-.682L3 4.5M3 15V4.5"/>'
    };

    var SOLID = {
        crown: '<path d="M3 8l4.5 4L12 5l4.5 7L21 8l-1.6 9.5a1 1 0 01-.99.85H5.59a1 1 0 01-.99-.85L3 8z"/><rect x="5" y="19" width="14" height="2" rx="1"/>',
        star: '<path d="M12 2.5l2.9 5.9 6.5.9-4.7 4.6 1.1 6.5L12 17.3l-5.8 3.1 1.1-6.5L2.6 9.3l6.5-.9L12 2.5z"/>',
        'check-circle': '<path fill-rule="evenodd" d="M2.25 12c0-5.385 4.365-9.75 9.75-9.75s9.75 4.365 9.75 9.75-4.365 9.75-9.75 9.75S2.25 17.385 2.25 12zm13.36-1.814a.75.75 0 10-1.22-.872l-3.236 4.53L9.53 12.22a.75.75 0 00-1.06 1.06l2.25 2.25a.75.75 0 001.14-.094l3.75-5.25z" clip-rule="evenodd"/>'
    };

    // The game marks: a die, a letter tile and a meeple. A game is told apart by its mark and name, not its colour.
    var GAME_MARKS = {
        yahtzee: '<rect x="3.5" y="3.5" width="17" height="17" rx="4.5" fill="none" stroke="currentColor" stroke-width="1.75"/><circle cx="8.6" cy="8.6" r="1.4" fill="currentColor"/><circle cx="15.4" cy="8.6" r="1.4" fill="currentColor"/><circle cx="12" cy="12" r="1.4" fill="currentColor"/><circle cx="8.6" cy="15.4" r="1.4" fill="currentColor"/><circle cx="15.4" cy="15.4" r="1.4" fill="currentColor"/>',
        scrabble: '<rect x="3.5" y="3.5" width="17" height="17" rx="4.5" fill="none" stroke="currentColor" stroke-width="1.75"/><path d="M7.9 16.6L11 7.8h.2l3.1 8.8M8.9 13.9h4.6" fill="none" stroke="currentColor" stroke-width="1.9" stroke-linecap="round" stroke-linejoin="round"/><path d="M15.6 16.9v-2.3l-.7.5" fill="none" stroke="currentColor" stroke-width="1.2" stroke-linecap="round" stroke-linejoin="round"/>',
        carcassonne: '<circle cx="12" cy="5.4" r="3" fill="currentColor"/><path d="M12 9.6c-2 0-3.5.4-4.9 1.2l-3.9 1c-.7.2-1 1-.6 1.7l.6 1c.4.6 1.1.8 1.7.5L7 14.2l-1.6 5.3c-.3 1 .4 1.9 1.4 1.9h2.2c.6 0 1.1-.3 1.3-.9L12 17.4l1.7 3c.2.5.7.9 1.3.9h2.2c1 0 1.7-.9 1.4-1.9L17 14.2l2.1.6c.6.3 1.3.1 1.7-.5l.6-1c.4-.7.1-1.5-.6-1.7l-3.9-1c-1.4-.8-2.9-1.2-4.9-1.2z" fill="currentColor"/>'
    };

    var GAME_NAMES = { yahtzee: 'Yahtzee', scrabble: 'Scrabble', carcassonne: 'Carcassonne' };

    function icon(name, classes, solid) {
        var inner = solid ? SOLID[name] : OUTLINE[name];
        if (inner === undefined) { throw new Error('Unknown icon ' + name); }
        return '<svg class="' + (classes || 'h-5 w-5') + '" viewBox="0 0 24 24" ' + (solid ? 'fill="currentColor"' : 'fill="none" stroke="currentColor" stroke-width="1.75" stroke-linecap="round" stroke-linejoin="round"') + ' aria-hidden="true">' + inner + '</svg>';
    }

    function gameMark(game, classes) {
        return '<svg class="' + (classes || 'h-5 w-5') + '" viewBox="0 0 24 24" aria-hidden="true">' + GAME_MARKS[game] + '</svg>';
    }

    // <i data-icon="share" class="h-5 w-5"> and <i data-solid-icon="crown"> become inline SVG
    function hydrateIcons(root) {
        (root || document).querySelectorAll('[data-icon], [data-solid-icon], [data-game-mark]').forEach(function (placeholder) {
            try {
                var html;
                if (placeholder.dataset.gameMark) { html = gameMark(placeholder.dataset.gameMark, placeholder.className); }
                else if (placeholder.dataset.solidIcon) { html = icon(placeholder.dataset.solidIcon, placeholder.className, true); }
                else { html = icon(placeholder.dataset.icon, placeholder.className); }
                placeholder.outerHTML = html;
            } catch (error) {
                // One bad icon name should not stop every other icon on the page
                console.error(error);
            }
        });
    }

    // ---- Players ---------------------------------------------------------------------------------------------------

    // A player keeps their colour for good, it comes from their place in the players list so there is nothing to store
    var TONES = [
        'bg-rose-100 text-rose-800', 'bg-sky-100 text-sky-800', 'bg-lime-100 text-lime-800',
        'bg-violet-100 text-violet-800', 'bg-orange-100 text-orange-800', 'bg-slate-200 text-slate-700'
    ];

    function esc(value) {
        return String(value).replace(/[&<>"']/g, function (c) { return { '&': '&amp;', '<': '&lt;', '>': '&gt;', '"': '&quot;', "'": '&#39;' }[c]; });
    }

    function avatar(name, index, classes) {
        return '<span class="inline-flex shrink-0 items-center justify-center rounded-full font-bold ' + TONES[index % TONES.length] + ' ' + (classes || 'h-9 w-9 text-sm') + '" aria-hidden="true">' + esc(name.charAt(0).toUpperCase()) + '</span>';
    }

    // A progress ring that wraps an avatar, fraction is 0 to 1
    function ring(fraction, classes) {
        var radius = 33, circumference = 2 * Math.PI * radius;
        return '<svg class="' + (classes || 'h-14 w-14') + '" viewBox="0 0 76 76" fill="none" aria-hidden="true">' +
            '<circle cx="38" cy="38" r="' + radius + '" class="stroke-stone-200" stroke-width="5"/>' +
            '<circle cx="38" cy="38" r="' + radius + '" class="stroke-brand-600" stroke-width="5" stroke-linecap="round" stroke-dasharray="' + (circumference * Math.min(1, Math.max(0, fraction))).toFixed(1) + ' ' + circumference.toFixed(1) + '" transform="rotate(-90 38 38)"/>' +
            '</svg>';
    }

    // ---- Illustrations for the empty states ------------------------------------------------------------------------

    function meeple(x, y, scale, colour) {
        return '<g transform="translate(' + x + ' ' + y + ') scale(' + scale + ')" fill="' + colour + '" stroke="' + colour + '" stroke-width="1.4" stroke-linejoin="round">' + GAME_MARKS.carcassonne.replace(/fill="currentColor"/g, '') + '</g>';
    }

    function tileArt(letter, points, x, y, rotation) {
        return '<g transform="rotate(' + rotation + ' ' + (x + 14) + ' ' + (y + 14) + ')">' +
            '<rect x="' + x + '" y="' + (y + 2) + '" width="28" height="28" rx="6" fill="#E7DDC2"/>' +
            '<rect x="' + x + '" y="' + y + '" width="28" height="28" rx="6" fill="#F9EFD2" stroke="#E2D2A4" stroke-width="1.5"/>' +
            '<text x="' + (x + 13) + '" y="' + (y + 20.5) + '" text-anchor="middle" font-family="Figtree, sans-serif" font-weight="800" font-size="17" fill="#3B2F14">' + letter + '</text>' +
            '<text x="' + (x + 23) + '" y="' + (y + 25) + '" text-anchor="middle" font-family="Figtree, sans-serif" font-weight="700" font-size="7" fill="#3B2F14">' + points + '</text></g>';
    }

    var ART = {
        yahtzee: '<svg viewBox="0 0 160 96" class="h-24 w-40" aria-hidden="true"><ellipse cx="80" cy="88" rx="54" ry="5" fill="#1C1917" opacity=".07"/>' +
            '<g transform="rotate(-12 55 52)"><rect x="28" y="24" width="54" height="54" rx="13" fill="#fff" stroke="#E7E5E4" stroke-width="2"/><circle cx="41" cy="37" r="5" fill="#057176"/><circle cx="69" cy="37" r="5" fill="#057176"/><circle cx="55" cy="51" r="5" fill="#057176"/><circle cx="41" cy="65" r="5" fill="#057176"/><circle cx="69" cy="65" r="5" fill="#057176"/></g>' +
            '<g transform="rotate(14 108 58)"><rect x="85" y="35" width="46" height="46" rx="11" fill="#038D93"/><circle cx="96" cy="46" r="4.2" fill="#fff"/><circle cx="108" cy="58" r="4.2" fill="#fff"/><circle cx="120" cy="70" r="4.2" fill="#fff"/></g></svg>',
        scrabble: '<svg viewBox="0 0 160 96" class="h-24 w-40" aria-hidden="true"><ellipse cx="80" cy="88" rx="62" ry="5" fill="#1C1917" opacity=".07"/>' +
            tileArt('S', 1, 4, 36, -5) + tileArt('C', 3, 36, 30, 3) + tileArt('O', 1, 68, 36, -3) + tileArt('R', 1, 100, 30, 4) + tileArt('E', 1, 130, 35, -4) + '</svg>',
        carcassonne: '<svg viewBox="0 0 160 96" class="h-24 w-40" aria-hidden="true"><ellipse cx="80" cy="88" rx="56" ry="5" fill="#1C1917" opacity=".07"/>' +
            meeple(22, 30, 2.1, '#FB7185') + meeple(58, 14, 2.9, '#38BDF8') + meeple(103, 30, 2.1, '#A3E635') + '</svg>'
    };

    // ---- The sheet: a native <dialog>, a bottom sheet on a phone and a centred dialog from sm up ------------------------

    var Sheet = (function () {
        var dialog, heading, subheading, content, onClose;

        function build() {
            dialog = document.createElement('dialog');
            dialog.setAttribute('aria-labelledby', 'sheet-title');
            dialog.className = 'm-0 mt-auto max-h-[92dvh] w-full max-w-none overflow-y-auto rounded-t-3xl bg-white p-0 text-stone-900 shadow-2xl backdrop:bg-stone-900/40 motion-safe:open:animate-sheet-in sm:m-auto sm:max-w-md sm:rounded-3xl';
            dialog.innerHTML = '<div class="p-5 pb-[max(1.5rem,env(safe-area-inset-bottom))] sm:p-6">' +
                '<div class="mx-auto mb-4 h-1 w-10 rounded-full bg-stone-200 sm:hidden"></div>' +
                '<div class="flex items-start justify-between gap-4">' +
                '<div class="min-w-0"><h2 id="sheet-title" class="text-xl font-extrabold tracking-tight"></h2><p id="sheet-subtitle" class="mt-0.5 text-sm text-stone-600"></p></div>' +
                '<button type="button" data-sheet-close class="-mr-3 -mt-2.5 shrink-0 rounded-full p-3 text-stone-500 hover:bg-stone-100 hover:text-stone-800 focus-visible:outline-2 focus-visible:outline-brand-600" aria-label="Close">' + icon('close', 'h-5 w-5') + '</button>' +
                '</div><div id="sheet-body" class="mt-5"></div></div>';
            document.body.appendChild(dialog);
            heading = dialog.querySelector('#sheet-title');
            subheading = dialog.querySelector('#sheet-subtitle');
            content = dialog.querySelector('#sheet-body');
            dialog.addEventListener('click', function (event) {
                // A click outside the panel lands on the dialog itself (its backdrop)
                if (event.target === dialog || event.target.closest('[data-sheet-close]')) { close(); }
            });
            dialog.addEventListener('close', function () {
                var callback = onClose;
                onClose = null;
                if (callback) { callback(); }
            });
        }

        function open(options) {
            if (!dialog) { build(); }
            heading.textContent = options.title;
            subheading.textContent = options.subtitle || '';
            subheading.hidden = !options.subtitle;
            content.innerHTML = options.body;
            onClose = options.onClose || null;
            if (!dialog.open) { dialog.showModal(); }
            var focus = content.querySelector(options.focus || '[data-autofocus]') || content.querySelector('input, button');
            if (focus) { focus.focus(); }
            return content;
        }

        function close() { if (dialog && dialog.open) { dialog.close(); } }

        return { open: open, close: close, body: function () { return content; }, element: function () { return dialog; } };
    })();

    // ---- The snackbar: a short message with an optional action, such as Undo -----------------------------------------

    var Snack = (function () {
        var el, text, action, timer, handler, bottom = 'bottom-6';

        function build() {
            el = document.createElement('div');
            el.className = 'pointer-events-none fixed inset-x-0 z-[70] flex justify-center px-4 opacity-0 transition-opacity duration-200 motion-reduce:transition-none ' + bottom;
            el.setAttribute('role', 'status');
            el.setAttribute('aria-live', 'polite');
            el.innerHTML = '<div class="pointer-events-auto flex max-w-md items-center gap-3 rounded-2xl bg-stone-900 py-2.5 pl-4 pr-2.5 text-sm font-medium text-white shadow-lift"><span data-snack-text></span><button type="button" data-snack-action class="min-h-10 rounded-lg px-3 font-bold text-brand-300 hover:bg-white/10 focus-visible:outline-2 focus-visible:outline-brand-300"></button></div>';
            document.body.appendChild(el);
            text = el.querySelector('[data-snack-text]');
            action = el.querySelector('[data-snack-action]');
            action.addEventListener('click', function () {
                var callback = handler;
                hide();
                if (callback) { callback(); }
            });
        }

        function hide() {
            clearTimeout(timer);
            handler = null;
            if (el) { el.classList.add('opacity-0'); }
        }

        function show(message, options) {
            if (!el) { build(); }
            options = options || {};
            clearTimeout(timer);
            text.textContent = message;
            handler = options.onAction || null;
            action.textContent = options.action || '';
            action.hidden = !options.action;
            el.classList.remove('opacity-0');
            timer = setTimeout(hide, options.duration || 6000);
        }

        // Where the snackbar sits, a page with a tab bar on a phone lifts it clear of the bar
        function position(classes) {
            bottom = classes;
            if (el) { el.className = el.className.replace(/(^| )(sm:)?bottom-\S+/g, '') + ' ' + bottom; }
        }

        return { show: show, hide: hide, position: position };
    })();

    // ---- Confetti: short, quiet and skipped for anyone who asks for reduced motion ---------------------------------

    function confetti() {
        if (window.matchMedia && window.matchMedia('(prefers-reduced-motion: reduce)').matches) { return; }

        var canvas = document.createElement('canvas');
        canvas.className = 'pointer-events-none fixed inset-0 z-[80] h-full w-full';
        canvas.setAttribute('aria-hidden', 'true');
        var scale = window.devicePixelRatio || 1;
        canvas.width = window.innerWidth * scale;
        canvas.height = window.innerHeight * scale;
        document.body.appendChild(canvas);

        var context = canvas.getContext('2d');
        var colours = ['#03B1B8', '#82E0E5', '#FBBF24', '#FB7185', '#38BDF8', '#A3E635'];
        var pieces = [];
        for (var i = 0; i < 70; i++) {
            var angle = (-90 + (Math.random() - 0.5) * 100) * Math.PI / 180, speed = 7 + Math.random() * 9;
            pieces.push({
                x: window.innerWidth / 2, y: window.innerHeight * 0.62,
                vx: Math.cos(angle) * speed, vy: Math.sin(angle) * speed,
                size: 5 + Math.random() * 6, rotation: Math.random() * Math.PI, spin: (Math.random() - 0.5) * 0.4,
                colour: colours[i % colours.length], round: i % 3 === 0
            });
        }

        var start = null;
        function frame(now) {
            if (start === null) { start = now; }
            var elapsed = now - start;
            context.setTransform(scale, 0, 0, scale, 0, 0);
            context.clearRect(0, 0, window.innerWidth, window.innerHeight);
            pieces.forEach(function (piece) {
                piece.vy += 0.32;
                piece.vx *= 0.992;
                piece.x += piece.vx;
                piece.y += piece.vy;
                piece.rotation += piece.spin;
                context.save();
                context.globalAlpha = Math.max(0, 1 - Math.max(0, elapsed - 1100) / 700);
                context.translate(piece.x, piece.y);
                context.rotate(piece.rotation);
                context.fillStyle = piece.colour;
                if (piece.round) { context.beginPath(); context.arc(0, 0, piece.size / 2, 0, Math.PI * 2); context.fill(); }
                else { context.fillRect(-piece.size / 2, -piece.size / 4, piece.size, piece.size / 2); }
                context.restore();
            });
            if (elapsed < 1800) { window.requestAnimationFrame(frame); } else { canvas.remove(); }
        }
        window.requestAnimationFrame(frame);
    }

    window.Mock = {
        icon: icon, gameMark: gameMark, hydrateIcons: hydrateIcons, avatar: avatar, ring: ring, esc: esc, tones: TONES,
        art: ART, gameNames: GAME_NAMES, Sheet: Sheet, Snack: Snack, confetti: confetti
    };

    hydrateIcons();
})();
