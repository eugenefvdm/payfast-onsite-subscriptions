<?php

namespace FintechSystems\Payfast\Concerns;

use Exception;
use FintechSystems\Payfast\Cashier;
use InvalidArgumentException;
use LogicException;

trait PerformsCharges
{
    /**
     * Generate a pay link for a "one off" charge on the customer for the given amount.
     *
     * TODO This method most likely does not exist in Payfast
     *
     * @param  float|array  $amount
     * @param  string  $title
     * @param  array  $options
     * @return string
     *
     * @throws Exception
     */
    public function charge($amount, $title, array $options = []): string
    {
        if (strlen($title) > 200) {
            throw new InvalidArgumentException('Charge title has a maximum length of 200 characters.');
        }

        return $this->generatePayLink(array_merge([
            'title' => $title,
            'webhook_url' => Cashier::webhookUrl(),
            'prices' => is_array($amount) ? $amount : [config('cashier.currency').':'.$amount],
        ], $options, $this->payfastOptions()));
    }

    /**
     * Generate a pay link for a product.
     *
     * @param  int  $productId
     * @param  array  $options
     * @return string
     */
    public function chargeProduct($productId, array $options = [])
    {
        return $this->generatePayLink(array_merge([
            'product_id' => $productId,
        ], $options, $this->payfastOptions()));
    }

    /**
     * Generate a new pay link.
     *
     * @param  array  $payload
     * @return string
     */
    protected function generatePayLink(array $payload)
    {
        $payload['customer_email'] = $payload['customer_email'] ?? (string) $this->payfastEmail();
        $payload['customer_country'] = $payload['customer_country'] ?? (string) $this->payfastCountry();
        $payload['customer_postcode'] = $payload['customer_postcode'] ?? (string) $this->payfastPostcode();

        // We'll need a way to identify the user in any webhook we're catching so before
        // we make the API request we'll attach the authentication identifier to this
        // payload so we can match it back to a user when handling Paddle webhooks.
        if (! isset($payload['passthrough'])) {
            $payload['passthrough'] = [];
        }

        if (! is_array($payload['passthrough'])) {
            throw new LogicException('The value for "passthrough" always needs to be an array.');
        }

        $payload['passthrough']['billable_id'] = $this->getKey();
        $payload['passthrough']['billable_type'] = $this->getMorphClass();

        $payload['passthrough'] = json_encode($payload['passthrough']);

        $payload = array_map(fn ($value) => is_string($value) ? trim($value) : $value, $payload);

        return Cashier::post('/product/generate_pay_link', $payload)['response']['url'];
    }

    /**
     * Refund a given order.
     *
     * @param  int  $orderId
     * @param  float|null  $amount
     * @param  string  $reason
     * @return int
     */
    public function refund($orderId, $amount = null, $reason = '')
    {
        $payload = array_merge([
            'order_id' => $orderId,
            'reason' => $reason,
        ], $this->payfastOptions());

        if ($amount) {
            $payload['amount'] = $amount;
        }

        return Cashier::post('/payment/refund', $payload)['response']['refund_request_id'];
    }
}
