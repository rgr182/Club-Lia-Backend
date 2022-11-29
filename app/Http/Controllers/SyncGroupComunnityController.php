<?php

namespace App\Http\Controllers;

use App\GroupModels\Group;
use App\LikeUserGroup;
use App\PhpFoxPageText;
use App\SyncModels\GradeGroupEnrollment;
use App\SyncModels\GroupStudentEnrollment;
use App\UserCommunity;
use App\School;
use App\SyncGroupComunnity;
use App\SyncModels\GroupUserEnrollment;
use App\User;
use Carbon\Traits\Timestamp;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;

class SyncGroupComunnityController extends ApiController
{
    public function syncSchool()
    {
        $results = School::where([
            ['is_active', '=', '1']
        ])->get();

        $i = 0;

        // ***********************  Sync Schools -> School Group
        if ($results->isEmpty()) {
            return $this->errorResponse('No hay escuelas por sincronizar', 422);
        } else {
            foreach ($results as $obj) {

                $syncSchool = $obj;

                if(User::select('active_phpfox')->where([
                    ['school_id', '=', $syncSchool->id ],
                    ['role_id', '=', 3]
                ])->exists()){
                    $userAdmin =  User::select('active_phpfox')->where([
                        ['school_id', '=', $syncSchool->id ],
                        ['role_id', '=', 3]
                    ])->get()->toArray();

                    $userAdmin = $userAdmin[0]['active_phpfox'];
                } else{
                    $userAdmin = 1;
                }

                if (SyncGroupComunnity::where([['title', '=', $syncSchool->name]])->exists()) {
                    $count[$i++] = array('error' => 'El nombre de grupo ya existe');
                } else {
                    // DATA PHPFOX_PAGES TABLE
                    $data = ([
                        'app_id' => 0,
                        'view_id' => 0,
                        'type_id' => 9,
                        "category_id" => 0,
                        "user_id" => $userAdmin,
                        "title" => $syncSchool->name,
                        "reg_method" => 2,
                        "landing_page" => null,
                        "time_stamp" => Carbon::now()->timestamp,
                        "image_path" => null,
                        "is_featured" => 0,
                        "is_sponsor" => 0,
                        "image_server_id" => 0,
                        "total_like" => 1,
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

                    $group = SyncGroupComunnity::create($data);

                    // DATA PHPFOX_PAGES TABLE
                    $dataText = [
                        'page_id' => $group->page_id,
                        'text' => null,
                        'text_parsed' => null
                    ];

                    $pageText = PhpFoxPageText::create($dataText);

                    $dataUserCommunity = ([
                        'profile_page_id' => $group->page_id,
                        'user_group_id' => 2,
                        'view_id' => 7,
                        'full_name' => $group->title,
                        'joined' => Carbon::now()->timestamp
                    ]);

                    $userCommunity = UserCommunity::create($dataUserCommunity);

                    // List all user of school to register on group phpfox
                    $userList = User::where([
                        ['school_id', '=', $syncSchool->id],
                        ['active_phpfox', '!=', 0]
                    ])->get();

                    $t = 0;

                    foreach ($userList as $user) {

                        $userId = $user->id;

                        // DATA PHPFOX_LIKE TABLE
                        $dataLike = ([
                            "type_id" => "groups",
                            "item_id" => $group->page_id,
                            "user_id" => $user->active_phpfox,
                            "feed_table" => "feed",
                            "time_stamp" => Carbon::now()->timestamp
                        ]);

                        $userLike = LikeUserGroup::create($dataLike);

                        $dataGroup = ([
                            'user_id' => $userId,
                            'school_id' => $syncSchool->id,
                            'group_id_community' => $group->page_id,
                            'group_id_academy' => 0
                        ]);

                        $groupCreated = GroupUserEnrollment::create($dataGroup);
                        $t++;
                        //$count[$t++] = [$userLike, $groupCreated];
                    }
                    $count[$i++] = array(["Grupo" => $group->title, "PageText" => $pageText->page_id,
                        "UserCommunity" => $userCommunity->full_name, "UsersEnroller" => $t],['Userlike' => $userLike, 'gropup' => $groupCreated, $t]);
                }
            }
        }
        return $this->successResponse($count);
    }

    //Method to sync grade to create phpfox groups
    public function syncGroupGrade()
    {
        $teachers = User::select('AppUserId')->where([
            ['role_id', '=', 4]
        ])->get();

        $resultsGroups = GroupStudentEnrollment::whereIn('TeacherId', $teachers)->get();
        $c = 0;

        // ***********************  Sync Grades -> Grade Group
        if ($resultsGroups->isEmpty()) {
            return $this->errorResponse('No hay grados por sincronizar', 422);
        } else {
            foreach ($resultsGroups as $gradeGroup) {

                $teacher = User::where([['AppUserId', '=', $gradeGroup->TeacherId]])->firstOrfail();

                $gradeGradeGroup = ([
                    'code' => $gradeGroup->Code,
                    'name' => $gradeGroup->Name,
                    'teacher_id' => $teacher->id,
                    'school_id' => $gradeGroup->SchoolId,
                    'grade' => $gradeGroup->Grade,
                    'is_active' => true,
                    'created_at' => Carbon::now()
                ]);

                $syncGrade = Group::create($gradeGradeGroup);
                $school = School::find($teacher->school_id);
                $schoolName = $school->name;

                if (SyncGroupComunnity::where([['title', '=', $schoolName . '-' .$teacher->name.' '.$teacher->last_name]])->exists()) {
                    $count[$c++] = array('error' => 'El nombre de grupo ya existe');
                } else {


                    // DATA PHPFOX_PAGES TABLE
                    $dataGradeCommunity = ([
                        'app_id' => 0,
                        'view_id' => 0,
                        'type_id' => 9,
                        "category_id" => 0,
                        "user_id" => $teacher->active_phpfox,
                        "title" => $schoolName . '-' .$teacher->name.' '.$teacher->last_name,
                        "reg_method" => 2,
                        "landing_page" => null,
                        "time_stamp" => Carbon::now()->timestamp,
                        "image_path" => null,
                        "is_featured" => 0,
                        "is_sponsor" => 0,
                        "image_server_id" => 0,
                        "total_like" => 1,
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
                    // List all user of school to register on group phpfox
                    $userList = GradeGroupEnrollment::select('StudentId')->where([
                        ['GroupId', '=', $gradeGroup->GroupId]
                    ])->get();

                    $resultsUserList = User::whereIn('AppUserId', $userList)->get();

                    $p = 0;

                    foreach ($resultsUserList as $users) {

                        $userId = $users->id;

                        // DATA PHPFOX_LIKE TABLE
                        $dataLike = ([
                            "type_id" => "groups",
                            "item_id" => $groupGradeCommunity->page_id,
                            "user_id" => $users->active_phpfox,
                            "feed_table" => "feed",
                            "time_stamp" => Carbon::now()->timestamp
                        ]);

                        $userLike = LikeUserGroup::create($dataLike);

                        $dataGroup = ([
                            'user_id' => $userId,
                            'school_id' => $users->school_id,
                            'group_id_community' => $groupGradeCommunity->page_id,
                            'group_id_academy' => 0
                        ]);

                        $groupCreated = GroupUserEnrollment::create($dataGroup);

                        $p++;
                        //$count[$t++] = [$userLike, $groupCreated];
                    }
                    $count[$c++] = array([
                        "Grupo" => $groupGradeCommunity->title,
                        "PageText" => $pageText->page_id,
                        "UserCommunity" => $userCommunity->full_name,
                        "UsersEnroller" => $p],
                        ['Group' => $groupCreated, 'user_like' => $userLike],);
                }
            }
        }
        return $this->successResponse($count);
    }

}
