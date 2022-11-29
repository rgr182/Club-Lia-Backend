<?php

namespace App\Http\Controllers;

use App\AvatarUsers;
use App\Homework;
use App\Jobs\DeleteGenericUserJob;
use App\Jobs\SendEmail;
use App\License;
use App\LicenseKey;
use App\LikeUserGroup;
use App\GroupModels\Group;
use App\School;
use App\SyncModels\GroupUserEnrollment;
use App\UserCommunity;
use App\UserLIA;
use App\UserThinkific;
use App\UserPhpFox;
use App\PhpFox_activitypoint_statistics;
use App\PhpFox_user_activity;
use App\PhpFox_user_count;
use App\PhpFox_user_field;
use App\PhpFox_user_space;
use Carbon\Carbon;
use DateTime;
use Exception;
use Google\Service\Classroom\Student;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Config;
use App\User;
use App\LicenseType;
use App\Order;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Ramsey\Uuid\Uuid;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use \Illuminate\Support\Facades\Validator;
use App\Activity;
use App\DigitalResources as DigitalResource;

class UserController extends ApiController
{
    /**
     * Display a listing of the resource.
     *
     * @return JsonResponse
     */
    public function index()
    {
        $user = Auth::user();
        $request = request()->all();
        $roleMembership = [
            '25' => '7', // Escuela-I - Escuela Invitado
            '26' => '8', // Escuela-M - Escuela Mensual
            '27' => '9', // Escuela-A - Escuela Anual
            '28' => '4', // Maestro-I - Maestro Invitado
            '29' => '5', // Maestro-M - Maestro Mensual
            '30' => '6', // Maestro-A - Maestro Anual
            '31' => '1', // Padre-I - Padre Invitado
            '32' => '2', // Padre-M - Padre Mensual
            '33' => '3', // Padre-A - Padre Anual
            '34' => '1', // Alumno-I - Alumno Invitado
            '35' => '2', // Alumno-M - Alumno Mensual
            '36' => '3'  // Alumno-A - Alumno Anual
        ];
        $filter = [];
        $i = -1;
        $group_id = false;
        $filter[++$i] = ['users.role_id', '<>', 1];
        if (array_key_exists('school_id', $request) && $request['school_id'] != null) {
            $filter[++$i] = array('users.school_id', '=', $request['school_id']);
        }
        if (array_key_exists('role_id', $request) && $request['role_id'] != null) {
            $filter[++$i] = array('users.role_id', '=', $request['role_id']);
        }
        if ($user->role_id > 2) {
            $filter[++$i] = array('users.school_id', '=', $user->school_id);
        }
        if (array_key_exists('group_id', $request) && $request['group_id'] != null) {
            $group_id = $request['group_id'];
            $filter[++$i] = array('group_user_enrollments.group_id', '=', $request['group_id']);
        }
        
        $users = \DB::table('users')
            ->leftJoin('schools', 'users.school_id', '=', 'schools.id')
            ->leftJoin('roles', 'users.role_id', '=', 'roles.id')
            ->when($group_id, function ($query) {
                return $query->leftJoin('group_user_enrollments', 'users.id', '=', 'group_user_enrollments.user_id');
            })
            ->select(
                'users.id',
                'users.uuid',
                'users.username',
                'users.name',
                'users.second_name',
                'users.last_name',
                'users.second_last_name',
                'users.school_id',
                'users.company_id',
                'schools.name as school_name',
                'roles.name as role_name',
                'users.role_id',
                'users.tutor_id',
                'users.email',
                'users.grade',
                'users.avatar',
                'users.is_active',
                'users.verified_email',
                'users.member_since',
                'users.last_login');

        if (array_key_exists('grade', $request) && $request['grade'] != null) {
            if ($request['grade'] > 6) {
                $filter[++$i] = array('users.grade', '=', $request['grade'] - 6);
                // $filter[++$i] = array('roles.name', '=', 'Preescolar');
                $users = $users->where(function($query) {
                    $query->where('roles.name', 'Preescolar')->orWhere('users.level_id', '1');
                });
            } else {
                $filter[++$i] = array('users.grade', '=', $request['grade']);
                $filter[++$i] = array('roles.name', '<>', 'Preescolar');
                // $filter[++$i] = array('users.level_id', '<>', '1');
            }
        }
        if (in_array($user->role_id, [4, 7, 8, 17, 22, 23, 24, 28, 29, 30])) {
            $users = $users->whereIn('users.role_id', [5, 6, 13, 18, 19, 20, 21, 34, 35, 36]);
        }

        if(isset($request['company_id'])){
            $users = $users->where('users.company_id', $request['company_id']);
        }
        $users = $users->where($filter)->get();
        foreach ($users as $user) {

            $license_type = "N/A";
            $expiration_date = "N/A";
            if ($user->role_id >= 25 && $user->role_id <= 36) {
                $license_type = LicenseType::select('description_license_type')->where('id', $roleMembership[$user->role_id])->firstOrFail();
                $license_type = $license_type->description_license_type;
                if (Order::where([['user_id', $user->id], ['status', 'approved']])->exists()) {
                    $expiration_date = Order::select('expiry_date')->where([['user_id', $user->id], ['status', 'approved']])->firstOrFail();
                    $expiration_date = $expiration_date->expiry_date;
                }
            }
            if ($user->role_name >= 'Preescolar') {
                $user->grade .= ' K';
            }
            $user->license_type = $license_type;
            if(isset($user->company_id)){
                $company = School::where('id', $user->company_id)
                    ->first();
                $user->company_name = $company->name ?? '';
            }
            $user->expiration_date = $expiration_date;
            $childrens = User::select('id as value', \DB::raw('CONCAT(name, " ", last_name) as label'))->where('tutor_id', $user->id)->get();
            $user->childrens_id = $childrens;
            $user->tutor_id = User::select('id as value', \DB::raw('CONCAT(name, " ", last_name) as label'))->where('id', $user->tutor_id)->get();
        }

        return $this->successResponse($users);
    }

