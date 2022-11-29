<?php

namespace App\Http\Controllers;

use App\Club;
use App\GroupModels\Group;
use App\GroupStudent;
use App\Subject;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Http\Request;
use Laravel\Socialite\Facades\Socialite;
use Illuminate\Support\Facades\Redirect;
use Illuminate\Support\Facades\Auth;
use App\GoogleToken;
use App\CustomSubject;
use App\Calendars;
use Google_Client;
use Google_Service_Calendar;
use Google_Service_Calendar_Calendar;
use Google_Service_Calendar_AclRule;
use Google_Service_Calendar_AclRuleScope;
use Google_Service_Oauth2;
use Google_Service_Calendar_Event;
use stdClass;
use function GuzzleHttp\Promise\iter_for;
use Exception;

class CalendarAPIController extends ApiController
{

    //
    public function redirect($id, $returnToPath)
    {
        try{

            $client = new Google_Client();
            $client->setAuthConfig(storage_path('/app/google-calendar/service-account-credentials.json'));
            $client->setClientSecret('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');
            $client->setClientId('908988232726-r1tnp5kkuk1cm9p5vk3msapteds21vph.apps.googleusercontent.com');
            // $client->setRedirectUri('http://localhost:8000/api/login/google/callback');
            // $client->setRedirectUri('https://test.clublia.com/api/api/login/google/callback');
            $client->setRedirectUri('https://comunidad.clublia.com/api/api/login/google/callback');
            $client->addScope(Google_Service_Calendar::CALENDAR);
            $client->addScope("email");
            $client->addScope("profile");
            // offline access will give you both an access and refresh token so that
            // your app can refresh the access token without user interaction.
            $client->setAccessType('offline');
            // Using "consent" ensures that your application always receives a refresh token.
            // If you are not using offline access, you can omit this.
            $client->setPrompt('consent');
            $client->setApprovalPrompt('force');

            $params = strtr(base64_encode('{ "id" : '.$id.' , "returnToPath" : "'.$returnToPath.'" }'), '+/=', '-_,');
            $client->setState($params);

            $client->setIncludeGrantedScopes(true);   // incremental auth

            $auth_url = $client->createAuthUrl();

            return Redirect::to($auth_url);

        }catch (ModelNotFoundException $exception){
            if  ($returnToPath == 'groups') {
                $returnToPath = '/apps/grupos/all';
            } else {
                $returnToPath = '/apps/eventscalendar';
            }
            return Redirect::to(env('REACT_APP_URL').$returnToPath);
        }
    }

    public function base64UrlEncode($inputStr)
    {
        return strtr(base64_encode($inputStr), '+/=', '-_,');
    }


    public function base64UrlDecode($inputStr)
    {
        return base64_decode(strtr($inputStr, '-_,', '+/='));
    }

    public function callback()
    {
        try{
            $state = request()->get('state');
            $state = base64_decode(strtr($state, '-_,', '+/='));
            $state = json_decode($state, true);

            $returnToPath = '/apps/eventscalendar';
            if  ($state["returnToPath"] == 'groups') {
                $returnToPath = '/apps/grupos/all';
            }

            $id = $state["id"];
            $token = '';

            $client = new Google_Client();
            $client->setAuthConfig(storage_path('/app/google-calendar/service-account-credentials.json'));
            // authenticate code from Google OAuth Flow
            if (isset($_GET['code'])) {
                $token = $client->fetchAccessTokenWithAuthCode($_GET['code']);

                $client->setAccessToken($token['access_token']);

                // get profile info
                $google_oauth = new Google_Service_Oauth2($client);
                $google_account_info = $google_oauth->userinfo->get();
                $email = $google_account_info->email;
            }

            $google_account_email = $email;
            $user_token = GoogleToken::where([['user_id', $id],['gmail', $google_account_email]])->get();

            if($user_token->isEmpty()){
                if (!GoogleToken::where([['gmail', $google_account_email]])->exists()) {
                    $dataToken = ([
                        'user_id' => $id,
                        'token' => json_encode($token['access_token']),
                        'is_active' => true,
                        'refresh_token' => json_encode($token),
                        'gmail' => $google_account_email
                    ]);
                    GoogleToken::create($dataToken);
                } else {
                    return Redirect::to(env('REACT_APP_URL').$returnToPath.'/emailError');
                }
            } else {
                $dataToken = ([
                    'token' => json_encode($token['access_token']),
                    'refresh_token' => json_encode($token),
                    'is_active' => true
                ]);
                GoogleToken::where([['user_id', $id],['gmail', $google_account_email]])->update($dataToken);
            }

            return Redirect::to(env('REACT_APP_URL').$returnToPath);
        }catch (ModelNotFoundException $exception){
            return Redirect::to(env('REACT_APP_URL').$returnToPath);
        }
    }

