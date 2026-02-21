<?php

namespace App\Providers;

use Illuminate\Support\ServiceProvider;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Event;
use Laravel\Horizon\Events\JobPushed;
use Laravel\Horizon\Events\JobReserved;
use Laravel\Horizon\Events\JobReleased;
use Laravel\Horizon\Events\JobDeleted;
use Laravel\Horizon\Events\JobFailed;
use Carbon\Carbon;

class AppServiceProvider extends ServiceProvider
{
    public function boot(): void
    {
        $node = config('app.name') . '-node-1';

        Event::listen(JobPushed::class, function (JobPushed $event) use ($node) {
            $this->recordEvent($node, $event->payload, $event->queue, 'queued');
        });

        Event::listen(JobReserved::class, function (JobReserved $event) use ($node) {
            $this->recordEvent($node, $event->payload, $event->queue, 'running');
        });

        Event::listen(JobReleased::class, function (JobReleased $event) use ($node) {
            $this->recordEvent($node, $event->payload, $event->queue, 'retrying');
        });

        Event::listen(JobDeleted::class, function (JobDeleted $event) use ($node) {
            $this->recordEvent($node, $event->payload, $event->queue, 'succeeded');
        });

        Event::listen(JobFailed::class, function (JobFailed $event) use ($node) {
            $this->recordEvent($node, $event->payload, $event->queue, 'failed');
        });
    }

    protected function recordEvent($node, $payload, $queue, $state)
    {
        $uuid    = $payload['id'] ?? null;
        $jobType = $payload['displayName'] ?? null;

        if (
            !$uuid ||
            !$jobType ||
            str_contains($jobType, 'FlushTelemetryJob')
        ) {
            return;
        }

        \Log::info('Horizon event fired', [
            'state' => $state,
            'uuid'  => $uuid,
            'job'   => $jobType
        ]);

        $key = "telemetry:$node";

        $envelope = Cache::get($key, [
            'schema_version' => '1.0',
            'application' => [
                'name' => config('app.name'),
                'environment' => config('app.env'),
                'node' => $node,
            ],
            'sent_at' => now()->toISOString(),
            'executions' => [],
        ]);

        if (!isset($envelope['executions'][$uuid])) {
            $envelope['executions'][$uuid] = [
                'uuid' => $uuid,
                'job_type' => $jobType,
                'queue' => $queue ?? 'default',
                'events' => [],
            ];
        }

        $envelope['executions'][$uuid]['events'][] = [
            'state'       => $state,
            'occurred_at' => now()->toISOString(),
            'attempt'     => $payload['attempts'] ?? 1,
        ];

        Cache::put($key, $envelope, now()->addMinutes(5));
    }
}
