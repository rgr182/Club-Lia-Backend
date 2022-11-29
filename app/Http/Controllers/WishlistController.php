<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Exception;
use App\Wishlist;

class WishlistController extends ApiController
{
    public function index()
    {
        //
    }

    public function store(Request $request)
    {
        try {
            $input = $request->all();
            $user = Auth::user();
            $dataWish = ([
                'course_id' => $input['course_id'],
                'user_id' => $user->id,
            ]);
            $wish = Wishlist::create($dataWish);
            return $this->successResponse($wish, 200);
        } catch (ModelNotFoundException $e) {
            return $this->errorResponse('Ha ocurrido un error!', 422);
        }
    }

    public function show(Request $request)
    {
        try {
            $user = Auth::user();
            $input = $request->all();

            $user_id = array_key_exists('user_id', $input) ? $input['user_id'] : $user->id;
            $wishlist = Wishlist::select('course_id')->where('user_id', $user_id)->get();

            return $this->successResponse($wishlist, 200);
        } catch (ModelNotFoundException $e) {
            return $this->errorResponse('No hay elementos que coincidan', 422);
        }
    }

    public function destroy($course_id)
    {
        try {
            $user = Auth::user();
            $wish = Wishlist::where([['user_id', $user->id], ['course_id', $course_id]])->firstOrfail();

            $wish = $wish->delete();
            return $this->successResponse($wish, 200);
        } catch (ModelNotFoundException $e) {
            return $this->errorResponse('No hay elementos que coincidan', 404);
        }
    }
}
