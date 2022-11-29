<?php

namespace App\Http\Controllers;

use App\Jobs\SendEmail;
use App\UserCommunity;
use App\UserLIA;
use App\Order;
use App\UserThinkific;
use App\MercadoPago as AppMercadoPago;
use App\PhpFox_activitypoint_statistics;
use App\PhpFox_user_activity;
use App\PhpFox_user_count;
use App\PhpFox_user_field;
use App\PhpFox_user_space;
use App\Contact;
use Illuminate\Support\Str;
use Carbon\Carbon;
use DateTime;
use Exception;
use DateTimeZone;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Config;
use App\User;
use Illuminate\Http\Request;
use \Illuminate\Support\Facades\Validator;
use App\Mail\RegisterSchool;
use Illuminate\Support\Facades\Http;
use App\Mail\SendInfoToSchool;
use App\Mail\SendInvoice;
use App\Mail\TeacherEmail;
use App\Mail\UserEmail;
use App\Appointments;
use App\GlobalSubscription;
use Illuminate\Support\Facades\Mail;
use MercadoPago;
use Illuminate\Support\Facades\Redirect;
use App\Mail\RegisterMember;
use App\Mail\SupportEmail;
use App\Files;
use App\Donors;
use App\Users;
use App\teacherValidations;
use App\Activity;
use App\TableOfAppointments;
use Illuminate\Support\Facades\Storage;
use App\Mail\GlobalSchoolingAccessEmail;

class ProfileController extends ApiController
{
    protected $token;

    public function __construct()
    {
        $this->token = Config::get('app.mercadopago_token');
    }

    /**
     * Store a newly created resource in storage.
     *
     * @param  Request  $request
     * @return JsonResponse
     */

    public function getProfileInfo(Request $request){

        try{

            $input = $request->all();
            $user = Auth::user();
            //$activity = Activity::find($id);

            //$path = storage_path() . '/app/public/teacher/6518/1651010318_pik.jpg';

            //$activity = Activity::find('6517');
            $user_id = array_key_exists('user_id', $input) ? $input['user_id'] : $user->id;

            $profile = User::select('users.uuid', 'users.name', 'users.email', 'users.last_name', 'users.username', 'users.level_id', 'teacher_validations.grade', 'contacts.phone_number', 'contacts.country', 'contacts.state', 'contacts.city', 'teacher_validations.school_name',
             'teacher_validations.intereses', 'teacher_validations.document_type', 'users.created_at', 'teacher_validations.level_school', 'teacher_validations.membership', 'teacher_validations.status', 'teacher_validations.comments')
            ->where('users.id', $user_id)
            ->leftJoin('contacts', 'contacts.user_id', '=', 'users.uuid')
            ->leftJoin('teacher_validations', 'teacher_validations.uuid', '=', 'users.id')
            ->get();

            /* if(!Str::contains($activity->file_path,'.') && $activity->file_path != null){
                $files = Storage::disk('local')->allFiles('public/'.$activity->file_path);
                $activity->file_path = $files;
            }*/

            $files = Storage::disk('local')->allFiles('public/teacher/'.$user_id);

            $array= [];
            foreach($files as $file){

                $lastmodified = Storage::lastModified($file);
                $lastmodified = DateTime::createFromFormat("U", $lastmodified);
                $lastmodified = $lastmodified->format('Y-m-d');
                
                $data = [
                        'name' => $file,
                        'date' => $lastmodified,
                    ];
                array_push($array,$data );
            }

            $profile[0]->files = $array;

            if (json_decode($profile[0]->document_type, true)){
                $profile[0]->document_type = json_decode($profile[0]->document_type, true);
            }
            if (json_decode($profile[0]->grade, true)){
                $profile[0]->grade = json_decode($profile[0]->grade, true);
            }

            /* 
            $allFile = array();
            foreach($files as $file){

                $lastmodified = Storage::lastModified($file);
                $lastmodified = DateTime::createFromFormat("U", $lastmodified);
                $lastmodified = $lastmodified->format('Y-m-d');

                //$allFile[] = $lastmodified -> $lastmodified;
                array_push($allFile, $lastmodified);
            }

            $merged = collect($files)->zip($allFile)->transform(function ($values) {
                return [
                    'name' => $values[0],
                    'date' => $values[1],
                ];
            }); */

            $profile = $profile[0];
            return $this->successResponse($profile, 'Información del perfil',200);

        }catch(Exception $e){
            return $this->errorResponse('Ha ocurrido un error!', 422);
        }
    }

