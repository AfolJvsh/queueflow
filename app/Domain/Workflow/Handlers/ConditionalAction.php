<?php

namespace App\Domain\Workflow\Handlers;

use App\Domain\Workflow\{ActionHandler, ActionResult};
use InvalidArgumentException;

final class ConditionalAction implements ActionHandler
{
    public function type(): string { return 'conditional'; }

    public function validateConfig(array $config): void
    {
        if (empty($config['field'])) throw new InvalidArgumentException('field is required');
        if (! in_array($config['operator'] ?? 'equals', ['equals','not_equals','exists','gt','gte','lt','lte','contains'], true)) {
            throw new InvalidArgumentException('Unsupported conditional operator');
        }
    }

    public function execute(array $config, array $context, string $idempotencyKey): ActionResult
    {
        $this->validateConfig($config);
        $actual = $this->get($context, (string) $config['field']);
        $expected = $config['value'] ?? null;
        $matched = match ($config['operator'] ?? 'equals') {
            'equals' => $actual == $expected,
            'not_equals' => $actual != $expected,
            'exists' => $actual !== null,
            'gt' => (float) $actual > (float) $expected,
            'gte' => (float) $actual >= (float) $expected,
            'lt' => (float) $actual < (float) $expected,
            'lte' => (float) $actual <= (float) $expected,
            'contains' => str_contains((string) $actual, (string) $expected),
            default => false,
        };
        return new ActionResult(['condition' => ['matched' => $matched]], null, ! $matched);
    }

    private function get(array $data, string $path): mixed
    {
        $value = $data;
        foreach (explode('.', $path) as $segment) {
            if (! is_array($value) || ! array_key_exists($segment, $value)) return null;
            $value = $value[$segment];
        }
        return $value;
    }
}
