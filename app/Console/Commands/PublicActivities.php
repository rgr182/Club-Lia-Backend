<?php

namespace App\Console\Commands;

use App\Activity;
use Carbon\Carbon;
use Illuminate\Console\Command;

class PublicActivities extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'post:activity';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Cron para publicar tareas programadas';

    /**
     * Create a new command instance.
     *
     * @return void
     */
    public function __construct()
    {
        parent::__construct();
    }

    /**
     * Execute the console command.
     *
     * @return int
     */
    public function handle()
    {
        $activities = Activity::where([['public_day', Carbon::today()],['is_active', '=', 0]])->get();
        if(!$activities->isEmpty()) {
            foreach ($activities as $activity){
                $activity->is_active = 1;
                $activity->save();
                info($activity);
            }
        }else{
            info('No hay tareas para publicar');
        }
    }
}
