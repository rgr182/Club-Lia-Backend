<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Homework;

class ExpiredMemberships extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'membership:expired';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Check expired memberships';

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
        $homewrok = Homework::findOrFail(186);

        $status = $homewrok->is_active ? 0 : 1;

        Homework::find(186)->update(['is_active' => $status]);
    }
}
