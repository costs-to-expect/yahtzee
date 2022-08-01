(function (axios) {
    'use strict'

    let token = document.getElementById('token');

    let sleep = time => new Promise(resolve => setTimeout(resolve, time))
    let poll = (promiseFn, time) => promiseFn().then(
        sleep(time).then(() => poll(promiseFn, time)))

    let fetchPlayerScores = function() {
        let player_scores = document.querySelector('div.player-scores');

        axios.get('/public/game/' + token.value + '/player-scores')
            .then(response => {
                if (response.data.length > 0) {
                    player_scores.innerHTML = response.data;
                }
            });
    }

    poll(() => new Promise(() => fetchPlayerScores()), 1000 * 10)
})(axios);