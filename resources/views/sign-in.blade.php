<!doctype html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
    <head>
        <meta charset="utf-8">
        <meta name="viewport" content="width=device-width, initial-scale=1">
        <meta name="description" content="Yahtzee Game Score by Costs to Expect">
        <meta name="author" content="Dean Blackborough">
        <title>Yahtzee Game Scorer: Sign-in</title>
        <link href="{{ asset('css/theme.css') }}" rel="stylesheet" />

        <style>
            html,
            body {
                height: 100%;
            }

            body {
                display: flex;
                align-items: center;
                padding-bottom: 40px;
            }

            form {
                max-width: 320px;
                margin: auto;
            }

            .btn-primary {
                background-color: #392C70;
                border-color: #392C70;
                color: #ffffff;
                padding: 0.3rem 1rem;
            }

            .btn-primary:hover {
                background-color: #2d2d5c;
            }
        </style>
    </head>
    <body>
        <div class="container-fluid">
            <div class="row">
                <div class="col-12">
                    <form>
                        <div class="text-center">
                            <h1 class="display-1">Yahtzee</h1>
                            <h2 class="display-6">Game Scorer</h2>
                            powered by <a href="#" style="text-decoration: none; color: #000000;">
                                <img style="margin-right: -1rem;" src="https://app.costs-to-expect.com/images/theme/logo-190.png" width="64" height="64" alt="Costs to Expect Logo">
                                <span class="d-none">C</span>osts to Expect.com
                            </a>
                        </div>

                        <div class="mt-3 mb-3">
                            <label for="email" class="form-label">Email</label>
                            <input type="email" class="form-control" id="email" aria-describedby="email-help">
                            <div id="email-help" class="form-text">Please enter your email address, <em>we will never share
                                    your email address</em>.</div>
                        </div>
                        <div class="mb-3">
                            <label for="password" class="form-label">Password</label>
                            <input type="password" class="form-control" id="password" aria-describedby="password-help">
                            <div id="password-help" class="form-text">Please enter your password, <em>we will check this
                                    against the encrypted value in our database</em>.</div>
                        </div>
                        <div class="mb-3 form-check">
                            <input type="checkbox" class="form-check-input" id="remember_me">
                            <label class="form-check-label" for="remember_me">Check to stay signed-in for longer</label>
                        </div>
                        <button type="submit" class="btn btn-primary w-100">Sign-in</button>
                    </form>
                </div>
            </div>
        </div>
    </body>
</html>
