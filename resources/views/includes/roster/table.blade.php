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
            @if ($member->charactersCount() >= 3)
                <tr>
                    <td><a href="{{ URL::action('ProfileController@index', array($member->seo)) }}">{{ $member->gamertag }}</a></td>
                    <td class="grimoire-table">{{ $member->grimoire }}</td>
                    <td>
                        <span class="right floated author">
                            <img class="ui avatar image" src="{{ $member->characterAtPosition(1)->emblem->extra}}" />
                            <a href="{{ URL::action('ProfileController@index', [$member->seo, $member->characterAtPosition(1)->characterId]) }}">
                                {{ $member->characterAtPosition(1)->name() }}
                            </a>
                        </span>
                    </td>
                    <td>
                        <span class="right floated author">
                            <img class="ui avatar image" src="{{ $member->characterAtPosition(2)->emblem->extra}}" />
                            <a href="{{ URL::action('ProfileController@index', [$member->seo, $member->characterAtPosition(2)->characterId]) }}">
                                {{ $member->characterAtPosition(2)->name() }}
                            </a>
                        </span>
                    </td>
                    <td>
                        <span class="right floated author">
                            <img class="ui avatar image" src="{{ $member->characterAtPosition(3)->emblem->extra}}" />
                            <a href="{{ URL::action('ProfileController@index', [$member->seo, $member->characterAtPosition(3)->characterId]) }}">
                                {{ $member->characterAtPosition(3)->name() }}
                            </a>
                        </span>
                    </td>
                </tr>
            @endif
        @endforeach
    </tbody>
</table>
{!! with(new Onyx\Laravel\SemanticPresenter($members))->render() !!}