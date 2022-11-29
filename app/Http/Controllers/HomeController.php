<?php

namespace App\Http\Controllers;

use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\Auth;
use App\Activity;
use App\Homework;
use Carbon\Carbon;
use App\Http\Controllers\ActivityController;
use Illuminate\Support\Facades\Storage;
use App\Files;
use Illuminate\Support\Str;
use App\FirebaseFiles as FirebaseFiles;

class HomeController extends ApiController
{
    protected $ActivityController;
    public function __construct(ActivityController $ActivityController)
    {
        $this->ActivityController = $ActivityController;
    }
    /**
     * Display a listing of the resource.
     *
     * @return JsonResponse
     */
    public function index()
    {
        $user = Auth::user();
        $request = request()->all();

        $filter = [['homework.student_id','=',$user->id]];

        if (array_key_exists('group_id', $request) && $request['group_id'] != null) {
            array_push($filter,['activity.group_id',$request['group_id']]);
        }

        // if (array_key_exists('is_active', $request) && $request['is_active'] !== null) {
        //     array_push($filter,['activity.is_active',$request['is_active']]);
        // }

        if (array_key_exists('is_active', $request) && $request['is_active'] !== null) {
            if($request['is_active'] == 1){
                array_push($filter,['activity.finish_date', '>', Carbon::parse($request['today'])]);
            }
            if($request['is_active'] == 0){
                array_push($filter,['activity.finish_date', '<', Carbon::parse($request['today'])]);
            }
        }

        $orderDate = 'desc';
        if (array_key_exists('orderDate', $request) && $request['orderDate'] !== null) {
            $orderDate = $request['orderDate'] ? 'asc' : 'desc';
        }

        if (array_key_exists('status', $request) && $request['status'] != null) {
            array_push($filter,['homework.status',$request['status']]);
        }

        $homeworks = Homework::select('homework.*','custom_subjects.custom_name', 'activity.*', 'activity.file_path as file', 'activity.url_path as url', 'homework.file_path as file_path', 'homework.url_path as url_path', 'homework.id as id',
        'groups.name as group_name', \DB::raw('CONCAT(COALESCE(users.name,"")," ",COALESCE(users.second_name+" ",""),COALESCE(users.last_name,"")) as teachers_name'))
        ->where($filter)
        ->join('activity', 'homework.activity_id', '=', 'activity.id')
        ->join('custom_subjects','activity.subject_id','=','custom_subjects.id')
        ->join('groups','activity.group_id','=','groups.id')
        ->join('users','groups.teacher_id','=','users.id')
        ->orderBy('activity.created_at', $orderDate)
        ->get();

        foreach ($homeworks as $homework) {
            if(array_key_exists('today', $request) && $request['today']){
                $homework->is_active =  Carbon::parse($request['today'])->greaterThan(Carbon::parse($homework->finish_date)) ? 0 : $homework->is_active;
                $homework->remaining_days = Carbon::parse($request['today'])->diffInDays(Carbon::parse($homework->finish_date));
            }

            if($homework->status=='Entregado' ){
                $homework->on_time =  Carbon::parse($homework->finish_date)->greaterThan(Carbon::parse($homework->delivery_date)) ? true : false;
            }

            $homework->limit_date = $homework->finish_date;
            $homework->finish_date = Carbon::parse($homework->finish_date)->format( 'd-m-Y' );
            $homework->scored_date = $homework->scored_date ? Carbon::parse($homework->scored_date)->format('j M  H:i') : $homework->scored_date;
        }

        return $this->successResponse($homeworks,200);
    }

