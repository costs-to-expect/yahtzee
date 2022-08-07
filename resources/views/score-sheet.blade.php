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
        <x-offcanvas active="games" />

        <div class="col-lg-8 mx-auto p-3 py-md-5">

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
                            <svg xmlns="http://www.w3.org/2000/svg" width="32" height="32" fill="currentColor" class="bi bi-dice-1 @if(array_key_exists('ones', $score_sheet['upper-section'])) scored @endif" viewBox="0 0 16 16">
                                <circle cx="8" cy="8" r="1.5"/>
                                <path d="M13 1a2 2 0 0 1 2 2v10a2 2 0 0 1-2 2H3a2 2 0 0 1-2-2V3a2 2 0 0 1 2-2h10zM3 0a3 3 0 0 0-3 3v10a3 3 0 0 0 3 3h10a3 3 0 0 0 3-3V3a3 3 0 0 0-3-3H3z"/>
                            </svg>
                        </label>
                        <input type="number" min="1" max="6" step="1" size="1" class="form-control form-control-sm accessible @if(array_key_exists('ones', $score_sheet['upper-section'])) disabled @else active @endif" name="ones" id="ones" placeholder="3" @if(array_key_exists('ones', $score_sheet['upper-section'])) disabled="disabled" value="{{ $score_sheet['upper-section']['ones'] }}" @endif>
                    </div>
                    <div class="col-2 text-center">
                        <label for="twos" class="form-label dice">
                            <svg xmlns="http://www.w3.org/2000/svg" width="32" height="32" fill="currentColor" class="bi bi-dice-2 @if(array_key_exists('twos', $score_sheet['upper-section'])) scored @endif" viewBox="0 0 16 16">
                                <path d="M13 1a2 2 0 0 1 2 2v10a2 2 0 0 1-2 2H3a2 2 0 0 1-2-2V3a2 2 0 0 1 2-2h10zM3 0a3 3 0 0 0-3 3v10a3 3 0 0 0 3 3h10a3 3 0 0 0 3-3V3a3 3 0 0 0-3-3H3z"/>
                                <path d="M5.5 4a1.5 1.5 0 1 1-3 0 1.5 1.5 0 0 1 3 0zm8 8a1.5 1.5 0 1 1-3 0 1.5 1.5 0 0 1 3 0z"/>
                            </svg>
                        </label>
                        <input type="number" min="2" max="10" step="2" size="2" class="form-control form-control-sm accessible @if(array_key_exists('twos', $score_sheet['upper-section'])) disabled @else active @endif" name="twos" id="twos" placeholder="6" @if(array_key_exists('twos', $score_sheet['upper-section'])) disabled="disabled" value="{{ $score_sheet['upper-section']['twos'] }}" @endif>
                    </div>
                    <div class="col-2 text-center">
                        <label for="threes" class="form-label dice">
                            <svg xmlns="http://www.w3.org/2000/svg" width="32" height="32" fill="currentColor" class="bi bi-dice-3 @if(array_key_exists('threes', $score_sheet['upper-section'])) scored @endif" viewBox="0 0 16 16">
                                <path d="M13 1a2 2 0 0 1 2 2v10a2 2 0 0 1-2 2H3a2 2 0 0 1-2-2V3a2 2 0 0 1 2-2h10zM3 0a3 3 0 0 0-3 3v10a3 3 0 0 0 3 3h10a3 3 0 0 0 3-3V3a3 3 0 0 0-3-3H3z"/>
                                <path d="M5.5 4a1.5 1.5 0 1 1-3 0 1.5 1.5 0 0 1 3 0zm8 8a1.5 1.5 0 1 1-3 0 1.5 1.5 0 0 1 3 0zm-4-4a1.5 1.5 0 1 1-3 0 1.5 1.5 0 0 1 3 0z"/>
                            </svg>
                        </label>
                        <input type="number" min="3" max="15" step="3" size="2" class="form-control form-control-sm accessible @if(array_key_exists('threes', $score_sheet['upper-section'])) disabled @else active @endif" name="threes" id="threes" placeholder="9" @if(array_key_exists('threes', $score_sheet['upper-section'])) disabled="disabled" value="{{ $score_sheet['upper-section']['threes'] }}" @endif>
                    </div>
                    <div class="col-2 text-center">
                        <label for="fours" class="form-label dice">
                            <svg xmlns="http://www.w3.org/2000/svg" width="32" height="32" fill="currentColor" class="bi bi-dice-4 @if(array_key_exists('fours', $score_sheet['upper-section'])) scored @endif" viewBox="0 0 16 16">
                                <path d="M13 1a2 2 0 0 1 2 2v10a2 2 0 0 1-2 2H3a2 2 0 0 1-2-2V3a2 2 0 0 1 2-2h10zM3 0a3 3 0 0 0-3 3v10a3 3 0 0 0 3 3h10a3 3 0 0 0 3-3V3a3 3 0 0 0-3-3H3z"/>
                                <path d="M5.5 4a1.5 1.5 0 1 1-3 0 1.5 1.5 0 0 1 3 0zm8 0a1.5 1.5 0 1 1-3 0 1.5 1.5 0 0 1 3 0zm0 8a1.5 1.5 0 1 1-3 0 1.5 1.5 0 0 1 3 0zm-8 0a1.5 1.5 0 1 1-3 0 1.5 1.5 0 0 1 3 0z"/>
                            </svg>
                        </label>
                        <input type="number" min="4" max="20" step="4" size="2" class="form-control form-control-sm accessible @if(array_key_exists('fours', $score_sheet['upper-section'])) disabled @else active @endif" name="fours" id="fours" placeholder="12" @if(array_key_exists('fours', $score_sheet['upper-section'])) disabled="disabled" value="{{ $score_sheet['upper-section']['fours'] }}" @endif>
                    </div>
                    <div class="col-2 text-center">
                        <label for="fives" class="form-label dice">
                            <svg xmlns="http://www.w3.org/2000/svg" width="32" height="32" fill="currentColor" class="bi bi-dice-5 @if(array_key_exists('fives', $score_sheet['upper-section'])) scored @endif" viewBox="0 0 16 16">
                                <path d="M13 1a2 2 0 0 1 2 2v10a2 2 0 0 1-2 2H3a2 2 0 0 1-2-2V3a2 2 0 0 1 2-2h10zM3 0a3 3 0 0 0-3 3v10a3 3 0 0 0 3 3h10a3 3 0 0 0 3-3V3a3 3 0 0 0-3-3H3z"/>
                                <path d="M5.5 4a1.5 1.5 0 1 1-3 0 1.5 1.5 0 0 1 3 0zm8 0a1.5 1.5 0 1 1-3 0 1.5 1.5 0 0 1 3 0zm0 8a1.5 1.5 0 1 1-3 0 1.5 1.5 0 0 1 3 0zm-8 0a1.5 1.5 0 1 1-3 0 1.5 1.5 0 0 1 3 0zm4-4a1.5 1.5 0 1 1-3 0 1.5 1.5 0 0 1 3 0z"/>
                            </svg>
                        </label>
                        <input type="number" min="5" max="25" step="5" size="2" class="form-control form-control-sm accessible @if(array_key_exists('fives', $score_sheet['upper-section'])) disabled @else active @endif" name="fives" id="fives" placeholder="15" @if(array_key_exists('fives', $score_sheet['upper-section'])) disabled="disabled" value="{{ $score_sheet['upper-section']['fives'] }}" @endif>
                    </div>
                    <div class="col-2 text-center">
                        <label for="sixes" class="form-label dice">
                            <svg xmlns="http://www.w3.org/2000/svg" width="32" height="32" fill="currentColor" class="bi bi-dice-6 @if(array_key_exists('sixes', $score_sheet['upper-section'])) scored @endif" viewBox="0 0 16 16">
                                <path d="M13 1a2 2 0 0 1 2 2v10a2 2 0 0 1-2 2H3a2 2 0 0 1-2-2V3a2 2 0 0 1 2-2h10zM3 0a3 3 0 0 0-3 3v10a3 3 0 0 0 3 3h10a3 3 0 0 0 3-3V3a3 3 0 0 0-3-3H3z"/>
                                <path d="M5.5 4a1.5 1.5 0 1 1-3 0 1.5 1.5 0 0 1 3 0zm8 0a1.5 1.5 0 1 1-3 0 1.5 1.5 0 0 1 3 0zm0 8a1.5 1.5 0 1 1-3 0 1.5 1.5 0 0 1 3 0zm0-4a1.5 1.5 0 1 1-3 0 1.5 1.5 0 0 1 3 0zm-8 4a1.5 1.5 0 1 1-3 0 1.5 1.5 0 0 1 3 0zm0-4a1.5 1.5 0 1 1-3 0 1.5 1.5 0 0 1 3 0z"/>
                            </svg>
                        </label>
                        <input type="number" min="6" max="30" step="6" size="2" class="form-control form-control-sm accessible @if(array_key_exists('sixes', $score_sheet['upper-section'])) disabled @else active @endif" name="sixes" id="sixes" placeholder="18" @if(array_key_exists('sixes', $score_sheet['upper-section'])) disabled="disabled" value="{{ $score_sheet['upper-section']['sixes'] }}" @endif>
                    </div>
                </div>
                <div class="row">
                    <div class="col-12">
                        <p class="text-muted mb-0 mt-3">Use the checkbox to scratch a turn.</p>
                    </div>
                </div>
                <div class="row upper-section-scratch">
                    <div class="col-2">
                        <div class="text-center">
                            <input class="accessible form-check-input @if(array_key_exists('ones', $score_sheet['upper-section'])) disabled @else active @endif" type="checkbox" id="scratch_ones" value="ones" aria-label="Scratch the ones" @if(array_key_exists('ones', $score_sheet['upper-section']) && $score_sheet['upper-section']['ones'] === 0) checked="checked" @endif @if(array_key_exists('ones', $score_sheet['upper-section'])) disabled="disabled" @endif>
                        </div>
                    </div>
                    <div class="col-2">
                        <div class="text-center">
                            <input class="accessible form-check-input @if(array_key_exists('twos', $score_sheet['upper-section'])) disabled @else active @endif" type="checkbox" id="scratch_twos" value="twos" aria-label="Scratch the twos" @if(array_key_exists('twos', $score_sheet['upper-section']) && $score_sheet['upper-section']['twos'] === 0) checked="checked" @endif @if(array_key_exists('twos', $score_sheet['upper-section'])) disabled="disabled" @endif>
                        </div>
                    </div>
                    <div class="col-2">
                        <div class="text-center">
                            <input class="accessible form-check-input @if(array_key_exists('threes', $score_sheet['upper-section'])) disabled @else active @endif" type="checkbox" id="scratch_threes" value="threes" aria-label="Scratch the threes" @if(array_key_exists('threes', $score_sheet['upper-section']) && $score_sheet['upper-section']['threes'] === 0) checked="checked" @endif @if(array_key_exists('threes', $score_sheet['upper-section'])) disabled="disabled" @endif>
                        </div>
                    </div>
                    <div class="col-2">
                        <div class="text-center">
                            <input class="accessible form-check-input @if(array_key_exists('fours', $score_sheet['upper-section'])) disabled @else active @endif" type="checkbox" id="scratch_fours" value="fours" aria-label="Scratch the fours" @if(array_key_exists('fours', $score_sheet['upper-section']) && $score_sheet['upper-section']['fours'] === 0) checked="checked" @endif @if(array_key_exists('fours', $score_sheet['upper-section'])) disabled="disabled" @endif>
                        </div>
                    </div>
                    <div class="col-2">
                        <div class="text-center">
                            <input class="accessible form-check-input @if(array_key_exists('fives', $score_sheet['upper-section'])) disabled @else active @endif" type="checkbox" id="scratch_fives" value="fives" aria-label="Scratch the fives" @if(array_key_exists('fives', $score_sheet['upper-section']) && $score_sheet['upper-section']['fives'] === 0) checked="checked" @endif @if(array_key_exists('fives', $score_sheet['upper-section'])) disabled="disabled" @endif>
                        </div>
                    </div>
                    <div class="col-2">
                        <div class="text-center">
                            <input class="accessible form-check-input @if(array_key_exists('sixes', $score_sheet['upper-section'])) disabled @else active @endif" type="checkbox" id="scratch_sixes" value="sixes" aria-label="Scratch the sixes" @if(array_key_exists('sixes', $score_sheet['upper-section']) && $score_sheet['upper-section']['sixes'] === 0) checked="checked" @endif  @if(array_key_exists('sixes', $score_sheet['upper-section'])) disabled="disabled" @endif>
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
                    <div class="col-12 bonus-message">

                    </div>
                </div>

                <div class="row">
                    <div class="col-12 pt-3">
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
                        <p class="mb-0 three_of_a_kind_dice">
                            <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" fill="currentColor" class="bi bi-dice-3 @if(array_key_exists('three_of_a_kind', $score_sheet['lower-section'])) scored @endif" viewBox="0 0 16 16">
                                <path d="M13 1a2 2 0 0 1 2 2v10a2 2 0 0 1-2 2H3a2 2 0 0 1-2-2V3a2 2 0 0 1 2-2h10zM3 0a3 3 0 0 0-3 3v10a3 3 0 0 0 3 3h10a3 3 0 0 0 3-3V3a3 3 0 0 0-3-3H3z"/>
                                <path d="M5.5 4a1.5 1.5 0 1 1-3 0 1.5 1.5 0 0 1 3 0zm8 8a1.5 1.5 0 1 1-3 0 1.5 1.5 0 0 1 3 0zm-4-4a1.5 1.5 0 1 1-3 0 1.5 1.5 0 0 1 3 0z"/>
                            </svg>
                            <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" fill="currentColor" class="bi bi-dice-3 @if(array_key_exists('three_of_a_kind', $score_sheet['lower-section'])) scored @endif" viewBox="0 0 16 16">
                                <path d="M13 1a2 2 0 0 1 2 2v10a2 2 0 0 1-2 2H3a2 2 0 0 1-2-2V3a2 2 0 0 1 2-2h10zM3 0a3 3 0 0 0-3 3v10a3 3 0 0 0 3 3h10a3 3 0 0 0 3-3V3a3 3 0 0 0-3-3H3z"/>
                                <path d="M5.5 4a1.5 1.5 0 1 1-3 0 1.5 1.5 0 0 1 3 0zm8 8a1.5 1.5 0 1 1-3 0 1.5 1.5 0 0 1 3 0zm-4-4a1.5 1.5 0 1 1-3 0 1.5 1.5 0 0 1 3 0z"/>
                            </svg>
                            <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" fill="currentColor" class="bi bi-dice-3 @if(array_key_exists('three_of_a_kind', $score_sheet['lower-section'])) scored @endif" viewBox="0 0 16 16">
                                <path d="M13 1a2 2 0 0 1 2 2v10a2 2 0 0 1-2 2H3a2 2 0 0 1-2-2V3a2 2 0 0 1 2-2h10zM3 0a3 3 0 0 0-3 3v10a3 3 0 0 0 3 3h10a3 3 0 0 0 3-3V3a3 3 0 0 0-3-3H3z"/>
                                <path d="M5.5 4a1.5 1.5 0 1 1-3 0 1.5 1.5 0 0 1 3 0zm8 8a1.5 1.5 0 1 1-3 0 1.5 1.5 0 0 1 3 0zm-4-4a1.5 1.5 0 1 1-3 0 1.5 1.5 0 0 1 3 0z"/>
                            </svg>
                        </p>
                        <p class="mb-2 text-muted">
                            Score total of all dice.
                        </p>
                    </div>
                    <div class="col-3">
                        <input type="number" min="6" max="30" step="1" size="2" class="form-control form-control-sm @if(array_key_exists('three_of_a_kind', $score_sheet['lower-section'])) disabled @else active @endif" id="three_of_a_kind" placeholder="" @if(array_key_exists('three_of_a_kind', $score_sheet['lower-section'])) disabled="disabled" value="{{ $score_sheet['lower-section']['three_of_a_kind'] }}" @endif>
                        <label for="three_of_a_kind" class="visually-hidden">Three of a kind</label>
                    </div>
                    <div class="col-3">
                        <div class="text-center">
                            <input class="accessible form-check-input @if(array_key_exists('three_of_a_kind', $score_sheet['lower-section'])) disabled @else active @endif" type="checkbox" id="scratch_three_of_a_kind" value="three_of_a_kind" aria-label="Scratch three of a kind" @if(array_key_exists('three_of_a_kind', $score_sheet['lower-section'])) disabled="disabled" @endif @if(array_key_exists('three_of_a_kind', $score_sheet['lower-section']) && $score_sheet['lower-section']['three_of_a_kind'] === 0) checked="checked" @endif>
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
                        <p class="mb-0 four_of_a_kind_dice">
                            <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" fill="currentColor" class="bi bi-dice-4 @if(array_key_exists('four_of_a_kind', $score_sheet['lower-section'])) scored @endif" viewBox="0 0 16 16">
                                <path d="M13 1a2 2 0 0 1 2 2v10a2 2 0 0 1-2 2H3a2 2 0 0 1-2-2V3a2 2 0 0 1 2-2h10zM3 0a3 3 0 0 0-3 3v10a3 3 0 0 0 3 3h10a3 3 0 0 0 3-3V3a3 3 0 0 0-3-3H3z"/>
                                <path d="M5.5 4a1.5 1.5 0 1 1-3 0 1.5 1.5 0 0 1 3 0zm8 0a1.5 1.5 0 1 1-3 0 1.5 1.5 0 0 1 3 0zm0 8a1.5 1.5 0 1 1-3 0 1.5 1.5 0 0 1 3 0zm-8 0a1.5 1.5 0 1 1-3 0 1.5 1.5 0 0 1 3 0z"/>
                            </svg>
                            <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" fill="currentColor" class="bi bi-dice-4 @if(array_key_exists('four_of_a_kind', $score_sheet['lower-section'])) scored @endif" viewBox="0 0 16 16">
                                <path d="M13 1a2 2 0 0 1 2 2v10a2 2 0 0 1-2 2H3a2 2 0 0 1-2-2V3a2 2 0 0 1 2-2h10zM3 0a3 3 0 0 0-3 3v10a3 3 0 0 0 3 3h10a3 3 0 0 0 3-3V3a3 3 0 0 0-3-3H3z"/>
                                <path d="M5.5 4a1.5 1.5 0 1 1-3 0 1.5 1.5 0 0 1 3 0zm8 0a1.5 1.5 0 1 1-3 0 1.5 1.5 0 0 1 3 0zm0 8a1.5 1.5 0 1 1-3 0 1.5 1.5 0 0 1 3 0zm-8 0a1.5 1.5 0 1 1-3 0 1.5 1.5 0 0 1 3 0z"/>
                            </svg>
                            <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" fill="currentColor" class="bi bi-dice-4 @if(array_key_exists('four_of_a_kind', $score_sheet['lower-section'])) scored @endif" viewBox="0 0 16 16">
                                <path d="M13 1a2 2 0 0 1 2 2v10a2 2 0 0 1-2 2H3a2 2 0 0 1-2-2V3a2 2 0 0 1 2-2h10zM3 0a3 3 0 0 0-3 3v10a3 3 0 0 0 3 3h10a3 3 0 0 0 3-3V3a3 3 0 0 0-3-3H3z"/>
                                <path d="M5.5 4a1.5 1.5 0 1 1-3 0 1.5 1.5 0 0 1 3 0zm8 0a1.5 1.5 0 1 1-3 0 1.5 1.5 0 0 1 3 0zm0 8a1.5 1.5 0 1 1-3 0 1.5 1.5 0 0 1 3 0zm-8 0a1.5 1.5 0 1 1-3 0 1.5 1.5 0 0 1 3 0z"/>
                            </svg>
                            <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" fill="currentColor" class="bi bi-dice-4 @if(array_key_exists('four_of_a_kind', $score_sheet['lower-section'])) scored @endif" viewBox="0 0 16 16">
                                <path d="M13 1a2 2 0 0 1 2 2v10a2 2 0 0 1-2 2H3a2 2 0 0 1-2-2V3a2 2 0 0 1 2-2h10zM3 0a3 3 0 0 0-3 3v10a3 3 0 0 0 3 3h10a3 3 0 0 0 3-3V3a3 3 0 0 0-3-3H3z"/>
                                <path d="M5.5 4a1.5 1.5 0 1 1-3 0 1.5 1.5 0 0 1 3 0zm8 0a1.5 1.5 0 1 1-3 0 1.5 1.5 0 0 1 3 0zm0 8a1.5 1.5 0 1 1-3 0 1.5 1.5 0 0 1 3 0zm-8 0a1.5 1.5 0 1 1-3 0 1.5 1.5 0 0 1 3 0z"/>
                            </svg>
                        </p>
                        <p class="mb-2 text-muted">
                            Score total of all dice.
                        </p>
                    </div>
                    <div class="col-3">
                        <input type="number" min="6" max="30" step="1" size="2" class="form-control form-control-sm @if(array_key_exists('four_of_a_kind', $score_sheet['lower-section'])) disabled @else active @endif" id="four_of_a_kind" placeholder="" @if(array_key_exists('four_of_a_kind', $score_sheet['lower-section'])) disabled="disabled" value="{{ $score_sheet['lower-section']['four_of_a_kind'] }}" @endif>
                        <label for="four_of_a_kind" class="visually-hidden">Four of a kind</label>
                    </div>
                    <div class="col-3">
                        <div class="text-center">
                            <input class="accessible form-check-input @if(array_key_exists('four_of_a_kind', $score_sheet['lower-section'])) disabled @else active @endif" type="checkbox" id="scratch_four_of_a_kind" value="four_of_a_kind" aria-label="Scratch four of a kind" @if(array_key_exists('four_of_a_kind', $score_sheet['lower-section'])) disabled="disabled" @endif @if(array_key_exists('four_of_a_kind', $score_sheet['lower-section']) && $score_sheet['lower-section']['four_of_a_kind'] === 0) checked="checked" @endif>
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
                        <p class="mb-0 full_house_dice">
                            <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" fill="currentColor" class="bi bi-dice-4 @if(array_key_exists('full_house', $score_sheet['lower-section'])) scored @endif" viewBox="0 0 16 16">
                                <path d="M13 1a2 2 0 0 1 2 2v10a2 2 0 0 1-2 2H3a2 2 0 0 1-2-2V3a2 2 0 0 1 2-2h10zM3 0a3 3 0 0 0-3 3v10a3 3 0 0 0 3 3h10a3 3 0 0 0 3-3V3a3 3 0 0 0-3-3H3z"/>
                                <path d="M5.5 4a1.5 1.5 0 1 1-3 0 1.5 1.5 0 0 1 3 0zm8 0a1.5 1.5 0 1 1-3 0 1.5 1.5 0 0 1 3 0zm0 8a1.5 1.5 0 1 1-3 0 1.5 1.5 0 0 1 3 0zm-8 0a1.5 1.5 0 1 1-3 0 1.5 1.5 0 0 1 3 0z"/>
                            </svg>
                            <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" fill="currentColor" class="bi bi-dice-4 @if(array_key_exists('full_house', $score_sheet['lower-section'])) scored @endif" viewBox="0 0 16 16">
                                <path d="M13 1a2 2 0 0 1 2 2v10a2 2 0 0 1-2 2H3a2 2 0 0 1-2-2V3a2 2 0 0 1 2-2h10zM3 0a3 3 0 0 0-3 3v10a3 3 0 0 0 3 3h10a3 3 0 0 0 3-3V3a3 3 0 0 0-3-3H3z"/>
                                <path d="M5.5 4a1.5 1.5 0 1 1-3 0 1.5 1.5 0 0 1 3 0zm8 0a1.5 1.5 0 1 1-3 0 1.5 1.5 0 0 1 3 0zm0 8a1.5 1.5 0 1 1-3 0 1.5 1.5 0 0 1 3 0zm-8 0a1.5 1.5 0 1 1-3 0 1.5 1.5 0 0 1 3 0z"/>
                            </svg>
                            <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" fill="currentColor" class="bi bi-dice-4 @if(array_key_exists('full_house', $score_sheet['lower-section'])) scored @endif" viewBox="0 0 16 16">
                                <path d="M13 1a2 2 0 0 1 2 2v10a2 2 0 0 1-2 2H3a2 2 0 0 1-2-2V3a2 2 0 0 1 2-2h10zM3 0a3 3 0 0 0-3 3v10a3 3 0 0 0 3 3h10a3 3 0 0 0 3-3V3a3 3 0 0 0-3-3H3z"/>
                                <path d="M5.5 4a1.5 1.5 0 1 1-3 0 1.5 1.5 0 0 1 3 0zm8 0a1.5 1.5 0 1 1-3 0 1.5 1.5 0 0 1 3 0zm0 8a1.5 1.5 0 1 1-3 0 1.5 1.5 0 0 1 3 0zm-8 0a1.5 1.5 0 1 1-3 0 1.5 1.5 0 0 1 3 0z"/>
                            </svg>
                            <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" fill="currentColor" class="bi bi-dice-3 @if(array_key_exists('full_house', $score_sheet['lower-section'])) scored @endif" viewBox="0 0 16 16">
                                <path d="M13 1a2 2 0 0 1 2 2v10a2 2 0 0 1-2 2H3a2 2 0 0 1-2-2V3a2 2 0 0 1 2-2h10zM3 0a3 3 0 0 0-3 3v10a3 3 0 0 0 3 3h10a3 3 0 0 0 3-3V3a3 3 0 0 0-3-3H3z"/>
                                <path d="M5.5 4a1.5 1.5 0 1 1-3 0 1.5 1.5 0 0 1 3 0zm8 8a1.5 1.5 0 1 1-3 0 1.5 1.5 0 0 1 3 0zm-4-4a1.5 1.5 0 1 1-3 0 1.5 1.5 0 0 1 3 0z"/>
                            </svg>
                            <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" fill="currentColor" class="bi bi-dice-3 @if(array_key_exists('full_house', $score_sheet['lower-section'])) scored @endif" viewBox="0 0 16 16">
                                <path d="M13 1a2 2 0 0 1 2 2v10a2 2 0 0 1-2 2H3a2 2 0 0 1-2-2V3a2 2 0 0 1 2-2h10zM3 0a3 3 0 0 0-3 3v10a3 3 0 0 0 3 3h10a3 3 0 0 0 3-3V3a3 3 0 0 0-3-3H3z"/>
                                <path d="M5.5 4a1.5 1.5 0 1 1-3 0 1.5 1.5 0 0 1 3 0zm8 8a1.5 1.5 0 1 1-3 0 1.5 1.5 0 0 1 3 0zm-4-4a1.5 1.5 0 1 1-3 0 1.5 1.5 0 0 1 3 0z"/>
                            </svg>
                        </p>
                        <p class="mb-2 text-muted">
                            Score 25
                        </p>
                    </div>
                    <div class="col-3">
                        <div class="text-center">
                            <input class="accessible form-check-input @if(array_key_exists('full_house', $score_sheet['lower-section'])) disabled @else active @endif" type="checkbox" id="full_house" value="25" aria-label="Full house" @if(array_key_exists('full_house', $score_sheet['lower-section'])) disabled="disabled" @endif @if(array_key_exists('full_house', $score_sheet['lower-section']) && $score_sheet['lower-section']['full_house'] === 25) checked="checked" @endif>
                        </div>
                    </div>
                    <div class="col-3">
                        <div class="text-center">
                            <input class="accessible form-check-input @if(array_key_exists('full_house', $score_sheet['lower-section'])) disabled @else active @endif" type="checkbox" id="scratch_full_house" value="full_house" aria-label="Scratch full house" @if(array_key_exists('full_house', $score_sheet['lower-section'])) disabled="disabled" @endif @if(array_key_exists('full_house', $score_sheet['lower-section']) && $score_sheet['lower-section']['full_house'] === 0) checked="checked" @endif>
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
                        <p class="mb-0 small_straight_dice">
                            <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" fill="currentColor" class="bi bi-dice-1 @if(array_key_exists('small_straight', $score_sheet['lower-section'])) scored @endif" viewBox="0 0 16 16">
                                <circle cx="8" cy="8" r="1.5"/>
                                <path d="M13 1a2 2 0 0 1 2 2v10a2 2 0 0 1-2 2H3a2 2 0 0 1-2-2V3a2 2 0 0 1 2-2h10zM3 0a3 3 0 0 0-3 3v10a3 3 0 0 0 3 3h10a3 3 0 0 0 3-3V3a3 3 0 0 0-3-3H3z"/>
                            </svg>
                            <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" fill="currentColor" class="bi bi-dice-2 @if(array_key_exists('small_straight', $score_sheet['lower-section'])) scored @endif" viewBox="0 0 16 16">
                                <path d="M13 1a2 2 0 0 1 2 2v10a2 2 0 0 1-2 2H3a2 2 0 0 1-2-2V3a2 2 0 0 1 2-2h10zM3 0a3 3 0 0 0-3 3v10a3 3 0 0 0 3 3h10a3 3 0 0 0 3-3V3a3 3 0 0 0-3-3H3z"/>
                                <path d="M5.5 4a1.5 1.5 0 1 1-3 0 1.5 1.5 0 0 1 3 0zm8 8a1.5 1.5 0 1 1-3 0 1.5 1.5 0 0 1 3 0z"/>
                            </svg>
                            <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" fill="currentColor" class="bi bi-dice-3 @if(array_key_exists('small_straight', $score_sheet['lower-section'])) scored @endif" viewBox="0 0 16 16">
                                <path d="M13 1a2 2 0 0 1 2 2v10a2 2 0 0 1-2 2H3a2 2 0 0 1-2-2V3a2 2 0 0 1 2-2h10zM3 0a3 3 0 0 0-3 3v10a3 3 0 0 0 3 3h10a3 3 0 0 0 3-3V3a3 3 0 0 0-3-3H3z"/>
                                <path d="M5.5 4a1.5 1.5 0 1 1-3 0 1.5 1.5 0 0 1 3 0zm8 8a1.5 1.5 0 1 1-3 0 1.5 1.5 0 0 1 3 0zm-4-4a1.5 1.5 0 1 1-3 0 1.5 1.5 0 0 1 3 0z"/>
                            </svg>
                            <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" fill="currentColor" class="bi bi-dice-4 @if(array_key_exists('small_straight', $score_sheet['lower-section'])) scored @endif" viewBox="0 0 16 16">
                                <path d="M13 1a2 2 0 0 1 2 2v10a2 2 0 0 1-2 2H3a2 2 0 0 1-2-2V3a2 2 0 0 1 2-2h10zM3 0a3 3 0 0 0-3 3v10a3 3 0 0 0 3 3h10a3 3 0 0 0 3-3V3a3 3 0 0 0-3-3H3z"/>
                                <path d="M5.5 4a1.5 1.5 0 1 1-3 0 1.5 1.5 0 0 1 3 0zm8 0a1.5 1.5 0 1 1-3 0 1.5 1.5 0 0 1 3 0zm0 8a1.5 1.5 0 1 1-3 0 1.5 1.5 0 0 1 3 0zm-8 0a1.5 1.5 0 1 1-3 0 1.5 1.5 0 0 1 3 0z"/>
                            </svg>
                        </p>
                        <p class="mb-2 text-muted">
                            Score 30
                        </p>
                    </div>
                    <div class="col-3">
                        <div class="text-center">
                            <input class="accessible form-check-input @if(array_key_exists('small_straight', $score_sheet['lower-section'])) disabled @else active @endif" type="checkbox" id="small_straight" value="30" aria-label="Small straight" @if(array_key_exists('small_straight', $score_sheet['lower-section'])) disabled="disabled" @endif @if(array_key_exists('small_straight', $score_sheet['lower-section']) && $score_sheet['lower-section']['small_straight'] === 30) checked="checked" @endif>
                        </div>
                    </div>
                    <div class="col-3">
                        <div class="text-center">
                            <input class="accessible form-check-input @if(array_key_exists('small_straight', $score_sheet['lower-section'])) disabled @else active @endif" type="checkbox" id="scratch_small_straight" value="full_house" aria-label="Scratch small straight" @if(array_key_exists('small_straight', $score_sheet['lower-section'])) disabled="disabled" @endif @if(array_key_exists('small_straight', $score_sheet['lower-section']) && $score_sheet['lower-section']['small_straight'] === 0) checked="checked" @endif>
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
                        <p class="mb-0 large_straight_dice">
                            <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" fill="currentColor" class="bi bi-dice-1 @if(array_key_exists('large_straight', $score_sheet['lower-section'])) scored @endif" viewBox="0 0 16 16">
                                <circle cx="8" cy="8" r="1.5"/>
                                <path d="M13 1a2 2 0 0 1 2 2v10a2 2 0 0 1-2 2H3a2 2 0 0 1-2-2V3a2 2 0 0 1 2-2h10zM3 0a3 3 0 0 0-3 3v10a3 3 0 0 0 3 3h10a3 3 0 0 0 3-3V3a3 3 0 0 0-3-3H3z"/>
                            </svg>
                            <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" fill="currentColor" class="bi bi-dice-2 @if(array_key_exists('large_straight', $score_sheet['lower-section'])) scored @endif" viewBox="0 0 16 16">
                                <path d="M13 1a2 2 0 0 1 2 2v10a2 2 0 0 1-2 2H3a2 2 0 0 1-2-2V3a2 2 0 0 1 2-2h10zM3 0a3 3 0 0 0-3 3v10a3 3 0 0 0 3 3h10a3 3 0 0 0 3-3V3a3 3 0 0 0-3-3H3z"/>
                                <path d="M5.5 4a1.5 1.5 0 1 1-3 0 1.5 1.5 0 0 1 3 0zm8 8a1.5 1.5 0 1 1-3 0 1.5 1.5 0 0 1 3 0z"/>
                            </svg>
                            <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" fill="currentColor" class="bi bi-dice-3 @if(array_key_exists('large_straight', $score_sheet['lower-section'])) scored @endif" viewBox="0 0 16 16">
                                <path d="M13 1a2 2 0 0 1 2 2v10a2 2 0 0 1-2 2H3a2 2 0 0 1-2-2V3a2 2 0 0 1 2-2h10zM3 0a3 3 0 0 0-3 3v10a3 3 0 0 0 3 3h10a3 3 0 0 0 3-3V3a3 3 0 0 0-3-3H3z"/>
                                <path d="M5.5 4a1.5 1.5 0 1 1-3 0 1.5 1.5 0 0 1 3 0zm8 8a1.5 1.5 0 1 1-3 0 1.5 1.5 0 0 1 3 0zm-4-4a1.5 1.5 0 1 1-3 0 1.5 1.5 0 0 1 3 0z"/>
                            </svg>
                            <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" fill="currentColor" class="bi bi-dice-4 @if(array_key_exists('large_straight', $score_sheet['lower-section'])) scored @endif" viewBox="0 0 16 16">
                                <path d="M13 1a2 2 0 0 1 2 2v10a2 2 0 0 1-2 2H3a2 2 0 0 1-2-2V3a2 2 0 0 1 2-2h10zM3 0a3 3 0 0 0-3 3v10a3 3 0 0 0 3 3h10a3 3 0 0 0 3-3V3a3 3 0 0 0-3-3H3z"/>
                                <path d="M5.5 4a1.5 1.5 0 1 1-3 0 1.5 1.5 0 0 1 3 0zm8 0a1.5 1.5 0 1 1-3 0 1.5 1.5 0 0 1 3 0zm0 8a1.5 1.5 0 1 1-3 0 1.5 1.5 0 0 1 3 0zm-8 0a1.5 1.5 0 1 1-3 0 1.5 1.5 0 0 1 3 0z"/>
                            </svg>
                            <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" fill="currentColor" class="bi bi-dice-5 @if(array_key_exists('large_straight', $score_sheet['lower-section'])) scored @endif" viewBox="0 0 16 16">
                                <path d="M13 1a2 2 0 0 1 2 2v10a2 2 0 0 1-2 2H3a2 2 0 0 1-2-2V3a2 2 0 0 1 2-2h10zM3 0a3 3 0 0 0-3 3v10a3 3 0 0 0 3 3h10a3 3 0 0 0 3-3V3a3 3 0 0 0-3-3H3z"/>
                                <path d="M5.5 4a1.5 1.5 0 1 1-3 0 1.5 1.5 0 0 1 3 0zm8 0a1.5 1.5 0 1 1-3 0 1.5 1.5 0 0 1 3 0zm0 8a1.5 1.5 0 1 1-3 0 1.5 1.5 0 0 1 3 0zm-8 0a1.5 1.5 0 1 1-3 0 1.5 1.5 0 0 1 3 0zm4-4a1.5 1.5 0 1 1-3 0 1.5 1.5 0 0 1 3 0z"/>
                            </svg>
                        </p>
                        <p class="mb-2 text-muted">
                            Score 40
                        </p>
                    </div>
                    <div class="col-3">
                        <div class="text-center">
                            <input class="accessible form-check-input @if(array_key_exists('large_straight', $score_sheet['lower-section'])) disabled @else active @endif" type="checkbox" id="large_straight" value="40" aria-label="Large straight" @if(array_key_exists('large_straight', $score_sheet['lower-section'])) disabled="disabled" @endif @if(array_key_exists('large_straight', $score_sheet['lower-section']) && $score_sheet['lower-section']['large_straight'] === 40) checked="checked" @endif>
                        </div>
                    </div>
                    <div class="col-3">
                        <div class="text-center">
                            <input class="accessible form-check-input @if(array_key_exists('large_straight', $score_sheet['lower-section'])) disabled @else active @endif" type="checkbox" id="scratch_large_straight" value="large_straight" aria-label="Scratch large straight" @if(array_key_exists('large_straight', $score_sheet['lower-section'])) disabled="disabled" @endif @if(array_key_exists('large_straight', $score_sheet['lower-section']) && $score_sheet['lower-section']['large_straight'] === 0) checked="checked" @endif>
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
                        <p class="mb-0 yahtzee_dice">
                            <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" fill="currentColor" class="bi bi-dice-6 @if(array_key_exists('yahtzee', $score_sheet['lower-section'])) scored @endif" viewBox="0 0 16 16">
                                <path d="M13 1a2 2 0 0 1 2 2v10a2 2 0 0 1-2 2H3a2 2 0 0 1-2-2V3a2 2 0 0 1 2-2h10zM3 0a3 3 0 0 0-3 3v10a3 3 0 0 0 3 3h10a3 3 0 0 0 3-3V3a3 3 0 0 0-3-3H3z"/>
                                <path d="M5.5 4a1.5 1.5 0 1 1-3 0 1.5 1.5 0 0 1 3 0zm8 0a1.5 1.5 0 1 1-3 0 1.5 1.5 0 0 1 3 0zm0 8a1.5 1.5 0 1 1-3 0 1.5 1.5 0 0 1 3 0zm0-4a1.5 1.5 0 1 1-3 0 1.5 1.5 0 0 1 3 0zm-8 4a1.5 1.5 0 1 1-3 0 1.5 1.5 0 0 1 3 0zm0-4a1.5 1.5 0 1 1-3 0 1.5 1.5 0 0 1 3 0z"/>
                            </svg>
                            <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" fill="currentColor" class="bi bi-dice-6 @if(array_key_exists('yahtzee', $score_sheet['lower-section'])) scored @endif" viewBox="0 0 16 16">
                                <path d="M13 1a2 2 0 0 1 2 2v10a2 2 0 0 1-2 2H3a2 2 0 0 1-2-2V3a2 2 0 0 1 2-2h10zM3 0a3 3 0 0 0-3 3v10a3 3 0 0 0 3 3h10a3 3 0 0 0 3-3V3a3 3 0 0 0-3-3H3z"/>
                                <path d="M5.5 4a1.5 1.5 0 1 1-3 0 1.5 1.5 0 0 1 3 0zm8 0a1.5 1.5 0 1 1-3 0 1.5 1.5 0 0 1 3 0zm0 8a1.5 1.5 0 1 1-3 0 1.5 1.5 0 0 1 3 0zm0-4a1.5 1.5 0 1 1-3 0 1.5 1.5 0 0 1 3 0zm-8 4a1.5 1.5 0 1 1-3 0 1.5 1.5 0 0 1 3 0zm0-4a1.5 1.5 0 1 1-3 0 1.5 1.5 0 0 1 3 0z"/>
                            </svg>
                            <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" fill="currentColor" class="bi bi-dice-6 @if(array_key_exists('yahtzee', $score_sheet['lower-section'])) scored @endif" viewBox="0 0 16 16">
                                <path d="M13 1a2 2 0 0 1 2 2v10a2 2 0 0 1-2 2H3a2 2 0 0 1-2-2V3a2 2 0 0 1 2-2h10zM3 0a3 3 0 0 0-3 3v10a3 3 0 0 0 3 3h10a3 3 0 0 0 3-3V3a3 3 0 0 0-3-3H3z"/>
                                <path d="M5.5 4a1.5 1.5 0 1 1-3 0 1.5 1.5 0 0 1 3 0zm8 0a1.5 1.5 0 1 1-3 0 1.5 1.5 0 0 1 3 0zm0 8a1.5 1.5 0 1 1-3 0 1.5 1.5 0 0 1 3 0zm0-4a1.5 1.5 0 1 1-3 0 1.5 1.5 0 0 1 3 0zm-8 4a1.5 1.5 0 1 1-3 0 1.5 1.5 0 0 1 3 0zm0-4a1.5 1.5 0 1 1-3 0 1.5 1.5 0 0 1 3 0z"/>
                            </svg>
                            <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" fill="currentColor" class="bi bi-dice-6 @if(array_key_exists('yahtzee', $score_sheet['lower-section'])) scored @endif" viewBox="0 0 16 16">
                                <path d="M13 1a2 2 0 0 1 2 2v10a2 2 0 0 1-2 2H3a2 2 0 0 1-2-2V3a2 2 0 0 1 2-2h10zM3 0a3 3 0 0 0-3 3v10a3 3 0 0 0 3 3h10a3 3 0 0 0 3-3V3a3 3 0 0 0-3-3H3z"/>
                                <path d="M5.5 4a1.5 1.5 0 1 1-3 0 1.5 1.5 0 0 1 3 0zm8 0a1.5 1.5 0 1 1-3 0 1.5 1.5 0 0 1 3 0zm0 8a1.5 1.5 0 1 1-3 0 1.5 1.5 0 0 1 3 0zm0-4a1.5 1.5 0 1 1-3 0 1.5 1.5 0 0 1 3 0zm-8 4a1.5 1.5 0 1 1-3 0 1.5 1.5 0 0 1 3 0zm0-4a1.5 1.5 0 1 1-3 0 1.5 1.5 0 0 1 3 0z"/>
                            </svg>
                            <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" fill="currentColor" class="bi bi-dice-6 @if(array_key_exists('yahtzee', $score_sheet['lower-section'])) scored @endif" viewBox="0 0 16 16">
                                <path d="M13 1a2 2 0 0 1 2 2v10a2 2 0 0 1-2 2H3a2 2 0 0 1-2-2V3a2 2 0 0 1 2-2h10zM3 0a3 3 0 0 0-3 3v10a3 3 0 0 0 3 3h10a3 3 0 0 0 3-3V3a3 3 0 0 0-3-3H3z"/>
                                <path d="M5.5 4a1.5 1.5 0 1 1-3 0 1.5 1.5 0 0 1 3 0zm8 0a1.5 1.5 0 1 1-3 0 1.5 1.5 0 0 1 3 0zm0 8a1.5 1.5 0 1 1-3 0 1.5 1.5 0 0 1 3 0zm0-4a1.5 1.5 0 1 1-3 0 1.5 1.5 0 0 1 3 0zm-8 4a1.5 1.5 0 1 1-3 0 1.5 1.5 0 0 1 3 0zm0-4a1.5 1.5 0 1 1-3 0 1.5 1.5 0 0 1 3 0z"/>
                            </svg>
                        </p>
                        <p class="mb-2 text-muted">
                            Score 50
                        </p>
                    </div>
                    <div class="col-3">
                        <div class="text-center">
                            <input class="accessible form-check-input @if(array_key_exists('yahtzee', $score_sheet['lower-section'])) disabled @else active @endif" type="checkbox" id="yahtzee" value="50" aria-label="Yahtzee" @if(array_key_exists('yahtzee', $score_sheet['lower-section'])) disabled="disabled" @endif @if(array_key_exists('yahtzee', $score_sheet['lower-section']) && $score_sheet['lower-section']['yahtzee'] === 50) checked="checked" @endif>
                        </div>
                    </div>
                    <div class="col-3">
                        <div class="text-center">
                            <input class="accessible form-check-input @if(array_key_exists('yahtzee', $score_sheet['lower-section'])) disabled @else active @endif" type="checkbox" id="scratch_yahtzee" value="yahtzee" aria-label="Scratch yahtzee" @if(array_key_exists('yahtzee', $score_sheet['lower-section'])) disabled="disabled" @endif @if(array_key_exists('yahtzee', $score_sheet['lower-section']) && $score_sheet['lower-section']['yahtzee'] === 0) checked="checked" @endif>
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
                    </div>
                    <div class="col-6">
                        <p class="mb-0 chance_dice">
                            <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" fill="currentColor" class="bi bi-dice-3 @if(array_key_exists('chance', $score_sheet['lower-section'])) scored @endif" viewBox="0 0 16 16">
                                <path d="M13 1a2 2 0 0 1 2 2v10a2 2 0 0 1-2 2H3a2 2 0 0 1-2-2V3a2 2 0 0 1 2-2h10zM3 0a3 3 0 0 0-3 3v10a3 3 0 0 0 3 3h10a3 3 0 0 0 3-3V3a3 3 0 0 0-3-3H3z"/>
                                <path d="M5.5 4a1.5 1.5 0 1 1-3 0 1.5 1.5 0 0 1 3 0zm8 8a1.5 1.5 0 1 1-3 0 1.5 1.5 0 0 1 3 0zm-4-4a1.5 1.5 0 1 1-3 0 1.5 1.5 0 0 1 3 0z"/>
                            </svg>
                            <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" fill="currentColor" class="bi bi-dice-6 @if(array_key_exists('chance', $score_sheet['lower-section'])) scored @endif" viewBox="0 0 16 16">
                                <path d="M13 1a2 2 0 0 1 2 2v10a2 2 0 0 1-2 2H3a2 2 0 0 1-2-2V3a2 2 0 0 1 2-2h10zM3 0a3 3 0 0 0-3 3v10a3 3 0 0 0 3 3h10a3 3 0 0 0 3-3V3a3 3 0 0 0-3-3H3z"/>
                                <path d="M5.5 4a1.5 1.5 0 1 1-3 0 1.5 1.5 0 0 1 3 0zm8 0a1.5 1.5 0 1 1-3 0 1.5 1.5 0 0 1 3 0zm0 8a1.5 1.5 0 1 1-3 0 1.5 1.5 0 0 1 3 0zm0-4a1.5 1.5 0 1 1-3 0 1.5 1.5 0 0 1 3 0zm-8 4a1.5 1.5 0 1 1-3 0 1.5 1.5 0 0 1 3 0zm0-4a1.5 1.5 0 1 1-3 0 1.5 1.5 0 0 1 3 0z"/>
                            </svg>
                            <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" fill="currentColor" class="bi bi-dice-2 @if(array_key_exists('chance', $score_sheet['lower-section'])) scored @endif" viewBox="0 0 16 16">
                                <path d="M13 1a2 2 0 0 1 2 2v10a2 2 0 0 1-2 2H3a2 2 0 0 1-2-2V3a2 2 0 0 1 2-2h10zM3 0a3 3 0 0 0-3 3v10a3 3 0 0 0 3 3h10a3 3 0 0 0 3-3V3a3 3 0 0 0-3-3H3z"/>
                                <path d="M5.5 4a1.5 1.5 0 1 1-3 0 1.5 1.5 0 0 1 3 0zm8 8a1.5 1.5 0 1 1-3 0 1.5 1.5 0 0 1 3 0z"/>
                            </svg>
                            <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" fill="currentColor" class="bi bi-dice-5 @if(array_key_exists('chance', $score_sheet['lower-section'])) scored @endif" viewBox="0 0 16 16">
                                <path d="M13 1a2 2 0 0 1 2 2v10a2 2 0 0 1-2 2H3a2 2 0 0 1-2-2V3a2 2 0 0 1 2-2h10zM3 0a3 3 0 0 0-3 3v10a3 3 0 0 0 3 3h10a3 3 0 0 0 3-3V3a3 3 0 0 0-3-3H3z"/>
                                <path d="M5.5 4a1.5 1.5 0 1 1-3 0 1.5 1.5 0 0 1 3 0zm8 0a1.5 1.5 0 1 1-3 0 1.5 1.5 0 0 1 3 0zm0 8a1.5 1.5 0 1 1-3 0 1.5 1.5 0 0 1 3 0zm-8 0a1.5 1.5 0 1 1-3 0 1.5 1.5 0 0 1 3 0zm4-4a1.5 1.5 0 1 1-3 0 1.5 1.5 0 0 1 3 0z"/>
                            </svg>
                            <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" fill="currentColor" class="bi bi-dice-1 @if(array_key_exists('chance', $score_sheet['lower-section'])) scored @endif" viewBox="0 0 16 16">
                                <circle cx="8" cy="8" r="1.5"/>
                                <path d="M13 1a2 2 0 0 1 2 2v10a2 2 0 0 1-2 2H3a2 2 0 0 1-2-2V3a2 2 0 0 1 2-2h10zM3 0a3 3 0 0 0-3 3v10a3 3 0 0 0 3 3h10a3 3 0 0 0 3-3V3a3 3 0 0 0-3-3H3z"/>
                            </svg>
                        </p>
                        <p class="mb-2 text-muted">
                            Score total of all dice
                        </p>
                    </div>
                    <div class="col-3">
                        <input type="number" min="6" max="30" step="1" size="2" class="form-control form-control-sm @if(array_key_exists('chance', $score_sheet['lower-section'])) disabled @else active @endif" id="chance" placeholder="" @if(array_key_exists('chance', $score_sheet['lower-section'])) disabled="disabled" value="{{ $score_sheet['lower-section']['chance'] }}" @endif>
                        <label for="chance" class="visually-hidden">Chance</label>
                    </div>
                    <div class="col-3">
                    </div>
                </div>

                <div class="row">
                    <div class="col-6">
                        <h5 class="mb-0">Yahtzee Bonus</h5>
                    </div>
                    <div class="col-6 text-center">
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
                        <div class="text-center">
                            <input class="accessible me-3 form-check-input @if(array_key_exists('yahtzee_bonus_one', $score_sheet['lower-section']) || $turns === 13) disabled @else active @endif" type="checkbox" id="yahtzee_bonus_one" value="100" aria-label="Yahtzee bonus" @if(array_key_exists('yahtzee_bonus_one', $score_sheet['lower-section']) || $turns === 13) disabled="disabled" @endif @if(array_key_exists('yahtzee_bonus_one', $score_sheet['lower-section']) && $score_sheet['lower-section']['yahtzee_bonus_one'] === 100) checked="checked" @endif>
                            <input class="accessible me-3 form-check-input @if(array_key_exists('yahtzee_bonus_two', $score_sheet['lower-section'])  || $turns === 13) disabled @else active @endif" type="checkbox" id="yahtzee_bonus_two" value="100" aria-label="Yahtzee bonus" @if(array_key_exists('yahtzee_bonus_two', $score_sheet['lower-section']) || $turns === 13) disabled="disabled" @endif @if(array_key_exists('yahtzee_bonus_two', $score_sheet['lower-section']) && $score_sheet['lower-section']['yahtzee_bonus_two'] === 100) checked="checked" @endif>
                            <input class="accessible form-check-input @if(array_key_exists('yahtzee_bonus_three', $score_sheet['lower-section']) || $turns === 13) disabled @else active @endif" type="checkbox" id="yahtzee_bonus_three" value="100" aria-label="Yahtzee bonus" @if(array_key_exists('yahtzee_bonus_three', $score_sheet['lower-section']) || $turns === 13) disabled="disabled" @endif @if(array_key_exists('yahtzee_bonus_three', $score_sheet['lower-section']) && $score_sheet['lower-section']['yahtzee_bonus_three'] === 100) checked="checked" @endif>
                        </div>
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
            <div class="player-scores"></div>
            <footer class="container py-5">
                <div class="row">
                    <div class="col-12 col-md">
                        <small class="d-block mb-3 text-muted">&copy; 2022</small>
                        <small class="d-block mb-3 text-muted">v{{ $config['version'] }} - Released {{ $config['release_date'] }}</small>
                    </div>
                    <div class="col-6 col-md">
                        <h5>Game Scorers</h5>
                        <ul class="list-unstyled text-small">
                            <li><a class="link-secondary" href="https://yahtzee.game-scorer.com">Yahtzee</a></li>
                            <li><a class="link-secondary" href="#">Yatzy (Coming soon)</a></li>
                        </ul>
                    </div>
                    <div class="col-6 col-md">
                        <h5>Costs to Expect</h5>
                        <ul class="list-unstyled text-small">
                            <li><a class="link-secondary" href="https://api.costs-to-expect.com">The API</a></li>
                            <li><a class="link-secondary" href="https://github.com/costs-to-expect">GitHub</a></li>
                            <li><a class="link-secondary" href="https://www.costs-to-expect.com">Social Experiment</a></li>
                            <li><a class="link-secondary" href="https://www.deanblackborough.com">Dean Blackborough</a></li>
                        </ul>
                    </div>
                </div>
            </footer>
        </div>

        @if ($complete === 0)
        <x-toast />
        <script src="{{ asset('node_modules/axios/dist/axios.min.js') }}" defer></script>
        <script src="{{ asset('node_modules/bootstrap/dist/js/bootstrap.js') }}" defer></script>
        <script src="https://cdn.jsdelivr.net/npm/canvas-confetti@1.5.1/dist/confetti.browser.min.js" defer></script>
        <script type="module" src="{{ asset('js/score-sheet.js?v1.02.0') }}" defer></script>
        <script src="{{ asset('js/player-scores.js?v1.02.0') }}" defer></script>
        <script src="{{ asset('js/bonus-message.js?v1.02.0') }}" defer></script>
        @endif
    </body>
</html>
