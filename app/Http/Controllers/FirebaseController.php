<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Kreait\Firebase\Database;
use Kreait\Laravel\Firebase\Facades\Firebase;

class FirebaseController extends ApiController
{
    protected  $database;

    public function __construct(Database $database)
    {
        $this->database = app('firebase.database');
    }

    public function index(){
        try {
            $reference =  $this->database->getReference('users');
            $snapshot = $reference->getSnapshot();
            $value = $snapshot->getValue();

            return $this->successResponse($value, 'Sessions list', 200);

        }catch (\Exception $exception){
            return $this->errorResponse("Error al consultar la información", 422);
        }
    }
}