    public function getHomeworks($id)
    {
        $user = Auth::user();
        $homeworks = Homework::select('homework.*', \DB::raw("CONCAT(users.name, ' ', users.last_name) AS user_name"), 'activity.finish_date')
            ->where('activity_id',$id)->join('users', 'homework.student_id', '=', 'users.id')
            ->join('activity', 'homework.activity_id', '=', 'activity.id')->get();

        foreach ($homeworks as $homework) {
            $homework->due = $homework->delivery_date ? Carbon::parse($homework->delivery_date)->greaterThan(Carbon::parse($homework->finish_date)) : false;
            $homework->finish_date = Carbon::parse($homework->finish_date)->format('j M  H:i');
            $homework->scored_date = $homework->scored_date ? Carbon::parse($homework->scored_date)->format('j M  H:i') : $homework->scored_date;
            $homework->delivery_date = $homework->delivery_date ? Carbon::parse($homework->delivery_date)->format('j M  H:i') : $homework->delivery_date;
        }

        return $this->successResponse($homeworks,200);
    }

    public function getHomework($id)
    {
        $user = Auth::user();
        $homework = Homework::select('homework.*', \DB::raw('CONCAT(COALESCE(users.name,"")," ",COALESCE(users.second_name+" ",""),COALESCE(users.last_name,"")) as teachers_name'), 'activity.finish_date', 'activity.file_path as activityFile', 'activity.instructions', 'custom_subjects.custom_name')
            ->where('homework.id',$id)
            ->join('users', 'homework.student_id', '=', 'users.id')
            ->join('activity', 'homework.activity_id', '=', 'activity.id')
            ->join('custom_subjects','activity.subject_id','=','custom_subjects.id')
            ->first();
        if(Carbon::parse($homework->finish_date) < Carbon::now()){
            $homework->status = $homework->status == "No entregado" ? "Vencido" : $homework->status;
        }
        if(!Str::contains($homework->activityFile,'.') && $homework->activityFile != null){
            $files = Storage::disk('local')->allFiles('public/'.$homework->activityFile);
            $homework->activityFile = $files;
        }
        $homework->preeStars = $homework->score < 7.00 ? 3 : ($homework->score < 9.0 ? 4 : 5);
        return $this->successResponse($homework,200);
    }

    public function getDelivered()
    {
        $user = Auth::user();

        $delivered = Activity::select('homework.id')
        ->join('homework','activity.id','=','homework.activity_id')
        ->where([['activity.teacher_id','=',$user->id],['homework.status','=','Entregado']])
        ->count();

        return $this->successResponse($delivered,200);
    }

    /**
     * Store a newly created resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return JsonResponse
     */
    public function store(Request $request)
    {
        $validator = $this->validateHomework();
        if($validator->fails()){
            return $this->errorResponse($validator->messages(), 422);
        }
        $user = Auth::user();
        $input = $request->all();

        if(!Activity::where('id',$input['activityId'])->exists()){
            return $this->errorResponse("La actividad no existe",422);
        }
        if(Homework::where([['student_id',$user->id],['activity_id',$input['activityId']]])->exists()){
            return $this->errorResponse("El alumno ya subió tarea para esta actividad",422);
        }
        $dataHomework = ([
            'student_id' => $user->id,
            'activity_id' => $input['activityId'],
            'status' => array_key_exists('status', $input) ? $input['status'] : "Entregado",
            'score' => array_key_exists('score', $input) ? $input['score'] : 0,
            'file_path' => array_key_exists('filePath', $input) ? $input['filePath'] : null,
            'url_path' => array_key_exists('urlPath', $input) ? $input['urlPath'] : null,
            'is_active' => array_key_exists('isActive', $input) ? $input['isActive'] : 1,
        ]);

        $homeworkDB = Homework::create($dataHomework);

        return $this->successResponse($homeworkDB,200);

    }


    /**
     * Display the specified resource.
     *
     * @param  int  $id
     * @return JsonResponse
     */
    public function show($id)
    {
        try {
            $homework = Homework::findOrFail($id);
            return $this->successResponse($homework);
        }catch (ModelNotFoundException $e){
            return $this->errorResponse('Tipo de licencia invalido', 422);
        }
    }

