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

    // Scratch the upper section
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

                           console.log(response.data);
                       })
                       .catch(error => {
                           console.log(error);
                       });
               }, delay);

               console.log(this.checked, this.value);
           }
       });
    });

    // Score the upper section
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