<?php

namespace App\Jobs;

use App\UserPhpFox;
use App\UserThinkific;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\Log;

class DeleteGenericUserJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    protected $data;
    protected $dataFox;

    public function __construct($data, $dataFox)
    {
        $this->data = $data;
        $this->dataFox = $dataFox;
    }

    /**
     * Execute the job.
     *
     * @return void
     */
    public function handle()
    {
        $user = new UserThinkific();
        $user = $user->deleteUserSchooling($this->data);

        $userFox = new UserPhpFox();
        $userFox = $userFox->deleteUserCommunity($this->dataFox);

        $userSync = Arr::collapse(['schooling' => $user, 'comunidad' => $userFox]);
        var_dump($userSync);

        Log::info(json_encode(["respuesta" => $userSync, $this->data, $this->dataFox]));
    }
}
