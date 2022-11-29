<?php
namespace App\Http\Controllers\API;
use App\AvatarUsers;
use Illuminate\Contracts\Auth\MustVerifyEmail;
use Illuminate\Http\Request;
use App\Http\Controllers\Controller;
use App\User;
use App\School;
use App\GoogleToken;
use Illuminate\Support\Facades\Auth;
use Exception;
use Carbon\Carbon;
use Validator;

class UserController extends Controller
{
    // protected $token;

    // public function __construct()
    // {
    //     $this->token = Config::get('app.mercadopago_token');
    // }
    public $successStatus = 200;
    /**
     * login api
     *
     * @return \Illuminate\Http\Response
     */
    public function username()
    {
        return 'username';
    }

    public function logout()
    {
        try {

            $user = Auth::user();

            $user_token = GoogleToken::where('user_id', $user->id)->first();

            if($user_token /* && $user_token->is_active */){
                $dataToken = ([
                    'is_active' => false
                ]);
                GoogleToken::where('user_id', $user->id)->update($dataToken);
            }

            $user = Auth::user()->token();
            $user->revoke();
            return response()->json($user, $this->successStatus);
        }catch (Exception $e){
            $error["code"] = 'INVALID_USER';
            $error["message"] = "NOT User.";

            return response()->json(['error' => $error], 500);
            }
        return 'username';
    }