    public function updateProfile(Request $request){

        try{

            $input = $request->all();
            $user = Auth::user();

            $users = User::findOrFail($user->id);
            $contacts = Contact::where('user_id', $users->uuid)->first();
            $teacherValidation = teacherValidations::where('uuid', $users->id)->first();

            if ($input['email'] != $user->email  && User::where([['email', '=', $input['email']]])->exists()) {
                return $this->errorResponse('El correo ya existe.', 422);
            }
            /* if ($input['username'] != $user->username && User::where([['username', '=', $input['username']]])->exists()) {
                return $this->errorResponse('El username ya existe.', 422);
            } */

            if (!$teacherValidation) {
                $dataTeacherValidation = ([
                    'membership' => 'Maestro invitado',
                    'uuid' => $users->id,
                    'status' => 'no verificado',
                ]);
                $teacherValidation = teacherValidations::updateOrCreate($dataTeacherValidation);
            } 

            $dataUser = ([
                
                'name' => array_key_exists('name', $input) && $input['name'] ? $input['name'] : $users->name, 
                'last_name' => array_key_exists('last_name', $input) && $input['last_name'] ? $input['last_name'] : $users->last_name, 
                'level_id' => array_key_exists('level_id', $input) && $input['level_id'] ? $input['level_id'] : $users->level_id, 
                'email' => array_key_exists('email', $input) && $input['email'] ? $input['email'] : $users->email, 
                // 'username' => array_key_exists('username', $input) && $input['username'] ? $input['username'] : $users->username, 
            ]);

            $dataContacts = ([
                'user_id' => $users->uuid,
                'country' => array_key_exists('country', $input) && $input['country'] ? $input['country'] : $contacts->country,
                'state' => array_key_exists('state', $input) && $input['state'] ? $input['state'] : $contacts->state,
                'city' => array_key_exists('city', $input) && $input['city'] ? $input['city'] : $contacts->city,
                'phone_number' => array_key_exists('phone_number', $input) && $input['phone_number'] ? $input['phone_number'] : $contacts->phone_number,
            ]); 

            $dataTeacherValidation = ([
                'school_name' => array_key_exists('school_name', $input) && $input['school_name'] ? $input['school_name'] : $teacherValidation->school_name,
                'intereses' => array_key_exists('intereses', $input) && $input['intereses'] ? $input['intereses'] : $teacherValidation->intereses,
                'document_type'=> array_key_exists('document_type', $input) && $input['document_type'] ? $input['document_type'] : $teacherValidation->document_type,
                'level_school' => array_key_exists('level_school', $input) && $input['level_school'] ? $input['level_school'] : $teacherValidation->level_school,
                'grade' => array_key_exists('grade', $input) && $input['grade'] ? $input['grade'] : $teacherValidation->grade, 
            ]);

            $usersUpdt = $users->update($dataUser);
            $teacherValidationUpdt = $teacherValidation->update($dataTeacherValidation);
            if (!$contacts) {
                $contactsUpdt = Contact::updateOrCreate($dataContacts);
            } else {
                $contactsUpdt = $contacts->update($dataContacts);
            }

            if (array_key_exists('toDeleteFiles', $input) && $input['toDeleteFiles'] !== null){
                foreach($input['toDeleteFiles'] as $fileToRemove){
                    $path = storage_path() . '/app/public/teacher/' . $user->id . '/' .$fileToRemove;
                    if (file_exists($path)) {
                        unlink($path);
                    }
                }
            }

            if($request->file()) {
                foreach($request->file('files') as $fileReq){
                    $fileName = strtr($fileReq->getClientOriginalName(), " ", "_");
                    $fileStore = time().'_'.$fileName;

                    $filePath = $fileReq->storeAs('teacher/'.$user->id, $fileStore, 'public');
                }
            }

            $profileUpdated = User::select('users.uuid', 'users.name', 'users.email', 'users.username', 'users.grade', 'contacts.phone_number', 'contacts.country', 'contacts.state', 'contacts.city', 'teacher_validations.school_name',
             'teacher_validations.intereses', 'teacher_validations.document_type', 'teacher_validations.level_school', 'teacher_validations.membership', 'teacher_validations.status')
            ->where('users.id', $user->id)
            ->join('contacts', 'contacts.user_id', '=', 'users.uuid')
            ->join('teacher_validations', 'teacher_validations.uuid', '=', 'users.id')
            ->get();

            return $this->successResponse($profileUpdated,'El perfil se ha actualizado', 200);

        }catch(Exception $e){
            return $this->errorResponse($e->getMessage().' at '.$e->getLine(), 422);
            // return $this->errorResponse('Ha ocurrido un error!', 422);
        }

    }

