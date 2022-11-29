<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\TeacherValidations;
use Illuminate\Http\JsonResponse;
use Illuminate\Contracts\Filesystem\FileNotFoundException;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\Response;
use Illuminate\Support\Str;
use Illuminate\Support\UploadedFile;
use App\Contact;
use App\User;
use App\Files;
use App\UserThinkific;
use App\UserCommunity;
use Exception;
use Carbon\Carbon;
use App\MercadoPago as AppMercadoPago;
use App\PhpFox_activitypoint_statistics;
use App\PhpFox_user_activity;
use App\PhpFox_user_count;
use App\PhpFox_user_field;
use App\PhpFox_user_space;
use Illuminate\Support\Facades\Config;
use App\Jobs\SendEmail;
use App\UserLIA;
use App\Order;
use DateTime;
use App\Mail\RegisterSchool;
use Illuminate\Support\Facades\Http;
use App\Mail\SendInfoToSchool;
use App\Mail\SendInvoice;
use App\Mail\TeacherEmail;
use Illuminate\Support\Facades\Mail;
use MercadoPago;
use Illuminate\Support\Facades\Redirect;
use App\Mail\RegisterMember;
use App\Mail\SupportEmail;
use App\FirebaseFiles as FirebaseFiles;

class TeacherValidationsController extends ApiController
{
    protected $token;

    public function __construct()
    {
        $this->token = Config::get('app.mercadopago_token');
    }

