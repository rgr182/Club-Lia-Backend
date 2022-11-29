<?php

namespace App\Http\Controllers;

use App\LikeUserGroup;
use Illuminate\Http\Request;

class LikeUserGroupController extends ApiController
{
    public function list(){
        $likes = LikeUserGroup::all();

        return $this->successResponse($likes);
    }
}
