<?php

namespace App\Http\Controllers;

use App\Subject;
use Exception;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\Auth;
use App\CustomSubject;
use App\GroupModels\Group;
use App\DigitalResources;
use App\DigitalResourcesCategories;
use App\SyncModels\GroupUserEnrollment;

class DigitalResourcesController extends ApiController
{
    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function index()
    {
        $user = Auth::user();
        $request = request()->all();

        $filter = [];
            $i = -1;
            if (array_key_exists('id_category', $request) && $request['id_category'] != null) {
                $filter[++$i] = array('digital_resources.id_category', '=',$request['id_category']);
            }
            if (array_key_exists('grade', $request) && $request['grade'] != null) {
                $filter[++$i] = array('digital_resources.grade', '=',$request['grade']);
            }
            if (array_key_exists('id_materia_base', $request) && $request['id_materia_base'] != null) {
                $filter[++$i] = array('digital_resources.id_materia_base', '=',$request['id_materia_base']);
            }
            if (array_key_exists('level', $request) && $request['level'] != null) {
                $filter[++$i] = array('digital_resources.level', '=',$request['level']);
            }
            if (array_key_exists('bloque', $request) && $request['bloque'] != null) {
                $filter[++$i] = array('digital_resources.bloque', '=',$request['bloque']);
            }

        $resource = \DB::table('digital_resources')
        ->join('subjects','digital_resources.id_materia_base','=','subjects.id')
        ->join('digital_resources_categories','digital_resources.id_category','=','digital_resources_categories.id')
        ->select('digital_resources.*','subjects.name as subjects_name', 'digital_resources_categories.name as category_name');

        $resource = $resource->where($filter)->get();
        return $this->successResponse($resource,200);
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


    public function subjectResource(Request $request)
    {
        try{
            $user = Auth::user();

            $input = $request->all();
            $subject = CustomSubject::select('subject_id', 'group_id')->where('id', $input['id'])->first();
            
            // Determina el nivel segun el rol del maestro
            $level = 2;
            if ( $user->role_id == 7 || ($user->level_id == 1 && ($user->role_id == 28 || $user->role_id == 29 || $user->role_id == 30))){ // maestro_preescolar
                $level = 1;
            }else if ( $user->role_id == 8 || ($user->level_id == 3 && ($user->role_id == 28 || $user->role_id == 29 || $user->role_id == 30))){ //  maestro_secundaria
                $level = 3;
            } else {
                // En caso de que el maestro de clase a más de un nivel
                $level_user = GroupUserEnrollment::select('users.role_id')
                ->join('users','group_user_enrollments.user_id','=','users.id')
                ->where('group_user_enrollments.group_id','=',$subject->group_id)
                ->groupBy('users.role_id')
                ->orderByDesc(\DB::raw('COUNT(users.role_id)'))
                ->first();
                if ( $level_user && $level_user->role_id){
                    if ($level_user->role_id == 13 || ($user->level_id == 1 && ($user->role_id == 34 || $user->role_id == 35 || $user->role_id == 36))){ // 13 -> preescolar
                        $level = 1;
                    }else if ( $level_user->role_id == 6 || ($user->level_id == 3 && ($user->role_id == 34 || $user->role_id == 35 || $user->role_id == 36))){ // 6 -> alumno_secundaria
                        $level = 3;
                    }
                }
            }

            $grade = Group::select('grade')->where('id', $subject->group_id)->first();
            $resource = DigitalResources::select('id', 'name', 'url_resource', 'bloque', 'id_category', 'description')->where(
                [
                    ['id_materia_base', '=', $subject->subject_id],
                    ['grade', '=', $grade->grade],
                    ['level', '=', $level]
                ]
            )->get();
            
            return $this->successResponse($resource, 'Resources:', 200);
        }catch(ModelNotFoundException $e){
            return $this->errorResponse('No hay elementos que coincidan', 422);
        }
    }


    /**
     * Store a newly created resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Response
     */
    public function store(Request $request)
    {
        $validator = $this->validateCreateResource();
        if($validator->fails()){
            return $this->errorResponse($validator->messages(), 422);
        }
        $user = Auth::user();
        $input = $request->all();

        $dataResource = ([
            'bloque' => $input['bloque'],
            'grade' => $input['grade'],
            'level' => $input['level'],
            'name' => $input['name'],
            'url_resource' => array_key_exists('url_resource', $input) && $input['url_resource'] ? $input['url_resource'] : "",
            'id_materia_base' => $input['id_materia_base'],
            'id_category' => $input['id_category'],
            'description' => array_key_exists('description', $input) && $input['description'] ? $input['description'] : "",
        ]);

        $resourceDB = DigitalResources::create($dataResource);
        return $this->successResponse('Se ha creado el recurso con exito', 200

    );

    }

    /**
     * Display the specified resource.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function show($id)
    {
        try {
            $resourceDB = DigitalResources::findOrFail($id);
            return $this->successResponse($resourceDB);
            }catch (ModelNotFoundException $e){
                return $this->errorResponse('El Recurso es invalido', 422);
            }
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
     * @return \Illuminate\Http\Response
     */
    public function update(Request $request, $id)
    {
        if(!DigitalResources::where('id',$id)->exists()){
            return $this->errorResponse($id,422);
        }

        $resource = DigitalResources::findOrFail($id);
        $user = Auth::user();
        $input = $request->all();

        $dataResource = ([
            'bloque' => array_key_exists('bloque', $input) && $input['bloque'] ? $input['bloque'] : $resource->bloque,
            'grade' => array_key_exists('grade', $input) && $input['grade'] ? $input['grade'] : $resource->grade,
            'level' => array_key_exists('level', $input) && $input['level'] ? $input['level'] : $resource->level,
            'name' => array_key_exists('name', $input) && $input['name'] ? $input['name'] : $resource->name,
            'url_resource' => array_key_exists('url_resource', $input) && $input['url_resource'] ? $input['url_resource'] : $resource->url_resource,
            'id_materia_base' => array_key_exists('id_materia_base', $input) && $input['id_materia_base'] ? $input['id_materia_base'] : $resource->id_materia_base,
            'id_category' => array_key_exists('id_category', $input) && $input['id_category'] ? $input['id_category'] : $resource->id_category,
            'description' => array_key_exists('description', $input) && $input['description'] ? $input['description'] : $resource->description,
        ]);

        $resourceUpt = $resource->update($dataResource);
        $resourceUpdated = DigitalResources::findOrFail($id);

        return $this->successResponse($resourceUpdated,'Se ha actualizado el recurso con exito', 200);
        
    }

    /**
     * Remove the specified resource from storage.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function destroy($id)
    {
        try {
            DigitalResources::findOrFail($id);
            $resourceDlt = DigitalResources::destroy($id);

            return $this->successResponse($resourceDlt, "Se ha eliminado el recurso exitosamente", 200);
        }catch (ModelNotFoundException $e){
            return $this->errorResponse('Recurso inválido: No hay elementos que coincidan', 422);
        }
    }

    public function getCategories()
    {
        $user = Auth::user();
        $request = request()->all();

        $category = DigitalResourcesCategories::all();
        return $this->successResponse($category, 'Resources:', 200);

    }

    public function validateCreateResource(){
        $messages = [
            'required.name' => 'El campo nombre es requirido.',
        ];

        return Validator::make(request()->all(), [
                'bloque' => 'required|max:255',
                'grade' => 'required|max:255',
                'level' => 'required|max:255',
                'name' => 'required|max:255',
                'url_resource' => 'required|max:255',
                'id_materia_base' => 'required|max:255',
                'id_category' => 'required|max:255',
                'description' => 'max:400',
            ], $messages);
    }

    function getResources(Request $request){
        try{
            $user = Auth::user();
            $input = $request->all();
            
            if ($request->globalSchooling == 1){
                $resource = DigitalResources::where(
                    [
                        ['id_materia_base', '=', $request->id], 
                    ]
                )
                ->join('subjects','digital_resources.id_materia_base','=','subjects.id')
                ->join('digital_resources_categories','digital_resources.id_category','=','digital_resources_categories.id')
                ->select('digital_resources.*','subjects.name as subjects_name', 'digital_resources_categories.name as category_name')
                ->get();
                return $this->successResponse($resource, 'Resources:', 200);
            }else{
                $resource = DigitalResources::where(
                    [
                        ['id_materia_base', '=', $request->id], 
                        ['grade', '=', $user->grade]
                    ]
                )
                ->join('subjects','digital_resources.id_materia_base','=','subjects.id')
                ->join('digital_resources_categories','digital_resources.id_category','=','digital_resources_categories.id')
                ->select('digital_resources.*','subjects.name as subjects_name', 'digital_resources_categories.name as category_name')
                ->get();
                return $this->successResponse($resource, 'Resources:', 200);

            }
        }catch(ModelNotFoundException $e){
            return $this->errorResponse('No hay elementos que coincidan', 422);
        }
    } 
}