    public function studentList()
    {
        try {
            $user = Auth::user();
            $listStudents =  [];

            $students = User::where('users.school_id', $user->school_id)
                ->whereIn('users.role_id', [5, 6, 13, 18, 19, 20, 21, 34, 35, 36])
                ->get();

            $studentInfo = [];

            foreach ($students as $student) {

                if (AvatarUsers::where('user_id', $student->id)->exists()) {
                    $avatar = AvatarUsers::select('avatar_path')->where('user_id', $student->id)->first();
                    if (empty($avatar->avatar_path)) {
                        $studentInfo['avatar'] = "assets/images/avatars/bootFace.png";
                    } else {
                        $studentInfo['avatar'] = $avatar->avatar_path;
                    }
                } else {
                    $studentInfo['avatar'] = "assets/images/avatars/bootFace.png";
                }

                $studentInfo['created_at'] = $student->created_at;
                $studentInfo['id'] = $student->id;
                $studentInfo['name'] = $student->name . " " . $student->last_name;
                $studentInfo['grade'] = $student->grade;
                if ($student->role_id == '13' || ($student->level_id == 1 && ($student->role_id == 34 || $student->role_id == 35 || $student->role_id == 36))) {
                    $studentInfo['level'] = 1; // preescolar
                } else if ($student->role_id == '6' || ($student->level_id == 3 && ($student->role_id == 34 || $student->role_id == 35 || $student->role_id == 36))) {
                    $studentInfo['level'] = 3; // secundaria
                } else {
                    $studentInfo['level'] = 2; // primaria
                }

                if (GroupUserEnrollment::join('groups', 'groups.id', 'group_user_enrollments.group_id')->where([
                        ['group_user_enrollments.user_id', '=', $student->id],
                        ['groups.teacher_id', '=', $user->id]
                    ])->exists())
                {
                    $groups = GroupUserEnrollment::select('groups.name', 'groups.id')
                        ->join('groups', 'groups.id', 'group_user_enrollments.group_id')
                        ->where([
                            ['group_user_enrollments.user_id', '=', $student->id],
                            ['group_user_enrollments.school_id', '=', $user->school_id],
                            ['groups.teacher_id', '=', $user->id]
                        ])->get()->toArray();

                    $i = 0;
                    $dataG = [];
                    $dataGId = [];
                    foreach ($groups as $key => $a){
                        $dataG[$i++] = $a['name'];
                        $dataGId[$i++] = $a['id'];
                    }

                    $studentInfo['groups'] = implode(', ', $dataG);
                    $studentInfo['groups_id'] = implode(',', $dataGId);

                }else{
                    $studentInfo['groups'] = 'Sin asignar';
                    $studentInfo['groups_id'] = '0';
                }

                $promedio = Homework::join('activity', 'homework.activity_id', 'activity.id' )
                    ->join('users', 'homework.student_id', 'users.id')
                    ->join('group_user_enrollments', 'group_user_enrollments.user_id', 'users.id')
                    ->select(
                        DB::raw('sum(homework.score) as score'),
                        DB::raw('AVG(score) as promedio'))
                    ->where([['activity.teacher_id', '=', $user->id], ['group_user_enrollments.user_id', '=', $student->id],['activity.is_active', '=', '1']])
                    ->get()->toArray();

                $studentInfo['promedio']= $promedio[0]['promedio'];

                if($promedio[0]['promedio'] === null){
                    $studentInfo['promedio'] = "0.000000";
                }

                array_push($listStudents, $studentInfo);

            }

            return $this->successResponse($listStudents, 'Lista de estudiantes', 200);

        } catch (Exception $exception) {
            return $this->errorResponse('Hubo un problema al consultar la información', 422);

        }
    }

    public function groupListStudent($idStudent){
        try {
            $user = Auth::user();

            if (GroupUserEnrollment::join('groups', 'groups.id', 'group_user_enrollments.group_id')->where([
                ['group_user_enrollments.user_id', '=',$idStudent],
                ['groups.teacher_id', '=', $user->id]
            ])->exists())
            {
                $groups = GroupUserEnrollment::select('groups.name', 'groups.grade', 'groups.id')
                    ->join('groups', 'groups.id', 'group_user_enrollments.group_id')
                    ->where([
                        ['group_user_enrollments.user_id', '=', $idStudent],
                        ['group_user_enrollments.school_id', '=', $user->school_id],
                        ['groups.teacher_id', '=', $user->id],
                    ])->get()->toArray();

            }else{
                $groups = 'Sin asignar';
            }

            return $this->successResponse($groups, 'Lista de grupos Alumno', 200);
        }catch (ModelNotFoundException $exception ){
            return $this->errorResponse('Hubo un problema con la consulta', 422);
        }
    }

    public function studentIsNotInGroups($idStudent){
        try {
            $user = Auth::user();

            $groups = Group::select('id', 'name')
                ->whereRaw('groups.id not in (select group_user_enrollments.group_id from group_user_enrollments where group_user_enrollments.group_id IS NOT NULL AND group_user_enrollments.user_id = '.$idStudent.' ) ' )
                ->where([
                    ['teacher_id', '=', $user->id],
                    ['school_id', '=', $user->school_id],
                ])
                ->get();

            return $this->successResponse($groups, 'Lista de grupos Alumno', 200);
        }catch (ModelNotFoundException $exception ){
            return $this->errorResponse('Hubo un problema con la consulta', 422);
        }
    }

