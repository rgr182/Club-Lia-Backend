<?php

namespace App\Http\Controllers;

use App\SyncModels\Phpfox_pages_admin;
use Exception;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use App\GroupModels\Group;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Carbon;
use App\User;
use App\School;
use App\SyncGroupComunnity;
use App\PhpFoxPageText;
use App\UserCommunity;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Arr;
use App\SyncModels\GroupUserEnrollment;
use App\LikeUserGroup;

class GroupStudentController extends ApiController
{

    /**
     * Store a newly created resource in storage.
     *
     * @param  Request  $request
     * @return JsonResponse
     */
    public function store(Request $request)
    {
        $user = Auth::user();
        $validator = $this->validateGroupStudents();
        if($validator->fails()){
            return $this->errorResponse($validator->messages(), 422);
        }
        $group_id = $request->groupId;
        $user_uuids = $request->uuids;

        $groupLia = Group::find($group_id);
        $school = School::find($groupLia->school_id);
        $schoolName = $school->name;
        $dataGroupArr = array();
        $dataLikeArr = array();

        if(SyncGroupComunnity::where([['title', '=', $schoolName . '-' .$groupLia->name]])->exists()){
            $groupComunidad = SyncGroupComunnity::where([['title', '=', $schoolName . '-' .$groupLia->name]])->firstOrfail();
        }else{
            $school = School::find($user->school_id);

            // DATA PHPFOX_PAGES TABLE
            $dataGradeCommunity = ([
                'app_id' => 0,
                'view_id' => 0,
                'type_id' => 9,
                "category_id" => 0,
                "user_id" => $user->active_phpfox,
                "title" => $schoolName . '-' .$groupLia->name,
                "reg_method" => 2,
                "landing_page" => null,
                "time_stamp" => Carbon::now()->timestamp,
                "image_path" => null,
                "is_featured" => 0,
                "is_sponsor" => 0,
                "image_server_id" => 0,
                "total_dislike" => 0,
                "total_comment" => 0,
                "privacy" => 0,
                "designer_style_id" => 0,
                "cover_photo_id" => null,
                "cover_photo_position" => null,
                "location_latitude" => null,
                "location_longitude" => null,
                "location_name" => null,
                "use_timeline" => 0,
                "item_type" => 1
            ]);

            $groupComunidad = SyncGroupComunnity::create($dataGradeCommunity);

            $userAdmin = User::select('active_phpfox')->where([['school_id', $user->school_id], ['role_id', 3]])->firstOrFail();

            //DATA SUPER ADMIN GROUP
            $dataAdmin = [
                'page_id' => $groupComunidad->page_id,
                'user_id' => $user->active_phpfox
            ];
            //database phpfox pages_admin
            $adminAssign = Phpfox_pages_admin::create($dataAdmin);

            $dataLike = array(
                array(
                "type_id" => "groups",
                "item_id" => $groupComunidad->page_id,
                "user_id" => $userAdmin->active_phpfox,
                "feed_table" => "feed",
                "time_stamp" => Carbon::now()->timestamp
                ),
                array(
                    "type_id" => "groups",
                    "item_id" => $groupComunidad->page_id,
                    "user_id" => $user->active_phpfox,
                    "feed_table" => "feed",
                    "time_stamp" => Carbon::now()->timestamp
                )
            );

            $likes = $userLike = LikeUserGroup::insert($dataLike);

            // DATA PHPFOX_PAGES TABLE
            $dataText = [
                'page_id' => $groupComunidad->page_id,
                'text' => null,
                'text_parsed' => null
            ];
            //database phpfox pages_text
            $pageText = PhpFoxPageText::create($dataText);

            $dataUserCommunity = ([
                'profile_page_id' => $groupComunidad->page_id,
                'user_group_id' => 2,
                'view_id' => 7,
                'full_name' => $groupComunidad->title,
                'joined' => Carbon::now()->timestamp
            ]);

            $userCommunity = UserCommunity::create($dataUserCommunity);
            $groupArray[] = array(["Grupo", $groupComunidad], ['Comunidad', $pageText]);
        }

        foreach($user_uuids as $uuid){
            $user = User::where([['uuid', '=', $uuid]])->firstOrfail();

            if(!GroupUserEnrollment::where([['user_id', $user->id],['group_id', $group_id]])->exists()){

                $dataGroup = ([
                    'user_id' => $user->id,
                    'school_id' => $groupLia->school_id,
                    'group_id' => $group_id,
                    'group_id_community' => $groupComunidad->page_id,
                    'group_id_academy' => $user->role_id,
                    'created_at' => Carbon::now(),
                    'updated_at' => Carbon::now(),
                ]);
                array_push($dataGroupArr, $dataGroup);

                $dataLike = ([
                    "type_id" => "groups",
                    "item_id" => $groupComunidad->page_id,
                    "user_id" => $user->active_phpfox,
                    "feed_table" => "feed",
                    "time_stamp" => Carbon::now()->timestamp
                ]);
                array_push($dataLikeArr, $dataLike);

            }
        }

        $groupCreated = GroupUserEnrollment::insert($dataGroupArr);
        $userLike = LikeUserGroup::insert($dataLikeArr);
        if($groupCreated && $userLike){
            $groupArray[] = array( ["GrupoLIA", $dataGroupArr], ['Comunidad', $dataLikeArr]);
            return $this->successResponse($groupArray,'Se han insertado los usuarios con exito', 201);
        }elseif($groupCreated){
            return $this->errorResponse("Algo salió mal con la DB de comunidad", 422);
        }else{
            return $this->errorResponse("Algo salió mal con la DB de LIA", 422);
        }
    }

