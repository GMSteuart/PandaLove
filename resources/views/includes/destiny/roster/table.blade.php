<table class="ui striped compact table">
    <thead class="desktop only">
        <tr>
            <th>Gamertag</th>
            <th>Grimoire</th>
            <th>Character 1</th>
            <th>Character 2</th>
            <th>Character 3</th>
        </tr>
    </thead>
    <tbody>
        @foreach($members as $member)
            @if ($member->destiny->charactersCount() >= 3)
                <tr>
                    <td><a href="{{ URL::action('Destiny\ProfileController@index', array($member->seo)) }}">{{ $member->gamertag }}</a></td>
                    <td class="grimoire-table">{{ $member->destiny->grimoire }}</td>
                    <td>
                        <span class="right floated author">
                            <img class="ui avatar image" src="{{ $member->destiny->characterAtPosition(1)->emblem->extra}}" />
                            <a href="{{ URL::action('Destiny\ProfileController@index', [$member->seo, $member->destiny->characterAtPosition(1)->characterId]) }}">
                                {{ $member->destiny->characterAtPosition(1)->name() }}
                            </a>
                        </span>
                    </td>
                    <td>
                        <span class="right floated author">
                            <img class="ui avatar image" src="{{ $member->destiny->characterAtPosition(2)->emblem->extra}}" />
                            <a href="{{ URL::action('Destiny\ProfileController@index', [$member->seo, $member->destiny->characterAtPosition(2)->characterId]) }}">
                                {{ $member->destiny->characterAtPosition(2)->name() }}
                            </a>
                        </span>
                    </td>
                    <td>
                        <span class="right floated author">
                            <img class="ui avatar image" src="{{ $member->destiny->characterAtPosition(3)->emblem->extra}}" />
                            <a href="{{ URL::action('Destiny\ProfileController@index', [$member->seo, $member->destiny->characterAtPosition(3)->characterId]) }}">
                                {{ $member->destiny->characterAtPosition(3)->name() }}
                            </a>
                        </span>
                    </td>
                </tr>
            @endif
        @endforeach
    </tbody>
</table>
{!! with(new Onyx\Laravel\SemanticPresenter($members))->render() !!}