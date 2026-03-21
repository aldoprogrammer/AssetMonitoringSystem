<h1>{{ $eventType }}</h1>
<p>AssetMonitoringSystem generated the following event payload:</p>
<pre>{{ json_encode($payload, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES) }}</pre>
