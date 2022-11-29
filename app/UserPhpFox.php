<?php

namespace App;

use GuzzleHttp\Client;
//use GuzzleHttp\Psr7\Request;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\Http;
use mysql_xdevapi\Exception;

class UserPhpFox
{
    protected $key;
    protected $secret;
    protected $url;
    protected $token;
    protected $username;
    protected $password;

    function __construct() {
        $this->key = Config::get('app.phpfox');
        $this->secret = Config::get('app.secret_phpfox');
        $this->url = Config::get('app.url_phpfox');
        $this->username = Config::get('app.username_comunidad');
        $this->password = Config::get('app.pass_comunidad');
    }

    public function getAuthorization()
    {
        include public_path('token_phpfox.php');
        $validateToken = Http::withToken($token_phpfox)->get($this->url . "/restful_api/user");

        if (!$validateToken->ok()) {
            $response = Http::post($this->url . "/restful_api/token", [
                'grant_type' => 'client_credentials',
                'client_id' => $this->key,
                'client_secret' => $this->secret,
            ]);
            if ($response->ok()) {
                $token = json_decode($response, true);
                $val = $token['access_token'];
                $var_str = var_export($val, true);
                $var = "<?php\n\n\$token_phpfox = $var_str;\n\n?>";
                file_put_contents(public_path('token_phpfox.php'), $var);
                return $token["access_token"];
            } else {
                return false;
            }
        }

        return $token_phpfox;
    }

    public function createUser($data){

        $token = self::getAuthorization();

        $response = Http::withToken($token)->asForm()->post($this->url . '/restful_api/user', [
            'val[email]' => $data['email'],
            'val[full_name]' => $data['full_name'],
            'val[user_name]' => $data['user_name'],
            'val[password]' => 'ClubLia'
        ]);

        return $response->json();
    }

    public function deleteUserCommunity($userId)
    {

        $token = self::getAuthorization();

        $response = Http::withToken($token)->delete($this->url . '/restful_api/user/' . $userId);

        return $response->json();
    }

    public function updateUser($inputData, $userId)
    {

        try {
            $token = self::getAuthorization();
            $response = Http::withToken($token)->asForm()->put($this->url . '/restful_api/user/' . $userId, [
                'val[email]' => $inputData['email'],
                'val[full_name]' => $inputData['name'] . ' ' . $inputData['last_name'],
                'val[password]' => $inputData['name'],
            ]);

            return $response->json();

        } catch (\Exception $e) {
            $error["code"] = 'INVALID_DATA';
            $error["message"] = "Error al crear el usuario";
            $errors["username"] = "Error al crear el usuario.";

            $error["errors"] = [$errors];

            return response()->json(['error' => $error], 500);
        }
    }

    public function singleSignOn($user)
    {
        $token = self::getAuthorization();

        $header = json_encode(['typ' => 'JWT', 'alg' => 'HS256']);

        // Create token payload as a JSON string
        $payload =  self::encrypt($user->active_phpfox);

        // Encode Header to Base64Url String
        $base64UrlHeader = str_replace(['+', '/', '='], ['-', '_', ''], base64_encode($header));

        // Encode Payload to Base64Url String
        $base64UrlPayload = str_replace(['+', '/', '='], ['-', '_', ''], base64_encode($payload));

        // Create Signature Hash
        $tokenEncrypt = self::encrypt($token);

        $base64UrlSignature = str_replace(['+', '/', '='], ['-', '_', ''], base64_encode($tokenEncrypt));


        // Create JWT
        $jwt = $base64UrlHeader . "." . $base64UrlPayload . "." . $base64UrlSignature;

        $baseUrl =  $this->url."/s-s-o/";
        $url = $baseUrl . $jwt;

        echo $url;
    }
    public function PhpFoxRouteOn()
    {
        $url =  $this->url;        
        echo $url;
    }
    public function encrypt($val){
        $ciphering = "AES-128-CTR";
        $options = 0;
        $encryption_iv = '1987635498325191';
        $encryption_key = "KjiUyhasp";
        $encryption = openssl_encrypt($val, $ciphering, $encryption_key, $options, $encryption_iv);
        return $encryption;
    }
}
