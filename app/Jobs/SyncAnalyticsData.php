<?php

namespace App\Jobs;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;

class SyncAnalyticsData implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public int $tries = 4;
    public int $backoff = 2;

    public function __construct()
    {
        $this->onQueue('background');
    }

    public function handle()
    {
        if (rand(1,2) === 1) { 
            throw new \Exception("Analytics sync failed");
        }

        sleep(rand(2,4));
    }
}