    public function getToken()
    {
        try{
            $user = Auth::user();
            $user_token = GoogleToken::where([['user_id', $user->id], ['is_active', true]])->first();

            if($user_token && $user_token->is_active){
                $client = new Google_Client();
                $client->setAuthConfig(storage_path('/app/google-calendar/service-account-credentials.json'));
                $client->setAccessType('offline');

                $accessToken = json_decode($user_token->refresh_token, true);
                $client->setAccessToken($accessToken);

                if ($client->isAccessTokenExpired()) {
                    // Refresh the token if possible, else fetch a new one.
                    if ($client->getRefreshToken()) {
                        $client->fetchAccessTokenWithRefreshToken($client->getRefreshToken());
                        $dataToken = ([
                            'token' => json_encode($client->getAccessToken()['access_token']),
                            'refresh_token' => json_encode($client->getAccessToken())
                        ]);
                        GoogleToken::where([['id', $user_token->id]])->update($dataToken);
                        return $this->successResponse('logged');
                    } else {
                        return $this->successResponse('notLogged');
                    }
                } else {
                    return $this->successResponse('logged');
                }
            } else {
                return $this->successResponse('notLogged');
            }

        } catch (ModelNotFoundException $exception) {
            return $this->successResponse('notLogged');
        }
    }

    public function getCalendars()
    {
        try {

            $user = Auth::user();
            $input = request()->all();
            $groupAt = new Request();
            $groupAt->request->add(['group_id' => $input['group_id']]);

            if ($user->role_id !== 3) {
                $user_token = GoogleToken::where([['user_id', $user->id], ['is_active', true]])->first();

                $access_token = json_decode($user_token->token, true);
                //$access_token['refresh_token'] = json_decode($user_token->refresh_token, true);

                $client = new Google_Client();
                $client->setAuthConfig(storage_path('/app/google-calendar/service-account-credentials.json'));
                $client->setAccessToken($access_token);

                // Get user gmail
                $google_oauth = new Google_Service_Oauth2($client);
                $google_account_email = $google_oauth->userinfo->get()->email;

                // Consult the calendars
                $calendars = Calendars::select('calendar.id', 'calendar.calendar_id', 'calendar.gmail', 'custom_subjects.custom_name', 'custom_subjects.custom_color')
                    ->where([['custom_subjects.teacher_id', $user->id], ['custom_subjects.group_id', $input['group_id']], ['calendar.gmail', $google_account_email]])
                    ->join('custom_subjects', 'calendar.subject_id', '=', 'custom_subjects.id')
                    ->get();

                $calendarIds = [];
                foreach ($calendars as $calendar) {
                    array_push($calendarIds, $calendar->calendar_id);
                }

                $service = new Google_Service_Calendar($client);

                // Check deleted calendars
                $params = ([
                    "showDeleted" => true
                ]);
                $calendarList = $service->calendarList->listCalendarList($params);

                foreach ($calendarList as $calendar) {
                    if ($calendar->deleted && in_array($calendar->id, $calendarIds)) {
                        Calendars::where('calendar_id', $calendar->id)->delete();
                        $calendars = $calendars->filter(function ($calendar) {
                            return $calendar->calendar_id != $calendar->id;
                        });
                    }
                }
            } else {
                $calendars = self::getCalendarsAdmin($input['group_id']);
            }

            return $this->successResponse($calendars);

        } catch (ModelNotFoundException $exception) {
            $error["code"] = '422';
            $error["exception"] = "Error al consultar los calendarios";
            $error["message"] = "Error al consultar los calendarios";

            return $this->errorResponse(['error' => $error], 422);
        }
    }

