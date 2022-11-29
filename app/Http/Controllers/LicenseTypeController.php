<?php

namespace App\Http\Controllers;

use App\LicenseType;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\Auth;

class LicenseTypeController extends ApiController
{
    /**
     * Display a listing of the resource.
     */
    public function index()
    {
        $licenses = LicenseType::all();
        return $this->successResponse($licenses);
    }

    public function tipoMembresia()
    {
        $user = Auth::user();
        if($user->role_id == 28 || $user->role_id == 29 || $user->role_id == 30){
            $licenses = LicenseType::where('category_id','maestros')->get();
        }elseif($user->role_id == 31 || $user->role_id == 32 || $user->role_id == 33){
            $licenses = LicenseType::where('category_id','padres')->get();
        }else{
            $licenses = "no es padre o maestro de membresía";
        }
        return $this->successResponse($licenses);
    }

    /**
     * Store a newly created resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     */
    public function store(Request $request)
    {
        $validator = $this->validateTypeLicense();
        if($validator->fails()){
            return $this->errorResponse($validator->messages(), 422);
        }

        $license = LicenseType::create($request->all());

        return $this->successResponse($license);
    }

    /**
     * Display the specified resource.
     */
    public function show($id)
    {
        try {
            $licenseType = LicenseType::findOrFail($id);
            return $this->successResponse($licenseType);
        }catch(ModelNotFoundException $e){
            return $this->errorResponse('Tipo de licencia invalido', 422);
        }
    }

    /**
     * Update the specified resource in storage.
     */
    public function update($id)
    {
        try {
            LicenseType::firstOrFail($id);

            $type = LicenseType::updateDataId($id);
            return $this->successResponse($type,'Se ha actualizado el tipo de licencia', 201);

        }catch(ModelNotFoundException $e){
            return $this->errorResponse('Tipo de lincencia invalido', 422);
        }
    }

    /**
     * Remove the specified resource from Type license.
     */
    public function destroy($id)
    {
        try {
            LicenseType::firstOrFail($id);
            $type = LicenseType::destroy($id);

            return $this->successResponse($type, 'Se ha eliminado correctamente', 201);
        }catch(ModelNotFoundException $e){
            return $this->errorResponse('Tipo de lincencia invalido', 422);
        }
    }

    public function validateTypeLicense(){
        $messages = [
            'requiered' => 'Es necesario agregar una descripción del tipo de licencia',
        ];

        return Validator::make(request()->all(), [
            'description_license_type' => 'required',
        ], $messages);
    }
}
