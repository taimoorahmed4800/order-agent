<?php

namespace App\Services;

use App\Models\Order;
use Illuminate\Support\Facades\Log;
use Throwable;

/**
 * The "tools" the AI is allowed to use. These are normal PHP functions.
 * IMPORTANT: the AI only ASKS to run a tool. We run it, and we enforce
 * all the rules here (never trust the model with security or business rules).
 */
class OrderTools
{
    public const RETURN_DAYS = 14;

    private const NOT_FOUND = 'Order not found, or the email does not match this order.';

    /**
     * Tool descriptions sent to the AI so it knows what it can call.
     */
    public function definitions(): array
    {
        $params = [
            'type'       => 'object',
            'properties' => [
                'order_number' => ['type' => 'string', 'description' => 'The order number, for example SE-1001'],
                'email'        => ['type' => 'string', 'description' => 'The email address used for the order'],
            ],
            'required' => ['order_number', 'email'],
        ];

        return [
            [
                'type'     => 'function',
                'function' => [
                    'name'        => 'get_order_status',
                    'description' => 'Get the current status, items, tracking number and delivery date of an order.',
                    'parameters'  => $params,
                ],
            ],
            [
                'type'     => 'function',
                'function' => [
                    'name'        => 'check_return_eligibility',
                    'description' => 'Check if an order can be returned (14 days after delivery).',
                    'parameters'  => $params,
                ],
            ],
            [
                'type'     => 'function',
                'function' => [
                    'name'        => 'cancel_order',
                    'description' => 'Cancel an order. Only call this AFTER the customer clearly confirmed they want to cancel.',
                    'parameters'  => $params,
                ],
            ],
        ];
    }

    /**
     * Run a tool by name. Always returns an array (the AI reads it as JSON).
     */
    public function execute(string $name, array $args): array
    {
        try {
            return match ($name) {
                'get_order_status'         => $this->getOrderStatus($args),
                'check_return_eligibility' => $this->checkReturnEligibility($args),
                'cancel_order'             => $this->cancelOrder($args),
                default                    => ['error' => "Unknown tool: {$name}"],
            };
        } catch (Throwable $e) {
            Log::error("Tool {$name} failed: ".$e->getMessage());

            return ['error' => 'The tool failed. Please try again later.'];
        }
    }

    private function getOrderStatus(array $args): array
    {
        $order = $this->findOrder($args);

        if (! $order) {
            return ['error' => self::NOT_FOUND];
        }

        return [
            'order_number'       => $order->order_number,
            'status'             => $order->status,
            'items'              => $order->items,
            'total_rs'           => $order->total,
            'tracking_number'    => $order->tracking_number,
            'estimated_delivery' => $order->estimated_delivery?->toDateString(),
            'delivered_at'       => $order->delivered_at?->toDateString(),
        ];
    }

    private function checkReturnEligibility(array $args): array
    {
        $order = $this->findOrder($args);

        if (! $order) {
            return ['error' => self::NOT_FOUND];
        }

        if ($order->status !== 'delivered' || ! $order->delivered_at) {
            return [
                'eligible' => false,
                'reason'   => "The order is '{$order->status}', not delivered yet. Returns are possible only after delivery.",
            ];
        }

        $daysSince = (int) $order->delivered_at->diffInDays(now());

        if ($daysSince > self::RETURN_DAYS) {
            return [
                'eligible' => false,
                'reason'   => "The return window of ".self::RETURN_DAYS." days has passed (delivered {$daysSince} days ago).",
            ];
        }

        return [
            'eligible'  => true,
            'days_left' => self::RETURN_DAYS - $daysSince,
            'how_to'    => 'Open the order page and click "Request return". The product must be unused and in original packaging.',
        ];
    }

    private function cancelOrder(array $args): array
    {
        $order = $this->findOrder($args);

        if (! $order) {
            return ['error' => self::NOT_FOUND];
        }

        // Business rule is enforced HERE, not in the prompt.
        if ($order->status !== 'processing') {
            return [
                'cancelled' => false,
                'reason'    => "Only orders in 'processing' status can be cancelled. This order is '{$order->status}'.",
            ];
        }

        $order->update(['status' => 'cancelled']);

        return ['cancelled' => true, 'order_number' => $order->order_number, 'refund_note' => 'Refund will be sent within 5 to 7 working days.'];
    }

    /**
     * Find an order only if BOTH order number and email match.
     */
    private function findOrder(array $args): ?Order
    {
        $number = strtoupper(trim((string) ($args['order_number'] ?? '')));
        $email  = strtolower(trim((string) ($args['email'] ?? '')));

        if ($number === '' || $email === '') {
            return null;
        }

        $order = Order::where('order_number', $number)->first();

        if (! $order || strtolower($order->customer_email) !== $email) {
            return null;
        }

        return $order;
    }
}
