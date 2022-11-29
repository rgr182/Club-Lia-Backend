<?php

namespace App\Http\Controllers;

use App\Periodo;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;

class PeriodoController extends ApiController
{
    /**
     * Display a listing of the resource.
     * @return \Illuminate\Http\JsonResponse
     */
    public function index()
    {
        $periodos = Periodo::all();
        return $this->successResponse($periodos);
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {
        try {
            $validator = $this->validatePeriod();
            if($validator->fails()){
                return $this->errorResponse($validator->messages(), 422);
            }

            $period = Periodo::create($request->all());
            return $this->successResponse($period, "Se ha asignado la llave", 201);
        }catch (ModelNotFoundException $exception){
            return $this->errorResponse('Periodo inválido: No hay elementos que coincidan', 422);
        }
    }

    /**
     * Display the specified resource.
     * @return \Illuminate\Http\JsonResponse
     */
    public function show($id)
    {
        try {
            $period = Periodo::findOrfail($id);
            return $this->successResponse($period);
        }catch (ModelNotFoundException $exception){
            return $this->errorResponse('Periodo inválido: No hay elementos que coincidan', 422);
        }
    }

    /**
     * Update the specified resource in storage.
     * @return \Illuminate\Http\JsonResponse
     */
    public function update($id)
    {
        try {
            Periodo::findOrFail($id);
            $period = Periodo::updateDataId($id);
            return $this->successResponse($period, "Se ha actualizado el perido existosamente", 201);
        }catch (ModelNotFoundException $e){
            return $this->errorResponse('Periodo inválido: No hay elementos que coincidan', 422);
        }
    }

    /**
     * Remove the specified resource from storage.
     *
     * @param int  $id
     * @return \Illuminate\Http\JsonResponse
     */
    public function destroy($id)
    {
        try {
            Periodo::findOrFail($id);
            $periodDlt = Periodo::destroy($id);

            return $this->successResponse($periodDlt, "Se ha eliminado el periodo", 200);
        }catch (ModelNotFoundException $e){
            return $this->errorResponse('Periodo inválido: No hay elementos que coincidan', 422);
        }

    }

    public function validatePeriod(){
        $messages = [
            'periodo.required' => 'El campo periodo es requerido',
            'name.required' => 'El campo nombre es requerido',
            'description.required' => 'El campo descripción es requerido'
        ];

        return Validator::make(request()->all(), [
            'periodo' => 'required',
            'name' => 'required',
            'description' => 'required'
        ], $messages);
    }
}