    public function statusProfile (Request $request){

        try{

            $input = $request->all();
            $users = User::findOrFail($input['id']);
            $teacherValidation = teacherValidations::where('uuid', $input['id'])->first();

            $dataTeacherValidation = ([
                'status' => array_key_exists('status', $input) && $input['status'] ? $input['status'] : $teacherValidation->status,
                'comments' => array_key_exists('comments', $input) && $input['comments'] ? $input['comments'] : ''
            ]);

            if (!$teacherValidation) {
                $dataTeacherValidation['membership'] = 'Maestro invitado';
                $dataTeacherValidation['uuid'] = $users->id;
                $teacherValidation = teacherValidations::updateOrCreate($dataTeacherValidation);
                $success['validation'] = $teacherValidation;
            }

            if(array_key_exists('status', $input) && $input['status'] == 'aceptado' && $teacherValidation->membership != 'Maestro invitado'){

                if($teacherValidation->membership == 'Maestro Certificado'){
                    $dataUser =([
                        'role_id' => '29',
                    ]);
                }
                if($teacherValidation->membership == 'Maestro Acreditado'){
                    $dataUser =([
                        'role_id' => '30',
                    ]);
                }   

                $usersUpdt = $users->update($dataUser);
                $updateTeacher = self::updateTeacher($input['id'], $dataUser['role_id']);
                $success['usersUpdt'] = $usersUpdt;
                $success['updateTeacher'] = $updateTeacher;
            } else {
                $dataUser =([
                    'role_id' => '28',
                ]); 

                $usersUpdt = $users->update($dataUser);
                $success['usersUpdt'] = $usersUpdt;
            }
            
            $teacherValidationUpdt = $teacherValidation->update($dataTeacherValidation);
            $success['teacherValidationUpdt'] = $teacherValidationUpdt;
            return $this->successResponse($success, 'El estatus se ha actualizado', 200);

        } catch(Exception $e) {
            return $this->errorResponse('Ha ocurrido un error!', 422);
        }

    }

