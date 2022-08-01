(function (axios, bootstrap, confetti) {
    'use strict'

    let uri_score_upper = '/game/score-upper';
    let uri_score_lower = '/game/score-lower';

    let game_id = document.getElementById('game_id');
    let player_id = document.getElementById('player_id');

    let timeout = null;
    let delay = 1000;

    let factors = { "ones": 1, "twos": 2, "threes": 3, "fours": 4, "fives": 5, "sixes": 6 }

    let player_score_upper = document.getElementById('upper-score');
    let player_score_bonus = document.getElementById('upper-bonus');
    let player_score_upper_total = document.getElementById('upper-total');
    let player_score_lower_upper = document.getElementById('lower-upper-total');
    let player_score_lower = document.getElementById('lower-score');
    let player_total_score = document.getElementById('total');
    let player_final_score = document.getElementById('final-score');

    document.querySelectorAll('div.upper-section-scratch input[type="checkbox"].active').forEach(upper_scratch => {
       upper_scratch.addEventListener('change', function () {
           scratch_upper_combination(this);
       });
    });

    document.querySelectorAll('div.upper-section input[type="number"].active').forEach(upper =>
        upper.addEventListener('change', function() {
            score_upper_combination(this)
        })
    );

    let three_of_a_kind = document.querySelector('input[type="number"]#three_of_a_kind.active');
    if (three_of_a_kind !== null) {
        three_of_a_kind.addEventListener('change', function () {
            score_lower_combination(this);
        });
    }

    let scratch_three_of_a_kind = document.querySelector('input[type="checkbox"]#scratch_three_of_a_kind.active');
    if (scratch_three_of_a_kind !== null) {
        scratch_three_of_a_kind.addEventListener('change', function () {
            scratch_lower_combination(this);
        });
    }

    let four_of_a_kind = document.querySelector('input[type="number"]#four_of_a_kind.active');
    if (four_of_a_kind !== null) {
        four_of_a_kind.addEventListener('change', function () {
            score_lower_combination(this);
        });
    }

    let scratch_four_of_a_kind = document.querySelector('input[type="checkbox"]#scratch_four_of_a_kind.active');
    if (scratch_four_of_a_kind !== null) {
        scratch_four_of_a_kind.addEventListener('change', function () {
            scratch_lower_combination(this);
        });
    }

    let full_house = document.querySelector('input[type="checkbox"]#full_house.active');
    if (full_house !== null) {
        full_house.addEventListener('change', function () {
            score_lower_fixed_combination(this, 25);
        });
    }

    let scratch_full_house = document.querySelector('input[type="checkbox"]#scratch_full_house.active');
    if (scratch_full_house !== null) {
        scratch_full_house.addEventListener('change', function () {
            scratch_lower_fixed_combination(this);
        });
    }

    let small_straight = document.querySelector('input[type="checkbox"]#small_straight.active');
    if (small_straight !== null) {
        small_straight.addEventListener('change', function () {
            score_lower_fixed_combination(this, 30);
        });
    }

    let scratch_small_straight = document.querySelector('input[type="checkbox"]#scratch_small_straight.active');
    if (scratch_small_straight !== null) {
        scratch_small_straight.addEventListener('change', function () {
            scratch_lower_fixed_combination(this);
        });
    }

    let large_straight = document.querySelector('input[type="checkbox"]#large_straight.active');
    if (large_straight !== null) {
        large_straight.addEventListener('change', function () {
            score_lower_fixed_combination(this, 40);
        });
    }

    let scratch_large_straight = document.querySelector('input[type="checkbox"]#scratch_large_straight.active');
    if (scratch_large_straight !== null) {
        scratch_large_straight.addEventListener('change', function () {
            scratch_lower_fixed_combination(this);
        });
    }

    let yahtzee = document.querySelector('input[type="checkbox"]#yahtzee.active');
    if (yahtzee !== null) {
        yahtzee.addEventListener('change', function () {
            score_lower_fixed_combination(this, 50, 'yahtzee');
        });
    }

    let scratch_yahtzee = document.querySelector('input[type="checkbox"]#scratch_yahtzee.active');
    if (scratch_yahtzee !== null) {
        scratch_yahtzee.addEventListener('change', function () {
            scratch_lower_combination(this, 'yahtzee_scratch');
        });
    }

    let chance = document.querySelector('input[type="number"]#chance.active');
    if (chance !== null) {
        chance.addEventListener('change', function () {
            score_lower_combination(this);
        });
    }

    let scratch_chance = document.querySelector('input[type="checkbox"]#scratch_chance.active');
    if (scratch_chance !== null) {
        scratch_chance.addEventListener('change', function () {
            scratch_lower_combination(this, 'chance_scratch');
        });
    }

    let yahtzee_bonus_one = document.querySelector('input[type="checkbox"]#yahtzee_bonus_one.active');
    if (yahtzee_bonus_one !== null) {
        yahtzee_bonus_one.addEventListener('change', function () {
            score_a_yahtzee_bonus(this, 'yahtzee_bonus_one');
        });
    }

    let yahtzee_bonus_two = document.querySelector('input[type="checkbox"]#yahtzee_bonus_two.active');
    if (yahtzee_bonus_two !== null) {
        yahtzee_bonus_two.addEventListener('change', function () {
            score_a_yahtzee_bonus(this, 'yahtzee_bonus_two');
        });
    }

    let yahtzee_bonus_three = document.querySelector('input[type="checkbox"]#yahtzee_bonus_three.active');
    if (yahtzee_bonus_three !== null) {
        yahtzee_bonus_three.addEventListener('change', function () {
            score_a_yahtzee_bonus(this, 'yahtzee_bonus_three');
        });
    }

    let disable_yahtzee_bonus_if_game_over = function(turns) {
        if (turns === 13) {
            yahtzee_bonus_one.disabled = true;
            yahtzee_bonus_two.disabled = true;
            yahtzee_bonus_three.disabled = true;

            display_selected_toast('done');
        }
    }

    let display_selected_toast = function (show_toast) {
        if (show_toast !== 'none') {
            const toast = new bootstrap.Toast(document.getElementById('toast_' + show_toast))
            toast.show();

            if (show_toast === 'yahtzee') {
                confetti({
                    particleCount: 100,
                })
            }
            if (show_toast.startsWith('yahtzee_bonus')) {
                confetti({
                    particleCount: 500,
                })
            }
        }
    }

    let score_lower_combination = function(element, show_toast = 'none') {

        let score = parseInt(element.value);
        if (score >= 6 && score <= 30) {

            clearTimeout(timeout);

            timeout = setTimeout(() => {
                axios.post(
                    uri_score_lower,
                    {
                        game_id: game_id.value,
                        player_id: player_id.value,
                        combo: element.id,
                        score: score
                    }
                )
                .then(response => {
                    element.classList.remove('active');
                    element.classList.add('disabled');
                    element.disabled = true;

                    let scratch = document.getElementById('scratch_' + element.id);
                    scratch.classList.remove('active');
                    scratch.classList.add('disabled');
                    scratch.disabled = true;

                    player_score_lower.innerText = response.data.score.lower;
                    player_total_score.innerText = response.data.score.upper + response.data.score.bonus + response.data.score.lower;
                    player_final_score.innerText = response.data.score.upper + response.data.score.bonus + response.data.score.lower;

                    disable_yahtzee_bonus_if_game_over(response.data.turns);

                    display_selected_toast(show_toast);

                    document.querySelectorAll('p.' + element.id + '_dice svg').forEach(dice =>
                        dice.classList.add('scored')
                    );
                })
                .catch(error => {
                    console.log(error);
                });
            }, delay);
        }
    }

    let score_lower_fixed_combination = function(element, score, show_toast = 'none') {
        clearTimeout(timeout);

        timeout = setTimeout(() => {
            axios.post(
                uri_score_lower,
                {
                    game_id: game_id.value,
                    player_id: player_id.value,
                    combo: element.id,
                    score: score
                }
            )
            .then(response => {
                element.classList.remove('active');
                element.classList.add('disabled');
                element.disabled = true;

                let scratch = document.getElementById('scratch_' + element.id);
                scratch.classList.remove('active');
                scratch.classList.add('disabled');
                scratch.disabled = true;

                player_score_lower.innerText = response.data.score.lower;
                player_total_score.innerText = response.data.score.upper + response.data.score.bonus + response.data.score.lower;
                player_final_score.innerText = response.data.score.upper + response.data.score.bonus + response.data.score.lower;

                disable_yahtzee_bonus_if_game_over(response.data.turns);

                display_selected_toast(show_toast);

                document.querySelectorAll('p.' + element.id + '_dice svg').forEach(dice =>
                    dice.classList.add('scored')
                );
            })
            .catch(error => {
                console.log(error);
            });
        }, delay);
    }

    let scratch_lower_combination = function(element, show_toast = 'none') {
        clearTimeout(timeout);

        timeout = setTimeout(() => {
            axios.post(
                uri_score_lower,
                {
                    game_id: game_id.value,
                    player_id: player_id.value,
                    combo: element.id.toString().replace('scratch_', ''),
                    score: 0
                }
            )
            .then(response => {
                element.classList.remove('active');
                element.classList.add('disabled');
                element.disabled = true;

                let lower = document.getElementById(element.id.toString().replace('scratch_', ''));
                lower.classList.remove('active');
                lower.classList.add('disabled');
                lower.disabled = true;
                lower.value = 0;

                player_final_score.innerText = response.data.score.upper + response.data.score.bonus + response.data.score.lower;

                disable_yahtzee_bonus_if_game_over(response.data.turns);

                display_selected_toast(show_toast);

                document.querySelectorAll('p.' + element.id.toString().replace('scratch_', '') + '_dice svg').forEach(dice =>
                    dice.classList.add('scored')
                );
            })
            .catch(error => {
                console.log(error);
            });
        }, delay);
    }

    let scratch_lower_fixed_combination = function(element, show_toast = 'none') {
        clearTimeout(timeout);

        timeout = setTimeout(() => {
            axios.post(
                uri_score_lower,
                {
                    game_id: game_id.value,
                    player_id: player_id.value,
                    combo: element.id.toString().replace('scratch_', ''),
                    score: 0
                }
            )
            .then(response => {
                element.classList.remove('active');
                element.classList.add('disabled');
                element.disabled = true;

                let lower = document.getElementById(element.id.toString().replace('scratch_', ''));
                lower.classList.remove('active');
                lower.classList.add('disabled');
                lower.disabled = true;

                player_final_score.innerText = response.data.score.upper + response.data.score.bonus + response.data.score.lower;

                display_selected_toast(show_toast);

                disable_yahtzee_bonus_if_game_over(response.data.turns);

                document.querySelectorAll('p.' + element.id.toString().replace('scratch_', '') + '_dice svg').forEach(dice =>
                    dice.classList.add('scored')
                );
            })
            .catch(error => {
                console.log(error);
            });
        }, delay);
    }

    let score_upper_combination = function(element, show_toast = 'none') {

        let score = parseInt(element.value);
        let scoring_dice = 0;
        if (factors.hasOwnProperty(element.name)) {
            scoring_dice = (score / factors[element.name]);
        } else {
            score = 0; // Set score to zero if name not valid
        }

        if (
            score > 0 &&
            scoring_dice % 1 === 0 &&
            scoring_dice >= 1 &&
            scoring_dice <= 6
        ) {
            clearTimeout(timeout);
            timeout = setTimeout(() => {
                axios.post(
                    uri_score_upper,
                    {
                        game_id: game_id.value,
                        player_id: player_id.value,
                        dice: element.id,
                        score: score
                    }
                )
                .then(response => {
                    element.classList.remove('active');
                    element.classList.add('disabled');
                    element.disabled = true;

                    let scratch = document.getElementById('scratch_' + element.id);
                    scratch.classList.remove('active');
                    scratch.classList.add('disabled');
                    scratch.disabled = true;

                    player_score_upper.innerText= response.data.score.upper;
                    player_score_bonus.innerText = response.data.score.bonus;
                    player_score_upper_total.innerText = response.data.score.upper + response.data.score.bonus;
                    player_score_lower_upper.innerText = response.data.score.upper + response.data.score.bonus;
                    player_total_score.innerText = response.data.score.upper + response.data.score.bonus + response.data.score.lower;
                    player_final_score.innerText = response.data.score.upper + response.data.score.bonus + response.data.score.lower;

                    display_selected_toast(show_toast);

                    disable_yahtzee_bonus_if_game_over(response.data.turns);

                    document.querySelectorAll('label[for="' + element.id + '"] svg').forEach(dice =>
                        dice.classList.add('scored')
                    );
                })
                .catch(error => {
                    console.log(error);
                });
            }, delay);
        }
    }

    let scratch_upper_combination = function(element, show_toast = 'none') {
        if (factors.hasOwnProperty(element.value) && element.checked === true) {

            clearTimeout(timeout);

            timeout = setTimeout(() => {
                axios.post(
                    uri_score_upper,
                    {
                        game_id: game_id.value,
                        player_id: player_id.value,
                        dice: element.value,
                        score: 0
                    }
                )
                .then(response => {
                    element.classList.remove('active');
                    element.classList.add('disabled');
                    element.disabled = true;

                    let upper = document.getElementById(element.value);
                    upper.classList.remove('active');
                    upper.classList.add('disabled');
                    upper.value = 0;
                    upper.disabled = true;

                    player_final_score.innerText = response.data.score.upper + response.data.score.bonus + response.data.score.lower;

                    display_selected_toast(show_toast);

                    disable_yahtzee_bonus_if_game_over(response.data.turns);

                    document.querySelectorAll('label[for="' + element.value + '"] svg').forEach(dice =>
                        dice.classList.add('scored')
                    );
                })
                .catch(error => {
                    console.log(error);
                });
            }, delay);
        }
    }

    let score_a_yahtzee_bonus = function(element, show_toast = 'none') {

        let yahtzee = document.querySelector('input[type="checkbox"]#yahtzee.disabled');
        if (yahtzee !== null && yahtzee.checked === true) {
            clearTimeout(timeout);

            timeout = setTimeout(() => {
                axios.post(
                    uri_score_lower,
                    {
                        game_id: game_id.value,
                        player_id: player_id.value,
                        combo: element.id,
                        score: 100
                    }
                )
                .then(response => {
                    element.classList.remove('active');
                    element.classList.add('disabled');
                    element.disabled = true;

                    player_score_lower.innerText = response.data.score.lower;
                    player_total_score.innerText = response.data.score.upper + response.data.score.bonus + response.data.score.lower;
                    player_final_score.innerText = response.data.score.upper + response.data.score.bonus + response.data.score.lower;

                    disable_yahtzee_bonus_if_game_over(response.data.turns);

                    display_selected_toast(show_toast);
                })
                .catch(error => {
                    console.log(error);
                });
            }, delay);
        }
    }

})(axios, bootstrap, confetti);