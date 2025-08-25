<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

class UpdateStatusApproveDomain extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'shop:update-approve';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Update status and valid days of approved domains';

    /**
     * Execute the console command.
     *
     * @return int
     */
    public function handle()
    {
        DB::table('approve_domain')
            ->where('status', 'approved')
            ->update([
                'valid_days' => DB::raw('GREATEST(valid_days - EXTRACT(DAY FROM (CURRENT_DATE - used_at)), 0)'),
                'status'     => DB::raw('CASE
                                    WHEN valid_days - EXTRACT(DAY FROM (CURRENT_DATE - used_at)) <= 0
                                    THEN \'pending\'
                                    ELSE \'approved\'
                                 END'),
            ]);

        return Command::SUCCESS;
    }
}
