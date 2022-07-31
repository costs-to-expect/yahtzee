(function (axios) {
    'use strict'

    let token = document.getElementById('token');

    let sleep = time => new Promise(resolve => setTimeout(resolve, time))
    let poll = (promiseFn, time) => promiseFn().then(
        sleep(time).then(() => poll(promiseFn, time)))

    let fetchBonusMessage = function() {
        let bonus_message = document.querySelector('div.bonus-message');

        axios.get('/public/game/' + token.value + '/bonus')
            .then(response => {
                if (response.data.length > 0) {
                    bonus_message.innerHTML = response.data;
                }
            });
    }

    poll(() => new Promise(() => fetchBonusMessage()), 1000 * 5)
})(axios);