<?php

namespace App\Traits;

use App\Jobs\SendEmail;
use App\Jobs\UserGenericRegister;
use App\Mail\SendgridMail;
use App\User;
use App\UserLIA;
use App\UserCommunity;
use App\PhpFox_activitypoint_statistics;
use App\PhpFox_user_activity;
use App\PhpFox_user_count;
use App\PhpFox_user_field;
use App\PhpFox_user_space;
use App\UserThinkific;
use Carbon\Carbon;
use DateTime;
use Exception;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Str;

trait UpdateGenericClass{

    public static function updateData($uuid)
    {
        return self::where('uuid','like','%'.$uuid.'%')->firstOrFail()
            ->update(request()->all());
    }
    public static function updateDataId($id)
    {
        return self::where('id','like','%'.$id.'%')->firstOrFail()
            ->update(request()->all());
    }

    public static function getGrade($grade, $role, $seccion){
        $grades["PREESCOLAR - PRIMER GRADO"] = 1;
        $grades["PREESCOLAR - SEGUNDO GRADO"] = 2;
        $grades["PREESCOLAR - TERCER GRADO"] = 3;
        $grades["PRIMARIA - PRIMER GRADO"] = 1;
        $grades["PRIMARIA - SEGUNDO GRADO"] = 2;
        $grades["PRIMARIA - TERCER GRADO"] = 3;
        $grades["PRIMARIA - CUARTO GRADO"] = 4;
        $grades["PRIMARIA - QUINTO GRADO"] = 5;
        $grades["PRIMARIA - SEXTO GRADO"] = 6;
        $grades["SECUNDARIA - PRIMER GRADO"] = 1;
        $grades["SECUNDARIA - SEGUNDO GRADO"] = 2;
        $grades["SECUNDARIA - TERCER GRADO"] = 3;

        if (array_key_exists($grade, $grades)) {
            $role_result = $grades[$grade];
        }else{
            return 0;
        }
        if($seccion == "PREESCOLAR" && $role == "ALUMNO" && $role_result > 3){
            return 0;
        }
        return $role_result;
    }

    public static function getRole($role, $seccion){
        if($seccion == "PREESCOLAR" && $role == "MAESTRO"){
            $rol = 7;
        }
        if($seccion == "PRIMARIA" && $role == "MAESTRO"){
            $rol = 4;
        }
        if($seccion == "SECUNDARIA" && $role == "MAESTRO"){
            $rol = 8;
        }
        if($role == "PADRE"){
            $rol = 10;
        }
        if($role == "ADMINISTRADOR ESCUELA LIA"){
            $rol = 3;
        }
        if($seccion == "PREESCOLAR" && $role == "ALUMNO"){
            $rol= 13;
        }
        if($seccion == "PRIMARIA" && $role == "ALUMNO"){
            $rol = 5;
        }
        if($seccion == "SECUNDARIA" && $role == "ALUMNO"){
            $rol = 6;
        }
        if($seccion == "PROFESORSUMMIT2021"){
            $rol = 17;
        }
        if($seccion == "ALUMNOE0"){
            $rol = 18;
        }
        return $rol;
    }

    public static function createPassword( $seccion){
        if ($seccion == 'PREESCOLAR'){
            $password = Str::random(4);

        }else{
            if ($seccion == 'PRIMARIA' || $seccion == 'SECUNDARIA'){
                $password = Str::random(6);

            }
        }
        return $password;
    }