    public function getCalendarsAdmin($groupId)
    {
        try {

            $user = Auth::user();
            $input = request()->all();

            $teacher = Group::where('id',  $groupId)->firstOrFail();

            // Consult the calendars
            $calendars = Calendars::select('calendar.id', 'calendar.calendar_id', 'calendar.gmail', 'custom_subjects.custom_name', 'custom_subjects.custom_color')
                ->where([['custom_subjects.teacher_id', $teacher->teacher_id], ['custom_subjects.group_id', $groupId]])
                ->join('custom_subjects', 'calendar.subject_id', '=', 'custom_subjects.id')
                ->get();

            $calendarIds = [];
            foreach ($calendars as $calendar) {
                array_push($calendarIds, $calendar->calendar_id);
            }

            return $calendars;

        } catch (ModelNotFoundException $exception) {
            $error["code"] = '422';
            $error["exception"] = "Error al consultar los calendarios";
            $error["message"] = "Error al consultar los calendarios";

            return $this->errorResponse(['error' => $error], 422);
        }
    }

    public function store(Request $request)
    {
        try {

            $user = Auth::user();
            $input = $request->all();

            // Consult subject info
            $subject = CustomSubject::findOrFail($input['subject_id']);

            // Consult the token on the database
            $user_token = GoogleToken::where([['user_id', $user->id], ['is_active', true]])->first();
            $access_token = json_decode($user_token->token, true);
            //$access_token['refresh_token'] = json_decode($user_token->refresh_token, true);

            // Google client instance and authenticate
            $client = new Google_Client();
            $client->setAuthConfig(storage_path('/app/google-calendar/service-account-credentials.json'));
            $client->setAccessToken($access_token);

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
            $google_oauth = new Google_Service_Oauth2($client);
            $google_account_email = $google_oauth->userinfo->get()->email;

            // Store calendar id in the DB
            $data = ([
                'calendar_id' => $calendarList->getId(),
                'subject_id' => $input['subject_id'],
                'gmail' => $google_account_email
            ]);

            $new_calendar = Calendars::create($data);

            return $this->successResponse($new_calendar);

        } catch (ModelNotFoundException $exception) {
            $error["code"] = '422';
            $error["exception"] = "Error al consultar los calendarios";
            $error["message"] = "Error al agregar el calendario";

            return $this->errorResponse(['error' => $error], 422);
        }
    }

    public function getSubjects($id) {
        try {
            $user = Auth::user();
            $subjects = new \stdClass();
            if($user->role_id !== 3) {
                $subjects->calendars = Calendars::select('custom_subjects.*')
                    ->where([['custom_subjects.teacher_id', '=', $user->id], ['custom_subjects.group_id', '=', $id]])
                    ->join('custom_subjects', 'calendar.subject_id', '=', 'custom_subjects.id')
                    ->get();

                $subjects->nonCalendars = Calendars::select('custom_subjects.*', 'calendar.calendar_id')
                    ->where([['custom_subjects.teacher_id', '=', $user->id], ['custom_subjects.group_id', '=', $id], ['calendar.calendar_id', '=', null]])
                    ->rightjoin('custom_subjects', 'calendar.subject_id', '=', 'custom_subjects.id')
                    ->get();
            }else{
                $subjects = self::getSubjectsAdminT($id);
            }

            return $this->successResponse($subjects, 'Lista de materias por grupo', 200);
        } catch (ModelNotFoundException $e) {
            return $this->errorResponse('No ha elementos que coincidan', 422);
        }
    }

    public function getSubjectsAdminT($groupId)
    {
        try {
            $subjects = new \stdClass();

            $teacher = Group::where('id',  $groupId)->firstOrFail();

            $subjects->calendars = Calendars::select('custom_subjects.*')
                ->where([['custom_subjects.teacher_id', '=', $teacher->teacher_id], ['custom_subjects.group_id', '=', $groupId]])
                ->join('custom_subjects', 'calendar.subject_id', '=', 'custom_subjects.id')
                ->get();

            $subjects->nonCalendars = Calendars::select('custom_subjects.*', 'calendar.calendar_id')
                ->where([['custom_subjects.teacher_id', '=', $teacher->teacher_id], ['custom_subjects.group_id', '=', $groupId], ['calendar.calendar_id', '=', null]])
                ->rightjoin('custom_subjects', 'calendar.subject_id', '=', 'custom_subjects.id')
                ->get();

            return $subjects;
        } catch (ModelNotFoundException $e) {
            return $this->errorResponse('No ha elementos que coincidan', 422);
        }
    }

