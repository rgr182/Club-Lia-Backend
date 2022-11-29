<?php

namespace App;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\Http;

class SyncSchool
{
    protected $key;
    protected $subdomain;

    function __construct() {
        $this->key = Config::get('app.thinkific');
        $this->subdomain = Config::get('app.subdomain_thinkific');
    }

    //Group request list, creation, update, delete.
    public function listSchool(){

        $request = Http::withHeaders([
            'X-Auth-API-Key' => $this->key,
            'X-Auth-Subdomain' => $this->subdomain,
            'Content-Type' => 'application/json',
        ])->get('https://api.thinkific.com/api/public/v1/groups/');

        return $request;
    }

    public function createSchool($data){

        $request = Http::withHeaders([
            'X-Auth-API-Key' => $this->key,
            'X-Auth-Subdomain' => $this->subdomain,
            'Content-Type' => 'application/json',
        ])->post('https://api.thinkific.com/api/public/v1/groups/', [
            'name' => $data,
        ]);

        return $request;
    }

}
