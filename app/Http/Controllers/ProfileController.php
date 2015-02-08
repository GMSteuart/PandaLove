<?php namespace PandaLove\Http\Controllers;

use Carbon\Carbon;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Onyx\Account;
use Onyx\Destiny\Helpers\String\Text;
use PandaLove\Commands\UpdateAccount;
use PandaLove\Http\Requests;

use Illuminate\Http\Request;

class ProfileController extends Controller {

    private $request;

    public function __construct(Request $request)
    {
        $this->request = $request;
    }

	public function index($gamertag = '')
    {
        try
        {
            $account = Account::with('characters')->where('seo', Text::seoGamertag($gamertag))->firstOrFail();

            $client = new \Onyx\Destiny\Client();
            $client->fetchAccountData($account);
            return view('profile', ['account' => $account]);
        }
        catch (ModelNotFoundException $e)
        {
            \App::abort(404, 'Da Gone!!! We have no idea what you are looking for.');
        }
    }

    public function checkForUpdate($gamertag = '')
    {
        if ($this->request->ajax() && ! \Agent::isRobot())
        {
            try
            {
                $account = Account::with('characters')->where('seo', Text::seoGamertag($gamertag))->firstOrFail();

                $char = $account->firstCharacter();

                if (Carbon::now()->diffInMinutes($char->updated_at) >= 100)
                {
                    // update this
                    $this->dispatch(new UpdateAccount($account));

                    return response()->json(['updated' => true]);
                }

                return response()->json(['updated' => false]);
            }
            catch (ModelNotFoundException $e)
            {
                return response()->json(['error' => 'Gamertag not found']);
            }
        }
    }
}