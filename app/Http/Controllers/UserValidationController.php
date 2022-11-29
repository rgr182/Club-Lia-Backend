<?php

namespace App\Http\Controllers;

use App\Http\Controllers\UserSubscriptionController;
use App\MercadoPago as AppMercadoPago;
use App\UserCommunity;
use Illuminate\Http\Request;
use App\UserThinkific;
use App\LicenseType;
use Validator;
use App\Role;
use App\User;
use Hash;
use Auth;
use DB;
use Carbon\Carbon;
use App\PhpFox_activitypoint_statistics;
use App\PhpFox_user_activity;
use App\PhpFox_user_count;
use App\PhpFox_user_field;
use App\PhpFox_user_space;

class UserValidationController extends ApiController
{
    public function getStudentCourses(Request $request){
        $userThink = new UserThinkific();
        $courses = $userThink->coursesAvaibleKeyword(UserThinkific::STUDENT_KEYWORD);
        return $this->successResponse([
            'courses' => $courses
        ], 'Información del dashbord',200);
    }

    public function getStudentCourseById(Request $request){
        $userThink = new UserThinkific();
        $course = $userThink->getCourseById($request->courseId);
        return $this->successResponse([
            'course' => $course
        ], 'Información del dashbord',200);
    }

    public function getChilds(){
        $childs = User::select(
                'users.id',
                'users.name',
                'users.tutor_id',
                'users.last_name',
                'users.role_id',
                'users.active_thinkific',
                'users.email',
                'users.created_at',
                'users.grade',
                'avatar_users.avatar_path'
            )
            ->leftJoin('avatar_users', 'avatar_users.user_id', '=', 'users.id')
            ->where('tutor_id', Auth::user()->id)
            ->get();
        return $this->successResponse([
            'childs' => $childs
        ], 'Información del dashbord',200);
    }

    public function boosterMembership(Request $request){
        $rules = [
            'childs' => 'required|array|min:1',
            'course' => 'required'
		];
        $messages = [
            "required" => "El campo :attribute es obligatorio.",
        ];
        $attributes = [
            'childs' => 'Hijos',
            'course' => 'Curso'
        ];
		$validator = Validator::make($request->all(), $rules, $messages);
        $validator->setAttributeNames($attributes);
        if ($validator->fails()) {
            return response()->json([
                'msg'=>$validator->errors()->first(),
            ], 422);
        }else{
            $paymentOrder = $this->createPaymentProductOrder($request->childs, $request->course);
            return $this->successResponse($paymentOrder, 'Usuarios registrados', 200);
        }
    }

