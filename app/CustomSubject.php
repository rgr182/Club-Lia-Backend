<?php

namespace App;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class CustomSubject extends Model
{
    protected $fillable = [
        'custom_name',
        'description',
        'subject_id',
        'teacher_id',
        'group_id',
        'custom_name',
        'custom_color',
    ];

    protected $casts = [
        'created_at' => 'datetime:d-m-Y', 'update_at' => 'datetime:d-m-Y',
    ];

    public function createSubject($groupId, $subject){
        try {
            $user = Auth::user();
            $data = $subject;
            $subjectId = $data['subject_id'];

            $subject = CustomSubject::where([['custom_name', $data['custom_name']], ['group_id', $groupId]])->first();

            if($subject){
                return 'La materia ya existe';
            }

            $color = Subject::select('base_color')->where('id',$subjectId)->first();

            $input = [
                'custom_name' => $data['custom_name'],
                'subject_id' => $data['subject_id'],
                'teacher_id' => $user->id,
                'group_id' => $groupId,
                'custom_color' =>  $color->base_color,
            ];

            if($data['description']){
                $input['description'] = $data['description'];
            }

            $newSubject = CustomSubject::create($input);

            $calendar = new Calendars();
            $calendar->store($newSubject->id);

            return $newSubject;
        }catch (Exception $e){
            return "No se ha creado la asignatura";
        }
    }
}
