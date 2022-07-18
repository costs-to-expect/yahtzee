(function (axios) {
    'use strict'

    let game_id = document.getElementById('game_id').value;
    let player_id = document.getElementById('player_id').value;

    let timeout = null;
    let delay = 1000;
    let factors = { "ones": 1, "twos": 2, "threes": 3, "fours": 5, "fives": 5, "sixes": 6 }

    // Score the upper section
    document.querySelectorAll('form[name="upper-section"] input[type="number"].active').forEach(upper =>
        upper.addEventListener('change', function() {

            clearTimeout(timeout);
            timeout = setTimeout(() => {
                axios.post(
                    '',
                    {
                        game_id: game_id,
                        player_id: player_id,
                        dice: upper.id,
                        score: upper.value
                    }
                )
                .then(response => {
                    // Disable the relevant input
                    console.log(response.data);

                    // Update the score(s)
                })
                .catch(error => {
                    // Show an error, ask user to refresh and try again
                });

                console.log('Dice:', upper.id);
                console.log('Value:', upper.value);
            }, delay);
        })
    );
})(axios);