    public function storeChilds($childs){
        try {
            $parent = Auth::user();
            $role = Role::where('slug', 'Alumno-I')
                ->first();
            $responses = [];
            foreach ($childs as $key => $child) {
                $user = new User;
                $password  = $child['password'];
                $passwordBcrypt = bcrypt($password);
                $passwordEncode = str_replace("$2y$", "$2a$", $passwordBcrypt);
                $user->fill([
                    'username' => $child['username'],
                    'name' => $child['name'],
                    'last_name' => $child['lastName'],
                    'email' => $child['email'],
                    'grade' => $child['level'], //Reversed data by frontend
                    'level_id' => $child['grade'], //Reversed data by frontend
                    'role_id' => $role->id,
                    'tutor_id' => $parent->id,
                    'school_id' => 305, // #QuedateEnCasaconClubLIA
                    'password' => $passwordEncode
                ]);
                $user->save();
                $createdChild['lia'] = $user;

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
                    $createdChild['academia'] = $existingUser[0]["id"];
                }else{
                    $inputUser = $academyUser->createUser($dataThink);
                    if(array_key_exists("errors", $inputUser)){
                        $createdChild['academia'] = (array) ["academy err" => $inputUser,"id" => $user->id];
                    }else{
                        $affected->active_thinkific = $role->id;
                        $createdChild['academia'] = $role->id;
                    }
                }
                $affected->save();

                //Data to insert new user in the Comunity
                if($child['level'] === "1"){
                    $roleCommunity = 39; //Alumno Invitado Preescolar
                }elseif($child['level'] === "2"){
                    $roleCommunity = 36; //Alumno Invitado Primaria
                }else{
                    $roleCommunity = 42; //Alumno Invitado Secundaria
                }
                if (UserCommunity::where([['email', '=', $user->email]])->exists()) {
                    $repeatCommunity = UserCommunity::where([['email', '=', $user->email]])->first()->toArray();
                    $user->active_phpfox = $repeatCommunity['user_id'];
                    $user->save();
                    $createdChild['comunidad'] = ['El correo electronico ya esta asignado', $repeatCommunity, $user];
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
                    $createdChild['comunidad'] = $userCommunity;
                }
                array_push($responses,$createdChild);
            }
            return $responses;
        } catch (\Exception $e) {
            return $e->getMessage();
        }
    }

    public function saveChilds(Request $request){
        $rules = [
            'membership' => 'required',
            'membership.price' => 'required|numeric',
            'childs' => 'required|array|min:1',
            'childs.*.email' => 'required|email|unique:users,email,NULL,id,deleted_at,NULL',
            'childs.*.grade' => 'required',
            'childs.*.name' => 'required',
            'childs.*.lastName' => 'required',
            'childs.*.level' => 'required',
            'childs.*.username' => 'required',
            'childs.*.password' => 'required|string|min:6|required_with:password_confirmation|confirmed',
            'childs.*.password_confirmation' => 'required|string|min:6',
		];
        $messages = [
            "required" => "El campo :attribute es obligatorio.",
        ];
        $attributes = [
            'childs' => 'Hijos',
            'childs.*.email' => 'Email',
            'childs.*.grade' => 'Grado',
            'childs.*.name' => 'Nombre',
            'childs.*.lastName' => 'Apellido',
            'childs.*.level' => 'Nivel',
            'childs.*.username' => 'Usuario',
            'childs.*.password' => 'Contraseña',
            'childs.*.password_confirmation' => 'Confirmacion de contraseña',
            'membership' => 'Membresia de reforzamiento'
        ];
		$validator = Validator::make($request->all(), $rules, $messages);
        $validator->setAttributeNames($attributes);
        foreach ($request->childs as $key => $child) {
            if (User::where([['email', '=', $child['email']]])->exists()) {
                return $this->errorResponse([
                    'msg'=>'El correo '.$child['email'].' ya existe.'
                ], 422);
            }
            if (User::where([['username', '=', $child['username']]])->exists()) {
                return $this->errorResponse([
                    'msg'=>'El nombre de usuario '.$child['username'].' ya existe.'
                ], 422);
            }
        }
        $academyUser = new UserThinkific();
        if ($validator->fails()) {
            return response()->json([
                'msg'=>$validator->errors()->first(),
            ], 422);
        }else{
            foreach ($request->childs as $key => $child) {
                $dataThink = ([
                    'first_name' =>$child['name'],
                    'last_name' => $child['lastName'],
                    'email' => $child['email'],
                    'password' => 'clublia123'
                ]);
                $resultAcademy = $academyUser->createUser($dataThink);                
            }
            
            $idThinkific = $resultAcademy["id"];
            $createdChilds = $this->storeChilds($request->childs);
            foreach ($createdChilds as $key => $created) {
                $id = $created["lia"]["id"];              
                $resultQuery = User::where("id", "=", $id)->first();
                $resultQuery->update(["active_thinkific" => $idThinkific]);
            }            
            $paymentOrder = $this->createPaymentMembershipOrder($createdChilds, $request->membership);
            return $this->successResponse( $paymentOrder, 'Hijos registrados', 200);
        }
    }

    public function saveCourseChilds(Request $request){
        $rules = [
            'course' => 'required',
            'course.price' => 'required',
            'childs' => 'required|array|min:1',
            'childs.*.email' => 'required|email|unique:users,email,NULL,id,deleted_at,NULL',
            'childs.*.grade' => 'required',
            'childs.*.name' => 'required',
            'childs.*.lastName' => 'required',
            'childs.*.level' => 'required',
            'childs.*.username' => 'required',
            'childs.*.password' => 'required|string|min:6|required_with:password_confirmation|confirmed',
            'childs.*.password_confirmation' => 'required|string|min:6',
		];
        $messages = [
            "required" => "El campo :attribute es obligatorio.",
        ];
        $attributes = [
            'childs' => 'Hijos',
            'childs.*.email' => 'Email',
            'childs.*.grade' => 'Grado',
            'childs.*.name' => 'Nombre',
            'childs.*.lastName' => 'Apellido',
            'childs.*.level' => 'Nivel',
            'childs.*.username' => 'Usuario',
            'childs.*.password' => 'Contraseña',
            'childs.*.password_confirmation' => 'Confirmacion de contraseña',
            'course' => 'Curso'
        ];
		$validator = Validator::make($request->all(), $rules, $messages);
        $validator->setAttributeNames($attributes);
        foreach ($request->childs as $key => $child) {
            if (User::where([['email', '=', $child['email']]])->exists()) {
                return $this->errorResponse([
                    'msg'=>'El correo '.$child['email'].' ya existe.'
                ], 422);
            }
            if (User::where([['username', '=', $child['username']]])->exists()) {
                return $this->errorResponse([
                    'msg'=>'El nombre de usuario '.$child['username'].' ya existe.'
                ], 422);
            }
        }
        if ($validator->fails()) {
            return $this->errorResponse([
                'msg'=>$validator->errors()->first(),
            ], 422);
        }else{
            $createdChilds = $this->storeChilds($request->childs);
            $paymentOrder = $this->createPaymentProductOrder($createdChilds, $request->course);
            return $this->successResponse( $paymentOrder, 'Hijos registrados', 200);
        }
    }

    public function saveBoosterMembership(Request $request){
        $rules = [
            'membership' => 'required',
            'membership.price' => 'required|numeric',
            'childs' => 'required|array|min:1'
		];
        $messages = [
            "required" => "El campo :attribute es obligatorio.",
        ];
        $attributes = [
            'childs' => 'Hijos',
            'membership' => 'Membresia de reforzamiento'
        ];
		$validator = Validator::make($request->all(), $rules, $messages);
        $validator->setAttributeNames($attributes);
        if ($validator->fails()) {
            return response()->json([
                'msg'=>$validator->errors()->first(),
            ], 422);
        }else{
            $paymentOrder = $this->createPaymentMembershipOrder($request->childs, $request->membership);
            return $this->successResponse($paymentOrder);
        }
    }

    public function createPaymentMembershipOrder($childs, $membership){
        try {
            $parent = Auth::user();
            $numChildrens = count($childs);
            $affected_users = '';
            foreach($childs as $child){
                $affected_users .= (array_key_exists('lia', $child) ? $child['lia']['id'] : $child['id']).',';                
                $idThinkific = $child['active_thinkific'];
                $idChild = $child['id'];
            }
            $affected_users = substr($affected_users, 0, -1);
            $licenses_type_id = 2; // mensual
            if ($membership['name'] == 'anual')
                $licenses_type_id = 3; // anual
            // $license = \DB::table('licenses_type')->where('id',$licenses_type_id)->first();
            $license = LicenseType::where('id', $licenses_type_id)->first();
            $order = (new UserSubscriptionController)->createorder(
                $parent, // user
                $license->price, // price
                $licenses_type_id, // type of membership / recurrency of payment
                null, // phone not required
                $idChild,
                null, // licenses_type not specify equivalent to 'membership'  
                $numChildrens, // quantity
                null, // not required if licenses_type != 'course'
                $affected_users // childs to affect
            );
            // Mercado Pago
            $appMercadoPago = new AppMercadoPago();
            $orderData = ([
                'item' => ([
                    'unit_price' => $license->price,
                    'title' => $license->description_license_type.' para los servicios de ClubLIA, cantidad '.$numChildrens,
                ]),
                'payer' => ([ "email" => $parent['email'] ]),
                'quantity' => $numChildrens,
                'id_licenses_type' => $licenses_type_id,
                'id' => $idChild,
                'active_thinkific' => $idThinkific
            ]);
            $mercadoPago = $appMercadoPago->processOrderMembership($orderData);
            if (array_key_exists('id', $mercadoPago)) {
                $order->update(['preference_id' => $mercadoPago['id']]);
            }
            $mercadoPago['order'] = $order->id;
            return $mercadoPago;
        } catch (\Exception $e) {
            return $e->getMessage();
        }
    }
    
    public function createPaymentProductOrder($childs, $course){
        try {
            $parent = Auth::user();
            $numChildrens = count($childs);
            $affected_users = '';
            foreach($childs as $child){
                $affected_users .= (array_key_exists('lia', $child) ? $child['lia']['id'] : $child['id']).',';
            }
            $affected_users = substr($affected_users, 0, -1);
            $order = (new UserSubscriptionController)->createorder(
                $parent, // user
                $course['price'], // price
                1, //Free license,
                null, // phone not required
                'course', // licenses_type specify to buy a 'course'  
                $numChildrens, // quantity
                $course['productable_id'], // course id
                $affected_users // childs to affect
            );
            // Mercado Pago
            $appMercadoPago = new AppMercadoPago();
            $orderData = ([
                "email" => $parent['email'],
                "name" => $parent['name'],
                "surname" => $parent['last_name'],
                "items" => array([
                    "title" =>  $course['name'],
                    "quantity" => $numChildrens,
                    "unit_price" => intval($course['price']),
                ]),
            ]);
            $mercadoPago = $appMercadoPago->processOrderProducts($orderData);
            if (array_key_exists('id', $mercadoPago)) {
                $order->update(['preference_id' => $mercadoPago['id']]);
            }
            $mercadoPago['order'] = $order->id;
            return $mercadoPago;
        } catch (\Exception $e) {
            return $e->getMessage().' at '. $e->getLine();
        }
    }
}