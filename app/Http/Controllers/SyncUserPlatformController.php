<?php

namespace App\Http\Controllers;

use App\Jobs\UserGenericRegister;
use App\PhpFox_activitypoint_statistics;
use App\PhpFox_user_activity;
use App\PhpFox_user_count;
use App\PhpFox_user_field;
use App\PhpFox_user_space;
use App\SyncUser;
use App\User;
use App\UserCommunity;
use App\UserPhpFox;
use App\UserThinkific;
use Carbon\Carbon;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Validator;
use Illuminate\Http\Request;

class SyncUserPlatformController extends ApiController
{
    /**
     * Sync users in all the platforms
     */
    public function syncUserplatform()
    {
        $user = new SyncUser();
        $user = $user->transferUsers();
        return $user;
    }

    public function syncUserCommunity()
    {
        $input = request()->all();

        $inactive = 0;

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
            '9' => '18', //Director Escuela - DirectorEscuelaLIA
            '17' => '19', //ProfesorSummit2021 - ProfesorSummit2021
            '18' => '20', //AlumnoE0 - Metropolitan
            '19' => '21', //AlumnoE1 - AlumnoE1
            '20' => '22', //AlumnoE2 - AlumnoE1
            '21' => '23', //AlumnoE3 - AlumnoE1
            '22' => '24', //MaestroE1 - MaestroE1
            '23' => '25', //MaestroE2 - MaestroE2
            '24' => '26', //MaestroE3 - MaestroE3
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

        $results = User::where([
            ['role_id', '>', 1],
            ['active_phpfox', '=', $inactive]
        ])->offset($input["offset"])->limit($input["limit"])->get();

        $i = 0;
	
        if ($results->isEmpty()) {
            return ['message' => 'No hay usuarios por sincronizar'];
        } else {
            foreach ($results as $obj) {
                $syncUser = $obj;

		if($syncUser->role_id != 37){
			$dataFox = ([
			    'email' => $syncUser->email,
                      	    'full_name' => $syncUser->name . ' ' . $syncUser->last_name,
                    	    "user_name" => $syncUser->username,
                    	    "user_group_id" => $roleFox[$syncUser->role_id],
                    	    "joined" => Carbon::now()->timestamp
	    		]);
		}
		
                if (UserCommunity::where([['email', '=', $syncUser->email]])->exists()) {
                    $count[++$i] = (array)["message" => 'El correo electronico ya esta asignado', "id" => $syncUser->id, "email"=> $syncUser->email];
                } else {
                    //$user = new UserPhpFox();
                    //$userCommunity = $user->createUser($dataFox);
                    $userCommunity = UserCommunity::create($dataFox)->toArray();

		            $userCommunityId = ['user_id' => $userCommunity['user_id']];
                    PhpFox_activitypoint_statistics::create($userCommunityId);
                    PhpFox_user_activity::create($userCommunityId);
                    PhpFox_user_field::create($userCommunityId);
                    PhpFox_user_space::create($userCommunityId);
                    PhpFox_user_count::create($userCommunityId);

                    if (!empty($userCommunity)) {
                        $affected = User::find($syncUser->id);
                        $affected->active_phpfox = $userCommunity["user_id"];
                        $affected->save();
                        $count[++$i] = (array)["comunidad" => $userCommunity, "id" => $syncUser->id];
                    } else {
                        if ($userCommunity["status"] === 'failed') {
                            $count[++$i] = (array)["comunidad" => $userCommunity, "id" => $syncUser->id];
                        }
                    }
                }

            }
            return $this->successResponse(["usuarios" => $count]);
        }
    }

    public function syncUserAcademy(){

        $input = request()->all();
        $inactive = 0;

        $results = User::where([
            ['role_id', '=', 5],
            ['active_thinkific' ,'=', $inactive]
        ])->offset($input["offset"])->limit($input["limit"])->get();

        $i = 0;

        if($results->isEmpty()){
            return ['message' => 'No hay usuarios por sincronizar'];
        }
        else{
            foreach ($results as $obj) {
                $syncUser = $obj;

                $dataThink = ([
                    'first_name' =>$syncUser->name,
                    'last_name' => $syncUser->last_name,
                    'email' => $syncUser->email,
                    'password' => 'clublia'
                ]);

                $academyUser = new UserThinkific();
                $academyUser = $academyUser->createUser($dataThink);

                $inputuser =  $academyUser;

                if(array_key_exists("errors", $inputuser)){
                    $count[++$i]= (array) ["academy" => $inputuser,"id" => $syncUser->id] ;
                }else{
                    $groupName = [
                        '4' => 'Maestros LIA', //Maestro
                        '5' => 'Alumnos LIA Primaria', //Alumno Primari
                        '13' => 'Alumnos LIA Preescolar', //Alumno Preescolar
                        '17' => 'profesorsummit2021',
                        '18' => 'Metropolitan'
                    ];

                    $affected = User::find($syncUser->id);
                    $affected->active_thinkific = $inputuser['id'];
                    $affected->save();

                    $academyGroup = new UserThinkific();
                    $academyGroup = $academyGroup->groupAssign($affected->active_thinkific, $groupName[$affected->role_id]);
                    $count[++$i]= (array) ["academy" => $inputuser,"group_academy" => $academyGroup,"id" => $syncUser->id];
                }
            }
        }
        return (["usuarios" => $count]);
    }

    /**
     * Update the specified resource in storage in all platforms.
     *
     * @param \Illuminate\Http\Request $request
     * @param \App\SyncUser $syncUser
     * @return \Illuminate\Http\JsonResponse
     */
    public function updateUser($id)
    {
        $user = new SyncUser;
        $res = $user->update($id);
        return $res;
    }

    public function validateUserCommunity()
    {
        $messages = [
            'unique.email' => 'El correo electrónico ya esta asignado',
        ];

        return Validator::make(request()->all(), [
            'email' => 'required|unique'
        ], $messages);
    }

    public function updateRole(){
        $users = User::select('active_phpfox')->where([
            ['role_id', '=', 13],
            ['active_phpfox', '>', 0]
        ])->get();

        $userP = UserCommunity::whereIn('user_id', $users)->update(['user_group_id' => 14]);;

        return $userP;
    }

    public function rolePrincipito() {

        try {

        $users = User::where([
            ['school_id', '=', 98], ['role_id', '=', 5]
        ])->get();

        $studentsP = [];

        foreach($users as $user){
            $studentP = UserCommunity::where('user_id', $user->active_phpfox)->firstOrFail();
            $studentP->user_group_id = 51;
            $studentP->save();

            array_push($studentsP, $studentP);

        }
        return $this->successResponse($studentsP, 'Se han modificado los usuarios correctamente', 200);

        }catch (\Exception $exception){
            return $this->errorResponse('Hubo un problema al actualizar la información', 422);
        }

    }
}
