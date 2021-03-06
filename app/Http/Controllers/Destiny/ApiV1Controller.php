<?php namespace PandaLove\Http\Controllers\Destiny;

use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\View\Factory as View;
use Illuminate\Http\Request as Request;
use Illuminate\Routing\Redirector as Redirect;
use Illuminate\Support\Facades\Response;
use Onyx\Account;
use Onyx\Destiny\Client;
use Onyx\Destiny\Enums\Types;
use Onyx\Destiny\GameNotFoundException;
use Onyx\Destiny\Helpers\Event\MessageGenerator;
use Onyx\Destiny\Helpers\String\Hashes;
use Onyx\Destiny\Helpers\String\Text;
use Onyx\Destiny\Objects\GameEvent;
use Onyx\User;
use Carbon\Carbon;
use PandaLove\Commands\UpdateAccount;
use PandaLove\Http\Controllers\Controller;

class ApiV1Controller extends Controller {

    private $view;
    private $request;
    private $redirect;

    const MAX_GRIMOIRE = 4765; #http://destinytracker.com/destiny/leaderboards/xbox/grimoirescore

    protected $layout = "layouts.master";

    public function __construct(View $view, Redirect $redirect, Request $request)
    {
        parent::__construct();
        $this->view = $view;
        $this->request = $request;
        $this->redirect = $redirect;
        date_default_timezone_set('America/Chicago');
    }

    //---------------------------------------------------------------------------------
    // Destiny GET
    //---------------------------------------------------------------------------------

    public function getGrimoire($gamertag)
    {
        try
        {
            $account = Account::with('destiny.characters')->where('seo', Text::seoGamertag($gamertag))->firstOrFail();

            $msg = '<strong>' . $account->gamertag . "</strong><br/><br />Grimoire: ";

            $msg .= $account->destiny->grimoire;

            if ($account->destiny->getOriginal('grimoire') == self::MAX_GRIMOIRE)
            {
                $msg .= "<strong> [MAX]</strong>";
            }

            return Response::json([
                'error' => false,
                'msg' => $msg
            ], 200);
        }
        catch (ModelNotFoundException $e)
        {
            return $this->_error('Gamertag not found');
        }
    }

    public function getLightLeaderboard()
    {
        $pandas = Account::with('destiny.characters')->whereHas('destiny', function($query)
        {
            $query->where('clanName', 'Panda Love')
                ->where('clanTag', 'WRKD')
                ->where('inactiveCounter', '<', 10);
        })->get();

        $p = [];

        Hashes::cacheAccountsHashes($pandas);

        foreach($pandas as $panda)
        {
            $character = $panda->destiny->highestLevelHighestLight();
            $p[$character->level][] = [
                'name' => $panda->gamertag . " (" . $character->class->title . ")",
                'maxLight' => $character->highest_light,
                'light' => $character->light
            ];
        }

        krsort($p);

        foreach ($p as $key => $value)
        {
            // lets sort the sub levels
            usort($value, function($a, $b)
            {
                return $b['maxLight'] - $a['maxLight'];
            });

            $p[$key] = $value;
        }

        $msg = '<strong>Light Leaderboard</strong><br /><br />';

        foreach ($p as $level => $chars)
        {
            $msg .= "<strong>Level " . $level . "'s</strong><br />";

            $i = 1;
            foreach($chars as $char)
            {
                $msg .= $i . ". " . $char['name'] . " <strong>" . $char['maxLight'] . "</strong><br />";
                $i++;
            }

            $msg .= '<br />';
        }

        return Response::json([
            'error' => false,
            'msg' => $msg
        ], 200);
    }

    public function getXur()
    {
        $client = new Client();
        $xurData = $client->getXurData();

        if ($xurData == false && strlen($xurData) < 30)
        {
            return $this->_error('XUR is not here right now.');
        }
        else
        {
            return Response::json([
                'error' => false,
                'msg' => $xurData
            ]);
        }
    }

    public function getRaidTuesdayCountdown()
    {
        if (Carbon::now('America/Chicago')->isSameDay(new Carbon('Tuesday 4am CST', 'America/Chicago')))
        {
            $raidtuesday = new Carbon('Tuesday 4am CST', 'America/Chicago');
        }
        else
        {
            $raidtuesday = new Carbon('next Tuesday 4 AM','America/Chicago');
        }

        if ($raidtuesday->lt(Carbon::now('America/Chicago')))
        {
            return \Response::json([
                'error' => false,
                'msg' => 'Today is Raid Tuesday! Get your raids in!'
            ]);
        }
        else
        {
            $countdown = $raidtuesday->diffInSeconds(Carbon::now('America/Chicago'));
            $countdown = Text::timeDuration($countdown);

            return \Response::json([
                'error' => false,
                'msg' => $countdown
            ]);
        }
    }

