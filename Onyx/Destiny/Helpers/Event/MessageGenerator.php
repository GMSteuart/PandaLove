<?php namespace Onyx\Destiny\Helpers\Event;

use Illuminate\Database\Eloquent\ModelNotFoundException;
use Onyx\Destiny\Objects\Attendee;
use Onyx\Destiny\Objects\Character;
use Onyx\Destiny\Objects\GameEvent;

class MessageGenerator {

    /**
     * @param \Onyx\Destiny\Objects\GameEvent $event
     * @return string
     */
    public static function buildSingleEventResponse($event)
    {
        $msg = '<strong><a href="' . \URL::action('CalendarController@getEvent', [$event->id]) . '">' . $event->title . '</a></strong><br />';
        $msg .= '<i>' . $event->botDate() . '</i><br/><br />';

        $count = 1;
        foreach ($event->attendees as $attendee)
        {
            $msg .= $count++ . ') - <a href="' . \URL::action('Destiny\ProfileController@index', [$attendee->account->seo, $attendee->character->characterId]) .
                '">' . $attendee->account->gamertag . "</a> (" . $attendee->character->name() . ")<br />";
        }

        if (! $event->isFull())
        {
            $msg .= '<br /> Remember, you can apply via <strong>/bot rsvp ' . $event->id . '</strong>';
        }

        return $msg;
    }

    /**
     * @param $events
     * @return string
     */
    public static function buildEventsResponse($events)
    {
        $msg = '<strong>Upcoming Events</strong><br/><br />';
        foreach ($events as $event)
        {
            $msg .= $event->id . ") - " . '<a href="' . \URL::action('CalendarController@getEvent', [$event->id]) . '">' . $event->title . '</a> [' . $event->botDate() . '] - ';
            $msg .= $event->count() . "/" . $event->max_players . ($event->isFull() ? ' [full]' : ' slots') . '<br />';
        }

        $msg .= '<br /> Remember you can RSVP to any of the above events via <strong>/bot rsvp #</strong> where # is one of the IDs above.';

        return $msg;
    }

    /**
     * @param $user
     * @param $all
     * @return string
     */
    public static function buildRSVPResponse($user, $all)
    {
        $msg = '';

        // Lets check if char_id is 0, if so. Let the user know of their chars with numbers to pick one.
        if (intval($all['char_id']) == 0)
        {
            $count = 0;
            $msg = 'I need to know which character you want to be <strong>' . $user->account->gamertag . '</strong> for this event. Below are your characters with a number next to them. <br /><br />';
            foreach ($user->account->destiny->characters as $char)
            {
                $msg .= ++$count . ". - " . $char->name() . " " . $char->highest_light . "/" . $char->light . "<br />";
            }

            $msg .= '<br />Your new command will be <strong>/bot rsvp ' . $all['game_id'] . ' #</strong> Where # is one of the numbers above.';
        }
        else
        {
            // does this char even exist
            $char = $user->account->destiny->characterAtPosition($all['char_id']);

            if ($char instanceof Character)
            {
                try
                {
                    $event = GameEvent::where('id', intval($all['game_id']))->firstOrFail();

                    if ($event->isFull())
                    {
                        $msg = 'Ouch sorry man. This event is Full. No more RSVPs allowed';
                    }
                    else
                    {
                        if ($event->isAttending($user))
                        {
                            $msg = 'O think your slick eh? You are already attending this event. There is nothing you need to do.';
                        }
                        else
                        {

                            if ($event->isOver())
                            {
                                $msg = 'Sorry this event is over. No more RSVPs are allowed.';
                            }
                            else
                            {
                                $attendee = new Attendee();
                                $attendee->game_id = $event->id;
                                $attendee->membershipId = $user->account->destiny_membershipId;
                                $attendee->characterId = $char->characterId;
                                $attendee->account_id = $user->account->id;
                                $attendee->user_id = $user->id;
                                $attendee->save();

                                $msg = 'Congrats <strong> ' . $user->account->gamertag . '</strong> you have sealed a spot in this ';
                                $msg .= '<a href="' . \URL::action('CalendarController@getEvent', [$event->id]) . '">event</a>. There are <strong>' . ($event->spotsRemaining() - 1) . '</strong> spots remaining.';
                            }
                        }
                    }
                }
                catch (ModelNotFoundException $e)
                {
                    $msg = 'Sorry to break the news to you, but this event does not exist. Please try a different gameId.';
                }
            }
            else
            {
                $count = 0;
                $msg = 'Trying to be funny I see. That character does not exist for you. I guess I have to remind you. <br /><br />';
                foreach ($user->account->destiny->characters as $char)
                {
                    $msg .= ++$count . ". - " . $char->name() . " " . $char->highest_light . "/" . $char->light . "<br />";
                }

                $msg .= '<br />Your new command will be <strong>/bot rsvp ' . $all['game_id'] . ' #</strong> Where # is one of the numbers above.';
            }
        }

        return $msg;
    }
}