    public function updateTeacher($user_id, $role_id) {
        try{
            $userAcademy = User::find($user_id);
            $academyUser = new UserThinkific();

            // Compra de cursos de membresias
            $roleFox = [
                '28' => '30', // Maestro-I - Maestro Invitado
                '29' => '31', // Maestro-M - Maestro Mensual
                '30' => '32', // Maestro-A - Maestro Anual
            ];

            $assignRolesByMembership = [
                '4' => 28, //Maestro-I
                '5' => 29, //Maestro-M
                '6' => 30, //Maestro-A
            ];

            $academyT = '';
            $academyCourseT = '';
            if($role_id == 29){
                if($userAcademy['level_id'] == 1){
                    $academyT = $academyUser->groupAssign($userAcademy->active_thinkific, 'Maestro-MPrees');
                    $academyCourseT = $academyUser->enrollmentStudent($userAcademy->active_thinkific, 62710, "2040-01-01T01:01:00Z");
                }elseif ($userAcademy['level_id'] == 2){
                    $academyT = $academyUser->groupAssign($userAcademy->active_thinkific, 'Maestro-MPrim');
                    $academyCourseT = $academyUser->enrollmentStudent($userAcademy->active_thinkific, 62234, "2040-01-01T01:01:00Z");
                }else{
                    $academyT = $academyUser->groupAssign($userAcademy->active_thinkific, 'Maestro-MSec');
                    $academyCourseT = $academyUser->enrollmentStudent($userAcademy->active_thinkific, 62711, "2040-01-01T01:01:00Z");
                }
            }elseif ($role_id == 30){
                if($userAcademy['level_id'] == 1){
                    $academyT = $academyUser->groupAssign($userAcademy->active_thinkific, 'Maestro-APrees');
                    $academyCourseT = $academyUser->enrollmentStudent($userAcademy->active_thinkific, 62712, "2040-01-01T01:01:00Z");
                }elseif ($userAcademy['level_id'] == 2){
                    $academyT = $academyUser->groupAssign($userAcademy->active_thinkific, 'Maestro-APrim');
                    $academyCourseT = $academyUser->enrollmentStudent($userAcademy->active_thinkific, 62235, "2040-01-01T01:01:00Z");
                }else{
                    $academyT = $academyUser->groupAssign($userAcademy->active_thinkific, 'Maestro-ASec');
                    $academyCourseT = $academyUser->enrollmentStudent($userAcademy->active_thinkific, 62722, "2040-01-01T01:01:00Z");
                }
            }
            $success['academyCourseT']= $academyCourseT;
            $success['academyT']= $academyT;

            $user = User::find($user_id);
            $success['lia']= $user;

            //community
            if($role_id === 29){ //Mensual ?
                if($user['level_id'] == 1){
                    $roleCommunity = 46; //Maestro Mensual Preescolar
                }elseif($user['level_id'] == 2){
                    $roleCommunity = 31; //Maestro Mensual Primaria
                }else{
                    $roleCommunity = 49; //Maestro Mensual Secundaria
                }
            }elseif($role_id === 30){ //Anual ?
                if($user['level_id'] == 1){
                    $roleCommunity = 47; //Maestro Anual Preescolar
                }elseif($user['level_id'] == 2){
                    $roleCommunity = 32; //Maestro Anual Primaria
                }else{
                    $roleCommunity = 50; //Maestro Anual Secundaria
                }
            }else{
                $roleCommunity = $roleFox[$user['role_id']];
            }

            $userCommunityUpdate['user_group_id'] = $roleCommunity;
            UserCommunity::where('user_id',$user->active_phpfox)->firstOrFail()->update($userCommunityUpdate);
            $userCommunity = UserCommunity::find($user->active_phpfox);

            $success['userCommunity']= $userCommunity;

            return $success;

        } catch (Exception $e) {
            $errors["code"] = 'INVALID_DATA';
            $errors["message"] = $e->getMessage();
            $errors["username"] = "Error al actualizar los datos.";
            $error["errors"] =[$errors];

            return $error;
        }
    }

    public function getProfileInfoParent($id) {
        try{
            $profile = TableOfAppointments::select('table_of_appointments.*', 'users.username')
            ->where('table_of_appointments.id', $id)
            ->leftJoin('users', 'users.id', '=', 'table_of_appointments.user_id')
            ->first();

            if (json_decode($profile->grades, true))
                $profile->grades = json_decode($profile->grades, true);
            if (json_decode($profile->education_program, true))
                $profile->education_program = json_decode($profile->education_program, true);

            return $this->successResponse($profile, 'Información del perfil',200);

        }catch(Exception $e){
            return $this->errorResponse('Ha ocurrido un error!', 422);
        }
    }
    
