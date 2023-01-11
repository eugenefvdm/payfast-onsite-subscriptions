<?php

namespace Tests\Unit;

use Carbon\Carbon;
use FintechSystems\PayFast\Customer;
#use PHPUnit\Framework\TestCase;
#use Tests\TestCase; // See https://laracasts.com/discuss/channels/laravel/call-to-a-member-function-connection-on-null-at-laravel-unit-testing
use Orchestra\Testbench\TestCase;
use Tests\Fixtures\User;

class CustomerTest extends TestCase
{
    public function test_customer_can_be_put_on_a_generic_trial()
    {
        $user = new User();
        $user->customer = $customer = new Customer();
        $customer->setDateFormat('Y-m-d H:i:s');

        $this->assertFalse($user->onGenericTrial());

        $customer->trial_ends_at = Carbon::tomorrow();

        $this->assertTrue($user->onTrial());
        $this->assertTrue($user->onGenericTrial());

        $customer->trial_ends_at = Carbon::today()->subDays(5);

        $this->assertFalse($user->onGenericTrial());
    }

    public function test_we_can_check_if_a_generic_trial_has_expired()
    {
        $user = new User();
        $user->customer = $customer = new Customer();
        $customer->setDateFormat('Y-m-d H:i:s');

        $customer->trial_ends_at = Carbon::yesterday();

        $this->assertTrue($user->hasExpiredTrial());
        $this->assertTrue($user->hasExpiredGenericTrial());
    }
}