    /**
     * Show the form for editing the specified resource.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function edit($id)
    {
        //
    }

    /**
     * Update the specified resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  int  $id
     * @return JsonResponse
     */
    public function update(Request $request, $id)
    {
        try{
            $user = Auth::user();
            $input = $request->all();

            if(!Homework::where('id',$id)->exists()){
                return $this->errorResponse($id,422);
            }

            $homework = Homework::findOrFail($id);
            if($user->role_id == 5 || $user->role_id == 13 || $user->role_id == 6 || $user->role_id == 18 || $user->role_id == 19 || $user->role_id == 20 || $user->role_id == 21 || $user->role_id == 34 || $user->role_id == 35 || $user->role_id == 36){
                if($request->file()) {
                    $file = self::fileUpload($request,$user->id);
                    $input['filePath'] = $file['filePath'];
                }

                if($homework->status != 'Calificado'){
                    $input['status'] = (array_key_exists('filePath', $input) && $input['filePath']) || (array_key_exists('urlPath', $input) && $input['urlPath']) ? 'Entregado' : null;
                }
                $deliveyDate = Carbon::createFromFormat('Y-m-d H:i', $input['deliveryDate']);
            }

            $scoredDate = $homework->scored_date;

            if($user->role_id == 4 || $user->role_id == 7 || $user->role_id == 8 || $user->role_id == 17 || $user->role_id == 22 || $user->role_id == 23 || $user->role_id == 24 || $user->role_id == 28 || $user->role_id == 29 || $user->role_id == 30){
                $input['urlPath'] = $homework->url_path;
                $input['filePath'] = $homework->file_path;
                $deliveyDate = $homework->delivery_date;
                $scoredDate = Carbon::createFromFormat('Y-m-d H:i', $input['scoredDate']);
            }
                $dataHomework = ([
                    'status' => array_key_exists('status', $input) && $input['status'] ? $input['status'] : $homework->status,
                    'score' => array_key_exists('score', $input) ? $input['score'] : $homework->score,
                    'file_path' => array_key_exists('filePath', $input) ? $input['filePath'] : null,
                    'url_path' => array_key_exists('urlPath', $input) ? $input['urlPath'] : null,
                    'is_active' => array_key_exists('isActive', $input) ? $input['isActive'] : $homework->is_active,
                    'delivery_date' => $deliveyDate,
                    'scored_date' => $scoredDate,
                ]);
                $homeworkUpdt = $homework->update($dataHomework);
                $homeworkUpdated = Homework::findOrFail($id);

                return $this->successResponse($homeworkUpdated,'La tarea ha sido actualizada', 200);


        }catch (ModelNotFoundException $e){
            return $this->errorResponse('No hay elementos que coincidan', 404);
        }
    }

    /**
     * Remove the specified resource from storage.
     *
     * @param  int  $id
     * @return JsonResponse
     */
    public function destroy($id)
    {
        try{
            $user = Auth::user();
            $homework = Homework::findOrFail($id);

            if($homework->student_id === $user->id){
                $homework->delete();
                return $this->successResponse($homework,'La tarea ha sido eliminada exitosamente', 200);
            }else{
                return $this->errorResponse('La tarea no pertenece al alumno', 422);
            }

        }catch (ModelNotFoundException $e){
            return $this->errorResponse('No hay elementos que coincidan',404);
        }
    }

    public function fileUpload(Request $request, $id){
        try {

            $fileName = strtr($request->file->getClientOriginalName(), " ", "_");
            $fileStore = time().'_'.$fileName;
            $firebaseFile = new FirebaseFiles();
            $filePath = $firebaseFile->upload($request->file('file'), $fileStore, 'homework');
            $dataFile = ([
                'user_id' => $id,
                'file_url' => $fileStore
            ]);
            $fileId = Files::create($dataFile);
            $resFile = (['filePath' => $filePath, 'fileId' => $fileId->id]);
            return $resFile;

        }catch(InvalidOrderException $exception){
            return null;
        }
    }

    public function validateHomework(){
        $messages = [
            'required.name' => 'El campo :nombre es requirido.',
        ];

        return Validator::make(request()->all(), [
            'activityId' => 'required|max:255',
        ], $messages);
    }
}