    public function getEvents()
    {
        $events = GameEvent::where('start', '>=', Carbon::now('America/Chicago'))
            ->orderBy('start', 'ASC')
            ->get();

        if (count($events) > 0)
        {
            $msg = MessageGenerator::buildEventsResponse($events);

            return Response::json([
                'error' => false,
                'msg' => $msg
            ]);
        }
        else
        {
            return $this->_error('There are no events upcoming.');
        }
    }

    public function getEvent($id)
    {
        try
        {
            $event = GameEvent::where('id', intval($id))->firstOrFail();

            $msg = MessageGenerator::buildSingleEventResponse($event);

            return Response::json([
                'error' => false,
                'msg' => $msg
            ]);
        }
        catch (ModelNotFoundException $e)
        {
            return $this->_error('This game could not be found.');
        }
    }

    //---------------------------------------------------------------------------------
    // Destiny POST
    //---------------------------------------------------------------------------------

    public function postUpdate()
    {
        $all = $this->request->all();

        if (isset($all['google_id']))
        {
            try
            {
                $user = User::where('google_id', $all['google_id'])
                    ->firstOrFail();

                if ($user->account_id != 0)
                {
                    $this->dispatch(new UpdateAccount($user->account));

                    return Response::json([
                        'error' => false,
                        'msg' => 'Stats for: <strong>' . $user->account->gamertag . '</strong> have been updated.'
                    ], 200);
                }
                else
                {
                    return Response::json([
                        'error' => false,
                        'msg' => 'bitch pls. You need to confirm your gamertag on PandaLove so I know who you are.'
                    ], 200);
                }
            }
            catch (ModelNotFoundException $e)
            {
                return $this->_error('User account could not be found.');
            }
        }
    }

    public function postLight()
    {
        $all = $this->request->all();

        if (isset($all['google_id']))
        {
            try
            {
                $user = User::where('google_id', $all['google_id'])
                    ->firstOrFail();

                if ($user->account_id != 0)
                {
                    $msg = '<strong>' . $user->account->gamertag . ' Light</strong> <br /><br />';

                    foreach($user->account->destiny->charactersInOrder() as $char)
                    {
                        $msg .= "<strong>" . $char->name() . "</strong><br />";
                        $msg .= '<i>Highest Light:</i> <strong>' . $char->highest_light . "</strong><br />";
                        $msg .= '<i>Current Light:</i> <strong>' . $char->light . "</strong><br /><br />";
                    }

                    $msg .= '<br /><br />';
                    $msg .= '<i>Account updated: ' . $user->account->destiny->updated_at->diffForHumans() . "</i>";

                    return Response::json([
                        'error' => false,
                        'msg' => $msg
                    ], 200);
                }
                else
                {
                    return Response::json([
                        'error' => false,
                        'msg' => 'bitch pls. You need to confirm your gamertag on PandaLove so I know who you are.'
                    ], 200);
                }
            }
            catch (ModelNotFoundException $e)
            {
                return $this->_error('User account could not be found.');
            }
        }
    }

    public function postAddGame()
    {
        $all = $this->request->all();

        if (isset($all['google_id']))
        {
            try
            {
                $user = User::where('google_id', $all['google_id'])
                    ->where('admin', true)
                    ->firstOrFail();

                $client = new Client();

                try
                {
                    $game = $client->fetchGameByInstanceId($all['instanceId']);
                }
                catch (GameNotFoundException $e)
                {
                    return $this->_error('Game could not be found');
                }

                $client->updateTypeOfGame($all['instanceId'], Types::getProperFormat($all['type']), $all['passageId']);

                return Response::json([
                    'error' => false,
                    'msg' => 'Game Added! '
                ], 200);
            }
            catch (ModelNotFoundException $e)
            {
                return $this->_error('User account could not be found.');
            }
        }
    }

    //---------------------------------------------------------------------------------
    // XPrivate Functions
    //---------------------------------------------------------------------------------

    private function _error($message)
    {
        return Response::json([
            'error' => true,
            'message' => $message
        ], 200);
    }
}