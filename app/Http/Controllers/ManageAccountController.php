<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use App\User;
use App\UserCommunity;
use App\UserLIA;
use Ramsey\Uuid\Uuid;
use Illuminate\Support\Facades\Config;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use \Illuminate\Support\Facades\Validator;

class ManageAccountController extends ApiController
{
    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function index()
    {
        //
        try {
            $user = Auth::user();

            $account = User::where('id', $user->id)->firstOrFail();

            if(in_array($account->role_id, [10,31,32,33])){
                $childrens = User::where('tutor_id', $account->id)->get();
                $account->childrens_id = $childrens;
            }

            return $this->successResponse($account);

        } catch (ModelNotFoundException $e){
            return $this->errorResponse('No se pudo recuperar la información', 422);
        }
    }

    /**
     * Show the form for creating a new resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function create()
    {
        //
    }

    /**
     * Store a newly created resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Response
     */
    public function store(Request $request)
    {
        //
    }

    /**
     * Display the specified resource.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function show($id)
    {
        //
    }

    /**
     * Show the form for editing the specified resource.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function edit($id)
    {
        //
    }

    /**
     * Update the specified resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  int  $id
     * @return \Illuminate\Http\JsonResponse
     */
    public function update(Request $request, $uuid)
    {
        //
        try {

            $request = request()->all();

            $validator = Validator::make($request, [
                'name' => 'required',
                'last_name' => 'required',
                'email' => 'required|email',
            ]);
            if ($validator->fails()) {
                $error["message"] = "Información Invalida.";
                $error["errors"] =$validator->errors();
                return response()->json(['error' => $error], 200);
            }

            $user = User::where('uuid', $uuid)->firstOrFail();

            $input = $request;

            $dataCreate['name'] = $input['name'];
            $dataCreate['last_name'] = $input['last_name'];
            $dataCreate['email'] = $input['email'];

            $dataLIA = ([
                'Names' =>  $dataCreate['name'],
                'LastNames' => $dataCreate['last_name'],
                'Email' =>  $dataCreate['email'],
            ]);

            if (array_key_exists('password', $input) && $input['password']) {
                $password  = $input['password'];
                $passwordEncode = bcrypt($password);
                $passwordEncode = str_replace("$2y$", "$2a$", $passwordEncode);
                $dataCreate['password'] = $passwordEncode;
                $dataLIA['password'] = $passwordEncode;
            }

            if (User::where([['email', '=', $dataCreate['email']], ['uuid', '!=', $uuid]])->exists()) {
                return $this->errorResponse('El correo ya existe.', 422);
            }

            if(Config::get('app.sync_lia') && !(in_array($user->role_id, [19,20,21,22,23,24,25,26,27,28,29,30,31,32,33,34,35,36]))) {
                $userLIA = UserLIA::where('AppUserId','=',$user->AppUserId)->firstOrFail();
                if($userLIA){
                    $userLIA->update($dataLIA);
                }
            }

            User::where('uuid', $uuid)->firstOrFail()->update($dataCreate);

            $dataFox = ([
                'email' => $dataCreate['email'],
                'full_name' => $dataCreate['name'] .' '. $dataCreate['last_name'],
            ]);

            UserCommunity::where('user_id', '=', $user->active_phpfox)->firstOrFail()->update($dataFox);

            $success['message'] = 'Usuario Actualizado';
            $success['code'] = 200;
            return $this->successResponse($success,200);

        } catch (ModelNotFoundException $exception){
            $error["code"] = '422';
            $error["exception"] = "Error al actualizar el usuario";
            $error["message"] = "Error al actualizar el usuario";

            return $this->errorResponse(['error' => $error], 422);
        }
    }

    /**
     * Remove the specified resource from storage.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function destroy($id)
    {
        //
    }
}
