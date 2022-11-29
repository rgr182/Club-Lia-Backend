<?php

namespace App\Http\Controllers;

use App\School;
use App\SyncSchool;
use App\UserThinkific;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\Http;

class SyncSchoolController extends ApiController
{
    /**
     * Sync all the active schools to the Academy.
     * @return JsonResponse
     */
    public function store()
    {
        $list =  School::all();

        $results = School::where([
            ['is_active' ,'=', '1']
        ])->get('name');

        $schools = $results->count();

        $i = 0;

        if($results->isEmpty()){
            return $this->errorResponse('No hay escuelas por sincronizar', 422);
        }else {
            foreach ($results as $obj) {
                $syncSchool = $obj;
                $nameSchool = $syncSchool->name;

                $request =  new SyncSchool();
                $response = $request->createSchool($nameSchool);

                $count[$i++] = $response;
            }
        }
        return $this->successResponse($count, 'Se han sincronizado los usuarios');
    }
}
