<?php

namespace FintechSystems\Payfast;

use FintechSystems\PayFast\Exceptions\PayFastException;
use Illuminate\Support\Facades\Http;
use Money\Currencies\ISOCurrencies;
use Money\Currency;
use Money\Formatter\IntlMoneyFormatter;
use Money\Money;
use NumberFormatter;

class Cashier
{
    /**
     * The custom currency formatter.
     *
     * @var callable
     */
    protected static $formatCurrencyUsing;

    /**
     * Indicates if Cashier migrations will be run.
     *
     * @var bool
     */
    public static $runsMigrations = true;

    /**
     * Indicates if Cashier routes will be registered.
     *
     * @var bool
     */
    public static $registersRoutes = true;

    /**
     * Indicates if Cashier will mark past due subscriptions as inactive.
     *
     * @var bool
     */
    public static $deactivatePastDue = true;

    /**
     * The customer model class name.
     *
     * @var string
     */
    public static $customerModel = Customer::class;

    /**
     * The subscription model class name.
     *
     * @var string
     */
    public static $subscriptionModel = Subscription::class;

    /**
     * The receipt model class name.
     *
     * @var string
     */
    public static $receiptModel = Receipt::class;

    /**
     * Get the PayFast webhook url.
     *
     * @return string
     */
    public static function webhookUrl()
    {
        return config('cashier.webhook') ?? route('cashier.webhook');
    }

    /**
     * Get the Paddle vendors API url.
     *
     * @return string
     */
    public static function vendorsUrl()
    {
        return 'https://'.(config('cashier.sandbox') ? 'sandbox-' : '').'vendors.paddle.com';
    }

    /**
     * Get the Paddle checkout API url.
     *
     * @return string
     */
    public static function checkoutUrl()
    {
        return 'https://'.(config('cashier.sandbox') ? 'sandbox-' : '').'checkout.paddle.com';
    }

    /**
     * Perform a GET Paddle API call.
     *
     * @param  string  $uri
     * @param  array  $payload
     * @return \Illuminate\Http\Client\Response
     *
     * @throws \Laravel\Paddle\Exceptions\PaddleException
     */
    public static function get($uri, array $payload = [])
    {
        return static::makeApiCall('get', static::checkoutUrl().'/api/2.0'.$uri, $payload);
    }

    /**
     * Perform a POST Paddle API call.
     *
     * @param  string  $uri
     * @param  array  $payload
     * @return \Illuminate\Http\Client\Response
     *
     * @throws \Laravel\Paddle\Exceptions\PaddleException
     */
    public static function post($uri, array $payload = [])
    {
        return static::makeApiCall('post', static::vendorsUrl().'/api/2.0'.$uri, $payload);
    }

    /**
     * Perform a Paddle API call.
     *
     * @param  string  $method
     * @param  string  $uri
     * @param  array  $payload
     * @return \Illuminate\Http\Client\Response
     *
     * @throws \Laravel\Paddle\Exceptions\PaddleException
     */
    protected static function makeApiCall($method, $uri, array $payload = [])
    {
        $response = Http::$method($uri, $payload);

        if ($response['success'] === false) {
            throw new PayFastException($response['error']['message'], $response['error']['code']);
        }

        return $response;
    }

    /**
     * Get the default PayFast API options.
     *
     * @param  array  $options
     * @return array
     */
    public static function payfastOptions(array $options = [])
    {
        return array_merge([
            'vendor_id' => (int) config('cashier.vendor_id'),
            'vendor_auth_code' => config('cashier.vendor_auth_code'),
        ], $options);
    }

    /**
     * Set the custom currency formatter.
     *
     * @param  callable  $callback
     * @return void
     */
    public static function formatCurrencyUsing(callable $callback)
    {
        static::$formatCurrencyUsing = $callback;
    }

    /**
     * Format the given amount into a displayable currency.
     *
     * @param  int  $amount
     * @param  string|null  $currency
     * @param  string|null  $locale
     * @return string
     */
    public static function formatAmount($amount, $currency = null, $locale = null)
    {
        if (static::$formatCurrencyUsing) {
            return call_user_func(static::$formatCurrencyUsing, $amount, $currency);
        }

        $money = new Money($amount, new Currency(strtoupper($currency ?? config('cashier.currency'))));

        $locale = $locale ?? config('cashier.currency_locale');

        $numberFormatter = new NumberFormatter($locale, NumberFormatter::CURRENCY);
        $moneyFormatter = new IntlMoneyFormatter($numberFormatter, new ISOCurrencies());

        return $moneyFormatter->format($money);
    }

    /**
     * Configure Cashier to not register its migrations.
     *
     * @return static
     */
    public static function ignoreMigrations()
    {
        static::$runsMigrations = false;

        return new static();
    }

    /**
     * Configure Cashier to not register its routes.
     *
     * @return static
     */
    public static function ignoreRoutes()
    {
        static::$registersRoutes = false;

        return new static();
    }

    /**
     * Configure Cashier to maintain past due subscriptions as active.
     *
     * @return static
     */
    public static function keepPastDueSubscriptionsActive()
    {
        static::$deactivatePastDue = false;

        return new static();
    }

    /**
     * Set the customer model class name.
     *
     * @param  string  $customerModel
     * @return void
     */
    public static function useCustomerModel($customerModel)
    {
        static::$customerModel = $customerModel;
    }

    /**
     * Set the subscription model class name.
     *
     * @param  string  $subscriptionModel
     * @return void
     */
    public static function useSubscriptionModel($subscriptionModel)
    {
        static::$subscriptionModel = $subscriptionModel;
    }

    /**
     * Set the receipt model class name.
     *
     * @param  string  $receiptModel
     * @return void
     */
    public static function useReceiptModel($receiptModel)
    {
        static::$receiptModel = $receiptModel;
    }
}
