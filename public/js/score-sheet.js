(function (axios) {
    'use strict'

    let game_id = document.getElementById('game_id');
    let player_id = document.getElementById('player_id');

    let timeout = null;
    let delay = 2000;
    let factors = { "ones": 1, "twos": 2, "threes": 3, "fours": 5, "fives": 5, "sixes": 6 }

    // Score the upper section
    document.querySelectorAll('form[name="upper-section"] input[type="number"].active').forEach(upper =>
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

                        console.log(response.data.message);
                    })
                    .catch(error => {
                        console.log(error.data.message);
                    });
                }, delay);
            }
        })
    );
})(axios);