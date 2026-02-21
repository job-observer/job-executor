<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Jobs\ProcessPayment;
use App\Jobs\SendEmailNotification;
use App\Jobs\GenerateReport;
use App\Jobs\SyncAnalyticsData;

class GenerateTestJobs extends Command
{
    protected $signature = 'test:generate-jobs {count=100}';
    protected $description = 'Dispatch mixed priority test jobs';

    public function handle()
    {
        $count = (int) $this->argument('count');

        for ($i = 0; $i < $count; $i++) {

            ProcessPayment::dispatch();
            SendEmailNotification::dispatch();
            GenerateReport::dispatch();
            SyncAnalyticsData::dispatch();
        }

        $this->info("Dispatched {$count} batches of jobs.");
    }
}