<?php

namespace App\Services;

final class ActionCatalog
{
    public function all(): array
    {
        return [
            'transform' => [
                'label' => 'Transform data',
                'description' => 'Set, copy, uppercase, or lowercase values in execution context.',
                'config' => ['operations' => [['op' => 'set', 'key' => 'status', 'value' => 'processed']]],
            ],
            'store_value' => [
                'label' => 'Store value',
                'description' => 'Persist a literal or copied context value for downstream steps.',
                'config' => ['key' => 'customer_id', 'from' => 'payload.customer_id'],
            ],
            'conditional' => [
                'label' => 'Conditional branch',
                'description' => 'Evaluate a condition; when false, the immediately following step is skipped.',
                'config' => ['field' => 'status', 'operator' => 'equals', 'value' => 'processed'],
            ],
            'delay' => [
                'label' => 'Delay',
                'description' => 'Pause before releasing the next workflow step without blocking a worker.',
                'config' => ['seconds' => 5],
            ],
            'http_request' => [
                'label' => 'HTTP request',
                'description' => 'Call a public HTTP(S) endpoint with SSRF protection, templating, throttling, and idempotency.',
                'config' => ['method' => 'POST', 'url' => 'https://httpbin.org/post', 'headers' => ['Accept' => 'application/json'], 'secret_headers' => [], 'body' => ['source' => 'queueflow'], 'requests_per_minute' => 60, 'timeout_seconds' => 15],
            ],
            'outbound_webhook' => [
                'label' => 'Outbound webhook',
                'description' => 'Deliver a signed JSON webhook with downstream idempotency and throttling.',
                'config' => ['url' => 'https://httpbin.org/post', 'payload' => ['event' => 'workflow.completed'], 'requests_per_minute' => 60, 'timeout_seconds' => 15],
            ],
            'email_notification' => [
                'label' => 'Email notification',
                'description' => 'Render and send a notification email through Laravel mail transports.',
                'config' => ['to' => '{{ notification_email }}', 'subject' => 'QueueFlow execution update', 'body' => 'Execution completed.'],
            ],
        ];
    }
}
