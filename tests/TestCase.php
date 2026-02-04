<?php

namespace Mortogo321\LaravelThaiPromptPay\Tests;

use Mortogo321\LaravelThaiPromptPay\PromptPayServiceProvider;
use Orchestra\Testbench\TestCase as BaseTestCase;

abstract class TestCase extends BaseTestCase
{
    protected function getPackageProviders($app): array
    {
        return [
            PromptPayServiceProvider::class,
        ];
    }

    protected function getPackageAliases($app): array
    {
        return [
            'PromptPay' => \Mortogo321\LaravelThaiPromptPay\Facades\PromptPay::class,
        ];
    }
}
