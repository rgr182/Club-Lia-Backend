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
use Illuminate\Support\Facades\Mail;
use MercadoPago;
use Illuminate\Support\Facades\Redirect;
use App\Mail\RegisterMember;
use App\Mail\SupportEmail;
use App\Files;
use App\Donors;

class UserSubscriptionController extends ApiController
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
    public function store(Request $request)
    {
        //
        try {

            $validator = $this->validateUser($request->all());
            if($validator->fails()){
                return $this->errorResponse($validator->messages(), 422);
            }

            $input = $request->all();
            $roleFox = [
                '25' => '27', // Escuela-I - Escuela Invitado
                '26' => '28', // Escuela-M - Escuela Mensual
                '27' => '29', // Escuela-A - Escuela Anual
                '28' => '30', // Maestro-I - Maestro Invitado
                '29' => '31', // Maestro-M - Maestro Mensual
                '30' => '32', // Maestro-A - Maestro Anual
                '31' => '33', // Padre-I - Padre Invitado
                '32' => '34', // Padre-M - Padre Mensual
                '33' => '35', // Padre-A - Padre Anual
                '34' => '36', // Alumno-I - Alumno Invitado Primaria
                '35' => '37', // Alumno-M - Alumno Mensual Primaria
                '36' => '38'  // Alumno-A - Alumno Anual Primaria
            ];

            $dataCreate['name'] = $input['name'];
            $dataCreate['username'] = $input['username'];
            $dataCreate['last_name'] = $input['last_name'];
            $dataCreate['email'] = $input['email'];
            $dataCreate['role_id'] =  $input['role_id'];
            $dataCreate['school_id'] = array_key_exists('school_id', $input) ? $input['school_id'] : null;
            $dataCreate['grade'] = array_key_exists('grade', $input) ? $input['grade'] : null;
            $dataCreate['phone_number'] = $input['phone_number'];
            $dataCreate['country'] = $input['country'];
            $dataCreate['state'] = $input['state'];
            $dataCreate['city'] = $input['state'];
            $dataCreate['level_id'] = array_key_exists('level', $input) ? $input['level'] : null;
            $password  = $input['password'];
            $passwordBcrypt = bcrypt($password);
            $passwordEncode = str_replace("$2y$", "$2a$", $passwordBcrypt);
            $dataCreate['password'] = $passwordEncode;

            $email = $input['email'];
            $username = $dataCreate['username'];

            if (User::where([['email', '=', $email]])->exists()) {
                return $this->errorResponse('El correo ya existe.', 422);
            }
            if (User::where([['username', '=', $username]])->exists()) {
                return $this->errorResponse('El nombre de usuario ya existe.', 422);
            }

            $user = User::create($dataCreate);
            $success['lia'] = $user;

            if($input['unit_price'] != 0){
                $quantity = (array_key_exists('quantity', $input) && $input['quantity']) ? $input['quantity'] : '1';
                $order = self::createOrder($user,$input['unit_price'],$input['id_licenses_type'],$dataCreate['phone_number'], 'membership', $quantity, null);
                $success['order'] = $order->id;
            }

            $contactData = ([
                'user_id' => $user->uuid,
                'phone_number' => $dataCreate['phone_number'],
                'country' => $dataCreate['country'],
                'state' => $dataCreate['state'],
                'city' => $dataCreate['city']
            ]);
            $createContact = Contact::create($contactData);

            $dataEmail = ([
                'username' => $user->username,
                'name' => $user->name,
                'last_name' => $user->last_name,
                'email' => $user->email,
                'grade' => $user->grade,
                'password' => $password
            ]);

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
                if($input['level'] === "1"){
                    $roleCommunity = 45; //Maestro Invitado Preescolar
                }elseif($input['level'] === "2"){
                    $roleCommunity = 30; //Maestro Invitado Primaria
                }else{
                    $roleCommunity = 48; //Maestro Invitado Secundaria
                }
            }elseif($dataCreate['role_id'] === 29){ //Mensual ?
                if($input['level'] === "1"){
                    $roleCommunity = 46; //Maestro Mensual Preescolar
                }elseif($input['level'] === "2"){
                    $roleCommunity = 31; //Maestro Mensual Primaria
                }else{
                    $roleCommunity = 49; //Maestro Mensual Secundaria
                }
            }elseif($dataCreate['role_id'] === 30){ //Anual ?
                if($input['level'] === "1"){
                    $roleCommunity = 47; //Maestro Anual Preescolar
                }elseif($input['level'] === "2"){
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
            if(Config::get('app.send_email')) {
                SendEmail::dispatchNow($dataEmail);
            }

            if (!empty($comunidad)){
                $success['comunidad']= $comunidad['error'];
            }

            $success['message'] = 'Usuario creado';

            return $this->successResponse($success, 200);

        } catch (Exception $e) {

            $errors["code"] = 'INVALID_DATA';
            $errors["message"] = $e->getMessage();
            $errors["username"] = "Error al crear el usuario.";

            $error["errors"] =[$errors];

            return $this->errorResponse(['error' => $error], 422);
        }
    }

    public function getPreapproval(Request $request,$id){
        $user = Auth::user();
        $input = $request->all();
        $order = Order::latest()->first();
        $parent = User::where('id', '=', $order->user_id)->firstOrFail();
        $child = User::where('id', '=', $order->child_id)->firstOrFail();
        $monthly = Carbon::now()->add(1,'month');
        $yearly = Carbon::now()->add(1,'year');

        try{
            $data = Http::withToken($this->token)->get('https://api.mercadopago.com/preapproval/search?id='.$id);
            $req = $data['results'][0];
            $req['preapproval_id'] = $id;
            $req['payment_type'] = $req['auto_recurring']['currency_id'];

            if($parent->role_id === 10 || $parent->role_id === 31 || $parent->role_id === 32 || $parent->role_id === 33){
                $id = $input['order_id'];
                if($order->id_licenses_type == '5'  ||  $order->id_licenses_type == '6')
                $req['course_type'] = 'maestro';
                if($order->id_licenses_type == '2' || $order->id_licenses_type == '3')
                $req['course_type'] = 'hijo';
                $req['quantity'] = $order->quantity;
                $academyUser = new UserThinkific();
                // $course = $academyUser->enrollmentStudentInsert($child->active_thinkific, 850120);
                if($order->id_licenses_type === 2){
                    $child->update(["role_id" => 35]);
                    // $course = $academyUser->enrollmentStudent( $child['active_thinkific'], 42968, $monthly);
                    $course = $academyUser->enrollmentStudent( $child['active_thinkific'], 52816, $monthly);
                }else{ 
                    $child->update(["role_id" => 36]);
                    $course = $academyUser->enrollmentStudent( $child['active_thinkific'], 52816, $yearly);
                    // $course = $academyUser->enrollmentStudent( $child['active_thinkific'], 42968, $yearly);
                }
            }else{
                $updtOrder = self::updateOrder( $req, $input['order_id']);
                $req['updtOrder'] = $updtOrder;
                $id = $input['order_id'];
                if($order->id_licenses_type == '5'  ||  $order->id_licenses_type == '6')
                $req['course_type'] = 'maestro';
                if($order->id_licenses_type == '2' || $order->id_licenses_type == '3')
                $req['course_type'] = 'hijo';
    
                $req['quantity'] = $order->quantity;
            }

            return $this->successResponse($req, 200);
        }catch (Exception $e) {

            $errors["code"] = 'INVALID_DATA';
            $errors["message"] = $e->getMessage();
            $errors["username"] = "Error al crear la orden.";

            $error["errors"] =[$errors];

            return $this->errorResponse(['error' => $error], 422);
        }
    }

    public function getPreapprovalCourse(Request $request,$id){
        $input = $request->all();
        try{
            $data = Http::withToken($this->token)->get('https://api.mercadopago.com/v1/payments/'.$id);
            $req = $data->json();
            $req['payment_id'] = $id;
            $req['merchant_order_id'] = $req['order']['id'];
            $req['preference_id'] = $input['preference_id'];
            $req['preapproval_id'] = $req['order']['id']; // merchant_order_id
            $req['payment_type'] = $req['currency_id'];
            $req['application_id'] = $req['order']['id'];
            $updtOrder = self::updateOrderCourse( $req, $input['order_id']);

            $id = $input['order_id'];
            $order = Order::where([['id', $id]])->firstOrFail();
            if($order->id_licenses_type == '5'  ||  $order->id_licenses_type == '6')
            $req['course_type'] = 'maestro';
            if($order->id_licenses_type == '2' || $order->id_licenses_type == '3')
            $req['course_type'] = 'hijo';

            $req['quantity'] = $order->quantity;

            $req['updtOrder'] = $updtOrder;
            return $this->successResponse($req, 200);
        }catch (Exception $e) {

            $errors["code"] = 'INVALID_DATA';
            $errors["message"] = $e->getMessage();
            $errors["username"] = "Error al crear la orden.";

            $error["errors"] =[$errors];

            return $this->errorResponse(['error' => $error], 422);
        }
    }

    public function cancelSubscription(){
        $user = Auth::user();
        try{
            $preapprovalId = Order::select('merchant_order_id,status,user_id')->where([['user_id', $user->id],['status','authorized']])->firstOrFail();
            $data = Http::withToken($this->token)->get('https://api.mercadopago.com/preapproval/search?id='.$preapprovalId->merchant_order_id);
            // return $this->successResponse($data['results'], 200);
            if($data['results']){
                $req = $data['results'][0];
                if($user->email !== $req['payer_email']){
                    return $this->errorResponse('El usuario no es dueño de la subscripción.', 422);
                }
                if(!Order::where('merchant_order_id', $preapprovalId->merchant_order_id)->exists()){
                    return $this->errorResponse('La orden no existe.', 422);
                }
                $removeSubscription = Http::withToken($this->token)->put(
                    'https://api.mercadopago.com/preapproval/'.$preapprovalId->merchant_order_id,[
                        "status" => "cancelled"
                    ]);
                if($removeSubscription['status'] === 200){
                    $user = User::find($preapprovalId->user_id);
                    $userCommunity = UserCommunity::where([['email', '=', $user->email]])->first()->toArray();
                    if($user->role_id === 29 || $user->role_id === 30){
                        $user->role_id = 28;
                        $userCommunity->user_group_id = 30;
                    }elseif($user->role_id === 32 || $user->role_id === 33){
                        $children = User::where('tutor_id',$user->id)->get();
                        foreach($children as $child){
                            $childCommunity = UserCommunity::where([['email', '=', $child->email]])->first()->toArray();
                            $child->role_id = 34;
                            $childCommunity = 36;
                            $child->save();
                            $childCommunity->save();
                        }
                        $user->role_id = 31;
                        $userCommunity->user_group_id = 33;
                    }
                    $user->save();
                    $userCommunity->save();
                    $preapprovalId->status = "cancelled";
                    $preapprovalId->save();
                    return $this->successResponse("La subscripción se canceló correctamente", 200);
                }else{
                    return $this->errorResponse('No se encontró subscripción para cancelar.', 400);
                }

            }else{
                return $this->successResponse("not found", 404);
            }
            // return $this->successResponse($data['results'][0], 200);
        }catch (Exception $e) {

            $errors["code"] = 'INVALID_DATA';
            $errors["message"] = $e->getMessage();
            $errors["username"] = "Error al buscar la subscripción.";

            $error["errors"] =[$errors];

            return $this->errorResponse(['error' => $error], 422);
        }
    }

    public function storeOrder(Request $request)
    {
        try {

            $input = $request->all();

            $validator = $this->validateStoreOrder($input);
            if($validator->fails()){
                return $this->errorResponse($validator->messages(),422);
            }

            $user = Auth::user();
            $licenses_type =  \DB::table('licenses_type')->where('id',$input['id_licenses_type'])->get();

            $quantity = (array_key_exists('quantity', $input) && $input['quantity']) ? $input['quantity'] : '1';

            //order
            $order = self::createOrder($user,$licenses_type[0]->price,$input['id_licenses_type'], null, 'membership', $quantity, null);
            $success['order'] = $order;

            //payment
            $dataPayment = $request->all();
            // Agrega credenciales MercadoPago
            MercadoPago\SDK::setAccessToken($this->token);

            $path = parse_url($_SERVER["REQUEST_URI"], PHP_URL_PATH);
            $payer = new MercadoPago\Payer();
            $payer->name = $user->name;
            $payer->surname = $user->last_name;
            $payer->email = $user->email;
            $payer->phone = array(
                "area_code" => "",
                "number" => ""
            );

            // Crea un objeto de preferencia
            $preference = new MercadoPago\Preference();

            $item = new MercadoPago\Item();
            $item->title = $licenses_type[0]->title;
            $item->description = $licenses_type[0]->description_license_type;
            $item->category_id = "services";
            $item->quantity = 1;
            $item->currency_id = "MXN";
            $item->unit_price = $licenses_type[0]->price;

            $preference->back_urls = array(
                "success" => "https://comunidad.clublia.com/bill",
                "failure" => "https://comunidad.clublia.com/login",
                "pending" => "https://comunidad.clublia.com/login"
            );

            $preference->auto_return = "approved";

            $preference->items = array($item);

            $preference->payer = $payer;

            $preference->payment_methods = array(
                "excluded_payment_types" => array( array( "id"=>"ticket"), array("id"=>"bank_transfer"), array("id"=>"atm"), array("id"=>"digital_wallet"))
            );

            $preference->save();

            $success['payment'] = $preference->getAttributes();

            return $this->successResponse($success, 200);

        } catch (ModelNotFoundException $e) {

            $errors["code"] = 'INVALID_DATA';
            $errors["message"] = $e->getMessage();
            $errors["username"] = "Error al crear la orden.";

            $error["errors"] =[$errors];

            return $this->errorResponse(['error' => $error], 422);
        }
    }


    public function storeChildren(Request $request)
    {
        try {
            $childrens = $request->all();

            foreach($childrens as $children){
                $validator = $this->validateChild($children);
                if($validator->fails()){
                    return $this->errorResponse($validator->messages(), 422);
                }
            }
            $responses[] = "";
            foreach($childrens as $children){

                $roleFox = [
                    '22' => '24', // MaestroE1 - MaestroE1
                    '23' => '25', // MaestroE2 - MaestroE2
                    '24' => '26', // MaestroE3 - MaestroE3
                    '25' => '27', // Escuela-I - Escuela Invitado
                    '26' => '28', // Escuela-M - Escuela Mensual
                    '27' => '29', // Escuela-A - Escuela Anual
                    '28' => '30', // Maestro-I - Maestro Invitado
                    '29' => '31', // Maestro-M - Maestro Mensual
                    '30' => '32', // Maestro-A - Maestro Anual
                    '31' => '33', // Padre-I - Padre Invitado
                    '32' => '34', // Padre-M - Padre Mensual
                    '33' => '35', // Padre-A - Padre Anual
                    '34' => '36', // Alumno-I - Alumno Invitado
                    '35' => '37', // Alumno-M - Alumno Mensual
                    '36' => '38'  // Alumno-A - Alumno Anual
                ];

                if (User::where([['email', '=', $children['email']]])->exists()) {
                    return $this->errorResponse('El correo ya existe.', 422);
                }
                if (User::where([['username', '=', $children['username']]])->exists()) {
                    return $this->errorResponse('El nombre de usuario ya existe.', 422);
                }

                $dataCreate['name'] = $children['name'];
                $dataCreate['username'] = $children['username'];
                $dataCreate['last_name'] = $children['last_name'];
                $dataCreate['email'] = $children['email'];
                $dataCreate['role_id'] = 34;
                $dataCreate['tutor_id'] = $children['tutor_id'];
                $dataCreate['school_id'] = array_key_exists('school_id', $children) ? $children['school_id'] : null;
                $dataCreate['grade'] = array_key_exists('grade', $children) ? $children['grade'] : null;
                $dataCreate['level_id'] = array_key_exists('level', $children) ? $children['level'] : null;

                $password  = $children['password'];
                $passwordBcrypt = bcrypt($password);

                $passwordEncode = str_replace("$2y$", "$2a$", $passwordBcrypt);
                $dataCreate['password'] = $passwordEncode;

                $username = $dataCreate['username'];

                $user = User::create($dataCreate);

                $dataEmail = ([
                    'username' => $user->username,
                    'name' => $user->name,
                    'last_name' => $user->last_name,
                    'email' => $user->email,
                    'grade' => $user->grade,
                    'password' => $password
                ]);

                //Data to insert new user in the Academy
                $dataThink = ([
                    'first_name' => $user->name,
                    'last_name' => $user->last_name,
                    'email' => $user->email,
                    'password' => $password
                ]);

                $academyUser = new UserThinkific();

                $affected = User::find($user->id);
                $academyExUser = $academyUser->getUserByEmail($user->email);
                if(array_key_exists(0,$academyExUser["items"])){
                    $existingUser = $academyUser->getUserByEmail($user->email);

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

                if($dataCreate['role_id'] === 34){ //Invitado ?
                    if($children['level'] === "1"){
                        $roleCommunity = 39; //Alumno Invitado Preescolar
                    }elseif($children['level'] === "2"){
                        $roleCommunity = 36; //Alumno Invitado Primaria
                    }else{
                        $roleCommunity = 42; //Alumno Invitado Secundaria
                    }
                }elseif($dataCreate['role_id'] === 35){ //Mensual ?
                    if($children['level'] === "1"){
                        $roleCommunity = 40; //Alumno Mensual Preescolar
                    }elseif($children['level'] === "2"){
                        $roleCommunity = 37; //Alumno Mensual Primaria
                    }else{
                        $roleCommunity = 43; //Alumno Mensual Secundaria
                    }
                }else{ //Anual ?
                    if($children['level'] === "1"){
                        $roleCommunity = 41; //Alumno Anual Preescolar
                    }elseif($children['level'] === "2"){
                        $roleCommunity = 38; //Alumno Anual Primaria
                    }else{
                        $roleCommunity = 44; //Alumno Anual Secundaria
                    }
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
                if(Config::get('app.send_email')) {
                    SendEmail::dispatchNow($dataEmail);
                }

                if (!empty($comunidad)){
                    $success['comunidad']= $comunidad['error'];
                }

                $success['message'] = 'Usuario creado';

                array_push($responses,$success);
            }
            return $this->successResponse($responses,'Usuario registrado', 200);

        } catch (Exception $e) {

            $errors["code"] = 'INVALID_DATA';
            $errors["message"] = $e->getMessage();
            $errors["username"] = "Error al crear el usuario.";

            $error["errors"] =[$errors];

            return $this->errorResponse(['error' => $error], 422);
        }
    }

    public function storeParent(Request $request){

        try {
            $input = $request->all();
            $input['id_licenses_type'] = "1"; // Registro sin costo
            $input['unit_price'] = "0";
            if ($input['role_id'] == "32") {
                $input['id_licenses_type'] = "2"; // Registro Mensual
            } elseif ($input['role_id'] == "33") {
                $input['id_licenses_type'] = "3"; // Registro Anual
            }

            $validator = $this->validateUser($input);
            if($validator->fails()){
                return $this->errorResponse($validator->messages(), 422);
            }
            if (User::where([['email', '=', $input['email']]])->exists()) {
                return $this->errorResponse('El correo '.$input['email'].' ya existe.', 422);
            }
            if (User::where([['username', '=', $input['username']]])->exists()) {
                return $this->errorResponse('El nombre de usuario '.$input['username'].' ya existe.', 422);
            }

            $i = 0;
            $numChildrens = sizeof($input['childrens']);

            foreach($input['childrens'] as $children){
                $input['childrens'][$i]['role_id'] = '34'; // antes de pagar todos son invitados
                $validator = $this->validateChild($children);
                if($validator->fails()){
                    return $this->errorResponse($validator->messages(), 422);
                }
                if (User::where([['email', '=', $children['email']]])->exists()) {
                    return $this->errorResponse('El correo '.$children['email'].' ya existe.', 422);
                }
                if (User::where([['username', '=', $children['username']]])->exists()) {
                    return $this->errorResponse('El nombre de usuario '.$children['username'].' ya existe.', 422);
                }
                if ($children['email'] == $input['email']){
                    return $this->errorResponse('El email '.$children['email'].' está repetido, deben ser diferentes', 422);
                }
                if ($children['username'] == $input['username']){
                    return $this->errorResponse('El nombre de usuario '.$children['username'].' está repetido, deben ser diferentes', 422);
                }
                for ($j = $i + 1; $j < $numChildrens; $j++) {
                    if ($children['email'] == $input['childrens'][$j]['email']){
                        return $this->errorResponse('El email '.$children['email'].' está repetido, deben ser diferentes', 422);
                    }
                    if ($children['username'] == $input['childrens'][$j]['username']){
                        return $this->errorResponse('El nombre de usuario '.$children['username'].' está repetido, deben ser diferentes', 422);
                    }
                }
                $i++;
            }

            $roleFox = [
                '31' => '33', // Padre-I - Padre Invitado
                '34' => '36', // Alumno-I - Alumno Invitado Primaria
            ];

            $dataCreate['name'] = $input['name'];
            $dataCreate['username'] = $input['username'];
            $dataCreate['last_name'] = $input['last_name'];
            $dataCreate['email'] = $input['email'];
            $dataCreate['role_id'] =  31;
            $dataCreate['school_id'] = 305; // #QuedateEnCasaconClubLIA
            $dataCreate['phone_number'] = $input['phone_number'];
            $dataCreate['country'] = $input['country'];
            $dataCreate['state'] = $input['state'];
            $dataCreate['city'] = $input['state'];
            $password  = $input['password'];
            $passwordBcrypt = bcrypt($password);
            $passwordEncode = str_replace("$2y$", "$2a$", $passwordBcrypt);
            $dataCreate['password'] = $passwordEncode;

            $email = $input['email'];
            $username = $dataCreate['username'];

            $user = User::create($dataCreate);
            $success['lia'] = $user;

            $license = \DB::table('licenses_type')->where('id',$input['id_licenses_type'])->first();
            $order = '';
            if($license->price != 0){
                $order = self::createOrder($user,$license->price,$input['id_licenses_type'],$input['phone_number'], null, $numChildrens, null);
                $success['order'] = $order->id;
            }
            if (array_key_exists('course', $input) && $input['course']){
                $order = self::createOrder($user,$input['course']['price'], '1',$input['phone_number'], 'course', $numChildrens, $input['course']['id']);
                $success['order'] = $order->id;
            }

            $contactData = ([
                'user_id' => $user->uuid,
                'phone_number' => $dataCreate['phone_number'],
                'country' => $dataCreate['country'],
                'state' => $dataCreate['state'],
                'city' => $dataCreate['city'],
            ]);
            $createContact = Contact::create($contactData);

            $dataEmail = ([
                'name' => $user->name,
                'last_name' => $user->last_name,
                'email' => $user->email,
                'phone_number' => $dataCreate['phone_number'],
                'country' => $dataCreate['country'],
                'state' => $dataCreate['state'],
                'username' => $user->username,
                'role_id' => $input['role_id'],
                'licenses_type' => $license->title,
            ]);

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

            $roleCommunity = $roleFox[31];

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

            Mail::send(new RegisterMember($dataEmail));


            if (!empty($comunidad)){
                $success['comunidad']= $comunidad['error'];
            }

            $responses = [];
            foreach($input['childrens'] as $children){
                $dataCreate = [];
                $dataCreate['name'] = $children['name'];
                $dataCreate['username'] = $children['username'];
                $dataCreate['last_name'] = $children['last_name'];
                $dataCreate['email'] = $children['email'];
                $dataCreate['role_id'] = $children['role_id'];
                $dataCreate['tutor_id'] = $success['lia']['id'];
                $dataCreate['school_id'] = 305; // #QuedateEnCasaconClubLIA
                $dataCreate['grade'] = array_key_exists('grade', $children) ? $children['grade'] : null;
                $dataCreate['level_id'] = array_key_exists('level', $children) ? $children['level'] : null;

                $password  = $children['password'];
                $passwordBcrypt = bcrypt($password);

                $passwordEncode = str_replace("$2y$", "$2a$", $passwordBcrypt);
                $dataCreate['password'] = $passwordEncode;

                $username = $dataCreate['username'];

                $user = User::create($dataCreate);
                $successChildren['lia'] = $user;

                if (array_key_exists('course', $input) && $input['course']){ //Validation to add course field

                    $courseData = $input['course'];
                    // return $this->successResponse($courseData, 200);
                    $dataEmailC = ([
                        'username' => $user->username,
                        'name' => $user->name,
                        'last_name' => $user->last_name,
                        'email' => $user->email,
                        'grade' => $user->grade,
                        'password' => $password,
                        'role_id' => $dataCreate['role_id'],
                        'level_id' => $dataCreate['level_id'],
                        'licenses_type' => $license->title,
                        'course_name' => $courseData['name'],
                    ]);
                }else{
                    $dataEmailC = ([
                        'username' => $user->username,
                        'name' => $user->name,
                        'last_name' => $user->last_name,
                        'email' => $user->email,
                        'grade' => $user->grade,
                        'password' => $password,
                        'role_id' => $dataCreate['role_id'],
                        'level_id' => $dataCreate['level_id'],
                        'licenses_type' => $license->title,
                    ]);
                }

                //Data to insert new user in the Academy
                $dataThink = ([
                    'first_name' => $user->name,
                    'last_name' => $user->last_name,
                    'email' => $user->email,
                    'password' => $password
                ]);

                $academyUser = new UserThinkific();

                $affected = User::find($user->id);
                $academyExUser = $academyUser->getUserByEmail($user->email);
                if(array_key_exists(0,$academyExUser["items"])){
                    $existingUser = $academyUser->getUserByEmail($user->email);

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

                if($dataCreate['role_id'] == 34){ //Invitado ?
                    if($children['level'] === "1"){
                        $roleCommunity = 39; //Alumno Invitado Preescolar
                    }elseif($children['level'] === "2"){
                        $roleCommunity = 36; //Alumno Invitado Primaria
                    }else{
                        $roleCommunity = 42; //Alumno Invitado Secundaria
                    }
                } else {
                    $roleCommunity = 36;
                }

                if (UserCommunity::where([['email', '=', $user->email]])->exists()) {
                    $repeatCommunity = UserCommunity::where([['email', '=', $user->email]])->first()->toArray();
                    $user->active_phpfox = $repeatCommunity['user_id'];
                    $user->save();
                    $comunidadC['error'] = ['El correo electronico ya esta asignado', $repeatCommunity, $user];
                    $successChildren['comunidad']= $comunidadC['error'];
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
                    $successChildren['comunidad'] = $userCommunity;
                }
                Mail::send(new RegisterMember($dataEmailC));

                array_push($responses,$successChildren);

            }
            $success['childrens'] = $responses;

            $appMercadoPago = new AppMercadoPago();
            if ($license->price != 0) { // membresia mensual o anual
                $type = Str::contains($license->description_license_type, 'mensual') ? 'mensual' : 'anual';
                $title =  'membresía de reforzamiento ' . $type .' de ClubLIA.';
                $orderData = ([
                    'item' => ([
                        'unit_price' => $license->price,
                        'title' => $title,
                    ]),
                    'payer' => ([ "email" => $input['email'] ]),
                    'quantity' => $numChildrens,
                    'id_licenses_type' => $input['id_licenses_type'],
                ]);
                $mercadoPago = $appMercadoPago->processOrderMembership($orderData);
                if (array_key_exists('id', $mercadoPago)) {
                    $order->update(['preference_id' => $mercadoPago['id']]);
                }
                $mercadoPago['order'] = $success['order'];
                $mercadoPago['id_parent'] = $user->id;
                return $this->successResponse($mercadoPago, 'Usuarios registrados', 200);
            } elseif (array_key_exists('course', $input) && $input['course']) { // curso con registro gratuito
                $orderData = ([
                    "email" => $input['email'],
                    "name" => $input['name'],
                    "surname" => $input['last_name'],
                    "items" => array([
                        "title" =>  $input['course']['name'],
                        "quantity" => $numChildrens,
                        "unit_price" => $input['course']['price'],
                    ]),
                ]);
                $mercadoPago = $appMercadoPago->processOrderProducts($orderData);
                if (array_key_exists('id', $mercadoPago)) {
                    $order->update(['preference_id' => $mercadoPago['id']]);
                }
                $mercadoPago['order'] = $success['order'];
                $mercadoPago['id_parent'] = $user->id;
                return $this->successResponse($mercadoPago, 'Usuarios registrados', 200);
            } else {
                $returnToLogin = ([
                    'init_point' => env('REACT_APP_URL').'/login',
                ]);
                return $this->successResponse($returnToLogin, 'Usuarios registrados', 200);
            }


        } catch (Exception $e) {

            // return $this->errorResponse($e->getMessage(), 422);
            return $this->errorResponse('Ha ocurrido un error!', 422);
        }
    }

    public function storeSupport(Request $request)
    {
        try{

            $input = $request->all();
            $appMercadoPago = new AppMercadoPago();


            if($input['support'] == 1 || $input['support'] == 3){

                $dataCreate['name'] = $input['name'];
                $dataCreate['username'] = $input['institution'];
                $dataCreate['last_name'] = '';
                $dataCreate['email'] = $input['email'];
                $dataCreate['role_id'] =  '37';
                $dataCreate['school_id'] =  null;
                $dataCreate['grade'] =  null;
                $dataCreate['phone_number'] = $input['phone_number'];
                $dataCreate['country'] = $input['country'];
                $dataCreate['state'] = $input['state'];
                $dataCreate['city'] = $input['state'];
                $dataCreate['level_id'] =  null;
                $password  = 'clubliaDonor1';
                $passwordBcrypt = bcrypt($password);
                $passwordEncode = str_replace("$2y$", "$2a$", $passwordBcrypt);
                $dataCreate['password'] = $passwordEncode;

                $email = $input['email'];
                $username = $dataCreate['username'];

                if (User::where([['email', '=', $email]])->exists()) {
                    $idU = User::where([['email', '=', $email]])->firstOrFail();

                    $input['id'] = $idU->id;

                } else{
                    $user = User::create($dataCreate);

                    $input['id'] = $user->id;

                    if($request->file()){

                        $res = '';
                        $id = $user->id;
                        $resFile['fileId'] = "";
                        foreach($request->file('files') as $fileReq){
                            $fileName = strtr($fileReq->getClientOriginalName(), " ", "_");
                            $fileStore = time().'_'.$fileName;
                            $filePath = $fileReq->storeAs('logo/'.$id, $fileStore, 'public');

                            $dataFile = ([
                                'user_id' => $id,
                                'file_path' => $filePath
                            ]);
                            $fileId = Files::create($dataFile);
                            $resFile = ([ 'filePath'=>'logo/'.$id,'fileId'=>$resFile['fileId'] != "" ? $resFile['fileId'].",".$fileId->id : $fileId->id ]);
                            $res = $resFile;
                        }
                        $input['filePath'] = $filePath;

                    }

                }


                if($input['support'] == 1){
                    $unit_price = $input['supportStudents'] * 3600;
                }
                else{
                    $unit_price = $input['supportAmount'];
                    $input['supportStudents'] = '1';
                }
                if($input['publish_both'] == 'true'){
                $input['publish_donors'] = 'false';
                $input['publish_logo'] = 'false';
                }

                $order = self::createOrderSupport($input,$unit_price , '1',$input['phone_number'], 'donation', $input['supportStudents'], null);
                $success['order'] = $order->id;

                $orderData = ([
                    "email" => $input['email'],
                    "name" => $input['name'],
                    "surname" => '',
                    "items" => array([
                        "title" =>  'Donation',
                        "quantity" => '1',
                        "unit_price" => $unit_price,
                    ]),
                ]);

                $mercadoPago = $appMercadoPago->processOrderProducts($orderData);
                if (array_key_exists('id', $mercadoPago)) {

                    $order->update(['preference_id' => $mercadoPago['id']]);
                }

                $mercadoPago['order'] = $success['order'];
                $mercadoPago['id_parent'] = $input['id'];

                return $this->successResponse($mercadoPago, 'Enviando a Mercado Pago', 200);
            }
            if($input['support'] == 2){

                $dataCreate['institution'] = $input['institution'];
                $dataCreate['businessName'] = $input['businessName'];
                $dataCreate['position'] = $input['position'];
                $dataCreate['name'] = $input['name'];
                $dataCreate['email'] = $input['email'];
                $dataCreate['phone_number'] = $input['phone_number'];
                $dataCreate['instructions'] =  $input['instructions'];

                $dataEmail = ([
                    'institution' => $dataCreate['institution'],
                    'businessName' => $dataCreate['businessName'],
                    'position' => $dataCreate['position'],
                    'name' => $dataCreate['name'],
                    'email' => $dataCreate['email'],
                    'phone_number' => $dataCreate['phone_number'],
                    'instructions' => $dataCreate['instructions'],
                ]);

                Mail::send(new SupportEmail($dataEmail));
                return $this->successResponse($dataEmail, 'Mensaje enviado con exito', 200);
            }


        }catch (Exception $e) {

        return $this->errorResponse($e->getMessage(), 422);
        }
    }

    public function storeSponsorship(Request $request)
    {
        try{

            $input = $request->all();
            $appMercadoPago = new AppMercadoPago();

            if($input['supportStudents'] == 11){

                $dataCreate['sponsorship'] = $input['sponsorship'];
                $dataCreate['institution'] = $input['institution'];
                $dataCreate['businessName'] = $input['businessName'];
                $dataCreate['position'] = $input['position'];
                $dataCreate['name'] = $input['name'];
                $dataCreate['email'] = $input['email'];
                $dataCreate['phone_number'] = $input['phone_number'];
                $dataCreate['country'] =  $input['country'];
                $dataCreate['state'] =  $input['state'];

                $dataEmail = ([
                    'sponsorship' => $dataCreate['sponsorship'],
                    'institution' => $dataCreate['institution'],
                    'businessName' => $dataCreate['businessName'],
                    'position' => $dataCreate['position'],
                    'name' => $dataCreate['name'],
                    'email' => $dataCreate['email'],
                    'phone_number' => $dataCreate['phone_number'],
                    'country' => $dataCreate['country'],
                    'state' => $dataCreate['state'],
                ]);

                Mail::send(new SupportEmail($dataEmail));
                return $this->successResponse($dataEmail, 'Información registrada', 200);
            }
            else{

                $dataCreate['name'] = $input['name'];
                $dataCreate['username'] = $input['institution'];
                $dataCreate['last_name'] = '';
                $dataCreate['email'] = $input['email'];
                $dataCreate['role_id'] =  '37';
                $dataCreate['school_id'] =  null;
                $dataCreate['grade'] =  null;
                $dataCreate['phone_number'] = $input['phone_number'];
                $dataCreate['country'] = $input['country'];
                $dataCreate['state'] = $input['state'];
                $dataCreate['city'] = $input['state'];
                $dataCreate['level_id'] =  null;
                $password  = 'clubliaDonor1';
                $passwordBcrypt = bcrypt($password);
                $passwordEncode = str_replace("$2y$", "$2a$", $passwordBcrypt);
                $dataCreate['password'] = $passwordEncode;

                $email = $input['email'];
                $username = $dataCreate['username'];

                if (User::where([['email', '=', $email]])->exists()) {

                    $idU = User::where([['email', '=', $email]])->firstOrFail();

                    $input['id'] = $idU->id;
                }
                else {
                    $user = User::create($dataCreate);

                    $input['id'] = $user->id;

                    if($request->file()){

                        $res = '';
                        $id = $user->id;
                        $resFile['fileId'] = "";
                        foreach($request->file('files') as $fileReq){
                            $fileName = strtr($fileReq->getClientOriginalName(), " ", "_");
                            $fileStore = time().'_'.$fileName;
                            $filePath = $fileReq->storeAs('logo/'.$id, $fileStore, 'public');

                            $dataFile = ([
                                'user_id' => $id,
                                'file_path' => $filePath
                            ]);
                            $fileId = Files::create($dataFile);
                            $resFile = ([ 'filePath'=>'logo/'.$id,'fileId'=>$resFile['fileId'] != "" ? $resFile['fileId'].",".$fileId->id : $fileId->id ]);
                            $res = $resFile;
                        }
                        $input['filePath'] = $filePath;

                    }

                }

                if($input['sponsorship'] == 1 && $input['supportStudents'] != 12){
                    $unit_price = ($input['supportStudents'] * $input['supportMonths']) * 600;
                }
                elseif($input['sponsorship'] == 2){
                    $unit_price = $input['supportStudents'] * 2100;
                }
                else{
                    $unit_price = $input['supportAmount'];
                    $input['supportStudents'] = '1';
                }
                if($input['publish_both'] == 'true'){
                $input['publish_donors'] = 'false';
                $input['publish_logo'] = 'false';
                }


                $order = self::createOrderSupport($input,$unit_price , '1',$input['phone_number'], 'donation', $input['supportStudents'], null);
                $success['order'] = $order->id;

                $orderData = ([
                    "email" => $input['email'],
                    "name" => $input['name'],
                    "surname" => '',
                    "items" => array([
                        "title" =>  'Donacion',
                        "quantity" => '1',
                        "unit_price" => $unit_price,
                    ]),
                ]);


                $mercadoPago = $appMercadoPago->processOrderProducts($orderData);
                if (array_key_exists('id', $mercadoPago)) {

                    $order->update(['preference_id' => $mercadoPago['id']]);
                }

                $mercadoPago['order'] = $success['order'];
                $mercadoPago['id_parent'] = $input['id'];

                return $this->successResponse($mercadoPago, 'Enviando a Mercado Pago', 200);

            }


        }catch (Exception $e) {

        // return $this->errorResponse(['error' => $error], 422);
        return $this->errorResponse($e->getMessage(), 422);
    }
    }


    public function sendMailToStoreSchool(Request $request)
    {
        try{
            $validator = $this->validateSchoolData($request->all());
            if($validator->fails()){
                return $this->errorResponse($validator->messages(), 422);
            }
            $input = $request->all();

            $schoolData["email"] = "jorgeadelgadod@gmail.com";
            $schoolData["subject"] = "Registrar escuela";
            $schoolData["schoolName"] = $input['name'];
            $schoolData["schoolMail"] = $input['email'];
            $schoolData["schoolPhone"] = $input['phone'];
            $schoolData["schoolCountry"] = $input['country'];
            $schoolData["schoolCity"] = $input['city'];
            $schoolData["membership"] = $input['membership'];
            $schoolData["message"] = array_key_exists('message', $input) ? $input['message'] : null;

            Mail::send(new RegisterSchool($schoolData));
            Mail::send(new SendInfoToSchool($schoolData));

            return $this->successResponse("Email enviado");

        } catch (NotFoundException $e) {

            $errors["code"] = 'INVALID_DATA';
            $errors["message"] = $e->getMessage();
            $errors["username"] = "Error al mandar el correo.";

            $error["errors"] =[$errors];

            return $this->errorResponse(['error' => $error], 422);
        }

    }


    public function sendMailFromTeachers(Request $request)
    {
        try{
            // $validator = $this->validateSchoolData($request->all());
            // if($validator->fails()){
            //     return $this->errorResponse($validator->messages(), 422);
            // }
            $input = $request->all();

            $emailData["teacherName"] = $input['name'];
            $emailData["teacherLastName"] = $input['last_name'];
            $emailData["teacherEmail"] = $input['email'];
            $emailData["teacherPhoneNumber"] = $input['phone_number'];
            $emailData["teacherLevelSchool"] = $input['level_school'];
            $emailData["teacherLevel"] = $input['level_id'];
            $emailData["teacherMembership"] = $input['membership'];
            $emailData["teacherSchoolName"] = $input['school_name'];
            $emailData["teacherIntereses"] = $input['intereses'];
            $emailData["message"] = array_key_exists('message', $input) ? $input['message'] : null;

            $userData["userName"] = $input['username'];
            $userData["userPassword"] = $input['password'];
            $userData["userEmail"] = $input['email'];

            // maping email data object
            $teacherData['name'] = $emailData["teacherName"];
            $teacherData['last_name'] = $emailData["teacherLastName"];
            $teacherData['email'] = $emailData["teacherEmail"];
            $teacherData['phone_number'] = $emailData["teacherPhoneNumber"];
            $teacherData['school_name'] = $emailData["teacherSchoolName"];
            $teacherData['level_school'] = $emailData["teacherLevelSchool"];
            $teacherData['membership'] = $emailData["teacherMembership"];

            Mail::send(new TeacherEmail($teacherData));
            Mail::send(new UserEmail($userData));

            return $this->successResponse("Email enviado");

        } catch (NotFoundException $e) {

            $errors["code"] = 'INVALID_DATA';
            $errors["message"] = $e->getMessage();
            $errors["username"] = "Error al mandar el correo.";

            $error["errors"] =[$errors];

            return $this->errorResponse(['error' => $error], 422);
        }

    }

    /**
     * Update the specified resource in storage.
     *
     * @param  Request  $request
     * @param  int  $id
     * @return JsonResponse
     */
    public function updateOrder($request, $id)
    {

        $validator = $this->validateOrder($request);
        if($validator->fails()){
            return $this->errorResponse($validator->messages(), 422);
        }
        $input = $request;
        try{

            if (!Order::where([['id', $id],['status','pending']])->exists()) {
                return $this->errorResponse('No existe la orden.', 422);
            }

            $order = Order::where([['id', $id],['status','pending']])->firstOrFail();

            if($order->id_licenses_type == '5'  ||  $order->id_licenses_type == '6')
            $success['course_type'] = 'maestro';
            if($order->id_licenses_type == '2' || $order->id_licenses_type == '3')
            $success['course_type'] = 'hijo';

            $success['quantity'] = $order->quantity;

            $userAcademy = User::find($order->user_id);
            $academyUser = new UserThinkific();

            // Compra de cursos de membresias
            $roleFox = [
                '25' => '27', // Escuela-I - Escuela Invitado
                '26' => '28', // Escuela-M - Escuela Mensual
                '27' => '29', // Escuela-A - Escuela Anual
                '28' => '30', // Maestro-I - Maestro Invitado
                '29' => '31', // Maestro-M - Maestro Mensual
                '30' => '32', // Maestro-A - Maestro Anual
                '31' => '33', // Padre-I - Padre Invitado
                '32' => '34', // Padre-M - Padre Mensual
                '33' => '35', // Padre-A - Padre Anual
                '34' => '36', // Alumno-I - Alumno Invitado
                '35' => '37', // Alumno-M - Alumno Mensual
                '36' => '38'  // Alumno-A - Alumno Anual
            ];

            $assignRolesByMembership = [
                '1' => 31, //Padre-I
                '2' => 32, //Padre-M
                '3' => 33, //Padre-A
                '4' => 28, //Maestro-I
                '5' => 29, //Maestro-M
                '6' => 30, //Maestro-A
                '7' => 25, //Escuela-I
                '8' => 26, //Escuela-M
                '9' => 27, //Escuela-A
                '10' => 34, //Alumno-I
                '11' => 35, //Alumno-M
                '12' => 36  //Alumno-A
            ];

            $groupName = [
                '31' => 'Padre-I',   //Padres invitados
                '32' => 'Padre-M',   //Padres mensuales
                '33' => 'Padre-A',   //Padres anuales
            ];

            //order
            $monthly = Carbon::now()->add(1,'month');
            $yearly = Carbon::now()->add(1,'year');

            /* $pendingOrder = [
                'status' => "pending"
            ];
            Order::where('user_id',$order->user_id)->update($pendingOrder); */

            $licenses = \DB::table('licenses_type')->where('id',$order->id_licenses_type)->get();
            $success['licenses'] = $licenses;

            $userUpdate['role_id'] = $assignRolesByMembership[$order['id_licenses_type']];

            if($userUpdate['role_id'] == 29){
                if($userAcademy['level_id'] == 1){
                    $academyT = $academyUser->groupAssign($userAcademy->active_thinkific, 'Maestro-MPrees');
                    $academyCourseT = $academyUser->enrollmentStudent($userAcademy->active_thinkific, 62710, $monthly);
                }elseif ($userAcademy['level_id'] == 2){
                    $academyT = $academyUser->groupAssign($userAcademy->active_thinkific, 'Maestro-MPrim');
                    $academyCourseT = $academyUser->enrollmentStudent($userAcademy->active_thinkific, 62234, $monthly);
                }else{
                    $academyT = $academyUser->groupAssign($userAcademy->active_thinkific, 'Maestro-MSec');
                    $academyCourseT = $academyUser->enrollmentStudent($userAcademy->active_thinkific, 62711, $monthly);
                }
            }elseif ($userUpdate['role_id'] == 30){
                if($userAcademy['level_id'] == 1){
                    $academyT = $academyUser->groupAssign($userAcademy->active_thinkific, 'Maestro-APrees');
                    $academyCourseT = $academyUser->enrollmentStudent($userAcademy->active_thinkific, 62712, $yearly);
                }elseif ($userAcademy['level_id'] == 2){
                    $academyT = $academyUser->groupAssign($userAcademy->active_thinkific, 'Maestro-APrim');
                    $academyCourseT = $academyUser->enrollmentStudent($userAcademy->active_thinkific, 62235, $yearly);
                }else{
                    $academyT = $academyUser->groupAssign($userAcademy->active_thinkific, 'Maestro-ASec');
                    $academyCourseT = $academyUser->enrollmentStudent($userAcademy->active_thinkific, 62722, $yearly);
                }
            }

            if(User::where('tutor_id',$order->user_id)->exists()){
                $childrens = User::select('id','role_id', 'level_id', 'active_thinkific', 'active_phpfox')->where('tutor_id',$order->user_id)->get();

                if($order['id_licenses_type'] === 2){

                    $academyF = $academyUser->groupAssign($userAcademy->active_thinkific, $groupName[$userUpdate['role_id']]);
                    $academyCourseF = $academyUser->enrollmentStudent($userAcademy->active_thinkific, 62229, $monthly);

                    foreach($childrens as $children){
                        if($children['level_id'] == "1"){
                            $roleCommunity = 40; //Alumno Mensual Preescolar
                        }elseif($children['level_id'] == "2"){
                            $roleCommunity = 37; //Alumno Mensual Primaria
                        }else{
                            $roleCommunity = 43; //Alumno Mensual Secundaria
                        }
                        $children['role_id'] = 35;
                        $children->save();
                        $childrenCommunityUpdate['user_group_id'] = $roleCommunity;
                        UserCommunity::where('user_id',$children->active_phpfox)->firstOrFail()->update($childrenCommunityUpdate);

                        if($children['level_id'] == 1){
                            $academyGroup = $academyUser->groupAssign($children['active_thinkific'], 'Alumno-MPrees');
                            $academyCourse = $academyUser->enrollmentStudent($children['active_thinkific'], 62728, $yearly);
                            $success['academia']= (array) ["group_academy" => $academyGroup,"id" => $children['id']];
                        }elseif($children['level_id'] == 2){
                            $academyGroup = $academyUser->groupAssign($children['active_thinkific'], 'Alumno-MPrim');
                            $academyCourse = $academyUser->enrollmentStudent($children['active_thinkific'], 62226, $yearly);
                            $success['academia']= (array) ["group_academy" => $academyGroup,"id" => $children['id']];
                        }else{
                            $academyGroup = $academyUser->groupAssign($children['active_thinkific'], 'Alumno-MSec');
                            $academyCourse = $academyUser->enrollmentStudent($children['active_thinkific'], 62730, $yearly);
                            $success['academia']= (array) ["group_academy" => $academyGroup,"id" => $children['id']];
                        }

                    }
                }else{

                    $academyF = $academyUser->groupAssign($userAcademy->active_thinkific, $groupName[$userUpdate['role_id']]);
                    $academyCourseF = $academyUser->enrollmentStudent($userAcademy->active_thinkific, 62231, $yearly);

                    foreach($childrens as $children){
                        if($children['level_id'] == "1"){
                            $roleCommunity = 41; //Alumno Anual Preescolar
                        }elseif($children['level_id'] == "2"){
                            $roleCommunity = 38; //Alumno Anual Primaria
                        }else{
                            $roleCommunity = 44; //Alumno Anual Secundaria
                        }
                        $children['role_id'] = 36;
                        $children->save();
                        $childrenCommunityUpdate['user_group_id'] = $roleCommunity;
                        UserCommunity::where('user_id',$children->active_phpfox)->firstOrFail()->update($childrenCommunityUpdate);

                        if($children['level_id'] == 1){
                            $academyGroup = $academyUser->groupAssign($children['active_thinkific'], 'Alumno-APrees');
                            $academyCourse = $academyUser->enrollmentStudent($children['active_thinkific'], 62731, $yearly);
                            $success['academia']= (array) ["group_academy" => $academyGroup,"id" => $children['id']];
                        }elseif($children['level_id'] == 2){
                            $academyGroup = $academyUser->groupAssign($children['active_thinkific'], 'Alumno-APrim');
                            $academyCourse = $academyUser->enrollmentStudent($children['active_thinkific'], 62227, $yearly);
                            $success['academia']= (array) ["group_academy" => $academyGroup,"id" => $children['id']];
                        }else{
                            $academyGroup = $academyUser->groupAssign($children['active_thinkific'], 'Alumno-ASec');
                            $academyCourse = $academyUser->enrollmentStudent($children['active_thinkific'], 62732, $yearly);
                            $success['academia']= (array) ["group_academy" => $academyGroup,"id" => $children['id']];
                        }

                    }
                }
            }

            User::where('id',$order->user_id)->firstOrFail()->update($userUpdate);
            $user = User::find($order->user_id);
            $success['lia'] = $user;

            //community
            if($userUpdate['role_id'] === 29){ //Mensual ?
                if($user['level_id'] == 1){
                    $roleCommunity = 46; //Maestro Mensual Preescolar
                }elseif($user['level_id'] == 2){
                    $roleCommunity = 31; //Maestro Mensual Primaria
                }else{
                    $roleCommunity = 49; //Maestro Mensual Secundaria
                }
            }elseif($userUpdate['role_id'] === 30){ //Anual ?
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

            if($order->id_licenses_type == 2 || $order->id_licenses_type == 5 || $order->id_licenses_type == 8){
                $expiration_date = $monthly->toDateTimeString();
            }else{
                $expiration_date = $yearly->toDateTimeString();
            }

            $dataOrder = ([
                'payment_id' => "".$input['application_id'],
                'merchant_order_id' => "".$input['preapproval_id'],
                'preference_id' => "".$input['preapproval_id'],
                'payment_type' => $input['payment_type'],
                'expiry_date' => $expiration_date,
                'status' => 'approved'
            ]);
            Order::where('id',$id)->firstOrFail()->update($dataOrder);
            $orderUpdt = Order::where('id', $id)->firstOrFail();
            $success['order'] = $orderUpdt;

            $mailData['payment_id'] = "".$input['application_id'];
            $mailData['merchant_order_id'] = "".$input['preapproval_id'];
            $mailData['payment_type'] = $input['payment_type'];
            $mailData['date'] = Carbon::now()->toDateTimeString();
            $mailData['expiration_date'] = $expiration_date;
            $mailData['name'] = $user->name . " " . $user->last_name;
            $mailData['username'] = $user->username;
            $mailData['phone'] = $order->phone_number;
            $mailData['email'] = $user->email;
            $mailData['title'] = $licenses[0]->title;
            $mailData['description_license_type'] = $licenses[0]->description_license_type;
            $mailData['price'] = $licenses[0]->price;

            Mail::send(new SendInvoice($mailData));

            return $this->successResponse($success, 'La orden ha sido actualizada',200);

        } catch (Exception $e) {
            $errors["code"] = 'INVALID_DATA';
            $errors["message"] = $e->getMessage();
            $errors["username"] = "Error al actualizar la orden.";

            $error["errors"] =[$errors];

            return $this->errorResponse(['error' => $error], 422);
        }
    }

    public function updateOrderCourse($request, $id)
    {
        $validator = $this->validateOrder($request);
        if($validator->fails()){
            return $validator->messages();
        }
        $input = $request;
        try{

            if (!Order::where([['id', $id]])->exists())
                return 'No existe la orden.';
            $order = Order::where([['id', $id]])->firstOrFail();
            $success['title'] = 'Cursos';

            if ($order->status != 'pending')
                return $success;

            $userAcademy = User::find($order->user_id);
            $academyUser = new UserThinkific();

            // Compra de cursos de thinkific
            if (isset($order->license_type) && $order->license_type == 'course') {
                if ($order->id_licenses_type == '1') { // cursos para papas

                    $childrens = User::select('active_thinkific')->where('tutor_id',$order->user_id)->get();
                    $responses = [];
                    foreach($childrens as $children){
                        $course = $academyUser->enrollmentStudentInsert( $children->active_thinkific, $order->id_course);
                        array_push($responses,$course);
                    } 
                    $success['course'] = $responses;

                    // $never = '2038-01-19 03:14:07';
                    $dataOrder = ([
                        'payment_id' => "".$input['payment_id'],
                        'merchant_order_id' => "".$input['merchant_order_id'],
                        'preference_id' => "".$input['preference_id'],
                        'payment_type' => $input['payment_type'],
                        'status' => 'approved'
                    ]);
                    $orderUpdt = Order::where('id',$id)->firstOrFail()->update($dataOrder);
                    $success['updtOrder'] = $orderUpdt;
                    return $success;
                } elseif ($order->id_licenses_type == '4') { // cursos para maestros
                    $course = $academyUser->enrollmentStudentInsert( $userAcademy->active_thinkific, $order->id_course);
                    $success['course'] = $course;
                    $dataOrder = ([
                        'payment_id' => "".$input['payment_id'],
                        'merchant_order_id' => "".$input['merchant_order_id'],
                        'preference_id' => "".$input['preference_id'],
                        'payment_type' => $input['payment_type'],
                        'status' => 'approved'
                    ]);
                    $orderUpdt = Order::where('id',$id)->firstOrFail()->update($dataOrder);
                    $success['updtOrder'] = $orderUpdt;
                    return $success;
                }

            } elseif(isset($order->license_type) && $order->license_type == 'Donation') {

                $dataOrder = ([
                    'payment_id' => "".$input['payment_id'],
                    'merchant_order_id' => "".$input['merchant_order_id'],
                    'preference_id' => "".$input['preference_id'],
                    'payment_type' => $input['payment_type'],
                    //'expiry_date' => $never,
                    //'expiry_date' => null,
                    'status' => 'approved'
                ]);
                $orderUpdt = Order::where('id',$id)->firstOrFail()->update($dataOrder);
                $success['updtOrder'] = $orderUpdt;
                return $success;

            }else{
                    return 'El tipo orden no corresponde al método';

            }
        } catch (Exception $e) {

            $errors["code"] = 'INVALID_DATA';
            $errors["message"] = $e->getMessage();
            $errors["username"] = "Error al actualizar la orden.";

            $error["errors"] =[$errors];

            return $this->errorResponse(['error' => $error], 422);
        }
    }

    public function acceptPayment($topic,$id){
        $orderData = [
            'user_id' => 343,
            'name' => "mercadopago",
            'last_name' => "API",
            'email' => "mercago@pago.com",
            'unit_price' => 0,
            'id_licenses_type' => 2,
            'status' => $topic
        ];
        $order = Order::create($orderData);
        return $this->successResponse($order,"recibido",200);
    }

    public function createOrder($data,$unit_price,$id_licenses_type,$phone, $child, $license_type, $quantity, $id_course){
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
                'child_id' => $child,
                'license_type' => $license_type,
                'quantity' => $quantity,
                'id_course' => $id_course ? $id_course : null
            ];
            $order = Order::create($orderData);

            return $order;

        }catch (\Exception $exception){
            return $this->errorResponse($exception->getMessage(),422);
        }

    }

    public function createOrderSupport($data, $unit_price, $id_licenses_type, $phone, $license_type, $quantity, $logo){
        try {

            $orderData = [
                'user_id' => $data['id'], //id nuevo
                'name' => 'Donor',
                'last_name' => 'Donor',
                'email' => $data['email'],
                'unit_price' => $unit_price,
                'id_licenses_type' => $id_licenses_type ? $id_licenses_type : null,
                'status' => 'pending',
                'phone_number' => $phone,
                'license_type' => $license_type,
                'quantity' => $quantity,
                'id_course' => null
            ];
            $order = Order::create($orderData);

            $donorData = [
                'institution' => $data['institution'],
                'business_name' => $data['businessName'],
                'position' => $data['position'],
                'name' => $data['name'],
                'logo' => array_key_exists("filePath", $data) && $data['filePath'] ? $data['filePath'] : null,
                'publish_donors' => $data['publish_donors'],
                'publish_logo' => $data['publish_logo'],
                'id_order' => $order->id,
                'id_rol' => '37'
            ];
            $donor = Donors::create($donorData);
            return $order;

        }catch (Exception $exception){
            return $this->errorResponse($exception->getMessage(),422);
        }

    }

    public function donorList()
    {
        try {
            $donor = new Donors();
            $donors = $donor->show();

            return $this->successResponse($donors, 'Lista donadores', 201);

        }catch (Exception $e){
            return $this->errorResponse('Hubo un problema en su consulta. Intente de nuevo más tarde', 401 );
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
            'unit_price' => 'required',
            'id_licenses_type' => 'required'
        ], $messages);
    }

    public function validateStoreOrder($user){
        $messages = [
            'required.name' => 'El campo :nombre es requerido.',
        ];

        return Validator::make($user, [
            'id_licenses_type' => 'required'
        ], $messages);
    }

    public function validateChild($user){
        $messages = [
            'required.name' => 'El campo :nombre es requerido.',
        ];

        return Validator::make($user, [
            'name' => 'required',
            'last_name' => 'required',
            'email' => 'required|email',
            'username' => 'required',
            'password' => 'required'
        ], $messages);
    }

    public function validateOrder($user){
        $messages = [
            'required.name' => 'El campo :nombre es requerido.',
        ];

        return Validator::make($user, [
            'application_id' => 'required',
            'preapproval_id' => 'required',
            'payment_type' => 'required'
        ], $messages);
    }

    public function validateSchoolData($user){
        $messages = [
            'required.name' => 'El campo :nombre es requerido.',
        ];

        return Validator::make($user, [
            'name' => 'required',
            'email' => 'required|email',
            'phone' => 'required',
            'country' => 'required',
            'city' => 'required',
        ], $messages);
    }
}
