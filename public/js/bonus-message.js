(function (axios) {
    'use strict'

    let game_id = document.getElementById('game_id');
    let player_id = document.getElementById('player_id');
    let token = document.getElementById('token');

    let uri = (token === null) ? '/game/' + game_id.value + '/player/' + player_id.value + '/bonus' : '/public/game/' + token.value + '/bonus';

    let sleep = time => new Promise(resolve => setTimeout(resolve, time))
    let poll = (promiseFn, time) => promiseFn().then(
        sleep(time).then(() => poll(promiseFn, time)))

    let fetchBonusMessage = function() {
        let bonus_message = document.querySelector('div.bonus-message');

        axios.get(uri)
            .then(response => {
                if (response.data.length > 0) {
                    bonus_message.innerHTML = response.data;
                }
            });
    }

    poll(() => new Promise(() => fetchBonusMessage()), 1000 * 5)
})(axios);