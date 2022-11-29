<?php

namespace App;

use Google\Service\Oauth2;
use Google_Client;
use Google_Service_Calendar;
use Google_Service_Calendar_Calendar;
use Google_Service_Calendar_AclRule;
use Google_Service_Calendar_AclRuleScope;
use Google_Service_Oauth2;
use Illuminate\Database\Eloquent\Model;
use App\Traits\UpdateGenericClass;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class Calendars extends Model
{
    //
    use UpdateGenericClass;

    protected $table = 'calendar';
    protected $fillable = [
        'id',
        'calendar_id',
        'subject_id',
        'gmail',
    ];

    public function store($id)
    {
        try{

            $user = Auth::user();

            // Consult subject info
            $subject = CustomSubject::findOrFail($id);
            $client = new Google_Client();

            // Consult the token on the database
            if(GoogleToken::where([['user_id', $user->id], ['is_active', true]])->exists()){
            $user_token = GoogleToken::where([['user_id', $user->id], ['is_active', true]])->first();
            $access_token = json_decode($user_token->token, true);
            //$access_token['refresh_token'] = json_decode($user_token->refresh_token, true);

            // Google client instance and authenticate

            $client->setAuthConfig(storage_path('/app/google-calendar/service-account-credentials.json'));
            $client->setAccessToken($access_token);

            }else{
                if (isset($_GET['code'])) {
                    $token = $client->fetchAccessTokenWithAuthCode($_GET['code']);

                    $client->setAccessToken($token['access_token']);

                    // get profile info
                    $google_oauth = new Google_Service_Oauth2($client);
                }
            }

            // Calendar service instance
            $service = new Google_Service_Calendar($client);

            // Create new public Calendar
            $calendar = new Google_Service_Calendar_Calendar();
            $calendar->setSummary($subject->custom_name);
            $calendar->setTimeZone('America/Mexico_City');

            $calendarList = $service->calendars->insert($calendar);

            // Make public
            $rule = new Google_Service_Calendar_AclRule();
            $scope = new Google_Service_Calendar_AclRuleScope();

            $scope->setType("default");
            $scope->setValue("");
            $rule->setScope($scope);
            $rule->setRole("reader");

            $service->acl->insert($calendarList->getId(), $rule);

            // Get user gmail
            $google_oauth =new Google_Service_Oauth2($client);
            $google_account_email = $google_oauth->userinfo->get()->email;

            // Store calendar id in the DB
            $data = ([
                'calendar_id' => $calendarList->getId(),
                'subject_id' => $id,
                'gmail' => $google_account_email
            ]);

            $new_calendar = Calendars::create($data);

            return $new_calendar;

        }catch (ModelNotFoundException $exception){
            return  'Hubo un problema al crear la materia' ;
        }
    }
}
