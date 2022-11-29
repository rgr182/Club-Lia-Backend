<?php

namespace App\SyncModels;

use App\GroupModels\Group;
use App\LikeUserGroup;
use App\PhpFoxPageText;
use App\School;
use App\SyncGroupComunnity;
use App\User;
use App\UserCommunity;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Validator;
use Exception;

class GroupUserEnrollment extends Model
{
    protected $table = 'group_user_enrollments';
    protected $fillable = [
        'user_id',
        'school_id',
        'group_id',
        'group_id_community',
        'group_id_academy'
    ];

    /**
     * Store a newly created resource in storage.
     *
     * @param $groupId
     * @param $studentIds
     * @return array|string
     */
    public function enrollmentStudent($groupId, $studentIds)
    {
            $user = Auth::user();
            $group_id = $groupId;
            $user_uuids = $studentIds;

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
                $userAdmin = User::select('active_phpfox')->where('school_id', $user->school_id)->whereIn("role_id", [1, 3])->firstOrFail();

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
                if(!is_null($uuid) && is_object($uuid)){
                    $user = User::where([['id', '=', $uuid->user_id]])->firstOrfail();
                }
                else{
                    $user = User::where([['id', '=', $uuid]])->firstOrfail();
                }

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
                return $groupArray;
            }elseif($groupCreated){
                return "Algo salió mal con la DB de comunidad";
            }else{
                return "Algo salió mal con la DB de LIA";
            }
    }

    public function validateGroupStudents(): \Illuminate\Contracts\Validation\Validator
    {
        $messages = [
            'required.name' => 'El campo :nombre es requirido.',
        ];

        return Validator::make(request()->all(), [
            'groupId' => 'required|max:255',
            'uuids' => 'array|required'
        ], $messages);
    }
}
