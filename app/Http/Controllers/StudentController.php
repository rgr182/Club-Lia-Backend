<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Validator;
use App\User;
use Auth;

class StudentController extends Controller
{
    public function updateProfile(Request $request){
        $rules = [
            'user_id' => 'required',
            'name' => 'required|string',
            'last_name' => 'required|string',
            'email' => 'required|email',
            'level_id' => 'required',
            'grade' => 'required'
		];
        $messages = [
            "required" => "El campo :attribute es obligatorio.",
            'unique' => 'El :attribute ya esta registrado'
        ];
        $attributes = [
            'user_id' => 'User ID',
            'name' => 'Nombre',
            'last_name' => 'Apellido',
            'email' => 'Email',
            'level_id' => 'Nivel',
            'grade' => 'Grado'
        ];
		$validator = Validator::make($request->all(), $rules, $messages);
        $validator->setAttributeNames($attributes);
        if ($validator->fails()) {
            return response()->json([
                'msg'=>$validator->errors()->first(),
            ], 422);
        }else{
            $user = Auth::user();
            $child = User::where('id', $request->user_id)
                ->first();
            if(is_null($child)){
                return response()->json([
                    'msg' => 'Este hijo no esta registrado'
                ], 422);
            }
            if($child->tutor_id !== $user->id){
                return response()->json([
                    'msg' => 'Este hijo no existe o no esta registrado como tu hijo'
                ], 422);
            }else{
                $child->update([
                    'name' => $request->name,
                    'last_name' => $request->last_name,
                    'email' => $request->email,
                    'level_id' => $request->level_id,
                    'grade' => $request->grade
                ]);
                return response()->json([
                    'msg' => 'Los datos del hijo se actualizaron correctamente',
                    'child' => $child
                ]);
            }
        }
    }
}