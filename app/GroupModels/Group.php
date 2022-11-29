<?php

namespace App\GroupModels;

use App\ClassVC;
use App\User;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Auth;

class Group extends Model
{
    protected $table = 'groups';
    protected $fillable = [
        'id',
        'code',
        'description',
        'name',
        'teacher_id',
        'school_id',
        'grade',
        'is_active',
        'created_at'
    ];

    public $timestamps = false ;

    public function createRoom($group_id)
    {
        try{
            $user = Auth::user();
            if(in_array($user->role_id, [4,7,8,17,22,23,24,28,29,30])){
                if(Group::where([['id', '=', $group_id],['teacher_id',$user->id]])->doesntExist()){
                    return $this->errorResponse("El profesor no es dueño del grupo",422);
                }

                if(ClassVC::where([['teacher_id',$user->id],['group_id',$group_id]])->doesntExist()){
                    $dataClassVC = ([
                        'teacher_id' => $user->id,
                        'meeting_id' => 'ClubLIAMeet-'.$user->id.'-'.$group_id.'-'.$user->username,
                        'group_id' => $group_id
                    ]);
                    ClassVC::create($dataClassVC);
                }
                $response = ClassVC::where([['teacher_id',$user->id],['group_id',$group_id]])->firstOrfail();
            }else{
                $group = Group::findOrFail($group_id);
                $teacher = User::where([['id', '=', $group->teacher_id]])->firstOrfail();
                if(ClassVC::where([['group_id',$group_id]])->doesntExist()){
                    $dataClassVC = ([
                        'teacher_id' => $group->teacher_id,
                        'meeting_id' => 'ClubLIAMeet-'.$group->teacher_id.'-'.$group_id.'-'.$teacher->username,
                        'group_id' => $group_id
                    ]);
                    ClassVC::create($dataClassVC);
                }
                $response = ClassVC::where([['teacher_id',$teacher->id],['group_id',$group_id]])->firstOrfail();

            }
            return $response;
        }catch(InvalidOrderException $exception){
            return 'No hay elementos que coincidan';
        }
    }

}
