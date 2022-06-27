<!doctype html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
    <head>
        <meta charset="utf-8">
        <meta name="viewport" content="width=device-width, initial-scale=1">
        <meta name="description" content="Yahtzee Game Score by Costs to Expect">
        <meta name="author" content="Dean Blackborough">
        <title>Yahtzee Game Scorer: Game</title>
        <link href="{{ asset('css/theme.css') }}" rel="stylesheet" />
    </head>
    <body>
        <div class="col-lg-8 mx-auto p-3 py-md-5">
            <div class="header">
                <h1 class="display-1">
                    <a href="{{ route('home') }}">
                        <img src="{{ asset('images/logo.png') }}" width="64" height="64" alt="Costs to Expect Logo" title="Powered by Costs to Expect API">
                    </a>
                    Yahtzee
                </h1>
            </div>

            <nav class="nav nav-fill my-4 border-bottom border-top">
                <a class="nav-link" href="{{ route('home') }}">Home</a>
                <a class="nav-link active" href="#">Games</a>
                <a class="nav-link" href="#">Players</a>
                <a class="nav-link" href="#">Sign-out</a>
            </nav>

            <form>
                <div class="row">
                    <h2>Upper Section</h2>
                    <div class="col-6">
                        <div class="row mb-2">
                            <div class="col-6 mb-1">Aces </div>
                            <div class="col-6 mb-1 text-muted text-end small">Scratch</div>
                            <div class="col-9">
                                <label for="colFormLabelSm" class="visually-hidden">Sum of 1s</label>
                                <div class="col-12">
                                    <input type="number" class="form-control form-control-sm" id="colFormLabelSm" placeholder="Sum of all 1s">
                                </div>
                            </div>
                            <div class="col-3">
                                <div class="form-check">
                                    <input class="form-check-input" type="checkbox" value="" name="" id="">
                                </div>
                            </div>
                        </div>
                    </div>
                    <div class="col-6">
                        <div class="row mb-2">
                            <div class="col-6 mb-1">Twos </div>
                            <div class="col-6 mb-1 text-muted text-end small">Scratch</div>
                            <div class="col-9">
                                <label for="colFormLabelSm" class="visually-hidden">Sum of 2s</label>
                                <div class="col-12">
                                    <input type="number" class="form-control form-control-sm" id="colFormLabelSm" placeholder="Sum of all 2s">
                                </div>
                            </div>
                            <div class="col-3">
                                <div class="form-check">
                                    <input class="form-check-input" type="checkbox" value="" name="" id="">
                                </div>
                            </div>
                        </div>
                    </div>
                    <div class="col-6">
                        <div class="row mb-2">
                            <div class="col-6 mb-1">Aces </div>
                            <div class="col-6 mb-1 text-muted text-end small">Scratch</div>
                            <div class="col-9">
                                <label for="colFormLabelSm" class="visually-hidden">Sum of 1s</label>
                                <div class="col-12">
                                    <input type="number" class="form-control form-control-sm" id="colFormLabelSm" placeholder="Sum of all 1s">
                                </div>
                            </div>
                            <div class="col-3">
                                <div class="form-check">
                                    <input class="form-check-input" type="checkbox" value="" name="" id="">
                                </div>
                            </div>
                        </div>
                    </div>
                    <div class="col-6">
                        <div class="row mb-2">
                            <div class="col-6 mb-1">Twos </div>
                            <div class="col-6 mb-1 text-muted text-end small">Scratch</div>
                            <div class="col-9">
                                <label for="colFormLabelSm" class="visually-hidden">Sum of 2s</label>
                                <div class="col-12">
                                    <input type="number" class="form-control form-control-sm" id="colFormLabelSm" placeholder="Sum of all 2s">
                                </div>
                            </div>
                            <div class="col-3">
                                <div class="form-check">
                                    <input class="form-check-input" type="checkbox" value="" name="" id="">
                                </div>
                            </div>
                        </div>
                    </div>
                    <div class="col-6">
                        <div class="row mb-2">
                            <div class="col-6 mb-1">Aces </div>
                            <div class="col-6 mb-1 text-muted text-end small">Scratch</div>
                            <div class="col-9">
                                <label for="colFormLabelSm" class="visually-hidden">Sum of 1s</label>
                                <div class="col-12">
                                    <input type="number" class="form-control form-control-sm" id="colFormLabelSm" placeholder="Sum of all 1s">
                                </div>
                            </div>
                            <div class="col-3">
                                <div class="form-check">
                                    <input class="form-check-input" type="checkbox" value="" name="" id="">
                                </div>
                            </div>
                        </div>
                    </div>
                    <div class="col-6 mb-2">
                        <div class="row mb-2">
                            <div class="col-6 mb-1">Twos </div>
                            <div class="col-6 mb-1 text-muted text-end small">Scratch</div>
                            <div class="col-9">
                                <label for="colFormLabelSm" class="visually-hidden">Sum of 2s</label>
                                <div class="col-12">
                                    <input type="number" class="form-control form-control-sm" id="colFormLabelSm" placeholder="Sum of all 2s">
                                </div>
                            </div>
                            <div class="col-3">
                                <div class="form-check">
                                    <input class="form-check-input" type="checkbox" value="" name="" id="">
                                </div>
                            </div>
                        </div>
                    </div>
                    <h2>Lower Section</h2>
                </div>
            </form>
            <footer class="pt-4 my-4 text-muted border-top text-center">
                Created by <a href="https://twitter.com/DBlackborough">Dean Blackborough</a><br />
                powered by the <a href="https://api.costs-to-expect.com">Costs to Expect API</a>

                <div class="mt-3 small">
                    v0.01 - Released ##ordinal ## {{ date('Y') }}
                </div>
            </footer>
        </div>
    </body>
</html>
