<?php

namespace App\Http\Controllers;

use App\Activity;
use Google\Cloud\Core\Exception\NotFoundException;
use Illuminate\Contracts\Filesystem\FileNotFoundException;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Validator;
use App\Homework;
use Carbon\Carbon;
use App\User;
use App\Files;
use Mockery\Exception\InvalidOrderException;
use Illuminate\Support\Facades\Response;
use PhpParser\Internal\DiffElem;
use Illuminate\Support\Str;
use function GuzzleHttp\Psr7\_caseless_remove;
use \DateTime;
use App\FirebaseFiles as FirebaseFiles;

class ActivityController extends ApiController
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

        $userInGroups = \DB::table('groups')->where('teacher_id', $user->id)->get();
        $arr[] = '';

        $userInSubjects = \DB::table('custom_subjects')->where('teacher_id', $user->id)->get();
        $arr2[] = '';

        foreach($userInSubjects as $customsubjects){
            array_push($arr2, $customsubjects->id);
        }

        foreach($userInGroups as $group){
            array_push($arr,$group->id);
        }

        $filterGroups = $arr;
        if (array_key_exists('group_id', $request) && $request['group_id'] != null) {
            $filterGroups = [$request['group_id']];
        }

        $filterCustomSubjects = $arr2;
        if (array_key_exists('customsubjects_id', $request) && $request['customsubjects_id'] != 0) {
            $filterCustomSubjects = [$request['customsubjects_id']];
        }

        $activityDB = Activity::whereIn('activity.group_id',$filterGroups) -> whereIn('custom_subjects.id', $filterCustomSubjects)
        ->join('custom_subjects','activity.subject_id','=','custom_subjects.id')
        ->join('groups','activity.group_id','=','groups.id')
        ->join('users','groups.teacher_id','=','users.id')
        ->select(
            'activity.*',
            \DB::raw('DATE_FORMAT(activity.public_day, "%d/%m/%Y") AS public_day'),
            'groups.name as group_name',
            'custom_subjects.custom_name',
            \DB::raw('CONCAT(COALESCE(users.name,"")," ",COALESCE(users.second_name+" ",""),COALESCE(users.last_name,"")) as teachers_name'),
            \DB::raw("(select count(*) from homework where homework.activity_id = activity.id and homework.status= 'Entregado') as status_entregada"),
            'custom_subjects.custom_color'
        )
        ->orderBy('created_at', 'desc');

        if (array_key_exists('from', $request) && $request['from'] !== null) {
            $activityDB->where('finish_date', '>=', $request['from']);
        }
        if (array_key_exists('until', $request) && $request['until'] !== null) {
            $activityDB->where(\DB::raw('DATE(finish_date)'), '<=', $request['until']);
        }

        if (array_key_exists('active_id', $request) && $request['active_id'] != null) {
            $active_id = $request['active_id'];
            $activityDB->where(function ($query) use ($active_id) {
                if ($active_id == 2) {
                    $query->where('activity.is_active', '2');
                } else {
                    $todayDate = today()->format('Y-m-d');
                    if ($active_id == 1) {
                        $query->where([['activity.finish_date', '>=', $todayDate], ['activity.is_active','1']]);
                    } else if ($active_id == 0) {
                        $query->where([['activity.finish_date', '<', $todayDate], ['activity.is_active','1']]);
                    } else {
                        $query->where('activity.is_active', '0');
                    }
                }
            });
        }

        $activityDB = $activityDB->get();

        foreach ($activityDB as $activity) {
            if (array_key_exists('today', $request) && $request['today']) {
                if ($activity->is_active == 1 || $activity->is_active == 0) {
                    $activity->is_active = Carbon::parse($request['today'])->greaterThan(Carbon::parse($activity->finish_date)) ? 3 : $activity->is_active;
                }
            }
            $activity->finish_date = Carbon::parse($activity->finish_date)->format('d/m/Y H:i');

            if (!Str::contains($activity->file_path, '.') && $activity->file_path != null) {
                $files = Storage::disk('local')->allFiles('public/' . $activity->file_path);
                $activity->file_path = $files;
            }
        }

        return $this->successResponse($activityDB, 200);
    }

    /**
     * Display a listing of the resource.
     *
     * @return JsonResponse
     */
    public function getGroups()
    {
        try {
            $user = Auth::user();

            if ($user->role_id == 4 || $user->role_id == 7 || $user->role_id == 8 || $user->role_id == 17 || $user->role_id == 22 || $user->role_id == 23 || $user->role_id == 24 || $user->role_id == 28 || $user->role_id == 29 || $user->role_id == 30) {
                $groups = \DB::table('groups')
                    ->select('id', 'name')
                    ->where('teacher_id', $user->id)
                    ->groupByRaw('id, name')
                    ->get();
                return $this->successResponse($groups, 200);
            } else if ($user->role_id == 5 || $user->role_id == 13 || $user->role_id == 6 || $user->role_id == 18 || $user->role_id == 19 || $user->role_id == 20 || $user->role_id == 21 || $user->role_id == 34 || $user->role_id == 35 || $user->role_id == 36) {
                $groups = Homework::select('groups.id', 'groups.name')->where('student_id', $user->id)
                    ->join('activity', 'homework.activity_id', '=', 'activity.id')
                    ->join('groups', 'activity.group_id', '=', 'groups.id')
                    ->groupByRaw('groups.id, groups.name')
                    ->get();

                return $this->successResponse($groups, 200);
            } else {
                return $this->errorResponse('No se encontraron grupos', 422);
            }
        } catch (ModelNotFoundException $e) {
            return $this->errorResponse('No hay elementos que coincidad', 422);
        }
    }

    /**
     * Store a newly created resource in storage.
     *
     * @param \Illuminate\Http\Request $request
     * @return JsonResponse
     */
    public function store(Request $request)
    {
        try {

        $validator = $this->validateCreateActivity();
        if ($validator->fails()) {
            return $this->errorResponse($validator->messages(), 422);
        }
        $user = Auth::user();
        $input = $request->all();

        if ($user->role_id == 4 || $user->role_id == 7 || $user->role_id == 8 || $user->role_id == 17 || $user->role_id == 22 || $user->role_id == 23 || $user->role_id == 24 || $user->role_id == 28 || $user->role_id == 29 || $user->role_id == 30) {

            if (!\DB::table('groups')->where('id', $input['groupId'])->exists()) {
                return $this->errorResponse("El grupo no existe", 422);
            }
            if (!\DB::table('groups')->where('teacher_id', $user->id)->exists()) {
                return $this->errorResponse("El profesor no es el encargado del grupo", 422);
            }

            $dataActivity = ([
                'teacher_id' => $user->id,
                'group_id' => $input['groupId'],
                'name' => $input['name'],
                'theme' => array_key_exists('theme', $input) && $input['theme'] ? $input['theme'] : "",
                'platform' => array_key_exists('platform', $input) ? $input['platform'] : "",
                'instructions' => array_key_exists('instructions', $input) && $input['instructions'] ? $input['instructions'] : "",
                // 'file_path' => array_key_exists('filePath', $input) ? $input['filePath'] : null,
                'file_path' => null,
                'url_path' => array_key_exists('urlPath', $input) ? $input['urlPath'] : null,
                'resources' => array_key_exists('resources', $input) && $input['resources'] ? $input['resources'] : null,
                'finish_date' => Carbon::createFromFormat('Y-m-d H:i', $input['finishDate']),
                'public_day' => array_key_exists('publicDay', $input) && $input['publicDay'] ? str_replace("/", "-", $input['publicDay']) : null,
                'is_active' => array_key_exists('is_active', $input) && $input['is_active'] ? $input['is_active'] : 0,
                'subject_id' => array_key_exists('subject_id', $input) && $input['subject_id'] ? $input['subject_id'] : null,
            ]);

            $activityDB = Activity::create($dataActivity);

            if ($request->file('files')) {

                $request->filePath = 'homework/'.$activityDB->id;
                $fileRes = self::fileUpload($request, $user->id);
                $activityDB->file_path = $fileRes['filePath'];
                $input['fileId'] = $fileRes['fileId'];
                $activityDB->update(['file_path' => $fileRes['filePath']]);
            }

            //Register homeworks "No entregadas" to students
            $groupStudents = \DB::table('group_user_enrollments')->where('group_id', $input['groupId'])->get();

            foreach ($groupStudents as $students) {
                $dataHomework = ([
                    'student_id' => $students->user_id,
                    'activity_id' => $activityDB->id,
                    'status' => "No entregado",
                    'score' => 0,
                    'file_path' => null,
                    'url_path' => null,
                    'is_active' => array_key_exists('is_active', $input) && $input['is_active'] ? $input['is_active'] : 0,
                ]);

                $homeworkDB = Homework::create($dataHomework);
            }

            $responseActivity = ([
                'id' => $activityDB->id,
                'teacher_id' => $activityDB->teacher_id,
                'group_id' => $activityDB->group_id,
                'name' => $activityDB->name,
                'theme' => $activityDB->theme,
                'platform' => $activityDB->platform,
                'instructions' => $activityDB->instructions,
                'file_path' => $activityDB->file_path,
                'url_path' => $activityDB->url_path,
                'finish_date' => $activityDB->finish_date,
                'is_active' => $activityDB->is_active,
                'fileId' => array_key_exists('fileId', $input) ? $input['fileId'] : '',
            ]);
            return $this->successResponse($responseActivity, 200);
        } else {
            return $this->errorResponse("El usuario tiene que ser maestro", 422);
        }
        }catch (\Exception $exception){
            return $this->errorResponse('Hubo un problema al crear la tarea', 422);
        }
    }

    /**
     * Duplicate an activity.
     *
     * @return JsonResponse
     */
    public function duplicateActivity($id)
    {
        try {
            $activity = Activity::find($id);
            $newActivity = $activity->replicate();

            $user = Auth::user();

            $newName = $newActivity->name;
            if (substr($newName, 0, 44)) {
                $newName = substr($newActivity->name, 0, 44).' copia';
            } else {
                $newName .= ' copia';
            }
            $newActivity->name = $newName;

            $newActivity->save();

            if (isset($activity->file_path) && $activity->file_path !== null) {
                $newActivity->file_path = 'homework/'.$newActivity->id;
                $files = Storage::disk('local')->allFiles('public/'.$activity['file_path']);
                for ($i=0; $i < count($files) ; $i++) {
                    $toFile = str_replace('public/'.$activity['file_path'], 'public/'.$newActivity->file_path, $files[$i]);
                    Storage::copy( $files[$i], $toFile );
                }

                $newActivity->update(['file_path' => $newActivity->file_path]);
            }


            //Register homeworks "No entregadas" to students
            $groupStudents = \DB::table('group_user_enrollments')->where('group_id', $newActivity->group_id)->get();

            foreach ($groupStudents as $students) {
                $dataHomework = ([
                    'student_id' => $students->user_id,
                    'activity_id' => $newActivity->id,
                    'status' => "No entregado",
                    'score' => 0,
                    'file_path' => null,
                    'url_path' => null,
                    'is_active' => $newActivity->is_active,
                ]);

                $homeworkDB = Homework::create($dataHomework);
            }

            $responseActivity = ([
                'id' => $newActivity->id,
                'teacher_id' => $newActivity->teacher_id,
                'group_id' => $newActivity->group_id,
                'name' => $newActivity->name,
                'theme' => $newActivity->theme,
                'platform' => $newActivity->platform,
                'instructions' => $newActivity->instructions,
                'file_path' => $newActivity->file_path,
                'url_path' => $newActivity->url_path,
                'finish_date' => $newActivity->finish_date,
                'is_active' => $newActivity->is_active,
            ]);
            return $this->successResponse($responseActivity, 200);

        } catch (NotFoundException $e) {
            return $this->errorResponse('No hay elementos que coincidan', 422);
        }
    }

    /**
     * Display the specified resource.
     *
     * @param int $id
     * @return JsonResponse
     */
    public function show($id)
    {
        try {
            $activity = Activity::findOrFail($id);

            if(!Str::contains($activity->file_path,'.') && $activity->file_path != null){
                $firebaseFile = new FirebaseFiles();
                $filePath = $firebaseFile->fileList($activity->file_path);
                $activity->file_path = $filePath;
            }
            if (json_decode($activity->url_path, true)){
                $activity->url_path = json_decode($activity->url_path, true);
            }
            return $this->successResponse($activity);
        } catch (ModelNotFoundException $e) {
            return $this->errorResponse('Tipo de licencia invalido', 422);
        }
    }

    /**
     * Update the specified resource in storage.
     *
     * @param \Illuminate\Http\Request $request
     * @param int $id
     * @return JsonResponse
     */
    public function update(Request $request, $id)
    {
        $validator = $this->validateUpdateActivity();
        if ($validator->fails()) {
            return $this->errorResponse($validator->messages(), 422);
        }
        try {
            $user = Auth::user();
            $input = $request->all();
            if ($user->role_id == 4 || $user->role_id == 7 || $user->role_id == 8 || $user->role_id == 17 || $user->role_id == 22 || $user->role_id == 23 || $user->role_id == 24 || $user->role_id == 28 || $user->role_id == 29 || $user->role_id == 30) {
                $activity = Activity::findOrFail($id);

                if (array_key_exists('toDeleteFiles', $input) && $input['toDeleteFiles'] !== null){
                    foreach($input['toDeleteFiles'] as $fileToRemove){
                        self::removeFile( $activity->file_path, $fileToRemove);
                    }
                }

                if ($request->file()) {
                    if (isset($activity->file_path) && $activity->file_path !== null) {
                        $request->filePath = $activity->file_path;
                    } else {
                        $request->filePath = 'homework/'.$activity->id;
                    }
                    $fileRes = self::fileUpload($request,$user->id);
                    $input['filePath'] = $fileRes['filePath'];
                    $input['fileId'] = $fileRes['fileId'];
                }

                if ($activity->teacher_id === $user->id) {
                    $dataActivity = ([
                        'group_id' => $input['groupId'],
                        'name' => $input['name'],
                        'theme' => array_key_exists('theme', $input) && $input['theme'] ? $input['theme'] : "",
                        'platform' => array_key_exists('platform', $input) ? $input['platform'] : "",
                        'instructions' => array_key_exists('instructions', $input) && $input['instructions'] ? $input['instructions'] : "",
                        'file_path' => array_key_exists('filePath', $input) && $input['filePath'] ? $input['filePath'] : $activity->file_path,
                        'url_path' => array_key_exists('urlPath', $input) && $input['urlPath'] ? $input['urlPath'] : null,
                        'resources' => array_key_exists('resources', $input) && $input['resources'] ? $input['resources'] : null,
                        'finish_date' => Carbon::createFromFormat('Y-m-d H:i', $input['finishDate']),
                        'public_day' => array_key_exists('publicDay', $input) && $input['publicDay'] ? str_replace("/", "-", $input['publicDay']) : null,
                        'is_active' => array_key_exists('is_active', $input) && $input['is_active'] ? $input['is_active'] : 0,
                        'subject_id' => array_key_exists('subject_id', $input) && $input['subject_id'] ? $input['subject_id'] : null,
                    ]);

                    $oldGroup = $activity->group_id;
                    $activityUpdt = $activity->update($dataActivity);
                    $activityUpdated = Activity::findOrFail($id);
                    $activityArray[] = array($activityUpdt, $activityUpdated);

                    if ( $input['groupId'] != $oldGroup ) {

                        \DB::table('homework')->where('activity_id', "=", $id)->delete();

                        //Register homeworks "No entregadas" to students
                        $groupStudents = \DB::table('group_user_enrollments')->where('group_id',$input['groupId'])->get();

                        foreach($groupStudents as $students){
                            $dataHomework = ([
                                'student_id' => $students->user_id,
                                'activity_id' => $id,
                                'status' => "No entregado",
                                'score' => 0,
                                'file_path' => null,
                                'url_path' => null,
                                'is_active' => array_key_exists('is_active', $input) && $input['is_active'] ? $input['is_active'] : 0,
                            ]);

                            $homeworkDB = Homework::create($dataHomework);
                        }
                    }

                    return $this->successResponse($activityArray,'La actividad ha sido actualizada', 200);
                }else{
                    return $this->errorResponse('La actividad no pertenece al maestro', 422);
                }
            } else {
                return $this->errorResponse('El usuario tiene que ser maestro', 422);
            }
        } catch (ModelNotFoundException $e) {
            return $this->errorResponse('No hay elementos que coincidan', 404);
        }

    }

    /**
     * Remove the specified resource from storage.
     *
     * @param int $id
     * @return JsonResponse
     */
    public function destroy($id)
    {
        try {
            $user = Auth::user();
            $activity = Activity::findOrFail($id);

            if ($activity->teacher_id === $user->id) {
                try {
                    if($activity->file_path){
                        $path = storage_path().'/'.'app'.'/public/'.$activity->file_path;
                        self::rrmdir($path);
                    }
                }catch (FileNotFoundException $e) { }
                $activity->delete();
                return $this->successResponse($activity, 'La actividad ha sido eliminado exitosamente', 200);
            } else {
                return $this->errorResponse('La actividad no pertenece al maestro', 422);
            }

        } catch (ModelNotFoundException $e) {
            return $this->errorResponse('No hay elementos que coincidan', 404);
        }

    }

    public function rrmdir($dir) {
        if (is_dir($dir)) {
            $objects = scandir($dir);
            foreach ($objects as $object) {
                if ($object != "." && $object != "..") {
                    if (filetype($dir."/".$object) == "dir") rrmdir($dir."/".$object); else unlink($dir."/".$object);
                }
            }
            reset($objects);
            rmdir($dir);
        }
    }

    public function validateCreateActivity()
    {
        $messages = [
            'required.name' => 'El campo :nombre es requirido.',
        ];

        return Validator::make(request()->all(), [
            'groupId' => 'required|max:255',
            'name' => 'required|max:255',
            'finishDate' => 'required|max:255',
            'name' => 'max:50',
            'theme' => 'max:50',
            'instructions' => 'max:400',
        ], $messages);
    }

    public function validateUpdateActivity()
    {
        $messages = [
            'required.name' => 'El campo :nombre es requirido.',
        ];

        return Validator::make(request()->all(), [
            'name' => 'required|max:255',
            'groupId' => 'required|max:255',
            'finishDate' => 'required|max:255',
            'name' => 'max:50',
            'theme' => 'max:50',
            'instructions' => 'max:400',
        ], $messages);
    }

    public function fileUpload($request, $id)
    {
        try {
            $res = "";
            $resFile['fileId'] = "";
            foreach ($request->file('files') as $fileReq) {
                $fileName = strtr($fileReq->getClientOriginalName(), " ", "_");
                $fileStore = time() . '_' . $fileName;
                
                $dataFile = ([
                    'user_id' => $id,
                    'file_url' => $fileStore
                ]);
                $fileId = Files::create($dataFile);
                $firebaseFile = new FirebaseFiles();
                $firebaseFile->upload($fileReq, $fileStore, $request->filePath);
                $resFile = (['filePath' => $request->filePath, 'fileId' => $resFile['fileId'] != "" ? $resFile['fileId'] . "," . $fileId->id : $fileId->id]);
                $res = $resFile;
            }
            return $res;

        } catch (InvalidOrderException $exception) {
            return null;
        }
    }

    public function downloadFile(Request $request)
    {
        try {
            $firebaseFile = new FirebaseFiles();
            $file = $firebaseFile->download($request->filename);

            if (file_exists($file)) {
                return response()->download($file);
            } else {
                return $this->errorResponse('El archivo no existe', 422);
            }
        } catch (FileNotFoundException $e) {
            return $this->errorResponse('No hay elementos que coincidan', 422);
        }
    }

    public function removeFile($filePath, $file)
    {
        try {
            $firebaseFile = new FirebaseFiles();
            $firebaseFile->delete($filePath . '/' . $file);
            return $this->successResponse($filePath, 'El archivo ha sido eliminado exitosamente', 200);
        } catch (FileNotFoundException $e) {
            return $this->errorResponse('No se han encontrado archivos relacionados con la busqueda', 422);
        }
    }

    public function navigate()
    {
        try {
            $user = Auth::user();
            $select = \DB::table('users')->select('username', 'password')->where('id', $user->id)->first();
            $data = base64_encode($select->username . '|' . $select->password);
            $url = 'https://plus.clublia.com/SSO/index?data=' . $data;
            return $this->successResponse($url, 'Lista semanal materias', 200);
        } catch (\Exception $exception) {
            return $this->errorResponse('No se han encontrado coincidencias', 422);
        }
    }

    public function subjectWeek()
    {
        try {

            $user = Auth::user();

            $log = Activity::select('finish_date')->get();

            $groups = \DB::table('group_user_enrollments')
                ->select('groups.id', 'groups.name')
                ->where('user_id', $user->id)
                ->join('groups', 'group_user_enrollments.group_id', '=', 'groups.id')
                ->get();

            $i = 0;
            $dates = [];
            $count = [];

            foreach (range(0, 4) as $i) {
                $date = Carbon::now()->startOfWeek()->addDays($i)->format('Y-m-d');
                array_push($dates, $date);
            }

            foreach ($groups as $group) {

                Carbon::setWeekStartsAt(Carbon::MONDAY);
                Carbon::setWeekEndsAt(Carbon::FRIDAY);


                foreach ($dates as $date) {

                    $activities = Activity::where('activity.group_id', '=', $group->id)
                        ->whereBetween('activity.finish_date', array($date . ' 00:00:00', $date . ' 23:59:59'))
                        ->join('custom_subjects', 'custom_subjects.id', '=', 'activity.subject_id')
                        ->select(
                            'activity.subject_id',
                            'custom_subjects.custom_name',
                            'custom_subjects.custom_color'
                        )
                        ->groupBy('activity.subject_id')
                        ->selectRaw('COUNT(activity.subject_id) AS total')
                        ->get();

                    array_push($count, ['day' => $date, 'dayActivities' => $activities]);
                }
            }
            return $this->successResponse($count, 'Lista semanal materias', 200);
        } catch (\Exception $exception) {
            return $this->errorResponse('No hay datos que mostrar', 422);
        }
    }

    /**
     * Display a listing of the resource.
     *
     * @return JsonResponse
     */
    public function getCustomSubjects()
    {
        try{
            $user = Auth::user();

                $customsubjects = \DB::table('custom_subjects')
                            ->select('id', 'custom_name')
                            ->where('teacher_id',$user->id)
                            ->groupByRaw('id, custom_name')
                            ->get();
                return $this->successResponse($customsubjects, 'Lista de materias',200);

        }catch (ModelNotFoundException $e){
            return $this->errorResponse('Hubo un problema al consultar la información', 422);
        }
    }
}
