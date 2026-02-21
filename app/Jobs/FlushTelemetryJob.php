<?php

namespace App\Jobs;

use Illuminate\Bus\Queueable;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;

class FlushTelemetryJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    /**
     * Exclude from telemetry tracking
     */
    public bool $dontTrack = true;

    /**
     * Retry behavior
     */
    public int $tries = 3;
    public int $backoff = 10;

    /**
     * Put on dedicated queue (optional but recommended)
     */
    public function __construct()
    {
        
    }

    public function handle(): void
    {

        \Log::info('FlushTelemetryJob running');

        $node = config('app.name') . '-node-1';
        $key  = "telemetry:$node";

        $payload = Cache::pull($key);

        if (!$payload || empty($payload['executions'])) {
            return;
        }

        $payload['sent_at'] = now()->toISOString();

        try {

            $response = Http::timeout(5)
                ->post(config('telemetry.tracker_url'), $payload);

            if (!$response->successful()) {

                // restore cache if API rejected
                Cache::put($key, $payload, now()->addMinutes(5));

                Log::warning('Telemetry flush failed', [
                    'status' => $response->status(),
                    'body'   => $response->body(),
                ]);
            }

        } catch (\Throwable $e) {

            // restore envelope if network failed
            Cache::put($key, $payload, now()->addMinutes(5));

            Log::error('Telemetry flush exception', [
                'message' => $e->getMessage(),
            ]);
        }
    }
}