    public function isIframeDisabled($src)
    {
        $response = 0;
        try {
            $headers = get_headers($src, 1);
            $headers = array_change_key_case($headers, CASE_LOWER);
            // Check Content-Security-Policy
            if (isset($headers[strtolower('Content-Security-Policy')]) || isset($headers[strtolower('referrer-policy]')])) {
                $response = 1;
            }
            // Check X-Frame-Options
            if (array_key_exists(strtolower('X-Frame-Options'), $headers)) {
                $response = 1;
            }
            return $response;
        } catch (Exception $ex) {
            // Ignore error
            $response = 1;
        }
        return $response;
    }

    public function verifyURL(Request $request)
    {
        $response = 0;
        $input = $request->all();
        try {
            $headers = get_headers($input['src'], 1);
            $headers = array_change_key_case($headers, CASE_LOWER);
            // Check Content-Security-Policy
            if (isset($headers[strtolower('Content-Security-Policy')]) || isset($headers[strtolower('referrer-policy]')])) {
                $response = 1;
            }
            // Check X-Frame-Options
            if (array_key_exists(strtolower('X-Frame-Options'), $headers)) {
                $response = 1;
            }

            return $this->successResponse($response);
        } catch (Exception $ex) {
            $response = 1;
            return $this->errorResponse('Error al obtener información del sitio', 422);
        }
    }

    public function deleteEvent(Request $request)
    {
        try {

            $user = Auth::user();
            $input = $request->all();

            // Consult the token on the database
            $user_token = GoogleToken::where([['user_id', $user->id], ['is_active', true]])->first();
            $access_token = json_decode($user_token->token, true);

            $client = new Google_Client();
            $client->setAuthConfig(storage_path('/app/google-calendar/service-account-credentials.json'));
            $client->setAccessToken($access_token);

            $service = new Google_Service_Calendar($client);
            $event = $service->events->delete($input['calendar_id'], $input['event_id']);

            return $this->successResponse($event, 'Evento eliminado');

        } catch (Exception $exception) {
            $error = "Error al eliminar el evento";
            return $this->errorResponse(['error' => $error], 422);
        }
    }

