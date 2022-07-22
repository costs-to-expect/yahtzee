<div class="table-responsive">

    <hr />

    <h2 class="text-primary">Player Scores</h2>

    <table class="table table-striped table-sm">
        <caption>Player scores, delayed by thirty seconds.</caption>
        <thead>
            <tr>
                <th scope="col">Name</th>
                <th scope="col">Score</th>
            </tr>
        </thead>
        <tbody class="table-group-divider">
            @foreach($scores as $__score)
            <tr>
                <th scope="row">{{ $__score['name'] }}</th>
                <td>{{ $__score['score'] }}</td>
            </tr>
            @endforeach
        </tbody>
    </table>
</div>