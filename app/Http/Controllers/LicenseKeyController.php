<?php

namespace App\Http\Controllers;

use App\LicenseKey;
use Exception;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;

class LicenseKeyController extends ApiController
{
    /**
     * Display a listing of the resource.
     */
    public function index()
    {
        $licenses = LicenseKey::all();
        return $this->successResponse($licenses);
    }

    /**
     * Store a newly created resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     */
    public function store(Request $request)
    {
        try {
            $validator = $this->validateKeyLicense();
            if($validator->fails()){
                return $this->errorResponse($validator->messages(), 422);
            }

            $license = LicenseKey::create($request->all());
            return $this->successResponse($license, "Se ha asignado la llave", 201);
        }catch (Exception $exception){
            return $this->errorResponse('Hubo problemas para crear la licencia', 422);
        }
    }

    /**
     * Display the specified resource.
     *
     */
    public function show($id)
    {
        try {
            $licenseKey = LicenseKey::findOrfail($id);
            return $this->successResponse($licenseKey);
        }catch (ModelNotFoundException $exception){
            return $this->errorResponse('Llave invalida no hay elemento sque coincidan');
        }
    }

    /**
     * Update the specified resource in storage.
     */
    public function update($id)
    {
        try {
            LicenseKey::findOrFail($id);
            $key = LicenseKey::updateDataId($id);
            return $this->successResponse($key, "Se ha actualizado la licencia existosamente", 201);
        }catch (ModelNotFoundException $e){
            return $this->errorResponse('Llave invalida: No hay elementos que coincidan', 422);
        }
    }

    /**
     * Remove the specified Key License from storage.
     */
    public function destroy($id)
    {
        try {
            LicenseKey::findOrFail($id);
            $keyDlt = LicenseKey::destroy($id);

            return $this->successResponse($keyDlt, "Se ha eliminado la licencia", 200);
        }catch (ModelNotFoundException $e){
            return $this->errorResponse('Llave invalida: No hay elementos que coincidan', 422);
        }
    }

    public function validateKeyLicense(){
        $messages = [
            'license_id.required' => 'Necesita asignar una licencia',
            'user_id.required' => 'Es necesario asignar un usuario valido'
        ];

        return Validator::make(request()->all(), [
            'license_id' => 'required',
            'user_id' => 'required',
        ], $messages);
    }
}