    public function updateEvent(Request $request)
    {
        try {
            $user = Auth::user();
            $input = $request->all();

            // Consult the token on the database
            $user_token = GoogleToken::where([['user_id', $user->id], ['is_active', true]])->first();
            $access_token = json_decode($user_token->token, true);

            $calendarDB = Calendars::select('custom_subjects.custom_name', 'calendar.calendar_id', 'classes.meeting_id')
                ->where('calendar.subject_id', $input['subject_id'])
                ->join('custom_subjects', 'calendar.subject_id', '=', 'custom_subjects.id')
                ->join('classes', 'custom_subjects.group_id', '=', 'classes.group_id')
                ->first();

            $resultRequest = $input['resources'];
            $array = '<div id="resourcesLinks">';
            $i = 0;
            $arrObj = '';

            if (count($resultRequest) > 0) {
                foreach ($resultRequest as $i => $value) {
                    $arrObj = $arrObj . '{"url":"' . $value['url_resource'] . '","name":"' . $value['name'] . '","allowFrame":' . self::isIframeDisabled($value['url_resource']) . '},';
                    if (self::isIframeDisabled($value['url_resource']) == 0) {
                        $array = $array . '<div><a href="' . $value['url_resource'] . '">' . $value['name'] . '</a></div>';
                    } else {
                        $array = $array . '<div><a href="' . $value['url_resource'] . '" target="_blank">' . $value['name'] . '</a></div>';
                    }
                }
            }
            $arrObjHelper = '[' . rtrim($arrObj, ',') . ']';
            $array = $array . '</div>';

            $client = new Google_Client();
            $client->setAuthConfig(storage_path('/app/google-calendar/service-account-credentials.json'));
            $client->setAccessToken($access_token);

            $service = new Google_Service_Calendar($client);

            //Update description of event after event created
            if ($input['calendar_id'] != $calendarDB->calendar_id){
                // Create Event
                $newEvent = new Google_Service_Calendar_Event(array(
                    'summary' => $calendarDB->custom_name,
                    'description' => $input['description'] . '*' . $array,
                    'start' => array(
                        'dateTime' => $input['start'],
                        'timeZone' => 'America/Mexico_City',
                    ),
                    'end' => array(
                        'dateTime' => $input['end'],
                        'timeZone' => 'America/Mexico_City',
                    ),
                ));

                $newEvent = $service->events->insert($calendarDB->calendar_id, $newEvent);

                //Update description of event after event created
                $eventHelper = $service->events->get($calendarDB->calendar_id, $newEvent->id);
                $descriptionHelper = '<div>' . $input['description'] . '<br><br><a href="' . env('REACT_APP_URL') . '/apps/aula/' . $calendarDB->meeting_id . '/' . $calendarDB->calendar_id . '/' . $eventHelper->getId() . '">Ir a la clase</a>';
                $descriptionHelper .= '<br><a href="' . env('REACT_APP_URL') . '/apps/eventscalendaredit/' . $calendarDB->calendar_id . '/' . $eventHelper->getId() . '">Ver</a><div>';
                $eventHelper->setDescription($descriptionHelper . '*' . $array . '*' . $arrObjHelper);
                $updatedEvent = $service->events->update($calendarDB->calendar_id, $newEvent->id, $eventHelper);
                $eventDelete = $service->events->delete($input['calendar_id'], $input['event_id']);

                return $this->successResponse($updatedEvent);
            } else {
                $eventHelper = $service->events->get($input['calendar_id'], $input['event_id']);
                $descriptionHelper = '<div>' . $input['description'] . '<br><br><a href="' . env('REACT_APP_URL') . '/apps/aula/' . $calendarDB->meeting_id . '/' . $calendarDB->calendar_id . '/' . $eventHelper->getId() . '">Ir a la clase</a>';
                $descriptionHelper .= '<br><a href="' . env('REACT_APP_URL') . '/apps/eventscalendaredit/' . $calendarDB->calendar_id . '/' . $eventHelper->getId() . '">Ver</a><div>';
                $eventHelper->setDescription($descriptionHelper . '*' . $array . '*' . $arrObjHelper);
                $eventHelper->setSummary($calendarDB->custom_name);
                $start = new \Google_Service_Calendar_EventDateTime();
                $start->setDateTime($input['start']);
                $eventHelper->setStart($start);
                $end = new \Google_Service_Calendar_EventDateTime();
                $end->setDateTime($input['end']);
                $eventHelper->setEnd($end);
                $updatedEvent = $service->events->update($calendarDB->calendar_id, $eventHelper->getId(), $eventHelper);

                return $this->successResponse($updatedEvent);
            }
        } catch (ModelNotFoundException $exception) {
            $error = "Error al editar el evento";
            return $this->errorResponse(['error' => $error], 422);
        }
    }

    public function getEvent(Request $request)
    {
        try {
            $user = Auth::user();
            $input = $request->all();

            // Consult the token on the database
            $user_token = GoogleToken::where([['user_id', $user->id], ['is_active', true]])->first();
            $access_token = json_decode($user_token->token, true);

            $client = new Google_Client();
            $client->setAuthConfig(storage_path('/app/google-calendar/service-account-credentials.json'));
            $client->setAccessToken($access_token);

            $service = new Google_Service_Calendar($client);
            $event = $service->events->get($input['calendar_id'], $input['event_id']);

            $getIdGroupSubject = \DB::table('calendar')
                ->select('calendar.subject_id', 'groups.id')
                ->join('custom_subjects','calendar.subject_id','custom_subjects.id')
                ->join('groups','custom_subjects.group_id','groups.id')
                ->where('calendar.calendar_id', $input['calendar_id'])
                ->first();

            $response = ([
                'id' => $getIdGroupSubject->subject_id,
                'group_id' => $getIdGroupSubject->id,
                'description' => $event->description,
                'start' => $event->start->dateTime,
                'end' => $event->end->dateTime,
            ]);
            return $this->successResponse($response, 200);

        } catch (ModelNotFoundException $exception) {
            $error = "Error al eliminar el evento";
            return $this->errorResponse(['error' => $error], 422);
        }
    }

