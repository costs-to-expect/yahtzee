<div class="table-responsive">

    <hr />

    <h2 class="text-primary">Player Scores</h2>

    <table class="table table-striped table-sm">
        <caption>Player scores, delayed by thirty seconds.</caption>
        <thead>
            <tr>
                <th scope="col">Name</th>
                <th scope="col">Upper</th>
                <th scope="col">Lower</th>
                <th scope="col">Total</th>
            </tr>
        </thead>
        <tbody class="table-group-divider">
            @foreach($scores as $__score)
            <tr>
                <th scope="row">{{ $__score['name'] }}</th>
                <td scope="row">{{ $__score['upper'] }}</td>
                <td scope="row">{{ $__score['lower'] }}</td>
                <th scope="row">{{ $__score['total'] }}</th>
            </tr>
            @endforeach
        </tbody>
    </table>
</div>