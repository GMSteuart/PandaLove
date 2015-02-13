<table class="ui table">
    <thead>
    <tr>
        <th>Raid</th>
        <th>Date</th>
        <th>Completion Time</th>
        <th>PandaLove Members Present</th>
    </tr>
    </thead>
    <tbody>
    @foreach($raids as $raid)
        <tr>
            <td>
                @if ($raid->isHard)
                    <div class="ui red horizontal label">Hard</div>
                @else
                    <div class="ui green horizontal label">Normal</div>
                @endif
                @if ($raid->raidTuesday != 0)
                    <a href="{{ URL::action('GameController@getTuesday', [$raid->raidTuesday]) }}">
                        {{ $raid->type()->title }}
                    </a>
                @else
                    <a href="{{ URL::action('GameController@getGame', [$raid->instanceId]) }}">
                        {{ $raid->type()->title }}
                    </a>
                @endif
            </td>
            <td>{{ $raid->occurredAt }}</td>
            <td>
                @if ($raid->raidTuesday != 0)
                    {{ \Onyx\Destiny\Helpers\String\Text::timeDuration($raid->totalTime) }}
                @else
                    {{ $raid->occurredAt }}
                @endif
            </td>
            <td>
                {{ $raid->completed() }}
            </td>
        </tr>
    @endforeach
    </tbody>
</table>
<div class="ui pagination menu">
    {!! with(new Onyx\Laravel\SemanticPresenter($raids))->render() !!}
</div>