    public function createEvents(Request $request)
    {
        try {

            $user = Auth::user();
            $input = $request->all();

            $calendarDB = Calendars::select('custom_subjects.custom_name', 'calendar.calendar_id', 'classes.meeting_id')
                ->where('calendar.subject_id', $input['subject_id'])
                ->join('custom_subjects', 'calendar.subject_id', '=', 'custom_subjects.id')
                ->join('classes', 'custom_subjects.group_id', '=', 'classes.group_id')
                ->first();

            // Consult the token on the database
            $user_token = GoogleToken::where([['user_id', $user->id], ['is_active', true]])->first();
            $access_token = json_decode($user_token->token, true);
            // Google client instance and authenticate
            $client = new Google_Client();
            $client->setAuthConfig(storage_path('/app/google-calendar/service-account-credentials.json'));
            $client->setAccessToken($access_token);

            // Calendar service instance
            $service = new Google_Service_Calendar($client);
            $description = '<div>' . $input['description'] . '<br><br><a href="' . env('REACT_APP_URL') . '/apps/aula/' . $calendarDB->meeting_id . '/' . $calendarDB->calendar_id . '/' . $calendarDB->custom_name . '">Ir a la clase</a><div>';

            $resultRequest = $input['resources'];
            $array = '<div id="resourcesLinks">';
            $i = 0;
            $arrObj = '';

            if (count($resultRequest) > 0) {
                foreach ($resultRequest as $i => $value) {
                    $arrObj = $arrObj . '{"url":"' . $value['url_resource'] . '","name":"' . $value['name'] . '","allowFrame":' . self::isIframeDisabled($value['url_resource']) . '},';
                    if (self::isIframeDisabled($value['url_resource']) == 0) {
                        $array = $array . '<div><a href="' . $value['url_resource'] . '">' . $value['name'] . '</a></div>';
                    } else {
                        $array = $array . '<div><a href="' . $value['url_resource'] . '" target="_blank">' . $value['name'] . '</a></div>';
                    }
                }
            }
            $arrObjHelper = '[' . rtrim($arrObj, ',') . ']';
            $array = $array . '</div>';

            // Create Event
            $event = new Google_Service_Calendar_Event(array(
                'summary' => $calendarDB->custom_name,
                'description' => $description . '*' . $array,
                'start' => array(
                    'dateTime' => $input['start'],
                    'timeZone' => 'America/Mexico_City',
                ),
                'end' => array(
                    'dateTime' => $input['end'],
                    'timeZone' => 'America/Mexico_City',
                ),
            ));

            $event = $service->events->insert($calendarDB->calendar_id, $event);

            //Update description of event after event created
            $eventHelper = $service->events->get($calendarDB->calendar_id, $event->id);
            $descriptionHelper = '<div>' . $input['description'] . '<br><br><a href="' . env('REACT_APP_URL') . '/apps/aula/' . $calendarDB->meeting_id . '/' . $calendarDB->calendar_id . '/' . $eventHelper->getId() . '">Ir a la clase</a>';
            $descriptionHelper .= '<br><a href="' . env('REACT_APP_URL') . '/apps/eventscalendaredit/' . $calendarDB->calendar_id . '/' . $eventHelper->getId() . '">Ver</a><div>';
            $eventHelper->setDescription($descriptionHelper . '*' . $array . '*' . $arrObjHelper);
            $updatedEvent = $service->events->update($calendarDB->calendar_id, $event->id, $eventHelper);

            return $this->successResponse($event);

        } catch (ModelNotFoundException $exception) {
            $error["code"] = '422';
            $error["exception"] = "Error al crear el evento";
            $error["message"] = "Error al crear el evento";

            return $this->errorResponse(['error' => $error], 422);
        }
    }

    public function example(Request $request)
    {
        try {
            $user = Auth::user();
            $input = $request->all();
            $sql = CustomSubject::select('custom_name')
                ->join('subjects', 'subjects.id', '=', 'subject_id')
                ->join('groups', 'groups.id', '=', 'group_id')
                ->get();

            return $this->successResponse($sql);
        } catch (ModelNotFoundException $exception) {
            $error["code"] = '422';
            $error["exception"] = "Error al crear el evento";
            $error["message"] = "Error al crear el evento";

            return $this->errorResponse(['error' => $error], 422);
        }

    }