    public function groupMultipleEnroll(Request $request){
        try {
            $groupsIds = $request->group_ids;
            $studentId = $request->student_id;
            $group = new GroupUserEnrollment();

            foreach ($groupsIds as $groupId){
                 $group->enrollmentStudent($groupId, $studentId);
            }

            //return $group;
            return $this->successResponse('Se ha insertado al usuario con exito', 201);
        } catch (ModelNotFoundException $exception){
            return $this->errorResponse('Algo salio mal', 422 );
        }
    }


    public function getGroupUsers($id)
    {
        $groupUsers = GroupUserEnrollment::select(
            'group_user_enrollments.user_id',
            DB::raw('CONCAT(COALESCE(users.name,""), " ", COALESCE(users.second_name,"")," ", COALESCE(users.last_name,"")," ", COALESCE(users.second_last_name,"")) as name')
        )
        ->join('users','group_user_enrollments.user_id','=','users.id')
        ->where([['group_user_enrollments.group_id','=',$id]])
        ->get();
        return $this->successResponse($groupUsers,200);
    }

    /**
     * Remove student of group
     * @return JsonResponse
     */
    public function removeStudentGroups(Request $request){
        try {

            $student = $request->studentId;
            $groupsIds = $request->groupIds;
            $studentDelete = [];

            foreach ($groupsIds as $group){
                $remove = self::removeStudent($student, $group);
                array_push($studentDelete, $remove);
            }

            return $this->successResponse(
                $studentDelete,
                'Se ha eliminado exitosamente al usuario de los grupos',
                200
            );

        }catch (ModelNotFoundException $exception){
            return $this->errorResponse('Algo salio mal', 422);
        }
    }
    
    public function removeStudentsGroups(Request $request){
        try {

            $students = $request->students;
            $students = json_decode($students, true);

            $studentsDelete = [];
            foreach ($students as $student){
                $studentID = $student["id"];
                $groupsIds = $student["groups"];
                foreach ($groupsIds as $group){
                    $remove = self::removeStudent($studentID, $group);
                    array_push($studentsDelete, $remove);
                }
            }

            return $this->successResponse(
                $studentsDelete,
                'Se ha eliminado exitosamente a los usuarios de los grupos',
                200
            );

        }catch (ModelNotFoundException $exception){
            return $this->errorResponse('Algo salio mal', 422);
        }
    }

    public function removeStudent($student, $groupId){
        try {
            $user = User::find($student);

            $groupName = Group::find($groupId);

            $groupE = GroupUserEnrollment::where([
                ['user_id', '=', $student],
                ['group_id', '=', $groupId]
            ])->firstOrFail();

            $groupCid = $groupE->group_id_community;

            $groupC = LikeUserGroup::where([
                ['item_id', '=', $groupCid],
                ["user_id", '=', $user->active_phpfox]])->delete();

            $groupE->delete();

            return 'Se ha eliminado al alumno del grupo '. $groupName->name;

        }catch (ModelNotFoundException $exception){
            return 'No hay elementos que coincidan';

        }
    }

    /**
     * Display the specified resource.
     *
     * @param  \App\GroupModels\Group  $group
     * @return JsonResponse
     */
    public function show($id)
    {
       //

        try {
            $user = Auth::user();

            return $this->successResponse($user);

        }catch (Exception $exception){
            return $this->errorResponse("No ha elementos que coincidan", 422);
        }
    }

    public function validateGroupStudents(){
        $messages = [
            'required.name' => 'El campo :nombre es requirido.',
        ];

        return Validator::make(request()->all(), [
                'groupId' => 'required|max:255',
                'uuids' => 'array|required'
            ], $messages);
    }
}
