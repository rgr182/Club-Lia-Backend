<?php

namespace App\Http\Controllers;

use App\Resource;
use App\Subject;
use App\DigitalResources;
use App\CustomSubject;
use App\GroupModels\Group;
use App\DigitalResourcesCategories;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Mockery\Exception\InvalidOrderException;

class ResourceController extends ApiController
{
    public function getResource()
    {
        try {

            $user = Auth::user();
            $subjects =  Subject::where('teacher_id', $user->id)->get();

            foreach ($subjects as $subject){
                $resources = Resource::where('subject_id',$subject->id)->get();

            }
            return $this->successResponse($resources,200);

        }catch (ModelNotFoundException $e){
            return $this->errorResponse("Error al actualizar el usuario" , 422);
        }
    }

    public function storeResource(Request $request, $id){
        try {

            $fileName = strtr($request->file->getClientOriginalName(), " ", "_");
            $fileStore = time().'_'.$fileName;
            $filePath = $request->file('file')->storeAs('resources', $fileStore, 'public');
            $dataResource = ([
                'custom_subject_id' => $request->subject_id,
                'path' => $filePath,
                'type' => $request->type,
            ]);
            $resource = Resource::create($dataResource);

            return $this->successResponse($resource, 'Operación exitosa', 200);
        }catch(InvalidOrderException $exception){
            return $this->errorResponse("Error al crear el recurso", 422);
        }
    }

    public function destroy($id){
        try {

            $resource = Resource::findOrFail($id);

            $resource->delete();

            return $this->successResponse($resource,'Se ha eliminado la materia', 200);
        }catch (ModelNotFoundException $e){
            return $this->errorResponse('No ha elementos que coincidan', 422);
        }
    }

}
