<?php

namespace App\Providers;

use Illuminate\Support\Facades\Gate;
use Laravel\Telescope\IncomingEntry;
use Laravel\Telescope\Telescope;
use Laravel\Telescope\TelescopeApplicationServiceProvider;

class TelescopeServiceProvider extends TelescopeApplicationServiceProvider
{
    public function register(): void
    {
        $this->hideSensitiveRequestDetails();

        $telescopeEnabled = filter_var(env('TELESCOPE_ENABLED', true), FILTER_VALIDATE_BOOL);
        $recordAllEntries = filter_var(env('TELESCOPE_RECORD_ALL', $telescopeEnabled), FILTER_VALIDATE_BOOL);

        config()->set('telescope.enabled', $telescopeEnabled);
        config()->set('telescope.path', env('TELESCOPE_PATH', 'identity/telescope'));

        $isLocal = $this->app->environment('local');

        Telescope::filter(function (IncomingEntry $entry) use ($isLocal, $recordAllEntries): bool {
            $requestPath = '/' . ltrim((string) data_get($entry->content, 'uri', data_get($entry->content, 'path', '')), '/');

            if ($requestPath === '/up') {
                return false;
            }

            return $recordAllEntries
                || $isLocal
                || $entry->isReportableException()
                || $entry->isFailedRequest()
                || $entry->isFailedJob()
                || $entry->isScheduledTask()
                || $entry->hasMonitoredTag();
        });

        Telescope::tag(function (IncomingEntry $entry): array {
            return array_values(array_filter([
                'service:' . env('TELESCOPE_SERVICE_NAME', 'identity-service'),
                isset($entry->content['response_status']) ? 'status:' . $entry->content['response_status'] : null,
                isset($entry->content['method']) ? 'method:' . strtolower((string) $entry->content['method']) : null,
            ]));
        });
    }

    protected function gate(): void
    {
        Gate::define('viewTelescope', fn ($user = null): bool => true);
    }

    protected function hideSensitiveRequestDetails(): void
    {
        if ($this->app->environment('local')) {
            return;
        }

        Telescope::hideRequestParameters(['_token']);
        Telescope::hideRequestHeaders([
            'authorization',
            'cookie',
            'x-csrf-token',
            'x-xsrf-token',
        ]);
    }
}