    public function register(Request $request)
    {

        try {
            $validator = $this->validateUser($request->all());
            if($validator->fails()){
                return $this->errorResponse($validator->messages(), 422);
            }

            $input = $request->all();


            $email = $input['email'];
            $username = $input['username'];

            if (User::where([['email', '=', $email]])->exists()) {
                return $this->errorResponse('El correo ya existe.', 422);
            }
            if (User::where([['username', '=', $username]])->exists()) {
                return $this->errorResponse('El nombre de usuario ya existe.', 422);
            }

            $roleFox = [
                '28' => '30', // Maestro-I - Maestro Invitado
                '29' => '31', // Maestro-M - Maestro Acreditado
                '30' => '32', // Maestro-A - Maestro Certificado
            ];

            if (array_key_exists('level_id', $input) && $input['level_id'] == '4') {
                $input['level_id'] = '2';
            }

            $dataCreate['name'] = $input['name'];
            $dataCreate['username'] = $input['username'];
            $dataCreate['last_name'] = $input['last_name'];
            $dataCreate['email'] = $input['email'];
            $dataCreate['role_id'] =  $input['role_id'];
            $dataCreate['school_id'] = 305; // #QuedateEnCasaconClubLIA
            $dataCreate['grade'] = array_key_exists('grade', $input) ? $input['grade'] : null;
            $dataCreate['phone_number'] = $input['phone_number'];
            $dataCreate['country'] = $input['country'];
            $dataCreate['state'] = $input['state'];
            $dataCreate['city'] = array_key_exists('city', $input) && $input['city'] ? $input['city'] : $input['state'];
            $dataCreate['level_id'] = array_key_exists('level_id', $input) ? $input['level_id'] : 2;
            $password  = $input['password'];
            $passwordBcrypt = bcrypt($password);
            $passwordEncode = str_replace("$2y$", "$2a$", $passwordBcrypt);
            $dataCreate['password'] = $passwordEncode;

            $user = User::create($dataCreate);
            $success['lia'] = $user;
            Mail::send(new TeacherEmail($request->all()));

            $courseType = '';
            if (array_key_exists('course', $input) && $input['course']) {
                $courseType = 'course';
                $order = self::createOrder($user,$input['course']['price'], '4',$input['phone_number'], $courseType, 1, $input['course']['id']);
                $success['order'] = $order->id;
            } else {
                $dataTeacher = ([
                    'status' => 'no verificado',
                    'level_school' => array_key_exists('level_school', $input) && $input['level_school'] ? $input['level_school'] : 'Preescolar',
                    'school_name' => $input['school_name'],
                    'intereses' => $input['intereses'],
                    'membership' => $input['membership'],
                    'document_type' => $input['document_type'],
                    'uuid' => $user->id
                ]);
                $query = TeacherValidations::updateOrCreate($dataTeacher);
                $success['validation'] = $query;
            }

            $contactData = ([
                'user_id' => $user->uuid,
                'phone_number' => $dataCreate['phone_number'],
                'country' => $dataCreate['country'],
                'state' => $dataCreate['state'],
                'city' => $dataCreate['city']
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
            }else{
                $inputUser = $academyUser->createUser($dataThink);
                if(array_key_exists("errors", $inputUser)){
                    $errors['academia']= (array) ["academy" => $inputUser,"id" => $user->id] ;
                }else{
                    $affected->active_thinkific = $inputUser['id'];
                }

            }
            $affected->save();

            if($dataCreate['role_id'] === 28){ //Invitado ?
                if($dataCreate['level_id'] === "1"){
                    $roleCommunity = 45; //Maestro Invitado Preescolar
                }elseif($dataCreate['level_id'] === "2"){
                    $roleCommunity = 30; //Maestro Invitado Primaria
                }else{
                    $roleCommunity = 48; //Maestro Invitado Secundaria
                }
            }elseif($dataCreate['role_id'] === 29){ //Mensual ?
                if($dataCreate['level_id'] === "1"){
                    $roleCommunity = 46; //Maestro Mensual Preescolar
                }elseif($dataCreate['level_id'] === "2"){
                    $roleCommunity = 31; //Maestro Mensual Primaria
                }else{
                    $roleCommunity = 49; //Maestro Mensual Secundaria
                }
            }elseif($dataCreate['role_id'] === 30){ //Anual ?
                if($dataCreate['level_id'] === "1"){
                    $roleCommunity = 47; //Maestro Anual Preescolar
                }elseif($dataCreate['level_id'] === "2"){
                    $roleCommunity = 32; //Maestro Anual Primaria
                }else{
                    $roleCommunity = 50; //Maestro Anual Secundaria
                }
            }else{
                $roleCommunity = $roleFox[$dataCreate['role_id']];
            }


            if (UserCommunity::where([['email', '=', $user->email]])->exists()) {
                $repeatCommunity = UserCommunity::where([['email', '=', $user->email]])->first()->toArray();
                $user->active_phpfox = $repeatCommunity['user_id'];
                $user->save();
                $comunidad['error'] = ['El correo electronico ya esta asignado', $repeatCommunity, $user];
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
            if (!empty($comunidad)){
                $success['comunidad']= $comunidad['error'];
            }

            // enrollment thinkific group
            if($dataCreate['role_id'] === 28) {
                $course = $academyUser->enrollmentStudentInsert( $affected->active_thinkific, '995494'); // 995494 -> prod / 1829129 -> test
                $success['courseEnrollment'] = $course;
            } else {
                if($user->role_id == 29){
                    if($user->level_id == 1){
                        $academyT = $academyUser->groupAssign($affected->active_thinkific, 'Maestro-MPrees');
                        $academyCourseT = $academyUser->enrollmentStudent($affected->active_thinkific, 62710, "2040-01-01T01:01:00Z");
                    }elseif ($user->level_id == 2){
                        $academyT = $academyUser->groupAssign($affected->active_thinkific, 'Maestro-MPrim');
                        $academyCourseT = $academyUser->enrollmentStudent($affected->active_thinkific, 62234, "2040-01-01T01:01:00Z");
                    }else{
                        $academyT = $academyUser->groupAssign($affected->active_thinkific, 'Maestro-MSec');
                        $academyCourseT = $academyUser->enrollmentStudent($affected->active_thinkific, 62711, "2040-01-01T01:01:00Z");
                    }
                }elseif ($user->role_id == 30){
                    if($user->level_id == 1){
                        $academyT = $academyUser->groupAssign($affected->active_thinkific, 'Maestro-APrees');
                        $academyCourseT = $academyUser->enrollmentStudent($affected->active_thinkific, 62712, "2040-01-01T01:01:00Z");
                    }elseif ($user->level_id == 2){
                        $academyT = $academyUser->groupAssign($affected->active_thinkific, 'Maestro-APrim');
                        $academyCourseT = $academyUser->enrollmentStudent($affected->active_thinkific, 62235, "2040-01-01T01:01:00Z");
                    }else{
                        $academyT = $academyUser->groupAssign($affected->active_thinkific, 'Maestro-ASec');
                        $academyCourseT = $academyUser->enrollmentStudent($affected->active_thinkific, 62722, "2040-01-01T01:01:00Z");
                    }
                }
            }

            $success['message'] = 'Usuario creado';

            $appMercadoPago = new AppMercadoPago();
            if ($courseType == '') {
                return $this->successResponse($success, 'Usuario registrado', 200);

            } elseif ($courseType == 'course') { // curso de un solo pago
                $orderData = ([
                    "email" => $input['email'],
                    "name" => $input['name'],
                    "surname" => $input['last_name'],
                    "items" => array([
                        "title" =>  $input['course']['name'],
                        "quantity" => '1',
                        "unit_price" => $input['course']['price'],
                    ]),
                ]);
                $mercadoPago = $appMercadoPago->processOrderProducts($orderData);
                if (array_key_exists('id', $mercadoPago)) {
                    $order->update(['preference_id' => $mercadoPago['id']]);
                }
                $mercadoPago['order'] = $success['order'];
                $mercadoPago['id_parent'] = $user->id;
                return $this->successResponse($mercadoPago, 'Usuario registrado!', 200);
            }

        } catch (Exception $e) {
            // return $this->errorResponse($e->getMessage(), 422);
            return $this->errorResponse('Ha ocurrido un error!', 422);
        }
    }

    function fileUpload(Request $request, $id){
        try {
            $input = $request->all();
            $res = "";
            $resFile['fileId'] = "";
            foreach($request->file('files') as $fileReq){
                $fileName = strtr($fileReq->getClientOriginalName(), " ", "_");
                $fileStore = time().'_'.$fileName;
                $firebaseFile = new FirebaseFiles();
                $filePath = $firebaseFile->upload($fileReq, $fileStore, 'teacher/' . $id);
                $dataFile = ([
                    'user_id' => $id,
                    'file_url' => $fileStore
                ]);
                $fileId = Files::create($dataFile);
                $resFile = ([ 'filePath'=> $filePath,'fileId'=>$resFile['fileId'] != "" ? $resFile['fileId'].",".$fileId->id : $fileId->id ]);
                $res = $resFile;
            }
            return $res;

        }catch(InvalidOrderException $exception){
            return null;
        }
    }

    public function createOrder($data,$unit_price,$id_licenses_type,$phone, $license_type, $quantity, $id_course){
        try {
            $orderData = [
                'user_id' => $data['id'],
                'name' => $data['name'],
                'last_name' => $data['last_name'],
                'email' => $data['email'],
                'unit_price' => $unit_price,
                'id_licenses_type' => $id_licenses_type ? $id_licenses_type : null,
                'status' => 'pending',
                'phone_number' => $phone,
                'license_type' => $license_type,
                'quantity' => $quantity,
                'id_course' => $id_course ? $id_course : null
            ];
            return Order::create($orderData);
        }catch (\Exception $exception){
            return $this->errorResponse($exception->getMessage(),422);
        }
    }

    public function validateUser($user){
        $messages = [
            'required.name' => 'El campo :nombre es requerido.',
        ];

        return Validator::make($user, [
            'name' => 'required',
            'last_name' => 'required',
            'email' => 'required|email',
            'username' => 'required',
            'password' => 'required',
        ], $messages);
    }

}
