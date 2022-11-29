<?php

namespace App\Http\Controllers;

use App\PhpFoxPageText;
use App\School;
use App\SchoolLIA;
use App\SyncGroupComunnity;
use App\User;
use App\GroupModels\Group;
use App\UserCommunity;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Validator;

class SchoolController extends ApiController
{
    /**
     * Display a listing of the resource.
     */
    public function index()
    {
        $schools = School::all();
        return $this->successResponse($schools);
    }

    /**
     * Store a newly created resource in storage.
     *
     * @param  Request  $request
     * @return JsonResponse
     */
    public function store(Request $request)
    {
        $validator = $this->validateSchool();
        if($validator->fails()){
            return $this->errorResponse($validator->messages(), 422);
        }

        $userAdmin = User::where([['email', '=' , $request->Admin]])->first();

        if(is_null($userAdmin) || is_null($userAdmin->active_phpfox)){
            return $this->errorResponse('Este email no existe o no esta registrado en la red de comunidad', 422);
        }
        $userAdminID = $userAdmin->active_phpfox;

        $schoolName = $request->School;
        $schoolDescription = $request->Description;
        $schoolActive = $request->IsActive;
        $schoolEditor = 68;
        $schoolCreator = 68;

        if (School::where([['name', '=', $schoolName]])->exists()) {
            return $this->errorResponse('El nombre de la escuela ya existe', 422);
        } else {

            $dataGroup = ([
                'app_id' => 0,
                'view_id' => 0,
                'type_id' => 6,
                "category_id" => 0,
                "user_id" => $userAdminID,
                "title" => $schoolName,
                "reg_method" => 1,
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

            $group = SyncGroupComunnity::create($dataGroup);

            // DATA PHPFOX_PAGES TABLE
            $dataText = [
                'page_id' => $group->page_id,
                'text' => null,
                'text_parsed' => null
            ];
            //database phpfox pages_text
            $pageText = PhpFoxPageText::create($dataText);

            $dataUserCommunity = ([
                'profile_page_id' => $group->page_id,
                'user_group_id' => 2,
                'view_id' => 7,
                'full_name' => $group->title,
                'joined' => Carbon::now()->timestamp
            ]);

            $userCommunity = UserCommunity::create($dataUserCommunity);

            $data = ([
                'name' => $schoolName,
                'description' => $schoolDescription,
                'type' => $request->Type,
                'is_active' => $schoolActive
            ]);

            $school = School::create($data);
            if($request->Type !== 'Empresa'){
                $userAdmin->school_id = $school->id;
                $userAdmin->save();
            }

            self::storeTutorGroup($userAdminID, $schoolName);
        }

        $schoolArray[] = array(['Sistema de licencias' => $school], ['Comunidad' => $group, 'userPage' =>  $pageText, $userCommunity]);
        
        return $this->successResponse($schoolArray,'Se ha creado la escuela con exito', 201);
    }

    public function getAdminSchool(Request $request)
    {
        $sql = User::where("school_id", "=", 305)->whereIn("role_id", [1,3])->get();
        $admin = null;
        foreach($sql as $key => $usr){
            if($usr->role_id === 1 || $usr->role_id === 3){
                $admin = $usr;
            }
        }
        return $admin;
    }

    public function getCompanies(Request $request)
    {
        $sql = School::where("type", "=", "Empresa")
            ->where('is_active', 1)
            ->get();        
        return $sql;
    }

    public function getUsersByCompany(Request $request){
        $companies = explode(',', $request->companies);
        $users = User::select(
                'email',
                'id',
                'grade',
                'name',
                'role_id',
                'school_id',
                'username'
            )
            ->whereIn('company_id', $companies)
            ->get();
        return $this->successResponse($users);
    }

    /**
     * Display the specified resource.
     *
     * @param  \App\School  $school
     * @param  int $id
     */
    public function show($id)
    {
        try {
        $school = School::findOrFail($id);
        return $this->successResponse($school);
        }catch (ModelNotFoundException $e){
            return $this->errorResponse('La escuela es invalida', 422);
        }

    }

    /**
     * Update the specified resource in storage.
     * @param Request $request
     * @return JsonResponse
     */
    public function update(Request $request, $id)
    {
        try {

            $school = School::findOrFail($id);
            // $schoolLia = SchoolLIA::findOrFail($id);

            $schoolName = $request->name;
            $schoolDescription = $request->description;

            $dataLia = ([
                'School' => $schoolName,
                'Description' => $schoolDescription,
                'IsActive' => $request->is_active,
            ]);

            // $schoolLiaUpt = $schoolLia->update($dataLia);;
            $schoolUpt = School::updateDataId($id);
            $schoolArray[] = array($schoolUpt);

            return $this->successResponse($schoolArray, 'La escuela ha sido actualizada', 200);

        }catch (ModelNotFoundException $e){
            return $this->errorResponse('No hay elementos que coincidan', 404);
        }
    }

    /**
     * Remove the specified resource from storage.
     * @return JsonResponse
     */
    public function destroy($id)
    {
        try {
            $school = School::findOrFail($id);
            $groups = Group::where('school_id', '=', $id)->delete();
            $users = User::where('school_id', '=', $id)->delete();
            $school->delete();
            $schoolArray[] = array($groups, $school, $users);

            return $this->successResponse($schoolArray, 'Se ha eliminado la escuela con exito');
        }catch (ModelNotFoundException $e){
            return $this->errorResponse('No hay elementos que coincidan',404);
        }
    }

    public function storeTutorGroup($userAdmin, $schoolName){

        $dataGroup = ([
            'app_id' => 0,
            'view_id' => 0,
            'type_id' => 6,
            "category_id" => 0,
            "user_id" => $userAdmin,
            "title" => 'Tutores' .' - '. $schoolName,
            "reg_method" => 1,
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

        $group = SyncGroupComunnity::create($dataGroup);

        // DATA PHPFOX_PAGES TABLE
        $dataText = [
            'page_id' => $group->page_id,
            'text' => null,
            'text_parsed' => null
        ];
        //database phpfox pages_text
        $pageText = PhpFoxPageText::create($dataText);

        $dataUserCommunity = ([
            'profile_page_id' => $group->page_id,
            'user_group_id' => 2,
            'view_id' => 7,
            'full_name' => $group->title,
            'joined' => Carbon::now()->timestamp
        ]);

        $userCommunity = UserCommunity::create($dataUserCommunity);

    }

    public function validateSchool(){
        $messages = [
            'required.School' => 'El campo nombre es requirido.',
        ];

        return Validator::make(request()->all(), [
                'School' => 'required|max:255',
                'Admin' => 'required|email'
            ], $messages);
    }
}