    public function listTutorsStudents()
    {
        try {
            $user = Auth::user();
            if ($user->role_id > 2) {
                $users['students'] = User::select('id', 'name', 'second_name', 'last_name', 'second_last_name')->whereIn('role_id', [5, 13, 6, 18, 19, 20, 21, 34, 35, 36])->where('school_id', $user->school_id)
                    ->orderBy('name')->orderBy('last_name')->get();
                $users['tutor'] = User::select('id', 'name', 'second_name', 'last_name', 'second_last_name')->whereIn('role_id', [10, 31, 32, 33])->where('school_id', $user->school_id)
                    ->orderBy('name')->orderBy('last_name')->get();
            } else {
                $users['students'] = User::select('id', 'name', 'second_name', 'last_name', 'second_last_name')->whereIn('role_id', [5, 13, 6, 18, 19, 20, 21, 34, 35, 36])
                    ->orderBy('name')->orderBy('last_name')->get();
                $users['tutor'] = User::select('id', 'name', 'second_name', 'last_name', 'second_last_name')->whereIn('role_id', [10, 31, 32, 33])
                    ->orderBy('name')->orderBy('last_name')->get();
            }
            return $this->successResponse($users);
        } catch (Exception $exception) {
            return $this->errorResponse("Error al consultar la información", 422);
        }
    }

