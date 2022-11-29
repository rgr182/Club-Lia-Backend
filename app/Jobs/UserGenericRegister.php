<?php

namespace App\Jobs;

use App\UserPhpFox;
use App\UserThinkific;
use http\Client\Curl\User;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\Log;
use function Symfony\Component\String\u;

class UserGenericRegister implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    protected $data;
    protected $dataFox;

    public function __construct($data)
    {
       $this->data = $data;
       //$this->dataFox = $dataFox;
    }

    /**
     * Execute the job.
     *
     * @return void
     */
    public function handle()
    {
        $user = new UserThinkific();
        $user = $user->createUser($this->data);

        //$userFox = new UserPhpFox();
        //$userFox = $userFox->createUser($this->dataFox);

        $userSync = Arr::collapse(['academy' => $user]);

        Log::info(json_encode(["respuesta" => $userSync]));
    }
}
