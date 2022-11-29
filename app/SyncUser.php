<?php

namespace App;

use App\Jobs\DeleteGenericUserJob;
use App\Jobs\SendEmail;
use App\Mail\PasswordChangeMail;
use App\Mail\SendgridMail;
use Carbon\Carbon;

use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use function Symfony\Component\String\s;


class SyncUser
{
    protected $key;
    protected $subdomain;

    function __construct() {
        $this->key = Config::get('app.thinkific');
        $this->subdomain = Config::get('app.subdomain_thinkific');
    }

    public function transferUsers(){

        $inactive = 0;

        $results = User::where([
            ['role_id', '>', 1],
            ['active_thinkific' ,'=', $inactive]
        ])->orWhere([
            ['role_id', '>', 1],
            ['active_phpfox' ,'=', $inactive]
        ])->get();

        $i = 0;

        if($results->isEmpty()){
           return ['message' => 'No hay usuarios por sincronizar'];
        }
        else{
            foreach ($results as $obj) {
                $syncUser = $obj;

                $request = Http::withHeaders([
                    'X-Auth-API-Key' => $this->key,
                    'X-Auth-Subdomain' => $this->subdomain,
                    'Content-Type' => 'application/json',
                ])->post('https://api.thinkific.com/api/public/v1/users', [
                    'first_name' =>$syncUser->name,
                    'last_name' => $syncUser->last_name,
                    'email' => $syncUser->email
                ]);

                $dataFox = ([
                    'email' => $syncUser->email,
                    'full_name' => $syncUser->name . ' ' .$syncUser->last_name,
                    "user_name" => $syncUser->username,
                    "password" => '1234567'
                ]);

                $requestFox =  new UserPhpFox();
                $requestFox = $requestFox->createUser($dataFox);

                $inputuser =  $request->json();
                $inputuserFox =  $requestFox;

                if(array_key_exists("id", $inputuser) && !empty($inputuserFox['data']) ){


                    $affected = User::find($syncUser->id);
                    $affected->active_thinkific = $inputuser['id'];
                    $affected->active_phpfox = $inputuserFox['data']['user_id'];
                    $affected->save();

                    $count[++$i]= (array) ["schooling" => $inputuser,"comunidad" => $inputuserFox,"id" => $syncUser->id];

                }
                if(array_key_exists("id", $inputuser) && empty($inputuserFox['data'])) {


                    $affected = User::find($syncUser->id);
                    $affected->active_thinkific = $inputuser['id'];
                    $affected->save();

                    $count[++$i] = (array)["schooling" => $inputuser, "comunidad" => $inputuserFox, "id" => $syncUser->id];
                }
                if(!array_key_exists("id", $inputuser) && !empty($inputuserFox['data'])){

                    $affected = User::find($syncUser->id);
                    $affected->active_phpfox = $inputuserFox['data']['user_id'];
                    $affected->save();

                    $count[++$i]= (array) ["schooling" => $inputuser,"comunidad" => $inputuserFox,"id" => $syncUser->id];
                }
                else{
                    if(!empty($inputuser['errors']) && $inputuserFox["status"] === 'failed'){
                        $count[++$i]= (array) ["schooling" => $inputuser,"comunidad" => $inputuserFox,"id" => $syncUser->id] ;
                    }
                }

            }
        }
        Log::info(json_encode(["usuarios" => $count]));
        return (["usuarios" => $count]);
    }

    public function destroyUser($id)
    {
        $user = User::find($id);

        DeleteGenericUserJob::dispatch($user->active_thinkific,$user->active_phpfox);

        //$userLIA = UserLIA::find($user->AppUserId);
        //$userLIA->delete();
        //$user->delete();

        return $user;

    }

    public function update($id)
    {
        $request = request()->all();

        try {
            $user = Auth::user();
            $input = $request;

            if($user->role_id == 1 || $user->role_id == 2){
                $dataCreate['role_id'] = $input['role_id'];
                $dataCreate['school_id'] = $input['school_id'];
            }else{
                if ( $input['role_id'] == 4 ||  $input['role_id'] == 5 ||  $input['role_id'] == 13 || $input['role_id'] == 6 || $input['role_id'] == 7 || $input['role_id'] == 8){
                    $dataCreate['role_id'] = $input['role_id'];
                }else{
                    $dataCreate['role_id'] = 4;
                }
                $dataCreate['school_id'] = $user->school_id;
            }

            $dataCreate['name'] = $input['first_name'];
            $dataCreate['last_name'] = $input['last_name'];
            $dataCreate['grade'] = $input['grade'];
            $dataCreate['email'] = $input['email'];

            if (array_key_exists('password', $input)) {

                $password  = $input['password'];
                $passwordEncode = bcrypt($password);
                $passwordEncode = str_replace("$2y$", "$2a$", $passwordEncode);
                $dataCreate['password'] = $passwordEncode;

                 $dataLIA = ([
                    'Names' =>  $dataCreate['name'],
                    'LastNames' => $dataCreate['last_name'],
                    'Email' =>  $dataCreate['email'],
                    'Grade' =>  $dataCreate['grade'],
                    'Password' => $dataCreate['password'],
                    'RoleId' =>  $dataCreate['role_id'],
                    'SchoolId' => $dataCreate['school_id']
                ]);
            }else{
                $dataLIA = ([
                    'Names' =>  $dataCreate['name'],
                    'LastNames' => $dataCreate['last_name'],
                    'Email' =>  $dataCreate['email'],
                    'Grade' =>  $dataCreate['grade'],
                    'RoleId' =>  $dataCreate['role_id'],
                    'SchoolId' => $dataCreate['school_id']
                ]);
            }

            //UserLIA::where('AppUserId','=',$user->AppUserId)->firstOrFail()->update($dataLIA);

            $results = User::findOrFail($id);

            $change = $results->updated_at->toDateTimeString();

            if($results['active_thinkific'] !== 0 && $results['active_phpfox'] !== 0){
                $userAcademy = new UserThinkific();
                $userAcademy = $userAcademy->updateUser($dataCreate, $results['active_thinkific']);

                $userComunidad = new UserPhpFox();
                $userComunidad = $userComunidad->updateUser($dataCreate,$results['active_phpfox'] );

            }
            if ($results['active_thinkific'] !== 0 && $results['active_phpfox'] == 0){
                $userAcademy = new UserThinkific();
                $userAcademy = $userAcademy->updateUser($dataCreate, $results['active_thinkific']);
            }
            if ($results['active_thinkific'] == 0 && $results['active_phpfox'] !== 0){
                $userComunidad = new UserPhpFox();
                $userComunidad = $userComunidad->updateUser($dataCreate,$results['active_phpfox'] );
            }

            $results->update($dataCreate);
            $results->save();
            $updt = $results->updated_at->toDateTimeString();

            if($updt !== $change && isset($password) !== false){
                var_dump('entre');
                $dataUpdt['username'] = $results->username;
                $dataUpdt['password'] = $password;
                $dataUpdt['email'] = $results->email;

                Mail::to($results->email)->send(new PasswordChangeMail($dataUpdt));
            }

            $success['message'] = 'Usuario Actualizado';
            $success['code'] = 200;

            return response()->json($success,200);

        } catch (ModelNotFoundException $exception) {

            $error["code"] = 'INVALID_DATA';
            $error["message"] = "Usuario no encontrado";
            $error["errors"] =[$exception->getMessage()];
            //return back()->withError($exception->getMessage())->withInput();
            return response()->json(['error' => $error], 404);
        }
    }
}
