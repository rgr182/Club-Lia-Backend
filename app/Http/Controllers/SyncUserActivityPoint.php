<?php

namespace App\Http\Controllers;
use App\PhpFox_activitypoint_statistics;
use App\UserCommunity;

use Illuminate\Http\Request;

class SyncUserActivityPoint extends ApiController
{
    /**
     * Display a listing of the resource.
     * @return \Illuminate\Http\JsonResponse
     */
    public function SyncUsers()
    {
        try {
            $userPoints = PhpFox_activitypoint_statistics::get('user_id')->pluck('user_id');

            $users = UserCommunity::whereNotIn('user_id', $userPoints)->get();
            
            foreach($users as $user){
                PhpFox_activitypoint_statistics::create(['user_id' => $user->user_id]);
            }

            return $this->successResponse('Usuarios sincronizados correctamente');
        }catch (ModelNotFoundException $e){
            return $this->errorResponse('No fue posible sincronizar los usuarios', 422);
        }
    }
}
