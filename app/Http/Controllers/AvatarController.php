<?php

namespace App\Http\Controllers;

use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\Auth;
use App\Avatar;
use App\User;
use App\AvatarUsers;

class AvatarController extends ApiController
{
    /**
     * Display a listing of the resource.
     *
     * @return JsonResponse
     */
    public function index()
    {
        $user = Auth::user();

        if ($user->role->id == '13') {

            $avatar = Avatar::where('type', 1)->get();
        } else {
            $avatar = Avatar::where('type', 2)->get();
        }

        return $this->successResponse($avatar);
    }

    /**
     * Show the form for creating a new resource.
     *
     * @return JsonResponse
     */
    public function syncAvatar()
    {
        try {
            $users = User::all();

            foreach ($users as $user) {
                $data = [
                    'user_id' => $user->id,
                    'avatar_id' => 11,
                    'custom_name' => 'Franky',
                    'avatar_path' => 'assets/images/avatars/bootFace.png'
                ];
                AvatarUsers::firstOrCreate($data);
            }
            return $this->successResponse('Operación exitosa', 200);
        } catch (ModelNotFoundException $e) {
            return $this->errorResponse('Operación denegada', 422);
        }
    }

    /**
     * Store a newly created resource in storage.
     *
     * @param Request $request
     * @return JsonResponse
     */
    public function store(Request $request, $uuid)
    {
        $user = User::where('uuid', $uuid)->firstOrfail();

        try {
            $validator = $this->validateAvatar($request->all());
            if ($validator->fails()) {
                return $this->errorResponse($validator->messages(), 422);
            }
            // $user = Auth::user();
            $request = request()->all();

            $data = ([
                'user_id' => $user->id,
                'avatar_id' => $request['avatar_id'],
                'avatar_name' => $request['avatar_name'],
                // 'avatar_name' => array_key_exists('avatar_name', $request) ? $request['avatar_name'] : null,
            ]);
            AvatarUsers::create($data);

            return $this->successResponse('opreración exitosa', 200);

        } catch (ModelNotFoundException $e) {
            return $this->errorResponse('Operación denegada', 422);
        }
    }

    /**
     * Update the specified resource in storage.
     *
     * @param Request $request
     * @param $id
     * @return JsonResponse
     */
    public function update(Request $request, $id)
    {
        try {

            $user = User::find($id);
            $input = $request->all();
            if($user->role_id == 13){
                $avatarImage = [
                    '0' => 'assets/images/avatars/laloPreFace.png',
                    '1' => 'assets/images/avatars/liaPreFace.png',
                ];

                $avatarId = [
                    '0' => 9,
                    '1' => 10,
                ];
            }else{
                $avatarImage = [
                    '0' => 'assets/images/avatars/laloFace.png',
                    '1' => "assets/images/avatars/liaFace.png",
                    '2' => 'assets/images/avatars/avatarFace4.png',
                    '3' => 'assets/images/avatars/avatarFace1.png',
                    '4' => 'assets/images/avatars/avatarFace3.png',
                    '5' => 'assets/images/avatars/avatarFace0.png',
                    '6' => 'assets/images/avatars/avatarFace2.png',
                    '7' => 'assets/images/avatars/bootFace.png',
                ];

                $avatarId = [
                    '0' => 1,
                    '1' => 2,
                    '2' => 4,
                    '3' => 5,
                    '4' => 6,
                    '5' => 7,
                    '6' => 8,
                    '7' => 11,
                ];
            }
            if(AvatarUsers::where('user_id', $user->id)->exists()){
                $userAvatar = AvatarUsers::where('user_id', $user->id)->firstOrfail();

                $data['avatar_id'] = $avatarId[$input['avatarId']];
                if (!empty($input['customName'])) {
                    $data['custom_name'] = $input['customName'];
                }
                $data['avatar_path'] = $avatarImage[$input['avatarId']];
                $userAvatar->update($data);
            }else{
                $data = [
                    'user_id' => $user->id,
                    'avatar_id' => $avatarId[$input['avatarId']],
                    'custom_name' => array_key_exists('customName',$input) ? $input['customName'] : "",
                    'avatar_path' => $avatarImage[$input['avatarId']]
                ];
                AvatarUsers::create($data);
            }

            return $this->successResponse($data, 'Se actualizo con exito', 200);

        } catch (ModelNotFoundException $e) {
            return $this->errorResponse('No se ha podido actulizar', 422);
        }
    }

    /**
     * Remove the specified resource from storage.
     *
     * @param int $id
     * @return \Illuminate\Http\Response
     */
    public function destroy($id)
    {
        //
    }

    public function validateAvatar()
    {
        $messages = [
            // 'user_id.unique' => 'El campo ya existe.',
            'avatar_id.required' => 'El id del avatar es requirido.',
            'avatar_name.required' => 'El campo nombre es requirido.',

        ];

        return Validator::make(request()->all(), [
            // 'user_id' => 'required|unique:users,id',
            'avatar_id' => 'required',
            'avatar_name' => 'required',

        ], $messages);
    }
}
