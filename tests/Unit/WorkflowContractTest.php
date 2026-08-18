<?php

namespace Tests\Unit;

use App\Domain\Workflow\Handlers\DelayAction;
use App\Domain\Workflow\Handlers\StoreValueAction;
use App\Domain\Workflow\WebhookSignature;
use InvalidArgumentException;
use PHPUnit\Framework\TestCase;

final class WorkflowContractTest extends TestCase
{
    public function test_store_value_and_delay_handlers_validate_and_execute(): void
    {
        $stored = (new StoreValueAction())->execute(['key' => 'customer_id', 'from' => 'id'], ['id' => 42], 'idem-1');
        self::assertSame(['customer_id' => 42], $stored->output);

        $delay = (new DelayAction())->execute(['seconds' => 15], [], 'idem-2');
        self::assertSame(15, $delay->delaySeconds);

        $this->expectException(InvalidArgumentException::class);
        (new DelayAction())->validateConfig(['seconds' => 0]);
    }

    public function test_webhook_signatures_are_timestamp_bound_and_tamper_evident(): void
    {
        $signatures = new WebhookSignature();
        $timestamp = time();
        $signature = $signatures->sign('{"ok":true}', 'secret', $timestamp);
        self::assertTrue($signatures->verify('{"ok":true}', 'secret', $timestamp, $signature));
        self::assertFalse($signatures->verify('{"ok":false}', 'secret', $timestamp, $signature));
        self::assertFalse($signatures->verify('{"ok":true}', 'secret', $timestamp - 301, $signature));
    }
}
