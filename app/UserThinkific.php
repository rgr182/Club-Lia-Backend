<?php

namespace App;

use Carbon\Carbon;
use Carbon\CarbonImmutable;
use DateTimeZone;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Http\Request;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class UserThinkific
{
    protected $key;
    protected $subdomain;
    const STUDENT_KEYWORD = 'programacion';

    function __construct() {
        $this->key = Config::get('app.thinkific');
        $this->subdomain = Config::get('app.subdomain_thinkific');
    }

    public function loadUsers(){
        $response = Http::withHeaders([
            'X-Auth-API-Key' => $this->key,
            'X-Auth-Subdomain' => $this->subdomain,
            'Content-Type' => 'application/json'
        ])->get('https://api.thinkific.com/api/public/v1/users')->json();

        return $response;
    }

    public function getUserByEmail($email){
        $response = Http::withHeaders([
            'X-Auth-API-Key' => $this->key,
            'X-Auth-Subdomain' => $this->subdomain,
            'Content-Type' => 'application/json'
        ])->get('https://api.thinkific.com/api/public/v1/users?query[email]=' . $email)->json();

        return $response;
    }

    public function createUser($inputData){

        $request = Http::withHeaders([
            'X-Auth-API-Key' => $this->key,
            'X-Auth-Subdomain' => $this->subdomain,
            'Content-Type' => 'application/json',
        ])->post('https://api.thinkific.com/api/public/v1/users', [
            'first_name' => $inputData["first_name"],
            'last_name' => $inputData["last_name"],
            'email' => $inputData["email"],
            'password' => $inputData["password"],
            "custom_profile_fields" => [
                [
                    "value" => "no",
                    "custom_profile_field_definition_id" => 62690
                ]
            ]
        ]);

        return $request->json();
    }

    public function updateUser($inputData, $userid){

        $request = Http::withHeaders([
            'X-Auth-API-Key' => $this->key,
            'X-Auth-Subdomain' => $this->subdomain,
            'Content-Type' => 'application/json',
        ])->put('https://api.thinkific.com/api/public/v1/users/'. $userid, [
            'first_name' => $inputData['name'],
            'last_name' => $inputData["last_name"],
            'email' => $inputData["email"],
        ]);

        return $request;
    }

    public function groupAssign($userid, $groupName){

        $request = Http::withHeaders([
            'X-Auth-API-Key' => $this->key,
            'X-Auth-Subdomain' => $this->subdomain,
            'Content-Type' => 'application/json',
        ])->post('https://api.thinkific.com/api/public/v1/group_users', [
            "user_id" => $userid,
            "group_names" => [
                $groupName
            ]
        ]);

        return $request;
    }

    public function deleteUserSchooling($userid){

        $request = Http::withHeaders([
            'X-Auth-API-Key' => $this->key,
            'X-Auth-Subdomain' => $this->subdomain,
            'Content-Type' => 'application/json',
        ])->delete('https://api.thinkific.com/api/public/v1/users/'. $userid);

        return $request;
    }

    // Create JWT single sign on Thikinfic
    public function singleSignOn($user){

        $header = json_encode(['typ' => 'JWT', 'alg' => 'HS256']);

        // Create token payload as a JSON string
        $payload = json_encode([
            'first_name' =>$user->name,
            "last_name" => $user->last_name,
            "email" => $user->email,
            "iat"=> time()
        ]);

        // Encode Header to Base64Url String
        $base64UrlHeader = str_replace(['+', '/', '='], ['-', '_', ''], base64_encode($header));

        // Encode Payload to Base64Url String
        $base64UrlPayload = str_replace(['+', '/', '='], ['-', '_', ''], base64_encode($payload));

        // Create Signature Hash
        $signature = hash_hmac('sha256', $base64UrlHeader . "." . $base64UrlPayload, $this->key, true);

        // Encode Signature to Base64Url String
        $base64UrlSignature = str_replace(['+', '/', '='], ['-', '_', ''], base64_encode($signature));

        // Create JWT
        $jwt = $base64UrlHeader . "." . $base64UrlPayload . "." . $base64UrlSignature;


        $baseUrl = 'https://'.env('THINKIFIC_SUBDOMAIN').'.thinkific.com/api/sso/v2/sso/jwt?jwt=';
        $returnTo = urlencode($user->chapter_url ? $user->chapter_url : 'https://'.env('THINKIFIC_SUBDOMAIN').'.thinkific.com');
        $errorUrl = urlencode('https://'.env('THINKIFIC_SUBDOMAIN').'.thinkific.com');
        $url = $baseUrl . $jwt . "&return_to=" . $returnTo . "&error_url=" . $errorUrl;

        echo $url;
    }

    public function enrollmentStudent($userId, $bundleId, $expiration){
        $mutable = Carbon::now();
        $date = Carbon::parse($mutable, 'UTC');

        $request = Http::withHeaders([
            'X-Auth-API-Key' => $this->key,
            'X-Auth-Subdomain' => $this->subdomain,
            'Content-Type' => 'application/json',
        ])->post('https://api.thinkific.com/api/public/v1/bundles/'. $bundleId .'/enrollments', [
            'user_id' => $userId,
            'activated_at' => $date,
            'expiry_date' => $expiration,
        ]);

        return $request;
    }
 
    public function enrollmentStudentInsert($userId, $courseId){
        $mutable = Carbon::now();
        $date = Carbon::parse($mutable, 'UTC');

        $request = Http::withHeaders([
            'X-Auth-API-Key' => $this->key,
            'X-Auth-Subdomain' => $this->subdomain,
            'Content-Type' => 'application/json',
        ])->post('https://api.thinkific.com/api/public/v1/enrollments', [
            "course_id"=> $courseId,
            "user_id"=> $userId,
            "activated_at" => $date,
            "expiry_date"=> "2040-01-01T01:01:00Z"
        ])->json();

        return $request;
    }

    public function userEnrollments($data){

        $request = Http::withHeaders([
            'X-Auth-API-Key' => $this->key,
            'X-Auth-Subdomain' => $this->subdomain,
            'Content-Type' => 'application/json',
        ])->asForm()->get('https://api.thinkific.com/api/public/v1/enrollments', $data)->json();

        return $request;
    }

    public function coursesAvaible(){

        $request = Http::withHeaders([
            'X-Auth-API-Key' => $this->key,
            'X-Auth-Subdomain' => $this->subdomain,
            'Content-Type' => 'application/json',
        ])->asForm()->get('https://api.thinkific.com/api/public/v1/courses')->json();

        $datos = collect($request['items']);

        $filtered = $datos->filter(function ($value, $key) {
            return $value['keywords'] === "Maestros";
        });

        return $filtered;
    }

    public function coursesAvaibleKeyword() {

        $request = Http::withHeaders([
            'X-Auth-API-Key' => $this->key,
            'X-Auth-Subdomain' => $this->subdomain,
            'Content-Type' => 'application/json',
        ])->asForm()->get('https://api.thinkific.com/api/public/v1/products?limit=100')->json();

        $datos = collect($request['items']);
        return $datos;
    }

    public function getLink($course_id) {

        $request = Http::withHeaders([
            'X-Auth-API-Key' => $this->key,
            'X-Auth-Subdomain' => $this->subdomain,
            'Content-Type' => 'application/json',
        ])->asForm()->get('https://api.thinkific.com/api/public/v1/courses/'.$course_id.'/chapters?page=1&limit=1')->json();
        $chapters = collect($request['items']);

        $chapterUrl = Http::withHeaders([
            'X-Auth-API-Key' => $this->key,
            'X-Auth-Subdomain' => $this->subdomain,
            'Content-Type' => 'application/json',
        ])->asForm()->get('https://api.thinkific.com/api/public/v1/chapters/'.$chapters[0]['id'].'/contents?page=1&limit=1')->json();

        $datos = collect($chapterUrl['items']);
        return $datos[0]['take_url'];
    }

    public function listCoursesAvaible($id){

        $course = Http::withHeaders([
            'X-Auth-API-Key' => $this->key,
            'X-Auth-Subdomain' => $this->subdomain,
            'Content-Type' => 'application/json',
        ])->asForm()->get('https://api.thinkific.com/api/public/v1/products/'.$id)->json();

        $keyword = $course['keywords'];

        $request = Http::withHeaders([
            'X-Auth-API-Key' => $this->key,
            'X-Auth-Subdomain' => $this->subdomain,
            'Content-Type' => 'application/json',
        ])->asForm()->get('https://api.thinkific.com/api/public/v1/products?limit=100')->json();

        $datos = collect($request['items']);

        if ($keyword) {
            $filtered = $datos->filter(function ($value, $key) use ($keyword) {
                return $value['keywords'] === $keyword;
            })->values()->all();
            return $filtered;
        } else {
            return [$course];
        }
    }

    public function getCourseById($id){
        $course = Http::withHeaders([
            'X-Auth-API-Key' => $this->key,
            'X-Auth-Subdomain' => $this->subdomain,
            'Content-Type' => 'application/json',
        ])->asForm()->get('https://api.thinkific.com/api/public/v1/products/'.$id)->json();
        return $course;
    }

}
