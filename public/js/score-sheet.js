(function (axios) {
    'use strict'

    let game_id = document.getElementById('game_id');
    let player_id = document.getElementById('player_id');

    let timeout = null;
    let delay = 1500;
    let factors = { "ones": 1, "twos": 2, "threes": 3, "fours": 4, "fives": 5, "sixes": 6 }

    let score_upper = document.getElementById('upper-score');
    let score_bonus = document.getElementById('upper-bonus');
    let score_upper_total = document.getElementById('upper-total');
    let score_lower_upper = document.getElementById('lower-upper-total');
    let score_lower = document.getElementById('lower-score');
    let total_score = document.getElementById('total');

    // Score the upper section
    document.querySelectorAll('div.upper-section input[type="number"].active').forEach(upper =>
        upper.addEventListener('change', function() {

            let score = parseInt(upper.value);
            let scoring_dice = (score / factors[upper.name]);

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
                            dice: upper.id,
                            score: score
                        }
                    )
                    .then(response => {
                        upper.classList.remove('active');
                        upper.classList.add('disabled');
                        upper.disabled = true;

                        score_upper.innerText= response.data.score.upper;
                        score_bonus.innerText = response.data.score.bonus;
                        score_upper_total.innerText = response.data.score.upper + response.data.score.bonus;
                        score_lower_upper.innerText = response.data.score.upper + response.data.score.bonus;
                        total_score.innerText = response.data.score.upper + response.data.score.bonus + response.data.score.lower;

                        console.log(response.data);
                    })
                    .catch(error => {
                        console.log(error);
                    });
                }, delay);
            }
        })
    );
})(axios);