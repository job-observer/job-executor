<?php

namespace App\Jobs;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;

class SendEmailNotification implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public int $tries = 2;
    public int $backoff = 2;

    public function __construct()
    {
        $this->onQueue('default');
    }

    public function handle()
    {
        if (rand(1,3) === 1) { 
            throw new \Exception("SMTP timeout");
        }

        sleep(rand(1,2));
    }
}
