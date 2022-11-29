<?php

namespace App\Http\Controllers;

use App\Contact;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use App\Mail\MassiveEmail;
use Illuminate\Support\Facades\Mail;

class MassiveEmailController extends ApiController
{
    
    public function index()
    {
        $data = [
            "ok" => true
        ];
        return $this->successResponse($data);
    }

    public function send(Request $request)
    {
        $validator = $this->validateMail();
        if($validator->fails()){
            return $this->errorResponse($validator->messages(), 422);
        }

        $input = request()->all();

        $emails = array_chunk($input['uuids'],1000);
        
        foreach ($emails as $mail) {
            $users = \DB::table('users')->whereIn('uuid', $mail)->get('email')->pluck('email')->toArray();
            $input['email'] = $users;
            
            Mail::send(new MassiveEmail($input));
        }

        return $this->successResponse(null,'Email sended');
    }

    public function validateMail(){
        $messages = [
            'subject.required' => 'El campo subject es requirido.',
            'message.required' => 'El campo message es requirido.',
            'uuids.required' => 'El campo uuid es requirido.',
        ];

        return Validator::make(request()->all(), [
                'subject' => 'string|required',
                'message' => 'string|required',
                'uuids' => 'array|required',
            ], $messages);
    }
}