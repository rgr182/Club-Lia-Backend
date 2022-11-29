<?php

namespace App\Http\Controllers;

use App\License;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;

class LicenseController extends ApiController
{
    /**
     * Display a listing of the resource.
     */
    public function index()
    {
        $licenses = License::all();
        return  $this->successResponse($licenses);
    }

    /**
     * Store a newly created resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     */
    public function store(Request $request)
    {
        try {
            $validator = $this->validateLicense();
            if($validator->fails()){
                return $this->errorResponse($validator->messages(), 422);
            }

            $license = License::create($request->all());

            return $this->successResponse($license,'Se ha creado una nueva licencia', 201);

        }catch (Exception $exception){
            return $this->errorResponse('Hubo problemas para crear la licencia', 422);
        }
    }

    /**
     * Display the specified resource.
     *
     * @param  \App\License  $license
     * @param  uuid $id
     */
    public function show($id)
    {
        try {
            $licenseType = License::find($id);
            return $this->successResponse($licenseType);
        } catch (ModelNotFoundException $e) {
            return $this->errorResponse('Licencia invalida: No hay elementos que coincidan', 422);
        }
    }

    /**
     * Update the specified resource in storage.
     */
    public function update($id)
    {
        try {
            License::firstOrFail($id);
            $license = License::updateDataId($id);
            return $this->successResponse($license,'Se ha actualizado la licencia', 201);
        }catch (ModelNotFoundException $e){
            return $this->errorResponse('Licencia invalida: No hay elementos que coincidan', 422);
        }
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy($id)
    {
        try {
            License::findOrFail($id);
            $licenseDlt = License::destroy($id);

        return $this->successResponse($licenseDlt, 'Se ha eliminado con exito la licencia');

        } catch (ModelNotFoundException $ex) { // User not found
            return $this->errorResponse('Licencia invalida: No hay elementos que coincidan', 422);
        }

    }

    public function validateLicense(){
        $messages = [
            'titular.required' => 'El campo titular es requerido.',
            'email.required' => 'El correo electronico es requerido.',
            'license_type_id.required' => 'Es necesario seleccionar un tipo de licencia',
        ];

        return Validator::make(request()->all(), [
            'titular' => 'required',
            'email_admin' => 'required|email',
            'license_type_id' =>'required',
            'studens_limit' => 'required',
        ], $messages);
    }
}
