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

        config()->set('telescope.enabled', filter_var(env('TELESCOPE_ENABLED', true), FILTER_VALIDATE_BOOL));
        config()->set('telescope.path', env('TELESCOPE_PATH', 'assignments/telescope'));

        $isLocal = $this->app->environment('local');

        Telescope::filter(function (IncomingEntry $entry) use ($isLocal): bool {
            return $isLocal
                || $entry->isReportableException()
                || $entry->isFailedRequest()
                || $entry->isFailedJob()
                || $entry->isScheduledTask()
                || $entry->hasMonitoredTag();
        });

        Telescope::tag(function (IncomingEntry $entry): array {
            return array_values(array_filter([
                'service:' . env('TELESCOPE_SERVICE_NAME', 'assignment-service'),
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
