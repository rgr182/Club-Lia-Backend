<?php

namespace App\Http\Controllers;

use App\Mail\SendgridMail;
use App\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Str;
use mysql_xdevapi\Exception;

class UserImportController extends Controller
{
    /**
     * Store a newly created resource in storage.
     *
     * @param \Illuminate\Http\Request $request
     * @return string
     */
    public function store(Request $request)
    {
        try {
            $user = Auth::user();
            $school_id =  $user->school_id;
            $password = null;
            $tutor_id = null;
            $tutorIdLIA = null;
            $input = request()->all();
            $company_id = null;

            if($user->role_id == 1 || $user->role_id == 2){
                $school_id = $request->input('school_id');
            }
            if (array_key_exists('password', $input)) {
                $password  = $input['password'];
            }
            if (array_key_exists('company_id', $input)) {
                $company_id  = $input['company_id'];
            }
            $data = $request->input('data');
            $i = -1;
            foreach ($data as $obj) {
                foreach ($obj as $key => $value) {

                    $insertArr[Str::slug($key, '_')] = $value;
                }
                if($insertArr['nombre_padre_madre_o_tutor'] && $insertArr['mail_padre']){
                    $tutorId = \DB::table('users')->where('email', [$insertArr['mail_padre']])->where('role_id', 10)->get('id')->first();

                    if(!$tutorId){

                        $tutorName = explode(' ',$insertArr['nombre_padre_madre_o_tutor']);

                        $tutor['tipo_usuario'] = 'PADRE';
                        $tutor['nombre'] = $tutorName[0];
                        $tutor['username'] = null;
                        $tutor['apellido_paterno'] = implode(' ',array_slice($tutorName, 1));
                        $tutor['segundo_nombre'] = null;
                        $tutor['apellido_materno'] = null;
                        $tutor['email'] = $insertArr['mail_padre'];
                        $tutor['seccion'] = $insertArr['seccion'];
                        $tutor['grado'] = $insertArr['grado'];
                        $tutor['school_id'] = null;
                        $tutor['nombre_padre_madre_o_tutor'] = null;
                        $tutor['mail_padre'] = null;
                        $tutor['result'] = $insertArr['result'];

                        $resp = $tutor;
                        $respCreate = User::dataUser($tutor, $school_id, $password);
                        $resp ['result'] = $respCreate["message"];
                        $resp ['username'] = $respCreate["username"];
                        $result [++$i] = (array) $resp;
                        if ($respCreate["username"] != "") {
                            $tutorId = \DB::table('users')->where('email', [$insertArr['mail_padre']])->where('role_id', 10)->get('id')->first();
                            $tutor_id = $tutorId->id;
                        }
                    } else {
                        $tutor_id = $tutorId->id;
                    }

                }

                $resp = $obj;
                $respCreate = User::dataUser($insertArr, $school_id, $password, $tutor_id, $tutorIdLIA, $company_id);
                //\DB::table('users')->where('username', $respCreate["username"])->update(['tutor_id' => $tutorId->id]);
                $resp ['result'] = $respCreate["message"];
                $resp ['username'] = $respCreate["username"];
                $result [++$i] = (array) $resp;
                $tutor_id = null;
            }

            return response((array) $result,200);

        } catch (Exception $e) {

            return ("Error al importar usuarios");
        }
    }
}