    public static function dataUser($input, $school_id, $passwordSource = null, $tutorId = null, $tutorIdLIA = null, $companyId = null)
    {
        try {
            $user = Auth::user();
            $roleFox = [
                '1' => '1', //Admin - Administrator
                '2' => '2', //Ventas - Registered User
                '3' => '7', //Admin Escuela - Escuela LIA - Director /coordinador
                '4' => '8', //Maestro - MaestroLIA
                '5' => '9', //Alumno - AlumnoLIA
                '10' => '10', //Padre - Papá-EscuelaLIA
                '13' => '14', //Preescolar - AlumnoPreescolarLIA
                '6' => '15', //Alumno Secundaria - AlumnoSecundariaLIA
                '7' => '16', //Maestro Preescolar - MaestroPreescolarLIA
                '8' => '17', //Maestro Secundaria - MaestroSecundariaLIA
                '9' => '18', //Director Escuela - DirectorEscuelaLIA,
                '17' => '19', //ProfesorSummit2021 - ProfesorSummit2021
                '18' => '20' //Metropolitan - Metropolitan
            ];

            // return (["message" => 'Hola', "username" => ""]);
            $role_id= self::getRole($input['tipo_usuario'],$input['seccion']);
            if($user->role_id == 1 || $user->role_id == 2){
                $dataCreate['school_id'] = $school_id;
                $dataCreate['role_id'] = $role_id;
            }else{
                if ($role_id == 4 ||  $role_id == 5 ||  $role_id == 13 || $role_id == 10 || $role_id == 6 || $role_id == 7 || $role_id == 8 | $role_id == 17 || $role_id == 18){
                    $dataCreate['role_id'] = $role_id;
                }else{
                    $dataCreate['role_id'] = 4;
                }
                $dataCreate['school_id'] = $user->school_id;
            }
            if(!is_null($companyId)){
                $dataCreate['company_id'] = $companyId;
            }
            $dataCreate['name'] = $input['nombre'].' '.$input['segundo_nombre'];
            $dataCreate['last_name'] = $input['apellido_paterno'].' '.$input['apellido_materno'];
            $dataCreate['grade'] = self::getGrade($input['grado'], $input['tipo_usuario'],$input['seccion']);
            $dataCreate['email'] = $input['email'];
            $dataCreate['tutor_id'] = $tutorId;

            $password  = $passwordSource ? $passwordSource : self::createPassword($input['seccion']);
            $passwordEncode = bcrypt($password);
            $passwordEncode = str_replace("$2y$", "$2a$", $passwordEncode);
            $dataCreate['password'] = $passwordEncode;

            $firstName = $input['nombre'];
            $lastName = $input['apellido_paterno'];
            $email = $input['email'];
            $username = Str::slug($firstName . $lastName);

            $userDB = \DB::select('Select
                            users.id,
                            users.username,
                            users.name,
                            users.last_name,
                            users.email,
                            users.tutor_id
                            FROM users
                            WHERE users.email = "'.$email.'"
                            LIMIT 1');

            if($userDB){
                if($tutorId && (array)$userDB[0]->tutor_id != $tutorId){
                    \DB::table('users')->where('email', (array)$userDB[0]->email)->update(['tutor_id' => $tutorId]);
                    return (["message" => "Usuario actualizado", "username" => (array)$userDB[0]->username]);
                }
                return (["message" => "El email ya existe", "username" => ""]);
            }
            
            $i = 0;
            while (self::whereUsername($username)->exists()) {
                $i++;
                $username = Str::slug($firstName[0] . $lastName . $i);
            }
            $dataCreate['username'] = $username;

            $now = new DateTime();

            $dataLIA = ([
                'AppUser' =>  $dataCreate['username'],
                'Names' =>  $dataCreate['name'],
                'LastNames' => $dataCreate['last_name'],
                'Email' =>  $dataCreate['email'],
                'Grade' =>  $dataCreate['grade'],
                'Password' => $dataCreate['password'],
                'RoleId' =>  $dataCreate['role_id'] == 7 ? 13 : $dataCreate['role_id'],
                'IsActive' => 1,
                'SchoolId' => $dataCreate['school_id'],
                'SchoolGroupKey'=> 140232,
                'MemberSince'=> $now,
                'CreatorId' => 68,
                'EditorId' => 68,
                'Avatar' => null,
            ]);
            // if(Config::get('app.sync_lia')){
            //     $userLIA = UserLIA::create($dataLIA);
            //     $dataCreate['AppUserId'] = $userLIA->AppUserId;
            // }

            $user = self::create($dataCreate);

            $data = ([
                'username' => $user->username,
                'name' => $user->name,
                'last_name' => $user->last_name,
                'email' => $user->email,
                'grade' => $user->grade,
                'password' => $password
            ]);

            $dataThink = ([
                'first_name' => $user->username,
                'last_name' => $user->last_name,
                'email' => $user->email,
                'password' => $password
            ]);

            $academyUser = new UserThinkific();
            $academyUser = $academyUser->createUser($dataThink);

            $inputUser =  $academyUser;

            if(array_key_exists("errors", $inputUser)){
                $errors['academia']= (array) ["academy" => $inputUser,"id" => $user->id] ;
            }else{
                $groupName = [
                    '4' => 'Maestros LIA', //Maestro
                    '5' => 'Alumnos LIA Primaria', //Alumno Primaria
                    '6' => 'Alumnos LIA Secundaria', //Alumno Secundaria
                    '10' => 'Papás LIA', //Padres
                    '13' => 'Alumnos LIA Preescolar', //Alumno Preescolar
                    '17' => 'profesorsummit2021',
                    '18' => 'Metropolitan'
                ];

                $affected = User::find($user->id);
                $affected->active_thinkific = $inputUser['id'];
                $affected->save();

                $academyGroup = new UserThinkific();
                $academyGroup = $academyGroup->groupAssign($affected->active_thinkific, $groupName[$affected->role_id]);
                $success['academia']= (array) ["academy" => $inputUser,"group_academy" => $academyGroup,"id" => $user->id];
            }

            $dataFox = ([
                'email' => $user->email,
                'full_name' => $user->name . $user->last_name,
                'password' => $password,
                "user_name" => $user->username,
                'user_group_id' => $roleFox[$dataCreate['role_id']],
                'joined' => Carbon::now()->timestamp,
            ]);

            $userCommunity = UserCommunity::create($dataFox)->toArray();
            $userCommunityId = ['user_id' => $userCommunity['user_id']];
            PhpFox_activitypoint_statistics::create($userCommunityId);
            PhpFox_user_activity::create($userCommunityId);
            PhpFox_user_field::create($userCommunityId);
            PhpFox_user_space::create($userCommunityId);
            PhpFox_user_count::create($userCommunityId);

            $lastUserGroup = UserCommunity::all()->last();
            $user->active_phpfox = $userCommunity['user_id'];
            $user->save();

            if(Config::get('app.send_email')) {
                SendEmail::dispatchNow($data);
            }
            return (["message" => "Usuario creado", "username" => $username]);

        } catch (Exception $e) {
            return (["message" => $e->getMessage(), "username" => ""]);
        }
    }

}