    public function statusParentUpdate(Request $request) {
        try{
            $input = $request->all();

            $statusData = ([
                'status' => $input['status'],
                'comments' => $input['comments'],
            ]);

            $updateStatus = TableOfAppointments::where('id',$input['id'])->firstOrFail()->update($statusData);
            return $this->successResponse($input, 'Estatus actualizado',200);

        }catch(Exception $e){
            // return $this->errorResponse($e->getMessage().' at '.$e->getLine(), 422);
            return $this->errorResponse('Ha ocurrido un error!', 422);
        }
    }
    
    public function createParentUser(Request $request) {
        try{

            $validator = $this->validateUser();
            if ($validator->fails()) {
                return $this->errorResponse($validator->messages(), 422);
            }

            $input = $request->all();
            $profileData = TableOfAppointments::where('id',$input['id'])->firstOrFail();

            $email = $profileData->email;
            if (User::where([['email', '=', $email]])->exists()) {
                return $this->errorResponse('El correo '.$email.' ya existe.', 422);
            }
            if (User::where([['username', '=', $input['username']]])->exists()) {
                return $this->errorResponse('El nombre de usuario '.$input['username'].' ya existe.', 422);
            }

            $roleFox = [
                '10' => '10', //Padre - Papá-EscuelaLIA
                '31' => '33', // Padre-I - Padre Invitado
                '32' => '34', // Padre-M - Padre Mensual
                '33' => '35', // Padre-A - Padre Anual
            ];

            $dataCreate['name'] = $profileData->name;
            $dataCreate['username'] = $input['username'];
            $dataCreate['last_name'] = $profileData->last_name;
            $dataCreate['email'] = $email;
            $dataCreate['role_id'] =  10; //Padre - Papá-EscuelaLIA
            $dataCreate['school_id'] = 326; // Global Schooling
            $dataCreate['phone_number'] = $profileData->phone_number;
            $dataCreate['country'] = 'Mexico';
            $dataCreate['state'] = $profileData->city;
            $dataCreate['city'] = $profileData->city;
            $password  = $input['password'];
            $passwordBcrypt = bcrypt($password);
            $passwordEncode = str_replace("$2y$", "$2a$", $passwordBcrypt);
            $dataCreate['password'] = $passwordEncode;

            $user = User::create($dataCreate);
            $success['lia'] = $user;

            $user_id_profile = ([
                'user_id' => $user->id,
            ]);

            $profileData = $profileData->update($user_id_profile);
            $success['profileData'] = $profileData;

            $contactData = ([
                'user_id' => $user->uuid,
                'phone_number' => $dataCreate['phone_number'],
                'country' => $dataCreate['country'],
                'state' => $dataCreate['state'],
                'city' => $dataCreate['city'],
            ]);
            $createContact = Contact::create($contactData);

            //Data to insert new user in the Academy
            $dataThink = ([
                'first_name' => $user->name,
                'last_name' => $user->last_name,
                'email' => $user->email,
                'password' => $password
            ]);

            $academyUser = new UserThinkific();
            $affected = User::find($user->id);

            $academyExUser = $academyUser->getUserByEmail($email);
            if(array_key_exists(0,$academyExUser["items"])){
                $existingUser = $academyUser->getUserByEmail($email);
                $existingUser = $existingUser["items"];
                $affected->active_thinkific = $existingUser[0]["id"];
                $success['affected'] = $existingUser[0]["id"];
            }else{
                $inputUser = $academyUser->createUser($dataThink);

                if(array_key_exists("errors", $inputUser)){
                    $errors['academia']= (array) ["academy" => $inputUser,"id" => $user->id] ;
                    $success['affected'] = $inputUser;
                }else{
                    $affected->active_thinkific = $inputUser['id'];
                    $success['affected'] = $inputUser['id'];
                }

            }
            $affected->save();

            $roleCommunity = $roleFox[$dataCreate['role_id']];
            if (UserCommunity::where([['email', '=', $user->email]])->exists()) {
                $repeatCommunity = UserCommunity::where([['email', '=', $user->email]])->first()->toArray();
                $user->active_phpfox = $repeatCommunity['user_id'];
                $user->save();
                $success['comunidad'] = ['El correo electronico ya esta asignado', $repeatCommunity, $user];
            }else{
                $dataFox = ([
                    'email' => $user->email,
                    'full_name' => $user->name .' '. $user->last_name,
                    "user_name" => $user->username,
                    'password' => $passwordBcrypt,
                    'user_group_id' => $roleCommunity,
                    'joined' => Carbon::now()->timestamp,
                ]);

                $userCommunity = UserCommunity::create($dataFox)->toArray();
                $userCommunityId = ['user_id' => $userCommunity['user_id']];
                PhpFox_activitypoint_statistics::create($userCommunityId);
                PhpFox_user_activity::create($userCommunityId);
                PhpFox_user_field::create($userCommunityId);
                PhpFox_user_space::create($userCommunityId);
                PhpFox_user_count::create($userCommunityId);

                $user->active_phpfox = $userCommunityId["user_id"];
                $user->save();
                $success['comunidad'] = $userCommunity;
            }

            $dataEmail = ([
                'name' => $user->name,
                'userName' => $user->username,
                'password' => $password,
            ]);
            Mail::send(new GlobalSchoolingAccessEmail($dataEmail));

            return $this->successResponse($success, 'Usuario registrado',200);

        }catch(Exception $e){
            // return $this->errorResponse($e->getMessage().' at '.$e->getLine(), 422);
            return $this->errorResponse('Ha ocurrido un error!', 422);
        }
    }

