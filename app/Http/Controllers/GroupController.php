<?php

namespace App\Http\Controllers;

use App\GroupModels\Group;
use App\LikeUserGroup;
use App\SyncModels\GroupUserEnrollment;
use App\SyncModels\Phpfox_pages_admin;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Carbon;
use App\User;
use App\School;
use App\SyncGroupComunnity;
use App\PhpFoxPageText;
use App\UserCommunity;
use App\CustomSubject;
use App\AvatarUsers;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Arr;
use Mockery\Exception\InvalidOrderException;

class GroupController extends ApiController
{
    /**
     * Display a listing of the resource.
     *
     * @return JsonResponse
     */
    public function index()
    {
        $user = Auth::user();
        if ($user->role_id == 1) {
            $groups = \DB::table('groups')
                ->join('users', 'groups.teacher_id', '=', 'users.id')
                ->join('schools', 'groups.school_id', '=', 'schools.id')
                ->select('groups.*', 'users.email', 'schools.name as school_id', \DB::raw('CONCAT(COALESCE(users.name,"")," ",COALESCE(users.second_name+" ",""),COALESCE(users.last_name,"")) as teachers_name'))
                ->groupBy('groups.id')
                ->get();
        } else if (in_array($user->role_id, [4, 7, 8, 17, 22, 23, 24, 28, 29, 30])) { //teachers
            $groups = \DB::table('groups')
                ->join('users', 'groups.teacher_id', '=', 'users.id')
                ->join('schools', 'groups.school_id', '=', 'schools.id')
                ->select('groups.*', 'users.email', 'schools.name as school_id', \DB::raw('CONCAT(COALESCE(users.name,"")," ",COALESCE(users.second_name+" ",""),COALESCE(users.last_name,"")) as teachers_name'))
                ->where([['groups.school_id', $user->school_id], ['groups.teacher_id', $user->id]])
                ->groupBy('groups.id')
                ->get();

        } else {
            $groups = \DB::table('groups')
                ->join('users', 'groups.teacher_id', '=', 'users.id')
                ->join('schools', 'groups.school_id', '=', 'schools.id')
                ->select('groups.*', 'users.email', 'schools.name as school_id', \DB::raw('CONCAT(COALESCE(users.name,"")," ",COALESCE(users.second_name+" ",""),COALESCE(users.last_name,"")) as teachers_name'))
                ->where('groups.school_id', '=', $user->school_id)
                ->groupBy('groups.id')
                ->get();

        }
        $count = \DB::table('group_user_enrollments')->select(\DB::raw('count(group_id) as count,group_id'))->groupBy('group_id')->get();
        foreach($groups as $group){
            if ( CustomSubject::where('group_id', $group->id)->exists() ){
                $subjects = CustomSubject::where('group_id', $group->id)->get();
                $subjects = $subjects->count();
                $group->subject_total = $subjects;
            }else{
                $group->subject_total = 0;
            }
        }
        foreach ($groups as $group) {
            foreach ($count as $obj) {
                if ($group->id == $obj->group_id) {
                    $group->students_count = $obj->count;
                }
            }
            if (!isset($group->students_count)) {
                $group->students_count = 0;
            }
        }

        return $this->successResponse($groups);
    }

