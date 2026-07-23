<?php

namespace Tests\Unit;

use App\Models\Order;
use Tests\TestCase;

class OrderNetAmountTest extends TestCase
{
    public function test_net_amount_returns_stored_value_if_positive(): void
    {
        $order = new Order([
            'total_amount' => 100000,
            'discount_amount' => 10000,
            'marketplace_fee' => 5000,
            'net_amount' => 85000,
        ]);

        $this->assertEquals(85000.0, $order->net_amount);
    }

    public function test_net_amount_uses_escrow_amount_from_financial_breakdown_if_net_amount_is_zero(): void
    {
        $order = new Order([
            'total_amount' => 100000,
            'net_amount' => 0,
            'financial_breakdown' => [
                'escrow_amount' => 88000,
            ],
        ]);

        $this->assertEquals(88000.0, $order->net_amount);
    }

    public function test_net_amount_calculates_estimated_amount_if_net_amount_and_breakdown_are_zero(): void
    {
        $order = new Order([
            'total_amount' => 100000,
            'discount_amount' => 10000,
            'marketplace_fee' => 5000,
            'net_amount' => 0,
        ]);

        // Estimated net_amount = total_amount (100k) - discount (10k) - fee (5k) = 85k
        $this->assertEquals(85000.0, $order->net_amount);
    }
}
