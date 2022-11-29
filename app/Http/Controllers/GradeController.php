<?php

namespace App\Http\Controllers;

use App\Grade;
use Exception;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;

class GradeController extends ApiController
{
    /**
     * Display a listing of the resource.
     *
     * @return JsonResponse
     */
    public function index()
    {
        $grades = Grade::all();
        return $this->successResponse($grades);
    }

    /**
     * Store a newly created resource in storage.
     *
     * @param  Request  $request
     * @return JsonResponse
     */
    public function store(Request $request)
    {
        try {
            $validator = $this->validateGrade();
            if($validator->fails()){
                return $this->errorResponse($validator->messages(), 422);
            }

            $grade = Grade::create($request->all());
            return $this->successResponse($grade, "Se ha creado un nuevo grado", 201);
        }catch (Exception $exception){
            return $this->errorResponse('No se ha creado el grado debido a un problema', 500);
        }
    }

    /**
     * Display the specified resource.
     *
     * @param  int $id
     * @return JsonResponse
     */
    public function show($id)
    {
        try {
            $licenseType = Grade::find($id);
            return $this->successResponse($licenseType);
        } catch (ModelNotFoundException $e) {
            return $this->errorResponse('Grado inválido: No hay elementos que coincidan', 422);
        }
    }

    /**
     * Update the specified resource in storage.
     *
     * @return JsonResponse
     */
    public function update(Request $request, $id)
    {
        try {
            Grade::findOrFail($id);
            $grade = grade::updateDataId($id);
            return $this->successResponse($grade, "Se ha actualizado el perido existosamente", 201);
        }catch (ModelNotFoundException $e){
            return $this->errorResponse('Grado inválido: No hay elementos que coincidan', 422);
        }
    }

    /**
     * Remove the specified resource from storage.
     *
     * @param  int  $id
     * @return JsonResponse
     */
    public function destroy($id)
    {
        try {
            Grade::findOrFail($id);
            $gradeDlt = Grade::destroy($id);

            return $this->successResponse($gradeDlt, "Se ha eliminado el grado exitosamente", 200);
        }catch (ModelNotFoundException $e){
            return $this->errorResponse('Grado inválido: No hay elementos que coincidan', 422);
        }
    }

    public function validateGrade(){
        $messages = [
            'grade_number.required' => 'El campo grado es requerido',
            'grade_name.required' => 'El campo nombre es requerido',
            'school_level.required' => 'El campo nivel escolar es requerido'
        ];

        return Validator::make(request()->all(), [
            'grade_number' => 'required',
            'grade_name' => 'required',
            'school_level' => 'required'
        ], $messages);
    }
}
