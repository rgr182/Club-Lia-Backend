<?php

namespace App\Http\Controllers;

use App\SyncUser;
use App\User;
use App\UserThinkific;
use Exception;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Http;
use App\Wishlist;


class UserThinkificController extends Controller
{
    public function getUsers(){
        $user = new UserThinkific();
        $user = $user->loadUsers();
        return $user;
    }

    public function listCourses($id){
        $user = new UserThinkific();
        $user = $user->listCoursesAvaible($id);
        return $user;
    }

    public function getUserCourses() {
        try {
            $user = Auth::user();
            $userThink = new UserThinkific();
            $data = [ 'query[user_id]' => $user->active_thinkific ];
            $enrollments = $userThink->userEnrollments($data);

            $keyword = UserThinkific::TEACHER_KEYWORD;
            $wishlist = [];

            if (in_array($user->role_id, [5, 6, 13, 18, 19, 20, 21, 34, 35, 36])) {
                $wishlist = Wishlist::select('course_id')->where('user_id', $user->id)->get();
                $keyword = UserThinkific::STUDENT_KEYWORD;
            }

            $courses = $userThink->coursesAvaibleKeyword($keyword);

            $filtered = [];
            foreach($courses as $course){
                $exist = false;
                foreach($enrollments['items'] as $key=>$enrollment) {
                    if ($course['productable_id'] == $enrollment['course_id']) {
                        $enrollments['items'][$key]['card_image_url'] = $course['card_image_url'];
                        $exist = true;
                        break;
                    }
                }
                if(!$exist && $course['keywords'] === $keyword) {
                    foreach($wishlist as $wish){
                        if($wish->course_id == $course['id']) {
                            $course['wish'] = true;
                            break;
                        }
                    }
                    array_push($filtered,$course);
                }
            }

            $infoT = collect(['enrollments' => $enrollments['items'], 'courses' => $filtered]);
            return $infoT;
        } catch (ModelNotFoundException $e) {
            return $this->errorResponse('No se pudo recuperar la información', 422);
        }
    }

    public function getChildCourses() {
        try {
            $user = Auth::user();
            $userThink = new UserThinkific();
            $courses = $userThink->coursesAvaibleKeyword(UserThinkific::STUDENT_KEYWORD);

            $children = User::where('tutor_id',$user->id)->get();
            $childrenCourses = [];
            foreach($children as $child){
                $data = [ 'query[user_id]' => $child->active_thinkific ];
                $enrollments = $userThink->userEnrollments($data);
                $wishlist = Wishlist::select('course_id')->where('user_id', $child->id)->get();
                $keyword = 'programacion';
                $coursesC = $courses;

                $filtered = [];
                foreach($coursesC as $course){
                    $exist = false;
                    foreach($enrollments['items'] as $key=>$enrollment) {
                        if ($course['productable_id'] == $enrollment['course_id']) {
                            $enrollments['items'][$key]['card_image_url'] = $course['card_image_url'];
                            $exist = true;
                            break;
                        }
                    }
                    if(!$exist && $course['keywords'] === $keyword) {
                        foreach($wishlist as $wish){
                            if($wish->course_id == $course['id']) {
                                $course['wish'] = true;
                                break;
                            }
                        }
                        array_push($filtered,$course);
                    }
                }

                $infoT = collect([
                    'enrollments' => $enrollments['items'],
                    'courses' => $filtered,
                    'childName' => $child->name.' '.$child->last_name
                ]);
                array_push($childrenCourses,$infoT);
            }
            return $childrenCourses;

        } catch (ModelNotFoundException $e) {
            return $this->errorResponse('No se pudo recuperar la información', 422);
        }
    }

    public function storeUser(Request $inputData){

        $user = new UserThinkific();
        $user = $user->createUser($inputData);
        return $user;
    }

    public function editUser(Request $inputData, $userid){

        $user = new UserThinkific();
        $user = $user->updateUser($inputData, $userid);
        return $user;
    }

    public function deleteUser($userid){

        $user = new UserThinkific();
        $user = $user->deleteUserSchooling($userid);
        return $user;

    }

    // Create JWT single sign on Thikinfic
    public function singleSignThinkific(Request $request){
        try {
            $user = Auth::user();
            $userThinkific = new UserThinkific();
            $input = $request->all();
            if (array_key_exists('course_id', $input) && $input['course_id']) {
                $course = $userThinkific->getCourseById($request->course_id);
                if(!is_null($course)){
                    $chapter_url = $userThinkific->getLink(
                        array_key_exists('productable_id', $course) ?
                            $course['productable_id']
                            :
                            $input['course_id']
                    );
                    $user->chapter_url = $chapter_url;
                }
            }
            $userThinkific = $userThinkific->singleSignOn($user);
            return response($userThinkific,200);
        } catch (Exception $e) {
            return response($e->getMessage().' at '.$e->getLine(), 500);
        }
    }

    public function syncUserplatform(){
        $user = new SyncUser();
        $user = $user->transferUsers();
        return $user;
    }

    public function enrollment(Request $request)
    {
        $input = $request->all();
        $user = new UserThinkific();
        $user->enrollmentStudent($input['user_id'], $input['role_id']);
        return $user;
    }
}