    public function emailGlobalSchooling (Request $request){
        try{

            $input = $request->all();

            error_log('holi');
                $dataCreate['name'] = $input['name'];
                $dataCreate['userName'] = $input['userName'];
                $dataCreate['password'] = $input['password'];

                $dataEmail = ([
                    'name' => $dataCreate['name'],
                    'userName' => $dataCreate['userName'],
                    'password' => $dataCreate['password'],
                ]);

                error_log(':3');

                Mail::send(new GlobalSchoolingAccessEmail($dataEmail));
                return $this->successResponse($dataEmail, 'Mensaje enviado con exito', 200);

        }catch(Exception $e){

            $errors["code"] = 'INVALID_DATA';
            $errors["message"] = $e->getMessage();
            $errors["username"] = "Error al actualizar el status.";

            $error["errors"] =[$errors];

            return $this->errorResponse(['error' => $error], 422);

        }
    }

    public function validateUser()
    {
        $messages = [
            'username.required' => 'El campo nombre de usuario es requerido',
            'password.required' => 'El campo contraseña es necesario',
        ];
        return Validator::make(request()->all(), [
            'username' => 'required',
            'password' => 'required',
        ], $messages);
    }


    public function getChildInfo(Request $request){

        try{

            $input = $request->all();
            $user = Auth::user();
            //$activity = Activity::find($id);

            //$path = storage_path() . '/app/public/teacher/6518/1651010318_pik.jpg';

            //$activity = Activity::find('6517');
            if($input['user_id'] === 0){
                
                $revision = User::select('user_id', 'status', 'name', 'gender')->where([['tutor_id', '=', $user->id], ['status', '=', 'En revisión']])
                ->leftJoin('global_subscriptions', 'global_subscriptions.user_id', '=', 'users.id')
                ->get();
        
                $proceso = User::select('user_id', 'status', 'name', 'gender')->where([['tutor_id', '=', $user->id], ['status', '=', 'En proceso']])
                ->leftJoin('global_subscriptions', 'global_subscriptions.user_id', '=', 'users.id')
                ->get();
        
                $actualizado = User::select('user_id', 'status', 'name', 'gender')->where([['tutor_id', '=', $user->id], ['status', '=', 'Actualizado']])
                ->leftJoin('global_subscriptions', 'global_subscriptions.user_id', '=', 'users.id')
                ->get();
        
                $aprobado = User::select('user_id', 'status', 'name', 'gender')->where([['tutor_id', '=', $user->id], ['status', '=', 'Aprobado']])
                ->leftJoin('global_subscriptions', 'global_subscriptions.user_id', '=', 'users.id')
                ->get();

                $sql = Appointments::select('city', 'phone_number')->where('user_id', $user->id)->get();
        
                $result = collect([['revision' => $revision], ['en_proceso' => $proceso], ['actualizado' => $actualizado], ['aprobado' => $aprobado], ['info' => $sql]]);

                return $this->successResponse($result, 'Información del perfil',200);   
            }else{
                $user_id = array_key_exists('user_id', $input) ? $input['user_id'] : $user->id;

                $profile = User::select('users.name', 'users.last_name', 'users.email', 'users.username', 'users.level_id', 'users.grade', 'users.username',
                'global_subscriptions.subscription', 'orders.created_at', 'orders.expiry_date')
                ->where('users.id', $user_id)
                ->leftJoin('global_subscriptions', 'global_subscriptions.user_id', '=', 'users.id')
                ->leftJoin('orders', 'orders.affected_users', '=', 'users.id')
                ->get();

                return $this->successResponse($profile, 'Información del perfil',200);
            }




            /* if(!Str::contains($activity->file_path,'.') && $activity->file_path != null){
                $files = Storage::disk('local')->allFiles('public/'.$activity->file_path);
                $activity->file_path = $files;
            }*/

            /* $files = Storage::disk('local')->allFiles('public/teacher/'.$user_id);

            $array= [];
            foreach($files as $file){

                $lastmodified = Storage::lastModified($file);
                $lastmodified = DateTime::createFromFormat("U", $lastmodified);
                $lastmodified = $lastmodified->format('Y-m-d');
                
                $data = [
                        'name' => $file,
                        'date' => $lastmodified,
                    ];
                array_push($array,$data );
            }

            $profile[0]->files = $array;

            if (json_decode($profile[0]->document_type, true)){
                $profile[0]->document_type = json_decode($profile[0]->document_type, true);
            }
            if (json_decode($profile[0]->grade, true)){
                $profile[0]->grade = json_decode($profile[0]->grade, true);
            } */

            /* 
            $allFile = array();
            foreach($files as $file){

                $lastmodified = Storage::lastModified($file);
                $lastmodified = DateTime::createFromFormat("U", $lastmodified);
                $lastmodified = $lastmodified->format('Y-m-d');

                //$allFile[] = $lastmodified -> $lastmodified;
                array_push($allFile, $lastmodified);
            }

            $merged = collect($files)->zip($allFile)->transform(function ($values) {
                return [
                    'name' => $values[0],
                    'date' => $values[1],
                ];
            }); */

            //$profile = $profile[0];            

        }catch(Exception $e){
            return $this->errorResponse($e, 'Ha ocurrido un error!', 422);
        }
    }

    function fileChildUpload(Request $request, $id){
        try {            
            $input = $request->all();
            $res = "";
            $resFile['fileId'] = "";            
            foreach($request->file('files') as $fileReq){
                $fileName = strtr($fileReq->getClientOriginalName(), " ", "_");
                $fileStore = time().'_'.$fileName;
                $dataFile = ([
                    'user_id' => $id,
                    'file_url' => $fileStore
                ]);
                $fileId = Files::create($dataFile);
                $filePath = $fileReq->storeAs('children/'.$id, $fileStore, 'public');
                $resFile = ([ 'filePath'=>'children/'.$id,'fileId'=>$resFile['fileId'] != "" ? $resFile['fileId'].",".$fileId->id : $fileId->id ]);
                $res = $resFile;


                \DB::table('global_child_documents')->insert([
                    'user_id' => $id,
                    'path' => $filePath,
                    'status' => 'Por revisar',
                ]);


            }
            return $res;

        }catch(InvalidOrderException $exception){
            return null;
        }
    }
}