<!doctype html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
    <head>
        <meta charset="utf-8">
        <meta name="viewport" content="width=device-width, initial-scale=1">
        <meta name="description" content="Yahtzee Game Scorer by Costs to Expect">
        <meta name="author" content="Dean Blackborough">
        <title>Yahtzee Game Scorer: Account</title>
        <link rel="icon" sizes="48x48" href="{{ asset('images/favicon.ico') }}">
        <link rel="icon" type="image/png" sizes="192x192" href="{{ asset('images/favicon.png') }}">
        <link href="{{ asset('css/theme.css') }}" rel="stylesheet" />
    </head>
    <body>
        <x-offcanvas active="account" />

        <div class="col-lg-8 mx-auto p-3 py-md-5">
            <main>
                <h2>Delete Yahtzee account</h2>

                <p class="lead">We will immediately create a background task to delete your data, the task should
                    start within a minute, once it completes all your Yahtzee data will be gone and your session
                    will be deleted.</p>

                <p>Please review the tables below to see what will be deleted and what will remain.</p>

                <h4>Data that will be deleted</h4>

                <div class="table-responsive">
                    <table class="table table-dark">
                        <thead>
                        <tr>
                            <th scope="col">Content</th>
                            <th scope="col">Description</th>
                            <th scope="col">Location</th>
                        </tr>
                        </thead>
                        <tbody>
                        <tr>
                            <td>Yahtzee Games</td>
                            <td>Your open and complete games</td>
                            <td>API</td>
                        </tr>
                        <tr>
                            <td>Share Tokens</td>
                            <td>Public share tokens for open games</td>
                            <td>Yahtzee</td>
                        </tr>
                        <tr>
                            <td>Game Log</td>
                            <td>The logs containing all game actions</td>
                            <td>API</td>
                        </tr>
                        <tr>
                            <td>Sessions</td>
                            <td>Session information</td>
                            <td>Yahtzee</td>
                        </tr>
                        </tbody>
                    </table>
                </div>

                <h4>Data that will be not deleted</h4>

                <div class="table-responsive">
                    <table class="table table-dark">
                        <thead>
                        <tr>
                            <th scope="col">Content</th>
                            <th scope="col">Description</th>
                            <th scope="col">Location</th>
                        </tr>
                        </thead>
                        <tbody>
                        <tr>
                            <td>Account</td>
                            <td>Your Costs to Expect account, one account for all our Apps</td>
                            <td>API</td>
                        </tr>
                        <tr>
                            <td>Players</td>
                            <td>Players you created, usable across all our game scoring Apps</td>
                            <td>API</td>
                        </tr>
                        <tr>
                            <td>Other Apps</td>
                            <td>Your will still have access to all the other Costs to Expect Apps and
                                none of your data will be touched</td>
                            <td>API & other Costs to Expect Apps</td>
                        </tr>
                        </tbody>
                    </table>
                </div>

                <form action="{{ route('account.delete-yahtzee-account.action') }}" method="POST" class="d-inline">
                    @csrf
                    <button type="submit" class="btn btn-sm btn-danger">Confirm Delete (Cannot be undone)</button>
                    <a href="{{ route('account') }}" class="btn btn-sm btn-primary">Cancel</a>
                </form>

            </main>
            <x-footer />
        </div>
        <script src="{{ asset('node_modules/bootstrap/dist/js/bootstrap.js') }}" defer></script>
    </body>
</html>