    public function listTeachers()
    {
        try {
            $user = Auth::user();

            if (in_array($user->role_id, [4, 7, 8, 28, 29, 30])) {
                $teachers = User::select('id', 'username', \DB::raw('CONCAT(COALESCE(name,"")," ",COALESCE(second_name+" ",""),COALESCE(last_name,"")) as teachers_name'), 'school_id', 'email')->where([
                    ['school_id', '=', $user->school_id]])
                    ->where('id', $user->id)
                    ->get();
            } else {
                $teachers = User::select('id', 'username', \DB::raw('CONCAT(COALESCE(name,"")," ",COALESCE(second_name+" ",""),COALESCE(last_name,"")) as teachers_name'), 'school_id', 'email')->where([
                    ['school_id', '=', $user->school_id]])
                    ->whereIn('role_id', [4, 7, 8, 28, 29, 30])
                    ->get();
            }

            if (!$teachers->isEmpty()) {
                return $this->successResponse($teachers);
            } else {
                $userMail[0] = ["email" => $user->email, "id" => $user->id, "school_id" => $user->school_id, "teachers_name" => ($user->name . $user->second_name . " " . $user->last_name)];
                return $this->successResponse($userMail);
            }

        } catch (Exception $exception) {
            return $this->errorResponse("Error al consultar la información", 422);
        }

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
            $validator = $this->validateUser();
            
            if ($validator->fails()) {
                return $this->errorResponse($validator->errors()->first(), 422);
            }

            $user = Auth::user();
            $input = $request->all();

            $roleFox = [
                '1' => '1', //Admin - Administrator
                '2' => '2', //Ventas - Registered User
                '3' => '7', //Admin Escuela - Escuela LIA - Director /coordinador
                '4' => '8', //Maestro - MaestroLIA
                '5' => '9', //Alumno - AlumnoLIA
                '10' => '10', //Padre - Papá-EscuelaLIA
                '13' => '14', //Preescolar - AlumnoPreescolarLIA
                '6' => '15', //Alumno Secundaria - AlumnoSecundariaLIA
                '7' => '16', //Maestro Preescolar - MaestroPreescolarLIA
                '8' => '17', //Maestro Secundaria - MaestroSecundariaLIA
                '9' => '18', //Director Escuela - DirectorEscuelaLIA
                '17' => '19', //ProfesorSummit2021 - ProfesorSummit2021
                '18' => '20', //AlumnoE0 - Metropolitan
                '19' => '21', //AlumnoE1 - AlumnoE1
                '20' => '22', //AlumnoE2 - AlumnoE1
                '21' => '23', //AlumnoE3 - AlumnoE1
                '22' => '24', //MaestroE1 - MaestroE1
                '23' => '25', //MaestroE2 - MaestroE2
                '24' => '26', //MaestroE3 - MaestroE3
                '25' => '27', // Escuela-I - Escuela Invitado
                '26' => '28', // Escuela-M - Escuela Mensual
                '27' => '29', // Escuela-A - Escuela Anual
                '28' => '30', // Maestro-I - Maestro Invitado
                '29' => '31', // Maestro-M - Maestro Mensual
                '30' => '32', // Maestro-A - Maestro Anual
                '31' => '33', // Padre-I - Padre Invitado
                '32' => '34', // Padre-M - Padre Mensual
                '33' => '35', // Padre-A - Padre Anual
                '34' => '36', // Alumno-I - Alumno Invitado Primaria
                '35' => '37', // Alumno-M - Alumno Mensual Primaria
                '36' => '38'  // Alumno-A - Alumno Anual Primaria
            ];
            if ($user->role_id == 1 || $user->role_id == 2) {
                $dataCreate['role_id'] = $input['role_id'];
                $dataCreate['school_id'] = $input['school_id'];
                $dataCreate['company_id'] = $input['company_id'];
            } else {
                if (in_array($input['role_id'], [4, 5, 13, 6, 7, 8, 9, 10, 17, 18, 19, 20, 21, 22, 23, 24, 25, 26, 27, 28, 29, 30, 31, 32, 33, 34, 35, 36])) {
                    $dataCreate['role_id'] = $input['role_id'];
                } else {
                    $dataCreate['role_id'] = 4;
                }
                $dataCreate['school_id'] = $user->school_id;
                $dataCreate['company_id'] = $user->company_id;
            }

            $dataCreate['name'] = $input['name'];
            $dataCreate['username'] = $input['username'];
            $dataCreate['last_name'] = $input['last_name'];
            $dataCreate['grade'] = $input['grade'];
            $dataCreate['email'] = $input['email'];

            $password = $input['password'];
            $passwordBcrypt = bcrypt($password);

            $passwordEncode = str_replace("$2y$", "$2a$", $passwordBcrypt);
            $dataCreate['password'] = $passwordEncode;

            $email = $input['email'];
            $username = $dataCreate['username'];

            if (array_key_exists('tutor_id', $input) && (in_array($dataCreate['role_id'], [5, 13, 6, 18, 19, 20, 21, 34, 35, 36])) && $input['tutor_id']) {
                $dataCreate['tutor_id'] = $input['tutor_id'];
            }

            if (User::where([['email', '=', $email]])->exists()) {
                return $this->errorResponse('El correo ya existe.', 422);
            } else if (User::where([['username', '=', $username]])->exists()) {
                return $this->errorResponse('El nombre de usuario ya existe.', 422);
            } else {
                $dataCreate['username'] = $username;
            }

            $now = new DateTime();
            //Data to insert new user in sql db
            $dataLIA = ([
                'AppUser' => $dataCreate['username'],
                'Names' => $dataCreate['name'],
                'LastNames' => $dataCreate['last_name'],
                'Email' => $dataCreate['email'],
                'Grade' => $dataCreate['grade'],
                'Password' => $dataCreate['password'],
                'RoleId' => $dataCreate['role_id'],
                'IsActive' => 1,
                'SchoolId' => $dataCreate['school_id'],
                'CompanyId' => $dataCreate['company_id'],
                'SchoolGroupKey' => 140232,
                'MemberSince' => $now,
                'CreatorId' => 68,
                'EditorId' => 68,
                'Avatar' => null,
            ]);

            if (Config::get('app.sync_lia') !== false && (in_array($dataCreate['role_id'], [13, 7]))) {
                $userLIA = UserLIA::create($dataLIA);
                $dataCreate['AppUserId'] = $userLIA->AppUserId;
            }

            $user = User::create($dataCreate);
            if(isset($request->childrens_id)){
                foreach ($request->childrens_id as $key => $child) {
                    $children = User::where('id', $child['value'])
                        ->first();
                    if(!is_null($children)){
                        $children->tutor_id = $user->id;
                        $children->save();
                    }
                }
            }
            if((in_array($user['role_id'], [5, 13, 6, 34, 35, 36]))){
                $data = [
                    'user_id' => $user->id,
                    'avatar_id' => 11,
                    'custom_name' => 'Franky',
                    'avatar_path' => 'assets/images/avatars/bootFace.png'
                ];
                AvatarUsers::firstOrCreate($data);
            }else {
                $data = [
                    'user_id' => $user->id,
                    'avatar_id' => 11,
                    'custom_name' => 'Franky',
                    'avatar_path' => 'assets/images/avatars/user.jpg'
                ];
                AvatarUsers::firstOrCreate($data);
            }
            $dataEmail = ([
                'username' => $user->username,
                'name' => $user->name,
                'last_name' => $user->last_name,
                'email' => $user->email,
                'grade' => $user->grade,
                'password' => $password
            ]);
            //Data to insert new user in the Academy
            $dataThink = ([
                'first_name' => $user->username,
                'last_name' => $user->last_name,
                'email' => $user->email,
                'password' => $password
            ]);

            $academyUser = new UserThinkific();
            $academyUser = $academyUser->createUser($dataThink);

            $inputuser = $academyUser;

            if (array_key_exists("errors", $inputuser)) {
                $errors['academia'] = (array)["academy" => $inputuser, "id" => $user->id];
            } else {
                $groupName = [
                    '4' => 'Maestros LIA', //Maestro
                    '5' => 'Alumnos LIA Primaria', //Alumno Primaria
                    '6' => 'Alumnos LIA Secundaria', //Alumno Secundaria
                    '10' => 'Papás LIA', //Padres
                    '13' => 'Alumnos LIA Preescolar', //Alumno Preescolar
                    '17' => 'profesorsummit2021',
                    '18' => 'Metropolitan'
                ];

                $affected = User::find($user->id);
                $affected->active_thinkific = $inputuser['id'];
                $affected->save();
                if ($affected->role_id == 4 || $affected->role_id == 5 || $affected->role_id == 6 || $affected->role_id == 10 ||
                    $affected->role_id == 13 || $affected->role_id == 17 || $affected->role_id == 18) {
                    $academyGroup = new UserThinkific();
                    $academyGroup = $academyGroup->groupAssign($affected->active_thinkific, $groupName[$affected->role_id]);
                    $success['academia'] = (array)["academy" => $inputuser, "group_academy" => $academyGroup, "id" => $user->id];
                }
            }

            //Data to insert new user in the Community
            if (UserCommunity::where([['email', '=', $user->email]])->exists()) {
                $repeatCommunity = UserCommunity::where([['email', '=', $user->email]])->first()->toArray();
                $user->active_phpfox = $repeatCommunity['user_id'];
                $user->save();
                $comunidad['error'] = ['El correo electronico ya esta asignado', $repeatCommunity, $user];
            } else {
                $dataFox = ([
                    'email' => $user->email,
                    'full_name' => $user->name . ' ' . $user->last_name,
                    "user_name" => $user->username,
                    'password' => $passwordBcrypt,
                    'user_group_id' => $roleFox[$dataCreate['role_id'] == 7 ? 13 : $dataCreate['role_id']],
                    'joined' => Carbon::now()->timestamp,
                ]);

                $userCommunity = UserCommunity::create($dataFox)->toArray();
                $userCommunityId = ['user_id' => $userCommunity['user_id']];
                PhpFox_activitypoint_statistics::create($userCommunityId);
                PhpFox_user_activity::create($userCommunityId);
                PhpFox_user_field::create($userCommunityId);
                PhpFox_user_space::create($userCommunityId);
                PhpFox_user_count::create($userCommunityId);

                $user->active_phpfox = $userCommunityId["user_id"];
                $user->save();
                $success['comunidad'] = $userCommunity;
            }
            if (Config::get('app.send_email')) {
                SendEmail::dispatchNow($dataEmail);
            }

            if (!empty($comunidad)) {
                $success['comunidad'] = $comunidad['error'];
            }

            $success['message'] = 'Usuario creado';

            return $this->successResponse($success, 200);

        } catch (Exception $e) {
            return $this->errorResponse(['message' => $e->getMessage()], 422);
        }
    }

    /**
     * Display the specified resource.
     *
     * @param uuid $uuid
     * @return JsonResponse
     */
    public function show($uuid)
    {

        $user = User::where('uuid', 'like', '%' . $uuid . '%')->get();
        return $this->successResponse($user);
    }
    public function getUserActitivies($userId){
        $user = User::where('id', $userId)
            ->firstOrFail();
        $activities = DigitalResource::where('level', $user->level_id)
            ->where('grade', $user->grade)
            ->inRandomOrder()
            ->limit(5)
            ->get();
        $user->activities = $activities;
        return $this->successResponse($user, 200);
    }
    /**
     * Update the specified resource in storage.
     *
     * @param uuid $uuid
     * @return JsonResponse
     */
    public function update($uuid)
    {
        $request = request()->all();

        try {
            $validator = Validator::make($request, [
                'name' => 'required',
                'email' => 'required|email',
                'role_id' => 'required',
                'school_id' => 'required',
                'last_name' => 'required',
                // 'grade' => 'required',
            ]);
            if ($validator->fails()) {
                $error["code"] = 'INVALID_DATA';
                $error["message"] = "Información Invalida.";
                $error["errors"] = $validator->errors();
                return response()->json(['error' => $error], 200);
            }

            $user = Auth::user();
            $input = $request;
            $lia = true;

            $roleFox = [
                '1' => '1', //Admin - Administrator
                '2' => '2', //Ventas - Registered User
                '3' => '7', //Admin Escuela - Escuela LIA - Director /coordinador
                '4' => '8', //Maestro - MaestroLIA
                '5' => '9', //Alumno - AlumnoLIA
                '10' => '10', //Padre - Papá-EscuelaLIA
                '13' => '14', //Preescolar - AlumnoPreescolarLIA
                '6' => '15', //Alumno Secundaria - AlumnoSecundariaLIA
                '7' => '16', //Maestro Preescolar - MaestroPreescolarLIA
                '8' => '17', //Maestro Secundaria - MaestroSecundariaLIA
                '9' => '18', //Director Escuela - DirectorEscuelaLIA
                '17' => '19', //ProfesorSummit2021 - ProfesorSummit2021
                '18' => '20', //AlumnoE0 - Metropolitan
                '19' => '21', //AlumnoE1 - AlumnoE1
                '20' => '22', //AlumnoE2 - AlumnoE1
                '21' => '23', //AlumnoE3 - AlumnoE1
                '22' => '24', //MaestroE1 - MaestroE1
                '23' => '25', //MaestroE2 - MaestroE2
                '24' => '26', //MaestroE3 - MaestroE3
                '25' => '27', // Escuela-I - Escuela Invitado
                '26' => '28', // Escuela-M - Escuela Mensual
                '27' => '29', // Escuela-A - Escuela Anual
                '28' => '30', // Maestro-I - Maestro Invitado
                '29' => '31', // Maestro-M - Maestro Mensual
                '30' => '32', // Maestro-A - Maestro Anual
                '31' => '33', // Padre-I - Padre Invitado
                '32' => '34', // Padre-M - Padre Mensual
                '33' => '35', // Padre-A - Padre Anual
                '34' => '36', // Alumno-I - Alumno Invitado Primaria
                '35' => '37', // Alumno-M - Alumno Mensual Primaria
                '36' => '38'  // Alumno-A - Alumno Anual Primaria
            ];

            if ($user->role_id == 1 || $user->role_id == 2) {
                $dataCreate['role_id'] = $input['role_id'];
                $dataCreate['school_id'] = $input['school_id'];
            } else {
                if (in_array($input['role_id'], [4, 5, 13, 6, 7, 8, 9, 10, 17, 18, 19, 20, 21, 22, 23, 24, 25, 26, 27, 28, 29, 30, 31, 32, 33, 34, 35, 36])) {
                    $dataCreate['role_id'] = $input['role_id'];
                } else {
                    $dataCreate['role_id'] = 4;
                }
                $dataCreate['school_id'] = $user->school_id;
            }

            $dataCreate['name'] = $input['name'];
            $dataCreate['last_name'] = $input['last_name'];
            $dataCreate['grade'] = $input['grade'];
            $dataCreate['email'] = $input['email'];

            if (array_key_exists('tutor_id', $input) && (in_array($dataCreate['role_id'], [5, 13, 6, 18, 19, 20, 21, 34, 35, 36]))) {
                $dataCreate['tutor_id'] = $input['tutor_id'] == 0 ? null : $input['tutor_id'];
            }

            if (array_key_exists('password', $input)) {

                $password = $input['password'];
                $passwordEncode = bcrypt($password);
                $passwordEncode = str_replace("$2y$", "$2a$", $passwordEncode);
                $dataCreate['password'] = $passwordEncode;

                $dataLIA = ([
                    'Names' => $dataCreate['name'],
                    'LastNames' => $dataCreate['last_name'],
                    'Email' => $dataCreate['email'],
                    'Grade' => $dataCreate['grade'],
                    'Password' => $dataCreate['password'],
                    'RoleId' => $dataCreate['role_id'],
                    'SchoolId' => $dataCreate['school_id']
                ]);
            } else {
                $dataLIA = ([
                    'Names' => $dataCreate['name'],
                    'LastNames' => $dataCreate['last_name'],
                    'Email' => $dataCreate['email'],
                    'Grade' => $dataCreate['grade'],
                    'RoleId' => $dataCreate['role_id'],
                    'SchoolId' => $dataCreate['school_id']
                ]);
            }

            if (User::where([['email', '=', $dataCreate['email']], ['uuid', '!=', $input['uuid']]])->exists()) {
                return $this->errorResponse('El correo ya existe.', 422);
            }

            $user = User::where('uuid', 'like', '%' . $uuid . '%')->firstOrFail();

            if (Config::get('app.sync_lia') && !(in_array($dataCreate['role_id'], [19, 20, 21, 22, 23, 24, 25, 26, 27, 28, 29, 30, 31, 32, 33, 34, 35, 36]))) {
                if($user->AppUserId != null){
                    $userLIA = UserLIA::where('AppUserId', '=', $user->AppUserId)->first();
                    if ($userLIA) {
                        $lia = false;
                        $userLIA->update($dataLIA);
                    }
                }else{
                    if($user->role_id = 7 || $user->role_id = 13){
                        $now = new DateTime();

                        $userPlus = ([
                            'AppUser' => $user->username,
                            'Names' => $user->name,
                            'LastNames' => $user->last_name,
                            'Email' => $user->email,
                            'Grade' => $user->grade,
                            'Password' => $user->password,
                            'RoleId' => $user->role_id,
                            'IsActive' => 1,
                            'SchoolId' => $user->school_id,
                            'SchoolGroupKey' => 140232,
                            'MemberSince' => $now,
                            'CreatorId' => 68,
                            'EditorId' => 68,
                            'Avatar' => null,
                        ]);
                       $userPl = UserLIA::create($userPlus);
                       $user->AppuserId = $userPl->AppUserId;
                       $user->save();
                    }
                    $userLIA = UserLIA::where('AppUserId', '=', $user->AppUserId)->first();
                    if ($userLIA) {
                        $lia = false;
                        $userLIA->update($dataLIA);
                    }
                }
            }

            $updatedUser = User::where('uuid', 'like', '%' . $uuid . '%')->firstOrFail();
            $updatedUser->update($dataCreate);
            if(isset($input['childrens_id'])){
                User::where('tutor_id', $updatedUser->id)
                    ->update([
                        'tutor_id' => null
                    ]);
                foreach ($input['childrens_id'] as $key => $child) {
                    $children = User::where('id', $child['value'])
                        ->first();
                    if(!is_null($children)){
                        $children->tutor_id = $updatedUser->id;
                        $children->save();
                    }
                }
            }
            $dataFox = ([
                'email' => $dataCreate['email'],
                'full_name' => $dataCreate['name'] . ' ' . $dataCreate['last_name'],
                'user_group_id' => $roleFox[$dataCreate['role_id'] == 7 ? 13 : $dataCreate['role_id']],
            ]);

            UserCommunity::where('user_id', '=', $user->active_phpfox)->firstOrFail()->update($dataFox);

            $success['message'] = 'Usuario Actualizado';
            $success['code'] = 200;
            return $this->successResponse($success, 200);

        } catch (ModelNotFoundException $exception) {
            $error["code"] = '422';
            $error["exception"] = "Error al actualizar el usuario";
            $error["message"] = "Error al actualizar el usuario";

            return $this->errorResponse(['error' => $error], 422);
        }
    }

    public function updateGroup()
    {
        $request = request()->all();
        $dataUpdate = null;
        $appUsersIds = [];
        $communityIds = [];
        try {
            $validator = Validator::make($request, [
                'users' => 'required'
            ]);
            if ($validator->fails()) {
                $error["code"] = 'INVALID_DATA';
                $error["message"] = "Información Invalida.";
                $error["errors"] = $validator->errors();
                return $this->errorResponse(['error' => $error], 422);
            }

            $user = Auth::user();
            $input = $request;
            $roleFox = [
                '1' => '1', //Admin - Administrator
                '2' => '2', //Ventas - Registered User
                '3' => '7', //Admin Escuela - Escuela LIA - Director /coordinador
                '4' => '8', //Maestro - MaestroLIA
                '5' => '9', //Alumno - AlumnoLIA
                '10' => '10', //Padre - Papá-EscuelaLIA
                '13' => '14', //Preescolar - AlumnoPreescolarLIA
                '6' => '15', //Alumno Secundaria - AlumnoSecundariaLIA
                '7' => '16', //Maestro Preescolar - MaestroPreescolarLIA
                '8' => '17', //Maestro Secundaria - MaestroSecundariaLIA
                '9' => '18', //Director Escuela - DirectorEscuelaLIA
                '17' => '19', //ProfesorSummit2021 - ProfesorSummit2021
                '18' => '20' //AlumnoE0 - Metropolitan
            ];

            if (array_key_exists('role_id', $input)) {
                if ($user->role_id == 1 || $user->role_id == 2) {
                    $dataUpdate['role_id'] = $input['role_id'];
                } else {
                    if ($input['role_id'] == 4 || $input['role_id'] == 5 || $input['role_id'] == 13 || $input['role_id'] == 10 || $input['role_id'] == 6 || $input['role_id'] == 7 || $input['role_id'] == 8 || $input['role_id'] == 9 || $input['role_id'] == 17 || $input['role_id'] == 18) {
                        $dataUpdate['role_id'] = $input['role_id'];
                    } else {
                        $dataUpdate['role_id'] = 4;
                    }
                }
                $dataLIA['RoleId'] = $dataUpdate['role_id'];
                $dataFox = ([
                    'user_group_id' => $roleFox[$dataUpdate['role_id'] == 7 ? 13 : $dataUpdate['role_id']],
                ]);
            }

            if (array_key_exists('school_id', $input)) {
                if ($user->role_id == 1 || $user->role_id == 2) {
                    $dataUpdate['school_id'] = $input['school_id'];
                } else {
                    $dataUpdate['school_id'] = $user->school_id;
                }
                $dataLIA['SchoolId'] = $dataUpdate['school_id'];
            }

            if (array_key_exists('grade', $input)) {
                $dataUpdate['grade'] = $input['grade'];
                $dataLIA['Grade'] = $dataUpdate['grade'];
            }

            if (array_key_exists('password', $input)) {
                $password = $input['password'];
                $passwordEncode = bcrypt($password);
                $passwordEncode = str_replace("$2y$", "$2a$", $passwordEncode);
                $dataUpdate['password'] = $passwordEncode;
                $dataLIA['Password'] = $dataUpdate['password'];
            }
            $users = \DB::table('users')->whereIn('uuid', $input['users'])->get()->toArray();

            foreach ($users as $obj) {
                if ($obj->AppUserId) {
                    $appUsersIds[] = $obj->AppUserId;
                }
                if ($obj->active_phpfox) {
                    $communityIds[] = $obj->active_phpfox;
                }
            }
            if ($dataUpdate) {
                $dataUpdateResult = \DB::table('users')->whereIn('uuid', $input['users'])->update($dataUpdate);

                if (array_key_exists('role_id', $input)) {
                    UserCommunity::whereIn('user_id', $communityIds)->update($dataFox);
                }

                $success['message'] = $dataUpdateResult . ' usuario(s) actualizado(s)';
                $success['code'] = 200;
            } else {
                $success['message'] = '0 usuarios actualizados';
                $success['code'] = 200;
            }
            return $this->successResponse($success, 200);

        } catch (ModelNotFoundException $exception) {
            $error["code"] = '500';
            $error["exception"] = "Error al actualizar los usuarios";
            $error["message"] = "Error al actualizar los usuarios";

            return $this->errorResponse(['error' => $error], 500);
        }

    }

    public function multiDestroy(Request $request)
    {

        $input = $request->all();
        $i = 0;


        foreach ($input as $userUUID) {
            $userDelete = self::destroy($userUUID);
        }
        return $this->successResponse($input, 200);

    }

    /**
     * Remove the specified resource from storage.
     * @param uuid $uuid
     * @return JsonResponse
     */
    public function destroy($uuid)
    {
        try {

            $user = User::where('uuid', 'like', '%' . $uuid . '%')->firstOrFail();

            if ($user->role_id == 10 || $user->role_id == 31 || $user->role_id == 32 || $user->role_id == 33) {
                \DB::table('users')->where('tutor_id', [$user->id])->update(['tutor_id' => null]);
            }

            if (Activity::where('teacher_id', '=', $user->id)->exists()) {

                Activity::where('teacher_id', '=', $user->id)->delete();
            }

            if (Group::where('teacher_id', '=', $user->id)->exists()) {

                $userGroup = Group::where('teacher_id', '=', $user->id)->get();

                foreach ($userGroup as $groupEnrollment) {
                    Group::where('id', '=', $groupEnrollment->id)->delete();
                }
            }


            GroupUserEnrollment::where('user_id', '=', $user->id)->delete();


            if ($user->active_thinkific != 0) {
                $deleteUserT = new UserThinkific();
                $delete = $deleteUserT->deleteUserSchooling($user->active_thinkific);
            }

            if ($user->active_phpfox != 0) {
                UserCommunity::where('user_id', '=', $user->active_phpfox)->delete();
            }


            $user->delete();

            $success['message'] = 'El usuario ha sido eliminado existosamente';
            $success['code'] = 200;
            return $this->successResponse($success, 200);
        } catch (ModelNotFoundException  $exception) {
            $error["code"] = '500';
            $error["message"] = "Error al eliminar el usuario";
            $error["getMessage"] = "Error al eliminar el usuario";

            return $this->errorResponse(['error' => $error], 500);
        }
    }

    public function assignLicense(Request $limit)
    {
        try {
            //listamos todos los usuarios
            $listUser = self::index()->getOriginalContent();

            $i = 0;

            foreach ($listUser as $obj => $user) {

                $schoolId = $user->school_id;
                $userUuid = $user->uuid;
                $roleId = $user->role_id;
                //Preguntamos si tiene el usuario cuenta con una llave
                if (LicenseKey::where([['user_id', '=', $userUuid]])->exists()) {
                    $count[$i++] = [
                        'message' => 'El usuario ya tiene una llave asignada',
                        'code' => 201
                    ];
                } else {
                    if ($roleId != 1) { //Aqui tienen que ir las demas condiciones de acuerdo al rol
                        $school = new School();
                        $school = $school->show($schoolId)->getOriginalContent();
                        if (License::where([['school_id', '=', $schoolId]])->exists()) {

                            $licenseId = License::where([['school_id', '=', $schoolId]])->first();

                            $dataKey = [
                                'user_id' => $userUuid,
                                'license_id' => $licenseId->id
                            ];

                            $licenseKey = LicenseKey::create($dataKey);

                        } else {
                            $dataLicense = [
                                'titular' => $school->name,
                                'email_admin' => 'dlievano@arkusnexus.com',
                                'school_id' => $schoolId,
                                'license_type_id' => 1,
                                'user_id' => $userUuid,
                                'studens_limit' => $limit["students_limit"],
                            ];

                            $license = License::create($dataLicense);
                            $license->save();

                            $dataKey = [
                                'user_id' => $userUuid,
                                'license_id' => $license->id
                            ];

                            $licenseKey = LicenseKey::create($dataKey);
                        }

                    }
                    $count[$i++] = [
                        'message' => 'El se a asignado una llave al usuario',
                        'data' => $licenseKey,
                        'code' => 201
                    ];
                }
            }
            return [
                'data' => $count
            ];
        } catch (\Exception $exception) {
            return $exception;
        }

    }

    public function validateUser()
    {
        $messages = [
            'name.required' => 'El campo nombre es requerido.',
            'email.required' => 'El correo electrónico es requerido.',
            'role_id.required' => 'Es necesario seleccionar un tipo de rol',
            'school_id.required' => 'Es necesario seleccionar una escuela',
            'username.required' => 'El campo nombre de usuario es requerido',
            'last_name.required' => 'El campo apellido paterno es requerido',
            // 'grade.required' => 'Selecciona un grado',
            'password.required' => 'El campo contraseña es necesario',
            'password.min' => 'La contraseña debe tener al menos :min caracteres'
        ];

        return Validator::make(request()->all(), [
            'name' => 'required',
            'email' => 'required|email',
            'role_id' => 'required',
            'school_id' => 'required',
            'username' => 'required',
            'last_name' => 'required',
            // 'grade' => 'required',
            'password' => 'required|min:8',
        ], $messages);
    }

    public function syncLevel()
    {
        try {

            $users = User::whereIn('role_id', [4, 5, 6, 7, 8, 13])->get();

            $level = [
                '4' => '2', //Maestro - MaestroLIA
                '5' => '2', //Alumno - AlumnoLIA
                '13' => '1', //Preescolar - AlumnoPreescolarLIA
                '6' => '3', //Alumno Secundaria - AlumnoSecundariaLIA
                '7' => '1', //Maestro Preescolar - MaestroPreescolarLIA
                '8' => '3', //Maestro Secundaria - MaestroSecundariaLIA
            ];

            $i = 0;

            foreach ($users as $user) {

                $role = $user->role_id;
                $user->level_id = $level[$role];
                $user->save();
                $count[$i++] = $user;
            }
            return $this->successResponse($count, 'Se han actulizado con exito', 200);
        } catch (Exception $e) {
            return $this->errorResponse("Error al sincronizar el nivel", 422);
        }
    }

    public function getStudents()
    {
        try{
            $user = Auth::user();
            // $teacher = User::where('id', $groupLia->teacher_id)->firstOrfail();

            $students = User::where('school_id', $user->school_id)
            ->whereIn('role_id', [5, 6, 13, 18, 19, 20, 21, 34, 35, 36])
            ->select('id','username',\DB::raw('CONCAT(COALESCE(name,"")," ",COALESCE(second_name+" ",""),COALESCE(last_name,"")) as name'),'school_id','email','grade','role_id')
            ->get();

            foreach($students as $student){
                $avatar = AvatarUsers::where('user_id', $student->id)
                    ->select('avatar_id','avatar_path')
                    ->get();
                $student->avatar = $avatar;
            }

            return $this->successResponse($students);
        }catch (Exception $exception){
            return $this->errorResponse("Error al consultar la información", 422);
        }
    }

    public function getStudentAverage($id)
    {
        try {
            $user = Auth::user();

            $average = User::select(
                \DB::raw('CONCAT(COALESCE(users.name,"")," ",COALESCE(users.second_name+" ",""),COALESCE(users.last_name,"")) as name'),
                \DB::raw('DATE_FORMAT(users.created_at, "%d/%m/%Y") AS date'),
                'avatar_users.avatar_path as avatar',
                \DB::raw('AVG(homework.score) as average'),
                'custom_subjects.custom_name as subject',
                'groups.name as group',
                'custom_subjects.custom_color as color'
            )
            ->join('group_user_enrollments', 'users.id', 'group_user_enrollments.user_id')
            ->join('groups', 'group_user_enrollments.group_id', 'groups.id')
            ->join('custom_subjects', 'groups.id', 'custom_subjects.group_id')
            ->join('activity', 'activity.subject_id', 'custom_subjects.id')
            ->join('homework', 'homework.activity_id', 'activity.id')
            ->leftJoin('avatar_users', 'users.id', 'avatar_users.user_id')
            ->where('homework.student_id', "=", $id)
            ->where('activity.is_active', "=", '1')
            ->where('activity.teacher_id', "=", $user->id)
            ->where('users.id', "=", $id)
            // saca el promedio general del estudiante con el rollup
            ->groupByRaw('custom_subjects.id with rollup')
            ->get();

            $n = sizeof($average);
            if ( $n <= 0) {
                // toma la información de usuario en el caso de que la consulta anterior venga vacia
                $average = User::select(
                    \DB::raw('CONCAT(COALESCE(users.name,"")," ",COALESCE(users.second_name+" ",""),COALESCE(users.last_name,"")) as name'),
                    \DB::raw('DATE_FORMAT(users.created_at, "%d/%m/%Y") AS date'),
                    'avatar_users.avatar_path as avatar',
                )
                ->leftJoin('avatar_users', 'users.id', 'avatar_users.user_id')
                ->where('users.id', "=", $id)
                ->get();
                $n = sizeof($average);
            } else {
                // Para que en el rollup no salga una materia y grupo cualquiera
                $average[$n - 1]->group = '--';
                $average[$n - 1]->subject = '--';
            }

            if ( !$average[$n - 1]->avatar || $average[$n - 1]->avatar == '' ){
                // valida que venga el avatar del usuario
                $average[$n - 1]->avatar = "assets/images/avatars/bootFace.png";
            }

            return $this->successResponse($average, 200);
        } catch (ModelNotFoundException $e) {
            return $this->errorResponse('No hay elementos que coincidad', 422);
        }
    }

    public function changePasswordPost(Request $request) {
        try {
            $input = $request->all();
            if($input['password-confirmation'] != $input['password']){
                // Current password and new password same
                return $this->errorResponse('Las contraseñas no son iguales', 402);
            }
            

            $validatedData = $request->validate([
                'id' => 'required',
                'password' => 'required|string|min:6',
                'password-confirmation' => 'required|string|min:6',
            ]);

            // if($validatedData->errors()){
            //     return $this->errorResponse('Faltan valores requeridos', 402);
            // }

            $user = User::find($input['id']);

            $passwordEncode = bcrypt($input['password']);
            $newPassword = str_replace("$2y$", "$2a$", $passwordEncode);
            $user->password = $newPassword;
            $user->save();

            if($user->role_id == 13){
                $userLIA = UserLIA::find($user->AppUserId);
                $userLIA->Password = $newPassword;
                $userLIA->save();
            }

            return $this->successResponse($user, 'La contraseña se actualizo exitosamente', 200);

        }catch (ModelNotFoundException $e){
            return $this->errorResponse('Hubo un error al actualizar la contraseña', 422);
        }
    }


}
