(function (axios, bootstrap, confetti) {
    'use strict'

    let game_id = document.getElementById('game_id');
    let player_id = document.getElementById('player_id');

    let timeout = null;
    let delay = 1000;
    let factors = { "ones": 1, "twos": 2, "threes": 3, "fours": 4, "fives": 5, "sixes": 6 }

    let score_upper = document.getElementById('upper-score');
    let score_bonus = document.getElementById('upper-bonus');
    let score_upper_total = document.getElementById('upper-total');
    let score_lower_upper = document.getElementById('lower-upper-total');
    let score_lower = document.getElementById('lower-score');
    let total_score = document.getElementById('total');

    document.querySelectorAll('div.upper-section-scratch input[type="checkbox"].active').forEach(upper_scratch => {
       upper_scratch.addEventListener('change', function () {

           if (factors.hasOwnProperty(this.value) && this.checked === true) {

               clearTimeout(timeout);
               timeout = setTimeout(() => {
                   axios.post(
                       '/game/score-upper',
                       {
                           game_id: game_id.value,
                           player_id: player_id.value,
                           dice: this.value,
                           score: 0
                       }
                   )
                       .then(response => {
                           this.classList.remove('active');
                           this.classList.add('disabled');
                           this.disabled = true;

                           let upper = document.getElementById(this.value);
                           upper.classList.remove('active');
                           upper.classList.add('disabled');
                           upper.value = 0;
                           upper.disabled = true;
                       })
                       .catch(error => {
                           console.log(error);
                       });
               }, delay);

               console.log(this.checked, this.value);
           }
       });
    });

    document.querySelectorAll('div.upper-section input[type="number"].active').forEach(upper =>
        upper.addEventListener('change', function() {

            let score = parseInt(this.value);
            let scoring_dice = 0;
            if (factors.hasOwnProperty(this.name)) {
                scoring_dice = (score / factors[this.name]);
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
                        '/game/score-upper',
                        {
                            game_id: game_id.value,
                            player_id: player_id.value,
                            dice: this.id,
                            score: score
                        }
                    )
                    .then(response => {
                        this.classList.remove('active');
                        this.classList.add('disabled');
                        this.disabled = true;

                        let scratch = document.getElementById('scratch_' + this.id);
                        scratch.classList.remove('active');
                        scratch.classList.add('disabled');
                        scratch.disabled = true;

                        score_upper.innerText= response.data.score.upper;
                        score_bonus.innerText = response.data.score.bonus;
                        score_upper_total.innerText = response.data.score.upper + response.data.score.bonus;
                        score_lower_upper.innerText = response.data.score.upper + response.data.score.bonus;
                        total_score.innerText = response.data.score.upper + response.data.score.bonus + response.data.score.lower;
                    })
                    .catch(error => {
                        console.log(error);
                    });
                }, delay);
            }
        })
    );

    let three_of_a_kind = document.querySelector('input[type="number"]#three_of_a_kind.active');
    if (three_of_a_kind !== null) {
        three_of_a_kind.addEventListener('change', function () {
            let score = parseInt(this.value);

            if (score >= 6 && score <= 30) {
                score_lower_combo(this, score);
            }
        });
    }

    let scratch_three_of_a_kind = document.querySelector('input[type="checkbox"]#scratch_three_of_a_kind.active');
    if (scratch_three_of_a_kind !== null) {
        scratch_three_of_a_kind.addEventListener('change', function () {
            scratch_lower_combo(this);
        });
    }

    let four_of_a_kind = document.querySelector('input[type="number"]#four_of_a_kind.active');
    if (four_of_a_kind !== null) {
        four_of_a_kind.addEventListener('change', function () {
            let score = parseInt(this.value);

            if (score >= 6 && score <= 30) {
                score_lower_combo(this, score);
            }
        });
    }

    let scratch_four_of_a_kind = document.querySelector('input[type="checkbox"]#scratch_four_of_a_kind.active');
    if (scratch_four_of_a_kind !== null) {
        scratch_four_of_a_kind.addEventListener('change', function () {
            scratch_lower_combo(this);
        });
    }

    let full_house = document.querySelector('input[type="checkbox"]#full_house.active');
    if (full_house !== null) {
        full_house.addEventListener('change', function () {
            score_lower_combo(this, 25);
        });
    }

    let scratch_full_house = document.querySelector('input[type="checkbox"]#scratch_full_house.active');
    if (scratch_full_house !== null) {
        scratch_full_house.addEventListener('change', function () {
            scratch_lower_fixed_combo(this);
        });
    }

    let small_straight = document.querySelector('input[type="checkbox"]#small_straight.active');
    if (small_straight !== null) {
        small_straight.addEventListener('change', function () {
            score_lower_combo(this, 30);
        });
    }

    let scratch_small_straight = document.querySelector('input[type="checkbox"]#scratch_small_straight.active');
    if (scratch_small_straight !== null) {
        scratch_small_straight.addEventListener('change', function () {
            scratch_lower_fixed_combo(this);
        });
    }

    let large_straight = document.querySelector('input[type="checkbox"]#large_straight.active');
    if (large_straight !== null) {
        large_straight.addEventListener('change', function () {
            score_lower_combo(this, 40);
        });
    }

    let scratch_large_straight = document.querySelector('input[type="checkbox"]#scratch_large_straight.active');
    if (scratch_large_straight !== null) {
        scratch_large_straight.addEventListener('change', function () {
            scratch_lower_fixed_combo(this);
        });
    }

    let yahtzee = document.querySelector('input[type="checkbox"]#yahtzee.active');
    if (yahtzee !== null) {
        yahtzee.addEventListener('change', function () {
            score_lower_combo(this, 50, 'yahtzee');
        });
    }

    let scratch_yahtzee = document.querySelector('input[type="checkbox"]#scratch_yahtzee.active');
    if (scratch_yahtzee !== null) {
        scratch_yahtzee.addEventListener('change', function () {
            scratch_lower_combo(this, 'yahtzee_scratch');
        });
    }

    let chance = document.querySelector('input[type="number"]#chance.active');
    if (chance !== null) {
        chance.addEventListener('change', function () {
            let score = parseInt(this.value);

            if (score >= 6 && score <= 30) {
                score_lower_combo(this, score);
            }
        });
    }

    let scratch_chance = document.querySelector('input[type="checkbox"]#scratch_chance.active');
    if (scratch_chance !== null) {
        scratch_chance.addEventListener('change', function () {
            scratch_lower_combo(this, 'chance_scratch');
        });
    }

    let yahtzee_bonus_one = document.querySelector('input[type="checkbox"]#yahtzee_bonus_one.active');
    if (yahtzee_bonus_one !== null) {
        yahtzee_bonus_one.addEventListener('change', function () {
            score_yahtzee_bonus(this, 'yahtzee_bonus_one');
        });
    }

    let yahtzee_bonus_two = document.querySelector('input[type="checkbox"]#yahtzee_bonus_two.active');
    if (yahtzee_bonus_two !== null) {
        yahtzee_bonus_two.addEventListener('change', function () {
            score_yahtzee_bonus(this, 'yahtzee_bonus_two');
        });
    }

    let yahtzee_bonus_three = document.querySelector('input[type="checkbox"]#yahtzee_bonus_three.active');
    if (yahtzee_bonus_three !== null) {
        yahtzee_bonus_three.addEventListener('change', function () {
            score_yahtzee_bonus(this, 'yahtzee_bonus_three');
        });
    }

    let display_toast = function (show_toast) {
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

    let score_lower_combo = function(element, score, show_toast = 'none') {
        clearTimeout(timeout);

        timeout = setTimeout(() => {
            axios.post(
                '/game/score-lower',
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

                score_lower.innerText = response.data.score.lower;
                total_score.innerText = response.data.score.upper + response.data.score.bonus + response.data.score.lower;

                display_toast(show_toast);
            })
            .catch(error => {
                console.log(error);
            });
        }, delay);
    }

    let score_yahtzee_bonus = function(element, show_toast = 'none') {

        let yahtzee = document.querySelector('input[type="checkbox"]#yahtzee.disabled');
        if (yahtzee !== null && yahtzee.checked === true) {
            clearTimeout(timeout);

            timeout = setTimeout(() => {
                axios.post(
                    '/game/score-lower',
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

                        score_lower.innerText = response.data.score.lower;
                        total_score.innerText = response.data.score.upper + response.data.score.bonus + response.data.score.lower;

                        display_toast(show_toast);
                    })
                    .catch(error => {
                        console.log(error);
                    });
            }, delay);
        }
    }

    let scratch_lower_combo = function(element, show_toast = 'none') {
        clearTimeout(timeout);

        timeout = setTimeout(() => {
            axios.post(
                '/game/score-lower',
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

                display_toast(show_toast);
            })
            .catch(error => {
                console.log(error);
            });
        }, delay);
    }

    let scratch_lower_fixed_combo = function(element) {
        clearTimeout(timeout);

        timeout = setTimeout(() => {
            axios.post(
                '/game/score-lower',
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
            })
            .catch(error => {
                console.log(error);
            });
        }, delay);
    }
})(axios, bootstrap, confetti);