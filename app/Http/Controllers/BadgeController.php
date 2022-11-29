<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use App\Badge;
use App\BadgeRelations;

class BadgeController extends Controller
{
    /**
     * Display a listing of the resource.
     *
     * @return JsonResponse
     */
    public function badge(Request $request)
    {
        $user = Auth::user();
        $input = $request->all();

        $dataBadge = ([
            'badge_id' => $input['badge_id'],
            'task_id' => $input['task_id'],
            'student_id' => $input['student_id'],
            'teacher_id' => $input['teacher_id'],
            'badges_data' => $input['badges_data']
        ]);

        if(BadgeRelations::where([['task_id', '=', $input['task_id']], ['student_id', '=', $input['student_id']], ['teacher_id', '=', $input['teacher_id']] ])->first()){
            BadgeRelations::where([['task_id', '=', $input['task_id']], ['student_id', '=', $input['student_id']], ['teacher_id', '=', $input['teacher_id']] ])->update(['badges_data' => $input['badges_data']]);
            $message = 'Se actualizo la insignia correctamente';
        }else{
            $badgeDB = BadgeRelations::create($dataBadge);
            $message = 'Se inserto la insignia correctamente';
        }


        return $message;
 
    }

    /**
     * Display a listing of the resource.
     *
     * @return JsonResponse
     */
    public function verBadge(Request $request)
    {
        $user = Auth::user();
        $input = $request->all();
        $dataBadgeGet = ([            
            'task_id' => $input['task_id'],
            'student_id' => $input['student_id'],
            'teacher_id' => $input['teacher_id']
        ]);
        $data = BadgeRelations::select('badges_data')->where($dataBadgeGet)->get();

        return $data;
    }
}
