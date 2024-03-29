<?php

namespace Tests\Feature;

use FintechSystems\Payfast\PayfastServiceProvider;
use Illuminate\Foundation\Bootstrap\LoadEnvironmentVariables;
use Livewire\LivewireServiceProvider;
use Orchestra\Testbench\TestCase;
use Tests\Fixtures\User;

abstract class FeatureTestCase extends TestCase
{
    // protected $loadEnvironmentVariables = true;

    protected function getEnvironmentSetUp($app)
    {
        // make sure, our .env file is loaded
        $app->useEnvironmentPath(__DIR__.'/..');

        // $this->app->useEnvironmentPath(__DIR__.'/..');

        $app->bootstrapWith([LoadEnvironmentVariables::class]);

        parent::getEnvironmentSetUp($app);
    }

    protected function setUp(): void
    {
        parent::setUp();

        $this->loadLaravelMigrations();

        $this->artisan('migrate')->run();

        //$this->loadEnvironmentVariables();
    }

    protected function createBillable($description = 'taylor', array $options = []): User
    {
        $user = $this->createUser($description);

        $user->createAsCustomer($options);

        return $user;
    }

    protected function createUser($description = 'taylor', array $options = []): User
    {
        return User::create(array_merge([
            'email' => "{$description}@payfast-test.com",
            'name' => 'Taylor Otwell',
            'password' => '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi',
        ], $options));
    }

    protected function getPackageProviders($app)
    {
        return [
            PayFastServiceProvider::class,
            LivewireServiceProvider::class,
        ];
    }
}
