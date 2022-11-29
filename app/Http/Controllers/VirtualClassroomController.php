<?php

namespace App\Http\Controllers;

use Illuminate\Contracts\Filesystem\FileNotFoundException;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use App\Files;
use App\ClassVC;
use App\Calendars;
use App\NonPlannedResources;
use App\GroupModels\Group;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Response;
use Illuminate\Support\Facades\Storage;
use App\User;
use Mockery\Exception\InvalidOrderException;
use App\FirebaseFiles as FirebaseFiles;

class VirtualClassroomController extends ApiController
{
    /**
     * Display a listing of the resource.
     *
     * @return JsonResponse
     */
    public function index($group_id)
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
            return $this->successResponse($response,200);
        }catch(InvalidOrderException $exception){
            return $this->errorResponse('No hay elementos que coincidan',404);
        }
    }

    public function getGroupStudent(){
        $user = Auth::user();
        $groups = \DB::table('group_user_enrollments')
            ->select('group_user_enrollments.group_id as id','group_user_enrollments.user_id','groups.name','groups.teacher_id')
            ->join('groups','group_user_enrollments.group_id','=','groups.id')
            ->where('user_id',$user->id)
            ->get();
        return $this->successResponse($groups,200);
    }

    public function getNames(Request $request){
        try{
            $input = $request->all();
            $table = '';
            $select = '';
            switch($input['type']){
                case('1'):
                    $table = 'schools';
                    $select = ['id', 'name'];
                    break;
                case('2'):
                    $table = 'users';
                    $select = ['id', \DB::raw('CONCAT(COALESCE(name,"")," ",COALESCE(second_name+" ",""),COALESCE(last_name,"")) as name')];
                    break;
                case('3'):
                    $table = 'groups';
                    $select = ['id', 'name','grade'];
                    break;
                case('4'):
                    $table = 'custom_subjects';
                    $select =  ['id', 'custom_name as name'];
                    break;
            }

            $response = \DB::table($table)
                ->select($select)
                ->whereIn('id', $input['paths'])
                ->get();

            return $this->successResponse($response,200);
        }catch(InvalidOrderException $exception){
            $data->data = [];
            return $this->successResponse($data,200);
        }
    }

    public function getGradeSuject($calendar_id){
        try{
            $response = \DB::table('calendar')
                ->select('calendar.subject_id', 'groups.name as group_name', 'custom_subjects.custom_name as subject_name')
                ->join('custom_subjects','calendar.subject_id','custom_subjects.id')
                ->join('groups','custom_subjects.group_id','groups.id')
                ->where('calendar.calendar_id',$calendar_id)
                ->get();
            return $this->successResponse($response[0],200);
        }catch(InvalidOrderException $exception){
            return $this->errorResponse('No hay elementos que coincidan',404);
        }
    }

    /**
     * Store a newly created resource in storage.
     *
     * @param  Request  $request
     * @return JsonResponse
     */
    public function store(Request $request)
    {
        $user = Auth::user();
        $input = $request->all();
        try{
            if($request->file()) {
                $file = self::fileUpload($request,$user->id);
                $response['filePath'] = $file['filePath'];
                $response['fileId'] = $file['fileId'];
             }
             return $this->successResponse($response,200);
        }catch(InvalidOrderException $exception){
            return $this->errorResponse('Hubo un error al subir el archivo',404);
        }
    }

    /**
     * Display the specified resource.
     *
     * @param  int  $id
     * @return JsonResponse
     */
    public function show($id)
    {
        try{
            $files = Storage::disk('local')->allFiles('public/virtualClassroom/'.$id);
            return $this->successResponse($files,200);
        }catch(InvalidOrderException $exception){
            return $this->errorResponse('No hay elementos que coincidan',404);
        }
    }

    public function fileUpload(Request $request, $id){
        try {
            $fileName = strtr($request->file->getClientOriginalName(), " ", "_");
            $fileStore = time().'_'.$fileName;
            $firebaseFile = new FirebaseFiles();
            $filePath = $firebaseFile->upload($request->file('file'), $fileStore, 'virtualClassroom/'.$request->meetingId);
            $dataFile = ([
                'user_id' => $id,
                'file_url' => $fileStore
            ]);
            $fileId = Files::create($dataFile);
            $resFile = (['filePath'=>$filePath,'fileId'=>$fileId->id]);
            return $resFile;
        }catch(InvalidOrderException $exception){
            return null;
        }
    }

    public function Deletefiles($id){
        try {
            $firebaseFile = new FirebaseFiles();
            $firebaseFile->delete('virtualClassroom/'.$id);
            $files =  $firebaseFile->fileList('virtualClassroom/'.$id);

            return $this->successResponse($file, 200);
        }catch(InvalidOrderException $exception){
            return null;
        }
    }

    public function DeletefileById(Request $request){
        try {

            $input = $request->all();
            $firebaseFile = new FirebaseFiles();
            $firebaseFile->delete('virtualClassroom/' . $input['meetingId'] . '/' . $input['file']);
            $files =  $firebaseFile->fileList('virtualClassroom/' . $input['meetingId']);

            return $this->successResponse($files, 200);
        }catch(InvalidOrderException $exception){
            return null;
        }
    }

    public function getFiles(Request $response)
    {
        try {
            $path = storage_path().'/'.'app'.'/public/'.$response->filename;

            if (file_exists($path)) {
                return $this->successResponse($path,200);
            } else {
                return $this->errorResponse('El archivo no existe', 422);
            }
        }catch (FileNotFoundException $e) {
            return $this->errorResponse('Error al consultar la información', 422);
        }
    }

    public function getNonPlannedResources(Request $request){
        $user = Auth::user();
        $input = $request->all();

        $class = ClassVC::where([['meeting_id', '=', $input['meeting_id']]])->firstOrfail();
        $calendar = Calendars::where([['calendar_id', '=', $input['calendar_id']]])->firstOrfail();

        $resources = NonPlannedResources::where(
            [
                ['id_class', '=', $class['id']],
                ['id_calendar', '=', $calendar['id']]
            ]
        )
        ->join('digital_resources','nonplanned_resources.id_resource','=','digital_resources.id')
        ->select('nonplanned_resources.*','digital_resources.name as name', 'digital_resources.url_resource as url')
        ->get();

        foreach ($resources as $verify ){
            $calendar = self::verifyURL($verify->url);
            $verify->allowFrame = $calendar;
        }
        return $this->successResponse($resources,200);

    }

    public function addNonPlannedResources(Request $request)
    {
        try{
            $user = Auth::user();
            $input = $request->all();
            $class = ClassVC::where([['meeting_id', '=', $input['meeting_id']]])->firstOrfail();
            $calendar = Calendars::where([['calendar_id', '=', $input['calendar_id']]])->firstOrfail();

            $dataResource = ([
                'id_class' => $class['id'],
                'id_calendar' => $calendar['id'],
                'id_resource' => $input['resource_id'],
            ]);

            $npResources = NonPlannedResources::create($dataResource);
            return $this->successResponse('Se ha añadido el recurso con exito', 200);

        }catch(ModelNotFoundException $e){
            return $this->errorResponse('Error al agregar recurso', 422);
        }
    }

    public function destroyNonPlannedResources(Request $request)
    {
        try {
            $input = $request->all();

            foreach ($input as $resource ){
                NonPlannedResources::where('id','=',$resource)->delete();
            }
            return $this->successResponse('Se han eliminado los recursos exitosamente',200);

        }catch (ModelNotFoundException $e){
            return $this->errorResponse('Recurso inválido: No hay elementos que coincidan', 422);
        }
    }

    public function verifyURL($url)
    {
        $response = 0;

        try {
            $headers = get_headers($url, 1);
            $headers = array_change_key_case($headers, CASE_LOWER);
            // Check Content-Security-Policy
            if (isset($headers[strtolower('Content-Security-Policy')]) || isset($headers[strtolower('referrer-policy]')])) {
                $response = 1;
            }
            // Check X-Frame-Options
            if (array_key_exists(strtolower('X-Frame-Options'), $headers)) {
                $response = 1;
            }

            return $response;
        } catch (Exception $ex) {
            $response = 1;
            return $this->errorResponse('Error al obtener información del sitio', 422);
        }
    }
}