    /**
     * Store a newly created resource in storage.
     *
     * @param Request $request
     * @return JsonResponse
     */
    public function store(Request $request)
    {
        try {
        $userAdmin = Auth::user();

        $validator = $this->validateCreateGroup();
        if ($validator->fails()) {
            return $this->errorResponse($validator->messages(), 422);
        }

        $pool = '0123456789abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ';

        $code = substr(str_shuffle(str_repeat($pool, 5)), 0, 8);
        $name = $request->groupName;
        $teacher_id = $request->teacherId;
        $school_id = $request->schoolId;
        $description = $request->description;
        $grade = $request->grade;

        if (Group::where([['name', '=', $name], ['school_id', $school_id]])->exists()) {
            return $this->errorResponse("El nombre del grupo ya existe dentro de esta escuela", 422);
        } else {

            $teacher = User::where([['id', '=', $teacher_id]])->firstOrfail();
            $school = School::find($teacher->school_id);
            if ($teacher->school_id !== $school_id) {
                return $this->errorResponse("El maestro no pertenece a esa escuela", 422);
            } else {

                $gradeGradeGroup = ([
                    'code' => $code,
                    'description' => $description,
                    'name' => $name,
                    'teacher_id' => $teacher_id,
                    'school_id' => $school_id,
                    'grade' => $grade,
                    'is_active' => true,
                    'created_at' => Carbon::now()
                ]);

                $syncGrade = Group::create($gradeGradeGroup);
                $room = new Group();
                $room = $room->createRoom($syncGrade->id);

                if ($request->newSubjects) {
                    $subjects = $request->newSubjects;

                    foreach ($subjects as $subject) {

                        $customS = new CustomSubject();
                        $customS->createSubject($syncGrade->id, $subject);
                    }
                }

                if ($request->newStudents) {
                    if($syncGrade->id){
                        $students = $request->newStudents;
    
                        $groupE = new GroupUserEnrollment();
                        $groupE->enrollmentStudent($syncGrade->id, $students);
                    }
                }

                $schoolName = $school->name;

                // DATA PHPFOX_PAGES TABLE
                $dataGradeCommunity = ([
                    'app_id' => 0,
                    'view_id' => 0,
                    'type_id' => 9,
                    "category_id" => 0,
                    "user_id" => $teacher->active_phpfox,
                    "title" => $schoolName . '-' . $name,
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

                $groupGradeCommunity = SyncGroupComunnity::create($dataGradeCommunity);

                //DATA SUPER ADMIN GROUP
                $dataAdmin = [
                    'page_id' => $groupGradeCommunity->page_id,
                    'user_id' => $userAdmin->active_phpfox
                ];
                //database phpfox pages_admin
                $adminAssign = Phpfox_pages_admin::create($dataAdmin);

                $dataLike = array(
                    array(
                        "type_id" => "groups",
                        "item_id" => $groupGradeCommunity->page_id,
                        "user_id" => $userAdmin->active_phpfox,
                        "feed_table" => "feed",
                        "time_stamp" => Carbon::now()->timestamp
                    ),
                    array(
                        "type_id" => "groups",
                        "item_id" => $groupGradeCommunity->page_id,
                        "user_id" => $teacher->active_phpfox,
                        "feed_table" => "feed",
                        "time_stamp" => Carbon::now()->timestamp
                    )
                );

                $likes = $userLike = LikeUserGroup::insert($dataLike);

                // DATA PHPFOX_PAGES TABLE
                $dataText = [
                    'page_id' => $groupGradeCommunity->page_id,
                    'text' => null,
                    'text_parsed' => null
                ];
                //database phpfox pages_text
                $pageText = PhpFoxPageText::create($dataText);

                $dataUserCommunity = ([
                    'profile_page_id' => $groupGradeCommunity->page_id,
                    'user_group_id' => 2,
                    'view_id' => 7,
                    'full_name' => $groupGradeCommunity->title,
                    'joined' => Carbon::now()->timestamp
                ]);

                $userCommunity = UserCommunity::create($dataUserCommunity);
                $groupArray[] = array(['Sistema Lia', $syncGrade], ["Grupo", $groupGradeCommunity], ['Comunidad', $pageText]);
            }
        }

        return $this->successResponse($groupArray, 'Se ha creado el grupo con exito', 201);

        }catch (\Exception $exception){
            return $this->errorResponse($exception->getMessage(),422 );
        }
    }

    /**
     * Duplicate group.
     *
     * @param Request $request
     * @return JsonResponse
     */
    public function duplicateGroup($id)
    {
        try {

            $group = Group::find($id);
            $copyGroup = $group->replicate();
            $userAdmin = Auth::user();

            $pool = '0123456789abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ';

            $copyGroup->code = substr(str_shuffle(str_repeat($pool, 5)), 0, 8);
            $rand = rand(1000,9999);
            $newName = $copyGroup->name;
            if (substr($newName, 0, 26)) {
                $newName = substr($group->name.' Copia ', 0, 26).$rand;
            } else {
                $newName .= $rand;
            }
            $copyGroup->name = $newName;
            $copyGroup->teacher_id = $group->teacher_id;
            $copyGroup->school_id = $group->school_id;
            $copyGroup->description = $group->description;
            $copyGroup->grade = $group->grade;
            $copyGroup->is_active = true;
            $copyGroup->created_at = Carbon::now();

            $school = School::find($userAdmin->school_id);

            $admin = User::where('school_id', $school->id)->whereIn('role_id', [1, 3])->firstOrFail();

            $copyGroup->save();

            $room = new Group();
            // Crear Room
            $room = $room->createRoom($copyGroup->id);

            if (CustomSubject::where('group_id', $id)->exists()) {
                $subjects = CustomSubject::where('group_id', $id)->get();

                foreach ($subjects as $subject) {

                    $customS = new CustomSubject();
                    $customS->createSubject($copyGroup->id, $subject);
                }
            }

            if (GroupUserEnrollment::where('group_id', $id)->exists()) {

                $students = GroupUserEnrollment::where('group_id', $id)->get();
                $groupE = new GroupUserEnrollment();
                $groupE->enrollmentStudent($copyGroup->id, $students);
            }

            $schoolName = $school->name;

            // DATA PHPFOX_PAGES TABLE
            $dataGradeCommunity = ([
                'app_id' => 0,
                'view_id' => 0,
                'type_id' => 9,
                "category_id" => 0,
                "user_id" => $userAdmin->active_phpfox,
                "title" => $schoolName . '-' . $copyGroup->name,
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

            $groupGradeCommunity = SyncGroupComunnity::create($dataGradeCommunity);

            //DATA SUPER ADMIN GROUP
            $dataAdmin = [
                'page_id' => $groupGradeCommunity->page_id,
                'user_id' => $admin->active_phpfox
            ];
            //database phpfox pages_admin
            // $adminAssign = Phpfox_pages_admin::create($dataAdmin);

            $dataLike = array(
                array(
                    "type_id" => "groups",
                    "item_id" => $groupGradeCommunity->page_id,
                    "user_id" => $admin->active_phpfox,
                    "feed_table" => "feed",
                    "time_stamp" => Carbon::now()->timestamp
                ),
                array(
                    "type_id" => "groups",
                    "item_id" => $groupGradeCommunity->page_id,
                    "user_id" => $userAdmin->active_phpfox,
                    "feed_table" => "feed",
                    "time_stamp" => Carbon::now()->timestamp
                )
            );

            LikeUserGroup::insert($dataLike);

            // DATA PHPFOX_PAGES TABLE
            $dataText = [
                'page_id' => $groupGradeCommunity->page_id,
                'text' => null,
                'text_parsed' => null
            ];
            //database phpfox pages_text
            $pageText = PhpFoxPageText::create($dataText);

            $dataUserCommunity = ([
                'profile_page_id' => $groupGradeCommunity->page_id,
                'user_group_id' => 2,
                'view_id' => 7,
                'full_name' => $groupGradeCommunity->title,
                'joined' => Carbon::now()->timestamp
            ]);

            UserCommunity::create($dataUserCommunity);
            $groupArray[] = array(['Sistema Lia', $copyGroup], ["Grupo", $groupGradeCommunity], ['Comunidad', $pageText]);

            return $this->successResponse($copyGroup, 'Se ha creado el grupo con exito', 201);
        } catch (\Exception $exception) {
            /**
             * Rewrite translate errors from API Google Calendar
             */
            $errors = [
                'usageLimits' => 'Ha superado el limite de calendarios. inténtalo mas tarde!',
                'global' => 'Ha ocurrido un problema de autenticacion con Google'
            ];
            $response = $exception->getMessage();
            $error = json_decode($exception->getMessage());
            if(isset($error->error->errors[0]->domain)){
                $response = $errors[$error->error->errors[0]->domain];
            }
            return $this->errorResponse($response, 422);
        }
    }

    /**
     * Display the specified resource.
     *
     *
     * @return JsonResponse
     */
    public function show($id)
    {
        try {
            $group = Group::findOrFail($id);
            $subjects = CustomSubject::where([['group_id', '=', $id]])->get();
            $group->subjects = $subjects;
            $groupUsers = GroupUserEnrollment::select(
                'group_user_enrollments.user_id',
                \DB::raw('CONCAT(name, " ", last_name) as name')
            )
                ->join('users', 'group_user_enrollments.user_id', '=', 'users.id')
                ->where([['group_user_enrollments.group_id', '=', $id]])
                ->get();

            foreach ($groupUsers as $student) {
                $avatar = AvatarUsers::where('user_id', $student->user_id)
                    ->select('avatar_id', 'avatar_path')
                    ->get();
                $student->avatar = $avatar;
            }

            $group->students = $groupUsers;

            return $this->successResponse($group, 'Información del grupo', 200);

        } catch (ModelNotFoundException $e) {
            return $this->errorResponse('Tipo de licencia invalido', 422);
        }
    }

    public function clasesInfo()
    {
        $user = Auth::user();
        $subjects = [];

        $subjects = CustomSubject::where('custom_subjects.teacher_id', $user->id)
            ->join('calendar', 'custom_subjects.id', '=', 'calendar.subject_id')
            ->join('groups', 'groups.id', '=', 'custom_subjects.group_id')
            ->join('classes', 'groups.id', '=', 'classes.group_id')
            ->select('custom_subjects.*', 'calendar.id as id_calendar', 'calendar.calendar_id as calendar_id', 'groups.name as group_name',
                'classes.id as id_class', 'classes.meeting_id as meeting_id')
            ->get();

        return $this->successResponse($subjects);

        $count = \DB::table('group_user_enrollments')->select(\DB::raw('count(group_id) as count,group_id'))->groupBy('group_id')->get();
        foreach ($groups as $group) {
            foreach ($count as $obj) {
                if ($group->id == $obj->group_id) {
                    $group->students_count = $obj->count;
                }
            }
            if (!isset($group->students_count)) {
                $group->students_count = 0;
            }
        }
        return $this->successResponse($groups);
    }

    /**
     * Update the specified resource in storage.
     *
     * @param Request $request
     * @return JsonResponse
     */
    public function update(Request $request)
    {
        $validator = $this->validateUpdateGroup();
        if ($validator->fails()) {
            return $this->errorResponse($validator->messages(), 422);
        }
        try {
            $user = Auth::user();
            $id = $request->groupId;
            $teacherId = $request->teacherId;
            $title = $request->groupTitle;
            $description = $request->description;
            $grade = $request->grade;

            if (Group::where([['name', '=', $title], ['id', '!=', $id], ['school_id', '=', $user->school_id]])->exists()) {
                return $this->errorResponse("El nombre del grupo ya existe", 422);
            } else {
                $groupLia = Group::findOrFail($id);
                $teacher = User::where('id', $groupLia->teacher_id)->firstOrfail();
                $teacherNew = User::where('id', $teacherId)->firstOrfail();
                $school = School::find($teacher->school_id);
                $schoolNew = School::find($teacherNew->school_id);
                $schoolName = $school->name;
                $schoolNameNew = $schoolNew->name;

                $groupComunidad = SyncGroupComunnity::where([['title', '=', $schoolName . '-' . $groupLia->name]])->first();

                $dataLia = ([
                    'teacher_id' => $teacherId,
                    'name' => $title,
                    'description' => $description,
                    'grade' => $grade
                ]);

                $groupLiaUpdt = $groupLia->update($dataLia);

                if ($request->newSubjects) {
                    $subjects = $request->newSubjects;

                    foreach ($subjects as $subject) {

                        $customS = new CustomSubject();
                        $customS->createSubject($groupLia->id, $subject);
                    }
                }

                if ($request->newStudents) {
                    $students = $request->newStudents;

                    $groupE = new GroupUserEnrollment();
                    $groupE->enrollmentStudent($groupLia->id, $students);
                }

                if ($request->deletedSubjects) {
                    $dsubjects = $request->deletedSubjects;

                    foreach ($dsubjects as $dsubject) {

                        if (CustomSubject::where('id', $dsubject)->exists()) {
                            $dcustomS = CustomSubject::where('id', $dsubject)->delete();
                        }
                    }
                }

                if ($request->deletedStudents) {
                    $dstudents = $request->deletedStudents;

                    foreach ($dstudents as $dstudent) {
                        $studentDelete = self::removeStudent($dstudent, $groupLia->id);
                    }
                }

                if ($groupComunidad) {
                    $dataComunidadLia = ([
                        'user_id' => ($teacherNew->active_phpfox),
                        'title' => ($schoolNameNew . '-' . $title)
                    ]);

                    $groupComunidadUpdt = $groupComunidad->update($dataComunidadLia);
                }

                $groupLiaUpdated = Group::findOrFail($id);
                $groupArray[] = array($groupLiaUpdt, $groupLiaUpdated);

                return $this->successResponse($groupArray, 'El grupo ha sido actualizado', 200);
            }

        } catch (ModelNotFoundException $e) {
            return $this->errorResponse("No se ha actualizado el grupo", 404);
        }
    }

    /**
     * Remove the specified resource from storage.
     *
     * @return JsonResponse
     */
    public function destroy($id)
    {
        try {
            $groupLia = Group::findOrFail($id);
            $teacher = User::where('id', $groupLia->teacher_id)->firstOrfail();
            $school = School::find($teacher->school_id);
            $schoolName = $school->name;

            if (SyncGroupComunnity::where([['title', '=', $schoolName . '-' . $groupLia->name]])->exists()) {
                $groupComunidad = SyncGroupComunnity::where([['title', '=', $schoolName . '-' . $groupLia->name]])->firstOrfail();
                $groupComunidadDelete = SyncGroupComunnity::destroy($groupComunidad->page_id);
                $groupArray[] = array($groupComunidadDelete);
            }

            $groupLiaDelete = Group::destroy($id);

            $groupArray[] = array($groupLiaDelete);

            return $this->successResponse($groupArray, 'Se ha eliminado el grupo con exito');
        } catch (ModelNotFoundException $e) {
            return $this->errorResponse('No hay elementos que coincidan', 404);
        }
    }

    public function validateCreateGroup()
    {
        $messages = [
            'required.name' => 'El campo :nombre es requirido.',
        ];

        return Validator::make(request()->all(), [
            'groupName' => 'required|max:255',
            'teacherId' => 'required|max:255',
            'schoolId' => 'required|max:255',
            'grade' => 'required|max:255',
        ], $messages);
    }

    public function validateUpdateGroup()
    {
        $messages = [
            'required.name' => 'El campo :nombre es requirido.',
        ];

        return Validator::make(request()->all(), [
            'groupId' => 'required|max:255',
            'teacherId' => 'required|max:255',
            'groupTitle' => 'required|max:255'
        ], $messages);
    }

    /**
     * Display students from groups.
     *
     * @return Response
     */
    /**
     * Display a listing of the resource.
     *
     * @param Request $request
     * @return JsonResponse
     */
    public function studentsByGroup(Request $request)
    {
        try {
            $input = $request->all();
            $ids = $input['ids'];
            $user = Auth::user();

            if (str_contains($ids, ',')) {

                $ids = explode(',', $ids);
                $resource = \DB::table('users')
                    ->join('roles', 'users.role_id', '=', 'roles.id')
                    ->join('group_user_enrollments', 'users.id', '=', 'group_user_enrollments.user_id')
                    ->join('groups', 'group_user_enrollments.group_id', '=', 'groups.id')
                    ->select('users.name', 'users.last_name', 'groups.name as group_name', 'users.grade', 'roles.name as role_name', 'users.email')
                    ->whereIn('groups.id', $ids)
                    ->get();

            } else {

                $id = (int)$ids;

                $resource = \DB::table('users')
                    ->join('roles', 'users.role_id', '=', 'roles.id')
                    ->join('group_user_enrollments', 'users.id', '=', 'group_user_enrollments.user_id')
                    ->join('groups', 'group_user_enrollments.group_id', '=', 'groups.id')
                    ->select('users.name', 'users.last_name', 'groups.name as group_name', 'users.grade', 'roles.name as role_name', 'users.email')
                    ->where('groups.id', '=', $id)
                    ->get();
            }

            return $this->successResponse($resource, 200);
        } catch (InvalidOrderException $exception) {
            return $this->errorResponse("No hay elementos que coincidan", 422);
        }


    }

    public function removeStudent($student, $groupId)
    {
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

            return 'Se ha eliminado al alumno del grupo ' . $groupName->name;

        } catch (ModelNotFoundException $exception) {
            return 'No hay elementos que coincidan';

        }
    }
}
