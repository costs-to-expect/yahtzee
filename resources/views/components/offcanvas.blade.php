<nav class="navbar navbar-dark bg-dark" aria-label="Offcanvas navbar">
    <div class="container-fluid">
        @auth
        <a class="navbar-brand" href="{{ route('home') }}">
            Yahtzee by <img src="{{ asset('images/logo.png') }}" width="30" height="30" class="d-inline-block align-middle" alt=""><span class="d-none">C</span>osts to Expect.com
        </a>
        @endauth

        @guest
            <a class="navbar-brand" href="/">
                Yahtzee by <img src="{{ asset('images/logo.png') }}" width="30" height="30" class="d-inline-block align-middle" alt=""><span class="d-none">C</span>osts to Expect.com
            </a>
        @endguest
        <button class="navbar-toggler" type="button" data-bs-toggle="offcanvas" data-bs-target="#offcanvasNavbarDark" aria-controls="offcanvasNavbarDark">
            <span class="navbar-toggler-icon"></span>
        </button>
        <div class="offcanvas offcanvas-end text-bg-dark" tabindex="-1" id="offcanvasNavbarDark" aria-labelledby="offcanvasNavbarDarkLabel">
            <div class="offcanvas-header">
                <h5 class="offcanvas-title" id="offcanvasNavbarDarkLabel">Yahtzee</h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="offcanvas" aria-label="Close"></button>
            </div>
            <div class="offcanvas-body">
                <ul class="navbar-nav justify-content-end flex-grow-1 pe-3">
                    <li class="nav-item">
                        <a class="nav-link @if($active === 'home') active @endif" @if($active === 'home') aria-current="page" @endif href="{{ route('home') }}">Home</a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link @if($active === 'games') active @endif" @if($active === 'games') aria-current="page" @endif href="{{ route('games') }}">Games</a>
                    </li>
                    <li class="nav-item ps-3">
                        <a class="nav-link" href="{{ route('game.create.view') }}">- New Game</a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link @if($active === 'players') active @endif" @if($active === 'players') aria-current="page" @endif href="{{ route('players') }}">Players</a>
                    </li>
                    <li class="nav-item ps-3">
                        <a class="nav-link" href="{{ route('player.create.view') }}">- New Player</a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link @if($active === 'account') active @endif" @if($active === 'account') aria-current="page" @endif href="{{ route('account') }}">Account</a>
                    </li>
                    <li class="nav-item ps-3">
                        <a class="nav-link" href="{{ route('account.confirm-delete-yahtzee-account') }}">- Delete Yahtzee Account</a>
                    </li>
                    <li class="nav-item ps-3">
                        <a class="nav-link" href="{{ route('account.confirm-delete-account') }}">- Delete Account</a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" href="{{ route('sign-out') }}">Sign-out</a>
                    </li>
                    <li class="nav-item text-white-50 pt-3">
                        <strong>Costs to Expect</strong>
                    </li>
                    <li class="nav-item ps-3">
                        <a class="nav-link" href="https://api.costs-to-expect.com">- API</a>
                    </li>
                    <li class="nav-item ps-3">
                        <a class="nav-link" href="https://budget.costs-to-expect.com">- Budget</a>
                    </li>
                    <li class="nav-item ps-3">
                        <a class="nav-link" href="https://budget-pro.costs-to-expect.com">- Budget Pro</a>
                    </li>
                    <li class="nav-item ps-3">
                        <a class="nav-link" href="https://yahtzee.game-scorer.com">- Yahtzee Game Scorer</a>
                    </li>
                    <li class="nav-item ps-3">
                        <a class="nav-link" href="https://yatzy.game-scorer.com">- Yatzy Game Scorer</a>
                    </li>
                </ul>
            </div>
        </div>
    </div>
</nav>