    public function login(){
        try {
            if (Auth::attempt(['username' => request('username'), 'password' => request('password')])) {
                $user = Auth::user();
                $number = intval($user->role_id);
                if(AvatarUsers::where('user_id',$user->id)->exists()){
                    $avatar =  AvatarUsers::select('avatar_path')->where('user_id',$user->id)->first();
                    if(empty($avatar->avatar_path)){
                        $picture = $this->getPicture($number);
                    }
                    else{
                        $picture = $avatar->avatar_path;
                    }
                }else{
                    $picture = $this->getPicture($number);
                }

                $schoolUser = School::find($user->school_id);

                if ($schoolUser->is_active === false) {
                    $error["code"] = 'INVALID_USER';
                    $error["message"] = "Tu escuela esta inactiva, consulta al administrador de tu escuela.";
                    return response()->json(['error' => $error], 422);
                }
                if($user->active_phpfox <= 0){
                    $error["code"] = 'INVALID_USER';
                    $error["message"] = "Upps! al parecer tu correo esta duplicado, por favor contacta al Administrador de tu Colegio, o manda un correo a soporte@clublia.com";
                    return response()->json(['error' => $error], 422);
                }else{
                    $success['access_token'] = $user->createToken('MyApp')->accessToken;


                // if($user->role_id >= 25 && $user->role_id <= 36){
                //     $preapprovalId = Order::select('merchant_order_id,status,user_id')->where([['user_id', $user->id],['status','authorized']])->firstOrFail();
                //     $data = Http::withToken($this->token)->get('https://api.mercadopago.com/preapproval/search?id='.$preapprovalId->merchant_order_id);
                // }

                    $dataUser['displayName'] = $user->name;
                    $dataUser['email'] = $user->email;
                    $dataUser['photoURL'] = $picture;
                    $dataUser['role'] = $user->role->slug;
                    $dataUser['school_id'] = $user->school_id;
                    $dataUser['username'] = $user->username;
                    $dataUser['school_name'] = $user->school_id ? $user->school->name : null;
                    $dataUser['grade'] = $user->grade;
                    $dataUser['lastName'] = $user->last_name;
                    $dataUser['level_id'] = $user->level_id;
                    if(!is_null($user->company_id)){
                        $user = $user->load('company');
                        $dataUser['company'] = $user->company['name'];
                    }

                    if($user->role->slug == 'Maestro-A' || $user->role->slug == 'Maestro-M' || $user->role->slug == 'Maestro-I') {
                        if ($dataUser['level_id'] == '1') {
                            $dataUser['role'] = $dataUser['role'].'-preescolar';
                        } elseif ($dataUser['level_id'] == '3') {
                            $dataUser['role'] = $dataUser['role'].'-secundaria';
                        }
                    }

                    if(str_contains($dataUser['role'], 'preescolar')){
                        $dataUser['uuid_'] = $user->password;
                    }

                    $dataUser['uuid'] = $user->id;
                    $userLastLogin['last_login'] = Carbon::now();
                    $userUpdt = User::where('id',$user->id)->update($userLastLogin);

                    $success['user'] = (['data' =>$dataUser]);


                    return response()->json($success, $this->successStatus);
                }
            } else {
                $error["code"] = 'INVALID_PASSWORD';
                $error["message"] = "The password is invalid or the user does not have a password.";

                $errors["message"] = "The password is invalid or the user does not have a password.";
                $errors["domain"] = "global";
                $errors["reason"] = "invalid";

                $error["errors"] =[$errors];

                return response()->json(['error' => $error], 400);
            }
        }catch (ModelNotFoundException $e){
            $error["code"] = 'INVALID_PASSWORD';
            $error["message"] = 'INVALID_PASSWORD';

            $errors["message"] = "The password is invalid or the user does not have a password.";
            $errors["domain"] = "global";
            $errors["reason"] = "invalid";

            $error["errors"] =[$errors];

            return response()->json(['error' => $error], 500);
        }
    }
    /**
     * Register api
     *
     * @return \Illuminate\Http\Response
     */
    public function register(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'name' => 'required',
            'email' => 'required|email',
            'password' => 'required',
            'c_password' => 'required|same:password',
        ]);
        if ($validator->fails()) {
            $error["code"] = 'INVALID_DATA';
            $error["message"] = "The field is invalid or the user does not have a password.";

            $errors["message"] = ['error'=>$validator->errors()];
            $errors["domain"] = "global";
            $errors["reason"] = "invalid";

            $error["errors"] =[$errors];

            return response()->json(['error' => $error], 401);
        }

        try{
            $input = $request->all();
            $input['password'] = bcrypt($input['password']);
            $user = User::create($input);

            $success['access_token'] = $user->createToken('MyApp')->accessToken;

            $dataUser['displayName'] = $user->name;
            $dataUser['email'] = $user->email;
            $dataUser['photoURL'] = $user->email;
            $dataUser['role'] = $user->role->slug;
            $dataUser['school_id'] = $user->school_id;
            $dataUser['school_name'] = $user->school_id ? $user->school->name : null;
            $dataUser['uuid'] = $user->id;

            $success['user'] = (['data' =>$dataUser]);


            return response()->json($success, $this->successStatus);

        }catch (Exception $e){
            $error["code"] = 'INVALID_DATA';
            $error["message"] = "The field is invalid or the user does not have a password.";

            $errors["message"] = "The fields is invalid or the user does not have a password.";
            $errors["domain"] = "global";
            $errors["reason"] = "invalid";

            $error["errors"] =[$errors];

            return response()->json(['error' => $error], 500);
        }

    }
    /**
     * details api
     *
     * @return \Illuminate\Http\Response
     */
    public function accessToken()
    {
        try{
            $user = Auth::user();

            if (AvatarUsers::where('user_id',$user->id)->exists()){
                $avatar =  AvatarUsers::select('avatar_path')->where('user_id',$user->id)->first();
                if (empty($avatar->avatar_path)){
                    $picture = $this->getPicture($user->role_id);
                } else {
                    $picture = $avatar->avatar_path;

                }
            } else {
                $picture = $this->getPicture($user->role_id);
            }

            $success['access_token'] = $user->createToken('MyApp')->accessToken;

            $dataUser['displayName'] = $user->name;
            $dataUser['email'] = $user->email;
            $dataUser['photoURL'] = $picture;
            $dataUser['role'] = $user->role->slug;
            $dataUser['school_id'] = $user->school_id;
            $dataUser['username'] = $user->username;
            $dataUser['school_name'] = $user->school_id ? $user->school->name : null;
            $dataUser['lastName'] = $user->last_name;
            $dataUser['uuid'] = $user->id;
            $dataUser['grade'] = $user->grade;
            $dataUser['level_id'] = $user->level_id;
            if(!is_null($user->company_id)){
                $user = $user->load('company');
                $dataUser['company'] = $user->company['name'];
            }

            if($user->role->slug == 'Maestro-A' || $user->role->slug == 'Maestro-M' || $user->role->slug == 'Maestro-I') {
                if ($dataUser['level_id'] == '1') {
                    $dataUser['role'] = $dataUser['role'].'-preescolar';
                } elseif ($dataUser['level_id'] == '3') {
                    $dataUser['role'] = $dataUser['role'].'-secundaria';
                }
            }

            if(str_contains($dataUser['role'], 'preescolar')){
                $dataUser['uuid_'] = $user->password;
            }

            $success['user'] = (['data' =>$dataUser]);
            return response()->json($success, $this->successStatus);
        }catch (ModelNotFoundException $e){
            $error["code"] = 'INVALID_TOKEN';
            $error["message"] = "The token is invalid.";

            $errors["message"] = "The token is invalid.";
            $errors["domain"] = "global";
            $errors["reason"] = "invalid";

            $error["errors"] =[$errors];

            return response()->json(['error' => $error], 500);

        }
    }

    /**
     * @param int $number
     * @return string
     */
    public function getPicture(int $number): string
    {
        if ($number == 5 || $number == 13 || $number == 6 || $number == 18 || $number == 19 || $number == 20 || $number == 21 || $number == 34 || $number == 35 || $number == 36) {
            return "assets/images/avatars/bootFace.png";
        } else {
            return "assets/images/avatars/user.jpg";
        }
    }
}
