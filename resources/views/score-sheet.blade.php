<!doctype html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
    <head>
        <meta charset="utf-8">
        <meta name="viewport" content="width=device-width, initial-scale=1">
        <meta name="description" content="Yahtzee Game Score by Costs to Expect">
        <meta name="author" content="Dean Blackborough">
        <title>Yahtzee Game Scorer: Game</title>
        <link rel="icon" sizes="48x48" href="{{ asset('images/favicon.ico') }}">
        <link rel="icon" type="image/png" sizes="192x192" href="{{ asset('images/favicon.png') }}">
        <link href="{{ asset('css/theme.css') }}" rel="stylesheet" />
    </head>
    <body>
        <div class="col-lg-8 mx-auto p-3 py-md-5">
            <x-layout.header />

            <nav class="nav nav-fill my-4 border-bottom border-top">
                <a class="nav-link" href="{{ route('home') }}">Home</a>
                <a class="nav-link active" href="{{ route('games') }}">Games</a>
                <a class="nav-link" href="{{ route('players') }}">Players</a>
                <a class="nav-link" href="{{ route('sign-out') }}">Sign-out</a>
            </nav>

            <h1>Player: {{ $player_name }}</h1>

            <form>
                <input type="hidden" id="game_id" name="game_id" value="{{ $game_id }}" />
                <input type="hidden" id="player_id" name="player_id" value="{{ $player_id }}" />
                <div class="row">
                    <div class="col-12">
                        <h2 class="text-primary">Upper Section</h2>
                        <h5>How to score</h5>
                        <p class="text-muted mb-2">Score the total of all the matched dice.</p>
                    </div>
                </div>
                <div class="row upper-section">
                    <div class="col-2 text-center">
                        <label for="ones" class="form-label dice">
                            <svg xmlns="http://www.w3.org/2000/svg" width="32" height="32" fill="currentColor" class="bi bi-dice-1" viewBox="0 0 16 16">
                                <circle cx="8" cy="8" r="1.5"/>
                                <path d="M13 1a2 2 0 0 1 2 2v10a2 2 0 0 1-2 2H3a2 2 0 0 1-2-2V3a2 2 0 0 1 2-2h10zM3 0a3 3 0 0 0-3 3v10a3 3 0 0 0 3 3h10a3 3 0 0 0 3-3V3a3 3 0 0 0-3-3H3z"/>
                            </svg>
                        </label>
                        <input type="number" min="1" max="6" step="1" size="1" class="form-control form-control-sm @if(array_key_exists('ones', $score_sheet['upper-section'])) disabled @else active @endif" name="ones" id="ones" placeholder="3" @if(array_key_exists('ones', $score_sheet['upper-section'])) disabled="disabled" value="{{ $score_sheet['upper-section']['ones'] }}" @endif>
                    </div>
                    <div class="col-2 text-center">
                        <label for="twos" class="form-label dice">
                            <svg xmlns="http://www.w3.org/2000/svg" width="32" height="32" fill="currentColor" class="bi bi-dice-2" viewBox="0 0 16 16">
                                <path d="M13 1a2 2 0 0 1 2 2v10a2 2 0 0 1-2 2H3a2 2 0 0 1-2-2V3a2 2 0 0 1 2-2h10zM3 0a3 3 0 0 0-3 3v10a3 3 0 0 0 3 3h10a3 3 0 0 0 3-3V3a3 3 0 0 0-3-3H3z"/>
                                <path d="M5.5 4a1.5 1.5 0 1 1-3 0 1.5 1.5 0 0 1 3 0zm8 8a1.5 1.5 0 1 1-3 0 1.5 1.5 0 0 1 3 0z"/>
                            </svg>
                        </label>
                        <input type="number" min="2" max="10" step="2" size="2" class="form-control form-control-sm @if(array_key_exists('twos', $score_sheet['upper-section'])) disabled @else active @endif" name="twos" id="twos" placeholder="6" @if(array_key_exists('twos', $score_sheet['upper-section'])) disabled="disabled" value="{{ $score_sheet['upper-section']['twos'] }}" @endif>
                    </div>
                    <div class="col-2 text-center">
                        <label for="threes" class="form-label dice">
                            <svg xmlns="http://www.w3.org/2000/svg" width="32" height="32" fill="currentColor" class="bi bi-dice-3" viewBox="0 0 16 16">
                                <path d="M13 1a2 2 0 0 1 2 2v10a2 2 0 0 1-2 2H3a2 2 0 0 1-2-2V3a2 2 0 0 1 2-2h10zM3 0a3 3 0 0 0-3 3v10a3 3 0 0 0 3 3h10a3 3 0 0 0 3-3V3a3 3 0 0 0-3-3H3z"/>
                                <path d="M5.5 4a1.5 1.5 0 1 1-3 0 1.5 1.5 0 0 1 3 0zm8 8a1.5 1.5 0 1 1-3 0 1.5 1.5 0 0 1 3 0zm-4-4a1.5 1.5 0 1 1-3 0 1.5 1.5 0 0 1 3 0z"/>
                            </svg>
                        </label>
                        <input type="number" min="3" max="15" step="3" size="2" class="form-control form-control-sm @if(array_key_exists('threes', $score_sheet['upper-section'])) disabled @else active @endif" name="threes" id="threes" placeholder="9" @if(array_key_exists('threes', $score_sheet['upper-section'])) disabled="disabled" value="{{ $score_sheet['upper-section']['threes'] }}" @endif>
                    </div>
                    <div class="col-2 text-center">
                        <label for="fours" class="form-label dice">
                            <svg xmlns="http://www.w3.org/2000/svg" width="32" height="32" fill="currentColor" class="bi bi-dice-4" viewBox="0 0 16 16">
                                <path d="M13 1a2 2 0 0 1 2 2v10a2 2 0 0 1-2 2H3a2 2 0 0 1-2-2V3a2 2 0 0 1 2-2h10zM3 0a3 3 0 0 0-3 3v10a3 3 0 0 0 3 3h10a3 3 0 0 0 3-3V3a3 3 0 0 0-3-3H3z"/>
                                <path d="M5.5 4a1.5 1.5 0 1 1-3 0 1.5 1.5 0 0 1 3 0zm8 0a1.5 1.5 0 1 1-3 0 1.5 1.5 0 0 1 3 0zm0 8a1.5 1.5 0 1 1-3 0 1.5 1.5 0 0 1 3 0zm-8 0a1.5 1.5 0 1 1-3 0 1.5 1.5 0 0 1 3 0z"/>
                            </svg>
                        </label>
                        <input type="number" min="4" max="20" step="4" size="2" class="form-control form-control-sm @if(array_key_exists('fours', $score_sheet['upper-section'])) disabled @else active @endif" name="fours" id="fours" placeholder="12" @if(array_key_exists('fours', $score_sheet['upper-section'])) disabled="disabled" value="{{ $score_sheet['upper-section']['fours'] }}" @endif>
                    </div>
                    <div class="col-2 text-center">
                        <label for="fives" class="form-label dice">
                            <svg xmlns="http://www.w3.org/2000/svg" width="32" height="32" fill="currentColor" class="bi bi-dice-5" viewBox="0 0 16 16">
                                <path d="M13 1a2 2 0 0 1 2 2v10a2 2 0 0 1-2 2H3a2 2 0 0 1-2-2V3a2 2 0 0 1 2-2h10zM3 0a3 3 0 0 0-3 3v10a3 3 0 0 0 3 3h10a3 3 0 0 0 3-3V3a3 3 0 0 0-3-3H3z"/>
                                <path d="M5.5 4a1.5 1.5 0 1 1-3 0 1.5 1.5 0 0 1 3 0zm8 0a1.5 1.5 0 1 1-3 0 1.5 1.5 0 0 1 3 0zm0 8a1.5 1.5 0 1 1-3 0 1.5 1.5 0 0 1 3 0zm-8 0a1.5 1.5 0 1 1-3 0 1.5 1.5 0 0 1 3 0zm4-4a1.5 1.5 0 1 1-3 0 1.5 1.5 0 0 1 3 0z"/>
                            </svg>
                        </label>
                        <input type="number" min="5" max="25" step="5" size="2" class="form-control form-control-sm @if(array_key_exists('fives', $score_sheet['upper-section'])) disabled @else active @endif" name="fives" id="fives" placeholder="15" @if(array_key_exists('fives', $score_sheet['upper-section'])) disabled="disabled" value="{{ $score_sheet['upper-section']['fives'] }}" @endif>
                    </div>
                    <div class="col-2 text-center">
                        <label for="sixes" class="form-label dice">
                            <svg xmlns="http://www.w3.org/2000/svg" width="32" height="32" fill="currentColor" class="bi bi-dice-6" viewBox="0 0 16 16">
                                <path d="M13 1a2 2 0 0 1 2 2v10a2 2 0 0 1-2 2H3a2 2 0 0 1-2-2V3a2 2 0 0 1 2-2h10zM3 0a3 3 0 0 0-3 3v10a3 3 0 0 0 3 3h10a3 3 0 0 0 3-3V3a3 3 0 0 0-3-3H3z"/>
                                <path d="M5.5 4a1.5 1.5 0 1 1-3 0 1.5 1.5 0 0 1 3 0zm8 0a1.5 1.5 0 1 1-3 0 1.5 1.5 0 0 1 3 0zm0 8a1.5 1.5 0 1 1-3 0 1.5 1.5 0 0 1 3 0zm0-4a1.5 1.5 0 1 1-3 0 1.5 1.5 0 0 1 3 0zm-8 4a1.5 1.5 0 1 1-3 0 1.5 1.5 0 0 1 3 0zm0-4a1.5 1.5 0 1 1-3 0 1.5 1.5 0 0 1 3 0z"/>
                            </svg>
                        </label>
                        <input type="number" min="6" max="30" step="6" size="2" class="form-control form-control-sm @if(array_key_exists('sixes', $score_sheet['upper-section'])) disabled @else active @endif" name="sixes" id="sixes" placeholder="18" @if(array_key_exists('sixes', $score_sheet['upper-section'])) disabled="disabled" value="{{ $score_sheet['upper-section']['sixes'] }}" @endif>
                    </div>
                </div>
                <div class="row">
                    <div class="col-12">
                        <p class="text-muted mb-0">Use the checkbox to scratch a turn.</p>
                    </div>
                </div>
                <div class="row upper-section-scratch">
                    <div class="col-2">
                        <div class="text-center">
                            <input class="form-check-input @if(array_key_exists('ones', $score_sheet['upper-section'])) disabled @else active @endif" type="checkbox" id="scratch_ones" value="ones" aria-label="Scratch the ones" @if(array_key_exists('ones', $score_sheet['upper-section']) && $score_sheet['upper-section']['ones'] === 0) checked="checked" @endif @if(array_key_exists('ones', $score_sheet['upper-section'])) disabled="disabled" @endif>
                        </div>
                    </div>
                    <div class="col-2">
                        <div class="text-center">
                            <input class="form-check-input @if(array_key_exists('twos', $score_sheet['upper-section'])) disabled @else active @endif" type="checkbox" id="scratch_twos" value="twos" aria-label="Scratch the twos" @if(array_key_exists('twos', $score_sheet['upper-section']) && $score_sheet['upper-section']['twos'] === 0) checked="checked" @endif @if(array_key_exists('twos', $score_sheet['upper-section'])) disabled="disabled" @endif>
                        </div>
                    </div>
                    <div class="col-2">
                        <div class="text-center">
                            <input class="form-check-input @if(array_key_exists('threes', $score_sheet['upper-section'])) disabled @else active @endif" type="checkbox" id="scratch_threes" value="threes" aria-label="Scratch the threes" @if(array_key_exists('threes', $score_sheet['upper-section']) && $score_sheet['upper-section']['threes'] === 0) checked="checked" @endif @if(array_key_exists('threes', $score_sheet['upper-section'])) disabled="disabled" @endif>
                        </div>
                    </div>
                    <div class="col-2">
                        <div class="text-center">
                            <input class="form-check-input @if(array_key_exists('fours', $score_sheet['upper-section'])) disabled @else active @endif" type="checkbox" id="scratch_fours" value="fours" aria-label="Scratch the fours" @if(array_key_exists('fours', $score_sheet['upper-section']) && $score_sheet['upper-section']['fours'] === 0) checked="checked" @endif @if(array_key_exists('fours', $score_sheet['upper-section'])) disabled="disabled" @endif>
                        </div>
                    </div>
                    <div class="col-2">
                        <div class="text-center">
                            <input class="form-check-input @if(array_key_exists('fives', $score_sheet['upper-section'])) disabled @else active @endif" type="checkbox" id="scratch_fives" value="fives" aria-label="Scratch the fives" @if(array_key_exists('fives', $score_sheet['upper-section']) && $score_sheet['upper-section']['fives'] === 0) checked="checked" @endif @if(array_key_exists('fives', $score_sheet['upper-section'])) disabled="disabled" @endif>
                        </div>
                    </div>
                    <div class="col-2">
                        <div class="text-center">
                            <input class="form-check-input @if(array_key_exists('sixes', $score_sheet['upper-section'])) disabled @else active @endif" type="checkbox" id="scratch_sixes" value="sixes" aria-label="Scratch the sixes" @if(array_key_exists('sixes', $score_sheet['upper-section']) && $score_sheet['upper-section']['sixes'] === 0) checked="checked" @endif  @if(array_key_exists('sixes', $score_sheet['upper-section'])) disabled="disabled" @endif>
                        </div>
                    </div>
                </div>
                <div class="row">
                    <div class="col-4">
                        <h3 class="text-center score"><strong>Upper</strong></h3>
                        <h2 class="text-center mb-0 score" id="upper-score">{{ $score_sheet['score']['upper'] }}</h2>
                    </div>
                    <div class="col-4">
                        <h3 class="text-center score"><strong>Bonus</strong></h3>
                        <h2 class="text-center mb-0 score bonus" id="upper-bonus">{{ $score_sheet['score']['bonus'] }}</h2>
                    </div>
                    <div class="col-4">
                        <h3 class="text-center score text-black"><strong>Total</strong></h3>
                        <h2 class="text-center mb-0 score total" id="upper-total">{{ $score_sheet['score']['upper'] + $score_sheet['score']['bonus'] }}</h2>
                    </div>
                </div>

                <div class="row">
                    <div class="col-12">
                        <hr />
                        <h2 class="text-primary">Lower Section</h2>
                    </div>
                </div>
                <div class="row">
                    <div class="col-6">
                        <h5 class="mb-0">Three of a kind</h5>
                    </div>
                    <div class="col-3 text-center">
                        <h6>Score</h6>
                    </div>
                    <div class="col-3 text-center">
                        <h6>Scratch</h6>
                    </div>
                    <div class="col-6">
                        <p class="mb-0">
                            <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" fill="currentColor" class="bi bi-dice-3" viewBox="0 0 16 16">
                                <path d="M13 1a2 2 0 0 1 2 2v10a2 2 0 0 1-2 2H3a2 2 0 0 1-2-2V3a2 2 0 0 1 2-2h10zM3 0a3 3 0 0 0-3 3v10a3 3 0 0 0 3 3h10a3 3 0 0 0 3-3V3a3 3 0 0 0-3-3H3z"/>
                                <path d="M5.5 4a1.5 1.5 0 1 1-3 0 1.5 1.5 0 0 1 3 0zm8 8a1.5 1.5 0 1 1-3 0 1.5 1.5 0 0 1 3 0zm-4-4a1.5 1.5 0 1 1-3 0 1.5 1.5 0 0 1 3 0z"/>
                            </svg>
                            <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" fill="currentColor" class="bi bi-dice-3" viewBox="0 0 16 16">
                                <path d="M13 1a2 2 0 0 1 2 2v10a2 2 0 0 1-2 2H3a2 2 0 0 1-2-2V3a2 2 0 0 1 2-2h10zM3 0a3 3 0 0 0-3 3v10a3 3 0 0 0 3 3h10a3 3 0 0 0 3-3V3a3 3 0 0 0-3-3H3z"/>
                                <path d="M5.5 4a1.5 1.5 0 1 1-3 0 1.5 1.5 0 0 1 3 0zm8 8a1.5 1.5 0 1 1-3 0 1.5 1.5 0 0 1 3 0zm-4-4a1.5 1.5 0 1 1-3 0 1.5 1.5 0 0 1 3 0z"/>
                            </svg>
                            <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" fill="currentColor" class="bi bi-dice-3" viewBox="0 0 16 16">
                                <path d="M13 1a2 2 0 0 1 2 2v10a2 2 0 0 1-2 2H3a2 2 0 0 1-2-2V3a2 2 0 0 1 2-2h10zM3 0a3 3 0 0 0-3 3v10a3 3 0 0 0 3 3h10a3 3 0 0 0 3-3V3a3 3 0 0 0-3-3H3z"/>
                                <path d="M5.5 4a1.5 1.5 0 1 1-3 0 1.5 1.5 0 0 1 3 0zm8 8a1.5 1.5 0 1 1-3 0 1.5 1.5 0 0 1 3 0zm-4-4a1.5 1.5 0 1 1-3 0 1.5 1.5 0 0 1 3 0z"/>
                            </svg>
                        </p>
                        <p class="mb-2 text-muted">
                            Score total of all dice.
                        </p>
                    </div>
                    <div class="col-3">
                        <input type="number" min="6" max="30" step="1" size="2" class="form-control form-control-sm" id="three_of_a_kind" placeholder="" value="">
                        <label for="three_of_a_kind" class="visually-hidden">Three of a kind</label>
                    </div>
                    <div class="col-3">
                        <div class="form-check">
                            <input class="form-check-input mx-auto" type="checkbox" value="" id="yahtzee_scratch">
                            <label class="form-check-label visually-hidden" for="yahtzee_scratch">Yahtzee (scratch)</label>
                        </div>
                    </div>
                </div>

                <div class="row">
                    <div class="col-6">
                        <h5 class="mb-0">Four of a kind</h5>
                    </div>
                    <div class="col-3 text-center">
                        <h6>Score</h6>
                    </div>
                    <div class="col-3 text-center">
                        <h6>Scratch</h6>
                    </div>
                    <div class="col-6">
                        <p class="mb-0">
                            <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" fill="currentColor" class="bi bi-dice-4" viewBox="0 0 16 16">
                                <path d="M13 1a2 2 0 0 1 2 2v10a2 2 0 0 1-2 2H3a2 2 0 0 1-2-2V3a2 2 0 0 1 2-2h10zM3 0a3 3 0 0 0-3 3v10a3 3 0 0 0 3 3h10a3 3 0 0 0 3-3V3a3 3 0 0 0-3-3H3z"/>
                                <path d="M5.5 4a1.5 1.5 0 1 1-3 0 1.5 1.5 0 0 1 3 0zm8 0a1.5 1.5 0 1 1-3 0 1.5 1.5 0 0 1 3 0zm0 8a1.5 1.5 0 1 1-3 0 1.5 1.5 0 0 1 3 0zm-8 0a1.5 1.5 0 1 1-3 0 1.5 1.5 0 0 1 3 0z"/>
                            </svg>
                            <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" fill="currentColor" class="bi bi-dice-4" viewBox="0 0 16 16">
                                <path d="M13 1a2 2 0 0 1 2 2v10a2 2 0 0 1-2 2H3a2 2 0 0 1-2-2V3a2 2 0 0 1 2-2h10zM3 0a3 3 0 0 0-3 3v10a3 3 0 0 0 3 3h10a3 3 0 0 0 3-3V3a3 3 0 0 0-3-3H3z"/>
                                <path d="M5.5 4a1.5 1.5 0 1 1-3 0 1.5 1.5 0 0 1 3 0zm8 0a1.5 1.5 0 1 1-3 0 1.5 1.5 0 0 1 3 0zm0 8a1.5 1.5 0 1 1-3 0 1.5 1.5 0 0 1 3 0zm-8 0a1.5 1.5 0 1 1-3 0 1.5 1.5 0 0 1 3 0z"/>
                            </svg>
                            <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" fill="currentColor" class="bi bi-dice-4" viewBox="0 0 16 16">
                                <path d="M13 1a2 2 0 0 1 2 2v10a2 2 0 0 1-2 2H3a2 2 0 0 1-2-2V3a2 2 0 0 1 2-2h10zM3 0a3 3 0 0 0-3 3v10a3 3 0 0 0 3 3h10a3 3 0 0 0 3-3V3a3 3 0 0 0-3-3H3z"/>
                                <path d="M5.5 4a1.5 1.5 0 1 1-3 0 1.5 1.5 0 0 1 3 0zm8 0a1.5 1.5 0 1 1-3 0 1.5 1.5 0 0 1 3 0zm0 8a1.5 1.5 0 1 1-3 0 1.5 1.5 0 0 1 3 0zm-8 0a1.5 1.5 0 1 1-3 0 1.5 1.5 0 0 1 3 0z"/>
                            </svg>
                            <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" fill="currentColor" class="bi bi-dice-4" viewBox="0 0 16 16">
                                <path d="M13 1a2 2 0 0 1 2 2v10a2 2 0 0 1-2 2H3a2 2 0 0 1-2-2V3a2 2 0 0 1 2-2h10zM3 0a3 3 0 0 0-3 3v10a3 3 0 0 0 3 3h10a3 3 0 0 0 3-3V3a3 3 0 0 0-3-3H3z"/>
                                <path d="M5.5 4a1.5 1.5 0 1 1-3 0 1.5 1.5 0 0 1 3 0zm8 0a1.5 1.5 0 1 1-3 0 1.5 1.5 0 0 1 3 0zm0 8a1.5 1.5 0 1 1-3 0 1.5 1.5 0 0 1 3 0zm-8 0a1.5 1.5 0 1 1-3 0 1.5 1.5 0 0 1 3 0z"/>
                            </svg>
                        </p>
                        <p class="mb-2 text-muted">
                            Score total of all dice.
                        </p>
                    </div>
                    <div class="col-3">
                        <input type="number" min="6" max="30" step="1" size="2" class="form-control form-control-sm" id="four_of_a_kind" placeholder="" value="">
                        <label for="four_of_a_kind" class="visually-hidden">Four of a kind</label>
                    </div>
                    <div class="col-3">
                        <div class="form-check">
                            <input class="form-check-input mx-auto" type="checkbox" value="" id="yahtzee_scratch">
                            <label class="form-check-label visually-hidden" for="yahtzee_scratch">Yahtzee (scratch)</label>
                        </div>
                    </div>
                </div>

                <div class="row">
                    <div class="col-6">
                        <h5 class="mb-0">Full House</h5>
                    </div>
                    <div class="col-3 text-center">
                        <h6>Score</h6>
                    </div>
                    <div class="col-3 text-center">
                        <h6>Scratch</h6>
                    </div>
                    <div class="col-6">
                        <p class="mb-0">
                            <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" fill="currentColor" class="bi bi-dice-4" viewBox="0 0 16 16">
                                <path d="M13 1a2 2 0 0 1 2 2v10a2 2 0 0 1-2 2H3a2 2 0 0 1-2-2V3a2 2 0 0 1 2-2h10zM3 0a3 3 0 0 0-3 3v10a3 3 0 0 0 3 3h10a3 3 0 0 0 3-3V3a3 3 0 0 0-3-3H3z"/>
                                <path d="M5.5 4a1.5 1.5 0 1 1-3 0 1.5 1.5 0 0 1 3 0zm8 0a1.5 1.5 0 1 1-3 0 1.5 1.5 0 0 1 3 0zm0 8a1.5 1.5 0 1 1-3 0 1.5 1.5 0 0 1 3 0zm-8 0a1.5 1.5 0 1 1-3 0 1.5 1.5 0 0 1 3 0z"/>
                            </svg>
                            <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" fill="currentColor" class="bi bi-dice-4" viewBox="0 0 16 16">
                                <path d="M13 1a2 2 0 0 1 2 2v10a2 2 0 0 1-2 2H3a2 2 0 0 1-2-2V3a2 2 0 0 1 2-2h10zM3 0a3 3 0 0 0-3 3v10a3 3 0 0 0 3 3h10a3 3 0 0 0 3-3V3a3 3 0 0 0-3-3H3z"/>
                                <path d="M5.5 4a1.5 1.5 0 1 1-3 0 1.5 1.5 0 0 1 3 0zm8 0a1.5 1.5 0 1 1-3 0 1.5 1.5 0 0 1 3 0zm0 8a1.5 1.5 0 1 1-3 0 1.5 1.5 0 0 1 3 0zm-8 0a1.5 1.5 0 1 1-3 0 1.5 1.5 0 0 1 3 0z"/>
                            </svg>
                            <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" fill="currentColor" class="bi bi-dice-4" viewBox="0 0 16 16">
                                <path d="M13 1a2 2 0 0 1 2 2v10a2 2 0 0 1-2 2H3a2 2 0 0 1-2-2V3a2 2 0 0 1 2-2h10zM3 0a3 3 0 0 0-3 3v10a3 3 0 0 0 3 3h10a3 3 0 0 0 3-3V3a3 3 0 0 0-3-3H3z"/>
                                <path d="M5.5 4a1.5 1.5 0 1 1-3 0 1.5 1.5 0 0 1 3 0zm8 0a1.5 1.5 0 1 1-3 0 1.5 1.5 0 0 1 3 0zm0 8a1.5 1.5 0 1 1-3 0 1.5 1.5 0 0 1 3 0zm-8 0a1.5 1.5 0 1 1-3 0 1.5 1.5 0 0 1 3 0z"/>
                            </svg>
                            <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" fill="currentColor" class="bi bi-dice-3" viewBox="0 0 16 16">
                                <path d="M13 1a2 2 0 0 1 2 2v10a2 2 0 0 1-2 2H3a2 2 0 0 1-2-2V3a2 2 0 0 1 2-2h10zM3 0a3 3 0 0 0-3 3v10a3 3 0 0 0 3 3h10a3 3 0 0 0 3-3V3a3 3 0 0 0-3-3H3z"/>
                                <path d="M5.5 4a1.5 1.5 0 1 1-3 0 1.5 1.5 0 0 1 3 0zm8 8a1.5 1.5 0 1 1-3 0 1.5 1.5 0 0 1 3 0zm-4-4a1.5 1.5 0 1 1-3 0 1.5 1.5 0 0 1 3 0z"/>
                            </svg>
                            <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" fill="currentColor" class="bi bi-dice-3" viewBox="0 0 16 16">
                                <path d="M13 1a2 2 0 0 1 2 2v10a2 2 0 0 1-2 2H3a2 2 0 0 1-2-2V3a2 2 0 0 1 2-2h10zM3 0a3 3 0 0 0-3 3v10a3 3 0 0 0 3 3h10a3 3 0 0 0 3-3V3a3 3 0 0 0-3-3H3z"/>
                                <path d="M5.5 4a1.5 1.5 0 1 1-3 0 1.5 1.5 0 0 1 3 0zm8 8a1.5 1.5 0 1 1-3 0 1.5 1.5 0 0 1 3 0zm-4-4a1.5 1.5 0 1 1-3 0 1.5 1.5 0 0 1 3 0z"/>
                            </svg>
                        </p>
                        <p class="mb-2 text-muted">
                            Score 25
                        </p>
                    </div>
                    <div class="col-3">
                        <div class="form-check">
                            <input class="form-check-input mx-auto" type="checkbox" value="" id="yahtzee_check">
                            <label class="form-check-label visually-hidden" for="yahtzee_check">Yahtzee</label>
                        </div>
                        <input type="hidden" id="yahtzee" value="" />
                        <label for="yahtzee" class="visually-hidden">Yahtzee</label>
                    </div>
                    <div class="col-3">
                        <div class="form-check">
                            <input class="form-check-input mx-auto" type="checkbox" value="" id="yahtzee_scratch">
                            <label class="form-check-label visually-hidden" for="yahtzee_scratch">Yahtzee (scratch)</label>
                        </div>
                    </div>
                </div>

                <div class="row">
                    <div class="col-6">
                        <h5 class="mb-0">Small Straight</h5>
                        <p class="text-muted small mb-0">Sequence of four</p>
                    </div>
                    <div class="col-3 text-center">
                        <h6>Score</h6>
                    </div>
                    <div class="col-3 text-center">
                        <h6>Scratch</h6>
                    </div>
                    <div class="col-6">
                        <p class="mb-0">
                            <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" fill="currentColor" class="bi bi-dice-1" viewBox="0 0 16 16">
                                <circle cx="8" cy="8" r="1.5"/>
                                <path d="M13 1a2 2 0 0 1 2 2v10a2 2 0 0 1-2 2H3a2 2 0 0 1-2-2V3a2 2 0 0 1 2-2h10zM3 0a3 3 0 0 0-3 3v10a3 3 0 0 0 3 3h10a3 3 0 0 0 3-3V3a3 3 0 0 0-3-3H3z"/>
                            </svg>
                            <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" fill="currentColor" class="bi bi-dice-2" viewBox="0 0 16 16">
                                <path d="M13 1a2 2 0 0 1 2 2v10a2 2 0 0 1-2 2H3a2 2 0 0 1-2-2V3a2 2 0 0 1 2-2h10zM3 0a3 3 0 0 0-3 3v10a3 3 0 0 0 3 3h10a3 3 0 0 0 3-3V3a3 3 0 0 0-3-3H3z"/>
                                <path d="M5.5 4a1.5 1.5 0 1 1-3 0 1.5 1.5 0 0 1 3 0zm8 8a1.5 1.5 0 1 1-3 0 1.5 1.5 0 0 1 3 0z"/>
                            </svg>
                            <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" fill="currentColor" class="bi bi-dice-3" viewBox="0 0 16 16">
                                <path d="M13 1a2 2 0 0 1 2 2v10a2 2 0 0 1-2 2H3a2 2 0 0 1-2-2V3a2 2 0 0 1 2-2h10zM3 0a3 3 0 0 0-3 3v10a3 3 0 0 0 3 3h10a3 3 0 0 0 3-3V3a3 3 0 0 0-3-3H3z"/>
                                <path d="M5.5 4a1.5 1.5 0 1 1-3 0 1.5 1.5 0 0 1 3 0zm8 8a1.5 1.5 0 1 1-3 0 1.5 1.5 0 0 1 3 0zm-4-4a1.5 1.5 0 1 1-3 0 1.5 1.5 0 0 1 3 0z"/>
                            </svg>
                            <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" fill="currentColor" class="bi bi-dice-4" viewBox="0 0 16 16">
                                <path d="M13 1a2 2 0 0 1 2 2v10a2 2 0 0 1-2 2H3a2 2 0 0 1-2-2V3a2 2 0 0 1 2-2h10zM3 0a3 3 0 0 0-3 3v10a3 3 0 0 0 3 3h10a3 3 0 0 0 3-3V3a3 3 0 0 0-3-3H3z"/>
                                <path d="M5.5 4a1.5 1.5 0 1 1-3 0 1.5 1.5 0 0 1 3 0zm8 0a1.5 1.5 0 1 1-3 0 1.5 1.5 0 0 1 3 0zm0 8a1.5 1.5 0 1 1-3 0 1.5 1.5 0 0 1 3 0zm-8 0a1.5 1.5 0 1 1-3 0 1.5 1.5 0 0 1 3 0z"/>
                            </svg>
                        </p>
                        <p class="mb-2 text-muted">
                            Score 30
                        </p>
                    </div>
                    <div class="col-3">
                        <div class="form-check">
                            <input class="form-check-input mx-auto" type="checkbox" value="" id="yahtzee_check">
                            <label class="form-check-label visually-hidden" for="yahtzee_check">Yahtzee</label>
                        </div>
                        <input type="hidden" id="yahtzee" value="" />
                        <label for="yahtzee" class="visually-hidden">Yahtzee</label>
                    </div>
                    <div class="col-3">
                        <div class="form-check">
                            <input class="form-check-input mx-auto" type="checkbox" value="" id="yahtzee_scratch">
                            <label class="form-check-label visually-hidden" for="yahtzee_scratch">Yahtzee (scratch)</label>
                        </div>
                    </div>
                </div>

                <div class="row">
                    <div class="col-6">
                        <h5 class="mb-0">Large Straight</h5>
                        <p class="text-muted small mb-0">Sequence of five</p>
                    </div>
                    <div class="col-3 text-center">
                        <h6>Score</h6>
                    </div>
                    <div class="col-3 text-center">
                        <h6>Scratch</h6>
                    </div>
                    <div class="col-6">
                        <p class="mb-0">
                            <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" fill="currentColor" class="bi bi-dice-1" viewBox="0 0 16 16">
                                <circle cx="8" cy="8" r="1.5"/>
                                <path d="M13 1a2 2 0 0 1 2 2v10a2 2 0 0 1-2 2H3a2 2 0 0 1-2-2V3a2 2 0 0 1 2-2h10zM3 0a3 3 0 0 0-3 3v10a3 3 0 0 0 3 3h10a3 3 0 0 0 3-3V3a3 3 0 0 0-3-3H3z"/>
                            </svg>
                            <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" fill="currentColor" class="bi bi-dice-2" viewBox="0 0 16 16">
                                <path d="M13 1a2 2 0 0 1 2 2v10a2 2 0 0 1-2 2H3a2 2 0 0 1-2-2V3a2 2 0 0 1 2-2h10zM3 0a3 3 0 0 0-3 3v10a3 3 0 0 0 3 3h10a3 3 0 0 0 3-3V3a3 3 0 0 0-3-3H3z"/>
                                <path d="M5.5 4a1.5 1.5 0 1 1-3 0 1.5 1.5 0 0 1 3 0zm8 8a1.5 1.5 0 1 1-3 0 1.5 1.5 0 0 1 3 0z"/>
                            </svg>
                            <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" fill="currentColor" class="bi bi-dice-3" viewBox="0 0 16 16">
                                <path d="M13 1a2 2 0 0 1 2 2v10a2 2 0 0 1-2 2H3a2 2 0 0 1-2-2V3a2 2 0 0 1 2-2h10zM3 0a3 3 0 0 0-3 3v10a3 3 0 0 0 3 3h10a3 3 0 0 0 3-3V3a3 3 0 0 0-3-3H3z"/>
                                <path d="M5.5 4a1.5 1.5 0 1 1-3 0 1.5 1.5 0 0 1 3 0zm8 8a1.5 1.5 0 1 1-3 0 1.5 1.5 0 0 1 3 0zm-4-4a1.5 1.5 0 1 1-3 0 1.5 1.5 0 0 1 3 0z"/>
                            </svg>
                            <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" fill="currentColor" class="bi bi-dice-4" viewBox="0 0 16 16">
                                <path d="M13 1a2 2 0 0 1 2 2v10a2 2 0 0 1-2 2H3a2 2 0 0 1-2-2V3a2 2 0 0 1 2-2h10zM3 0a3 3 0 0 0-3 3v10a3 3 0 0 0 3 3h10a3 3 0 0 0 3-3V3a3 3 0 0 0-3-3H3z"/>
                                <path d="M5.5 4a1.5 1.5 0 1 1-3 0 1.5 1.5 0 0 1 3 0zm8 0a1.5 1.5 0 1 1-3 0 1.5 1.5 0 0 1 3 0zm0 8a1.5 1.5 0 1 1-3 0 1.5 1.5 0 0 1 3 0zm-8 0a1.5 1.5 0 1 1-3 0 1.5 1.5 0 0 1 3 0z"/>
                            </svg>
                            <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" fill="currentColor" class="bi bi-dice-5" viewBox="0 0 16 16">
                                <path d="M13 1a2 2 0 0 1 2 2v10a2 2 0 0 1-2 2H3a2 2 0 0 1-2-2V3a2 2 0 0 1 2-2h10zM3 0a3 3 0 0 0-3 3v10a3 3 0 0 0 3 3h10a3 3 0 0 0 3-3V3a3 3 0 0 0-3-3H3z"/>
                                <path d="M5.5 4a1.5 1.5 0 1 1-3 0 1.5 1.5 0 0 1 3 0zm8 0a1.5 1.5 0 1 1-3 0 1.5 1.5 0 0 1 3 0zm0 8a1.5 1.5 0 1 1-3 0 1.5 1.5 0 0 1 3 0zm-8 0a1.5 1.5 0 1 1-3 0 1.5 1.5 0 0 1 3 0zm4-4a1.5 1.5 0 1 1-3 0 1.5 1.5 0 0 1 3 0z"/>
                            </svg>
                        </p>
                        <p class="mb-2 text-muted">
                            Score 40
                        </p>
                    </div>
                    <div class="col-3">
                        <div class="form-check">
                            <input class="form-check-input mx-auto" type="checkbox" value="" id="yahtzee_check">
                            <label class="form-check-label visually-hidden" for="yahtzee_check">Yahtzee</label>
                        </div>
                        <input type="hidden" id="yahtzee" value="" />
                        <label for="yahtzee" class="visually-hidden">Yahtzee</label>
                    </div>
                    <div class="col-3">
                        <div class="form-check">
                            <input class="form-check-input mx-auto" type="checkbox" value="" id="yahtzee_scratch">
                            <label class="form-check-label visually-hidden" for="yahtzee_scratch">Yahtzee (scratch)</label>
                        </div>
                    </div>
                </div>

                <div class="row">
                    <div class="col-6">
                        <h5 class="mb-0">Yahtzee</h5>
                        <p class="text-muted small mb-0">Five of a kind</p>
                    </div>
                    <div class="col-3 text-center">
                        <h6>Score</h6>
                    </div>
                    <div class="col-3 text-center">
                        <h6>Scratch</h6>
                    </div>
                    <div class="col-6">
                        <p class="mb-0">
                            <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" fill="currentColor" class="bi bi-dice-6" viewBox="0 0 16 16">
                                <path d="M13 1a2 2 0 0 1 2 2v10a2 2 0 0 1-2 2H3a2 2 0 0 1-2-2V3a2 2 0 0 1 2-2h10zM3 0a3 3 0 0 0-3 3v10a3 3 0 0 0 3 3h10a3 3 0 0 0 3-3V3a3 3 0 0 0-3-3H3z"/>
                                <path d="M5.5 4a1.5 1.5 0 1 1-3 0 1.5 1.5 0 0 1 3 0zm8 0a1.5 1.5 0 1 1-3 0 1.5 1.5 0 0 1 3 0zm0 8a1.5 1.5 0 1 1-3 0 1.5 1.5 0 0 1 3 0zm0-4a1.5 1.5 0 1 1-3 0 1.5 1.5 0 0 1 3 0zm-8 4a1.5 1.5 0 1 1-3 0 1.5 1.5 0 0 1 3 0zm0-4a1.5 1.5 0 1 1-3 0 1.5 1.5 0 0 1 3 0z"/>
                            </svg>
                            <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" fill="currentColor" class="bi bi-dice-6" viewBox="0 0 16 16">
                                <path d="M13 1a2 2 0 0 1 2 2v10a2 2 0 0 1-2 2H3a2 2 0 0 1-2-2V3a2 2 0 0 1 2-2h10zM3 0a3 3 0 0 0-3 3v10a3 3 0 0 0 3 3h10a3 3 0 0 0 3-3V3a3 3 0 0 0-3-3H3z"/>
                                <path d="M5.5 4a1.5 1.5 0 1 1-3 0 1.5 1.5 0 0 1 3 0zm8 0a1.5 1.5 0 1 1-3 0 1.5 1.5 0 0 1 3 0zm0 8a1.5 1.5 0 1 1-3 0 1.5 1.5 0 0 1 3 0zm0-4a1.5 1.5 0 1 1-3 0 1.5 1.5 0 0 1 3 0zm-8 4a1.5 1.5 0 1 1-3 0 1.5 1.5 0 0 1 3 0zm0-4a1.5 1.5 0 1 1-3 0 1.5 1.5 0 0 1 3 0z"/>
                            </svg>
                            <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" fill="currentColor" class="bi bi-dice-6" viewBox="0 0 16 16">
                                <path d="M13 1a2 2 0 0 1 2 2v10a2 2 0 0 1-2 2H3a2 2 0 0 1-2-2V3a2 2 0 0 1 2-2h10zM3 0a3 3 0 0 0-3 3v10a3 3 0 0 0 3 3h10a3 3 0 0 0 3-3V3a3 3 0 0 0-3-3H3z"/>
                                <path d="M5.5 4a1.5 1.5 0 1 1-3 0 1.5 1.5 0 0 1 3 0zm8 0a1.5 1.5 0 1 1-3 0 1.5 1.5 0 0 1 3 0zm0 8a1.5 1.5 0 1 1-3 0 1.5 1.5 0 0 1 3 0zm0-4a1.5 1.5 0 1 1-3 0 1.5 1.5 0 0 1 3 0zm-8 4a1.5 1.5 0 1 1-3 0 1.5 1.5 0 0 1 3 0zm0-4a1.5 1.5 0 1 1-3 0 1.5 1.5 0 0 1 3 0z"/>
                            </svg>
                            <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" fill="currentColor" class="bi bi-dice-6" viewBox="0 0 16 16">
                                <path d="M13 1a2 2 0 0 1 2 2v10a2 2 0 0 1-2 2H3a2 2 0 0 1-2-2V3a2 2 0 0 1 2-2h10zM3 0a3 3 0 0 0-3 3v10a3 3 0 0 0 3 3h10a3 3 0 0 0 3-3V3a3 3 0 0 0-3-3H3z"/>
                                <path d="M5.5 4a1.5 1.5 0 1 1-3 0 1.5 1.5 0 0 1 3 0zm8 0a1.5 1.5 0 1 1-3 0 1.5 1.5 0 0 1 3 0zm0 8a1.5 1.5 0 1 1-3 0 1.5 1.5 0 0 1 3 0zm0-4a1.5 1.5 0 1 1-3 0 1.5 1.5 0 0 1 3 0zm-8 4a1.5 1.5 0 1 1-3 0 1.5 1.5 0 0 1 3 0zm0-4a1.5 1.5 0 1 1-3 0 1.5 1.5 0 0 1 3 0z"/>
                            </svg>
                            <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" fill="currentColor" class="bi bi-dice-6" viewBox="0 0 16 16">
                                <path d="M13 1a2 2 0 0 1 2 2v10a2 2 0 0 1-2 2H3a2 2 0 0 1-2-2V3a2 2 0 0 1 2-2h10zM3 0a3 3 0 0 0-3 3v10a3 3 0 0 0 3 3h10a3 3 0 0 0 3-3V3a3 3 0 0 0-3-3H3z"/>
                                <path d="M5.5 4a1.5 1.5 0 1 1-3 0 1.5 1.5 0 0 1 3 0zm8 0a1.5 1.5 0 1 1-3 0 1.5 1.5 0 0 1 3 0zm0 8a1.5 1.5 0 1 1-3 0 1.5 1.5 0 0 1 3 0zm0-4a1.5 1.5 0 1 1-3 0 1.5 1.5 0 0 1 3 0zm-8 4a1.5 1.5 0 1 1-3 0 1.5 1.5 0 0 1 3 0zm0-4a1.5 1.5 0 1 1-3 0 1.5 1.5 0 0 1 3 0z"/>
                            </svg>
                        </p>
                        <p class="mb-2 text-muted">
                            Score 50
                        </p>
                    </div>
                    <div class="col-3">
                        <div class="form-check">
                            <input class="form-check-input mx-auto" type="checkbox" value="" id="yahtzee_check">
                            <label class="form-check-label visually-hidden" for="yahtzee_check">Yahtzee</label>
                        </div>
                        <input type="hidden" id="yahtzee" value="" />
                        <label for="yahtzee" class="visually-hidden">Yahtzee</label>
                    </div>
                    <div class="col-3">
                        <div class="form-check">
                            <input class="form-check-input mx-auto" type="checkbox" value="" id="yahtzee_scratch">
                            <label class="form-check-label visually-hidden" for="yahtzee_scratch">Yahtzee (scratch)</label>
                        </div>
                    </div>
                </div>

                <div class="row">
                    <div class="col-6">
                        <h5 class="mb-0">Chance</h5>
                    </div>
                    <div class="col-3 text-center">
                        <h6>Score</h6>
                    </div>
                    <div class="col-3 text-center">
                        <h6>Scratch</h6>
                    </div>
                    <div class="col-6">
                        <p class="mb-0">
                            <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" fill="currentColor" class="bi bi-dice-3" viewBox="0 0 16 16">
                                <path d="M13 1a2 2 0 0 1 2 2v10a2 2 0 0 1-2 2H3a2 2 0 0 1-2-2V3a2 2 0 0 1 2-2h10zM3 0a3 3 0 0 0-3 3v10a3 3 0 0 0 3 3h10a3 3 0 0 0 3-3V3a3 3 0 0 0-3-3H3z"/>
                                <path d="M5.5 4a1.5 1.5 0 1 1-3 0 1.5 1.5 0 0 1 3 0zm8 8a1.5 1.5 0 1 1-3 0 1.5 1.5 0 0 1 3 0zm-4-4a1.5 1.5 0 1 1-3 0 1.5 1.5 0 0 1 3 0z"/>
                            </svg>
                            <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" fill="currentColor" class="bi bi-dice-6" viewBox="0 0 16 16">
                                <path d="M13 1a2 2 0 0 1 2 2v10a2 2 0 0 1-2 2H3a2 2 0 0 1-2-2V3a2 2 0 0 1 2-2h10zM3 0a3 3 0 0 0-3 3v10a3 3 0 0 0 3 3h10a3 3 0 0 0 3-3V3a3 3 0 0 0-3-3H3z"/>
                                <path d="M5.5 4a1.5 1.5 0 1 1-3 0 1.5 1.5 0 0 1 3 0zm8 0a1.5 1.5 0 1 1-3 0 1.5 1.5 0 0 1 3 0zm0 8a1.5 1.5 0 1 1-3 0 1.5 1.5 0 0 1 3 0zm0-4a1.5 1.5 0 1 1-3 0 1.5 1.5 0 0 1 3 0zm-8 4a1.5 1.5 0 1 1-3 0 1.5 1.5 0 0 1 3 0zm0-4a1.5 1.5 0 1 1-3 0 1.5 1.5 0 0 1 3 0z"/>
                            </svg>
                            <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" fill="currentColor" class="bi bi-dice-2" viewBox="0 0 16 16">
                                <path d="M13 1a2 2 0 0 1 2 2v10a2 2 0 0 1-2 2H3a2 2 0 0 1-2-2V3a2 2 0 0 1 2-2h10zM3 0a3 3 0 0 0-3 3v10a3 3 0 0 0 3 3h10a3 3 0 0 0 3-3V3a3 3 0 0 0-3-3H3z"/>
                                <path d="M5.5 4a1.5 1.5 0 1 1-3 0 1.5 1.5 0 0 1 3 0zm8 8a1.5 1.5 0 1 1-3 0 1.5 1.5 0 0 1 3 0z"/>
                            </svg>
                            <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" fill="currentColor" class="bi bi-dice-5" viewBox="0 0 16 16">
                                <path d="M13 1a2 2 0 0 1 2 2v10a2 2 0 0 1-2 2H3a2 2 0 0 1-2-2V3a2 2 0 0 1 2-2h10zM3 0a3 3 0 0 0-3 3v10a3 3 0 0 0 3 3h10a3 3 0 0 0 3-3V3a3 3 0 0 0-3-3H3z"/>
                                <path d="M5.5 4a1.5 1.5 0 1 1-3 0 1.5 1.5 0 0 1 3 0zm8 0a1.5 1.5 0 1 1-3 0 1.5 1.5 0 0 1 3 0zm0 8a1.5 1.5 0 1 1-3 0 1.5 1.5 0 0 1 3 0zm-8 0a1.5 1.5 0 1 1-3 0 1.5 1.5 0 0 1 3 0zm4-4a1.5 1.5 0 1 1-3 0 1.5 1.5 0 0 1 3 0z"/>
                            </svg>
                            <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" fill="currentColor" class="bi bi-dice-1" viewBox="0 0 16 16">
                                <circle cx="8" cy="8" r="1.5"/>
                                <path d="M13 1a2 2 0 0 1 2 2v10a2 2 0 0 1-2 2H3a2 2 0 0 1-2-2V3a2 2 0 0 1 2-2h10zM3 0a3 3 0 0 0-3 3v10a3 3 0 0 0 3 3h10a3 3 0 0 0 3-3V3a3 3 0 0 0-3-3H3z"/>
                            </svg>
                        </p>
                        <p class="mb-2 text-muted">
                            Score total of all dice
                        </p>
                    </div>
                    <div class="col-3">
                        <input type="number" min="6" max="30" step="1" size="2" class="form-control form-control-sm" id="four_of_a_kind" placeholder="" value="">
                        <label for="four_of_a_kind" class="visually-hidden">Four of a kind</label>
                    </div>
                    <div class="col-3">
                        <div class="form-check">
                            <input class="form-check-input mx-auto" type="checkbox" value="" id="yahtzee_scratch">
                            <label class="form-check-label visually-hidden" for="yahtzee_scratch">Yahtzee (scratch)</label>
                        </div>
                    </div>
                </div>

                <div class="row">
                    <div class="col-6">
                        <h5 class="mb-0">Yahtzee Bonus</h5>
                    </div>
                    <div class="col-4 text-center">
                        <h6>Score</h6>
                    </div>
                    <div class="col-6">
                        <p class="mb-0">
                            <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" fill="currentColor" class="bi bi-dice-6" viewBox="0 0 16 16">
                                <path d="M13 1a2 2 0 0 1 2 2v10a2 2 0 0 1-2 2H3a2 2 0 0 1-2-2V3a2 2 0 0 1 2-2h10zM3 0a3 3 0 0 0-3 3v10a3 3 0 0 0 3 3h10a3 3 0 0 0 3-3V3a3 3 0 0 0-3-3H3z"/>
                                <path d="M5.5 4a1.5 1.5 0 1 1-3 0 1.5 1.5 0 0 1 3 0zm8 0a1.5 1.5 0 1 1-3 0 1.5 1.5 0 0 1 3 0zm0 8a1.5 1.5 0 1 1-3 0 1.5 1.5 0 0 1 3 0zm0-4a1.5 1.5 0 1 1-3 0 1.5 1.5 0 0 1 3 0zm-8 4a1.5 1.5 0 1 1-3 0 1.5 1.5 0 0 1 3 0zm0-4a1.5 1.5 0 1 1-3 0 1.5 1.5 0 0 1 3 0z"/>
                            </svg>
                            <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" fill="currentColor" class="bi bi-dice-6" viewBox="0 0 16 16">
                                <path d="M13 1a2 2 0 0 1 2 2v10a2 2 0 0 1-2 2H3a2 2 0 0 1-2-2V3a2 2 0 0 1 2-2h10zM3 0a3 3 0 0 0-3 3v10a3 3 0 0 0 3 3h10a3 3 0 0 0 3-3V3a3 3 0 0 0-3-3H3z"/>
                                <path d="M5.5 4a1.5 1.5 0 1 1-3 0 1.5 1.5 0 0 1 3 0zm8 0a1.5 1.5 0 1 1-3 0 1.5 1.5 0 0 1 3 0zm0 8a1.5 1.5 0 1 1-3 0 1.5 1.5 0 0 1 3 0zm0-4a1.5 1.5 0 1 1-3 0 1.5 1.5 0 0 1 3 0zm-8 4a1.5 1.5 0 1 1-3 0 1.5 1.5 0 0 1 3 0zm0-4a1.5 1.5 0 1 1-3 0 1.5 1.5 0 0 1 3 0z"/>
                            </svg>
                            <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" fill="currentColor" class="bi bi-dice-6" viewBox="0 0 16 16">
                                <path d="M13 1a2 2 0 0 1 2 2v10a2 2 0 0 1-2 2H3a2 2 0 0 1-2-2V3a2 2 0 0 1 2-2h10zM3 0a3 3 0 0 0-3 3v10a3 3 0 0 0 3 3h10a3 3 0 0 0 3-3V3a3 3 0 0 0-3-3H3z"/>
                                <path d="M5.5 4a1.5 1.5 0 1 1-3 0 1.5 1.5 0 0 1 3 0zm8 0a1.5 1.5 0 1 1-3 0 1.5 1.5 0 0 1 3 0zm0 8a1.5 1.5 0 1 1-3 0 1.5 1.5 0 0 1 3 0zm0-4a1.5 1.5 0 1 1-3 0 1.5 1.5 0 0 1 3 0zm-8 4a1.5 1.5 0 1 1-3 0 1.5 1.5 0 0 1 3 0zm0-4a1.5 1.5 0 1 1-3 0 1.5 1.5 0 0 1 3 0z"/>
                            </svg>
                            <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" fill="currentColor" class="bi bi-dice-6" viewBox="0 0 16 16">
                                <path d="M13 1a2 2 0 0 1 2 2v10a2 2 0 0 1-2 2H3a2 2 0 0 1-2-2V3a2 2 0 0 1 2-2h10zM3 0a3 3 0 0 0-3 3v10a3 3 0 0 0 3 3h10a3 3 0 0 0 3-3V3a3 3 0 0 0-3-3H3z"/>
                                <path d="M5.5 4a1.5 1.5 0 1 1-3 0 1.5 1.5 0 0 1 3 0zm8 0a1.5 1.5 0 1 1-3 0 1.5 1.5 0 0 1 3 0zm0 8a1.5 1.5 0 1 1-3 0 1.5 1.5 0 0 1 3 0zm0-4a1.5 1.5 0 1 1-3 0 1.5 1.5 0 0 1 3 0zm-8 4a1.5 1.5 0 1 1-3 0 1.5 1.5 0 0 1 3 0zm0-4a1.5 1.5 0 1 1-3 0 1.5 1.5 0 0 1 3 0z"/>
                            </svg>
                            <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" fill="currentColor" class="bi bi-dice-6" viewBox="0 0 16 16">
                                <path d="M13 1a2 2 0 0 1 2 2v10a2 2 0 0 1-2 2H3a2 2 0 0 1-2-2V3a2 2 0 0 1 2-2h10zM3 0a3 3 0 0 0-3 3v10a3 3 0 0 0 3 3h10a3 3 0 0 0 3-3V3a3 3 0 0 0-3-3H3z"/>
                                <path d="M5.5 4a1.5 1.5 0 1 1-3 0 1.5 1.5 0 0 1 3 0zm8 0a1.5 1.5 0 1 1-3 0 1.5 1.5 0 0 1 3 0zm0 8a1.5 1.5 0 1 1-3 0 1.5 1.5 0 0 1 3 0zm0-4a1.5 1.5 0 1 1-3 0 1.5 1.5 0 0 1 3 0zm-8 4a1.5 1.5 0 1 1-3 0 1.5 1.5 0 0 1 3 0zm0-4a1.5 1.5 0 1 1-3 0 1.5 1.5 0 0 1 3 0z"/>
                            </svg>
                        </p>
                        <p class="mb-1 text-muted">
                            Score 100 per Yahtzee
                        </p>
                    </div>
                    <div class="col-6">
                        <div class="form-check form-check-inline">
                            <input class="form-check-input" type="checkbox" id="inlineCheckbox1" value="option1">
                        </div>
                        <div class="form-check form-check-inline">
                            <input class="form-check-input" type="checkbox" id="inlineCheckbox2" value="option2">
                        </div>
                        <div class="form-check form-check-inline">
                            <input class="form-check-input" type="checkbox" id="inlineCheckbox3" value="option3">
                        </div>
                        <input type="hidden" name="yahtzee_bonus" id="yahtzee_bonus"/>
                    </div>
                </div>
                <div class="row score-lower">
                    <div class="col-4">
                        <h3 class="text-center score"><strong>Upper</strong></h3>
                        <h2 class="text-center mb-0 score" id="lower-upper-total">{{ $score_sheet['score']['upper'] + $score_sheet['score']['bonus'] }}</h2>
                    </div>
                    <div class="col-4">
                        <h3 class="text-center score"><strong>Lower</strong></h3>
                        <h2 class="text-center mb-0 score" id="lower-score">{{ $score_sheet['score']['lower'] }}</h2>
                    </div>
                    <div class="col-4">
                        <h3 class="text-center score text-black"><strong>Total</strong></h3>
                        <h2 class="text-center mb-0 score" id="total">{{ $score_sheet['score']['total'] }}</h2>
                    </div>
                </div>
            </form>
            <footer class="pt-4 my-4 text-muted border-top text-center">
                Created by <a href="https://twitter.com/DBlackborough">Dean Blackborough</a><br />
                powered by the <a href="https://api.costs-to-expect.com">Costs to Expect API</a>

                <div class="mt-3 small">
                    v{{ $config['version'] }} - Released {{ $config['release_date'] }}
                </div>
            </footer>
        </div>
        <script src="{{ asset('node_modules/axios/dist/axios.min.js') }}" defer></script>
        <script src="{{ asset('js/score-sheet.js') }}" defer></script>
    </body>
</html>
