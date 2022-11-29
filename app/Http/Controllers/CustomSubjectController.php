<?php

namespace App\Http\Controllers;

use App\Calendars;
use App\CustomSubject;
use App\Subject;
use App\SyncModels\GroupStudentEnrollment;
use Exception;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class CustomSubjectController extends ApiController
{
    public function show($id){
        try {
        $user = Auth::user();
        $subjects = CustomSubject::select('custom_subjects.*', 'subjects.name')
                ->addSelect(['activities' => function ($query) {
                    $query->selectRaw('count(*)')
                        ->from('activity')
                        ->whereColumn('subject_id', 'custom_subjects.id');
                }])
                ->where([['teacher_id', '=', $user->id],['group_id','=',$id]])
                ->join('subjects','custom_subjects.subject_id','=','subjects.id')
                ->get();
        return $this->successResponse($subjects, 'Lista de materias por grupo', 200);
        }catch (ModelNotFoundException $e){
            return $this->errorResponse('No ha elementos que coincidan', 422);
        }
    }

    public function store(Request $request){
        try {
            $user = Auth::user();
            $data = $request->all();
            $subjectId = $data['subject_id'];

            $subject = CustomSubject::where([['custom_name', $data['custom_name']], ['group_id', $data['group_id']]])->first();

            if($subject){
                return $this->errorResponse('La materia ya existe', 400);
            }

            $color = Subject::select('base_color')->where('id',$subjectId)->first();

            $input = [
                'custom_name' => $data['custom_name'],
                'subject_id' => $data['subject_id'],
                'teacher_id' => $user->id,
                'group_id' => $data['group_id'],
                'custom_color' =>  $color->base_color,
            ];

            $newSubject = CustomSubject::create($input);

            $calendar = new Calendars();
            $calendar->store($newSubject->id);

            return $this->successResponse($newSubject, 'Se ha creado exitosamente la materia', 200);
        }catch (Exception $e){
            return $this->errorResponse("No se ha creado la asignatura", 422);
        }
    }

    public function edit(Request $request, $id){
        try {

            $data = $request->all();

            $dataUp = CustomSubject::find($id);
            $subjectId = $data['subject_id'];

            $subject = CustomSubject::where([['custom_name', $data['custom_name']], ['group_id', $data['group_id']], ['id', '!=', $id]])->first();

            if($subject){
                return $this->errorResponse('La materia ya existe', 400);
            }

            $color = Subject::select('base_color')->where('id',$subjectId)->first();

            $input = [
                'custom_name' => $data['custom_name'],
                'subject_id' => $data['subject_id'],
                'group_id' => $data['group_id'],
                'custom_color' => $color->base_color,
            ];

            $subjectUp = $dataUp->update($input);

            return $this->successResponse($subjectUp, 'Se ha actualizado la materia', 200);
        }catch (Exception $e){
            return $this->errorResponse("No se ha actualizado la asignatura", 422);
        }
    }

    public function getSubjects(){
        try {

        $subjects = Subject::get();

        return $this->successResponse($subjects, 'Lista de materias', 200);
        }catch (ModelNotFoundException $e){
            return $this->errorResponse('No ha elementos que coincidan', 422);
        }
    }

    public function destroy($id){
        try {

        $subject = CustomSubject::findOrFail($id);

        $subject->delete();

        return $this->successResponse($subject,'Se ha eliminado la materia', 200);
        }catch (ModelNotFoundException $e){
            return $this->errorResponse('No ha elementos que coincidan', 422);
        }
    }
}