    public function getStudentCalendars(Request $request)
    {
        try {

            $user = Auth::user();
            $group_id = $request['group_id'];

            if ($group_id == '0') {
                return $this->successResponse([], 'Lista calendarios', 200);
            } else if (strcmp($group_id, "all") == 0) {
                // Consult the calendars
                $calendars = Calendars::select('calendar.id', 'calendar.calendar_id', 'calendar.gmail', \DB::raw('CONCAT(custom_subjects.custom_name, " ", groups.name) as custom_name'), 'custom_subjects.custom_color')
                    ->join('custom_subjects', 'calendar.subject_id', '=', 'custom_subjects.id')
                    ->join('groups', 'custom_subjects.group_id', '=', 'groups.id')
                    ->join('group_user_enrollments', 'group_user_enrollments.group_id','=','groups.id')
                    ->where([['group_user_enrollments.user_id', $user->id]])
                    ->get();

                $calendarIds = [];
                foreach ($calendars as $calendar) {
                    array_push($calendarIds, $calendar->calendar_id);
                }

                return $this->successResponse($calendars);
            } else {
                // Consult the calendars
                $teacher = Group::where('id', $group_id)->firstOrFail();
                $calendars = Calendars::select('calendar.id', 'calendar.calendar_id', 'calendar.gmail', 'custom_subjects.custom_name', 'custom_subjects.custom_color')
                    ->where([['custom_subjects.teacher_id', $teacher->teacher_id], ['custom_subjects.group_id', $group_id]])
                    ->join('custom_subjects', 'calendar.subject_id', '=', 'custom_subjects.id')
                    ->get();

                $calendarIds = [];
                foreach ($calendars as $calendar) {
                    array_push($calendarIds, $calendar->calendar_id);
                }

                return $this->successResponse($calendars);
            }

        } catch (ModelNotFoundException $exception) {
            $error["code"] = '422';
            $error["exception"] = "Error al consultar los calendarios";
            $error["message"] = "Error al consultar los calendarios";

            return $this->errorResponse(['error' => $error], 422);
        }
    }

    public function getSubjectCalendar(Request $request)
    {
        try {

            $user = Auth::user();
            $group_id = $request['group_id'];
            if ($group_id == '0') {
                return $this->successResponse([], 'Lista calendarios', 200);
            } else if (strcmp($group_id, "all") == 0) {

                $calendars = Calendars::select('calendar.id', 'calendar.calendar_id', 'clubs.id', 'clubs.club_name', 'clubs.base_color', \DB::raw('CONCAT(custom_subjects.custom_name, " ", groups.name) as custom_name'), 'custom_subjects.subject_id', 'custom_subjects.custom_color', 'custom_subjects.group_id')
                    ->join('custom_subjects', 'calendar.subject_id', '=', 'custom_subjects.id')
                    ->join('subjects', 'custom_subjects.subject_id', 'subjects.id')
                    ->join('clubs', 'subjects.club_id', 'clubs.id')
                    ->join('groups', 'custom_subjects.group_id', '=', 'groups.id')
                    ->join('group_user_enrollments', 'group_user_enrollments.group_id','=','groups.id')
                    ->where([['group_user_enrollments.user_id', $user->id]])
                    ->get();

                $grouped = $calendars->groupBy('club_name');
                return $this->successResponse($grouped, 'Lista calendarios', 200);
            } else {
                $teacher = Group::where('id', $group_id)->firstOrFail();

                $calendars = Calendars::select('calendar.id', 'calendar.calendar_id', 'clubs.id', 'clubs.club_name', 'clubs.base_color', 'custom_subjects.custom_name', 'custom_subjects.subject_id', 'custom_subjects.custom_color', 'custom_subjects.group_id')
                    ->where([['custom_subjects.teacher_id', '=', $teacher->teacher_id], ['custom_subjects.group_id', '=', $group_id]])
                    ->join('custom_subjects', 'calendar.subject_id', '=', 'custom_subjects.id')
                    ->join('subjects', 'custom_subjects.subject_id', 'subjects.id')
                    ->join('clubs', 'subjects.club_id', 'clubs.id')
                    ->get();

                $grouped = $calendars->groupBy('club_name');
                return $this->successResponse($grouped, 'Lista calendarios', 200);
            }

        } catch (ModelNotFoundException $e) {
            return $this->errorResponse('No ha elementos que coincidan', 422);
        }
    }
}
