<?php

namespace Meritum\Logger\Test;

use Georgeff\Kernel\DI\DefinitionInterface;
use Georgeff\Kernel\Environment;
use Georgeff\Kernel\KernelInterface;
use Meritum\Logger\LoggerFactory;
use Meritum\Logger\LoggerModule;
use PHPUnit\Framework\TestCase;
use Psr\Log\LoggerInterface;

final class LoggerModuleTest extends TestCase
{
    private function makeKernel(string &$registeredId = '', ?callable &$registeredFactory = null): KernelInterface
    {
        $definition = $this->createStub(DefinitionInterface::class);
        $definition->method('share')->willReturn($definition);

        $kernel = $this->createStub(KernelInterface::class);
        $kernel->method('define')
            ->willReturnCallback(function (string $id, callable $factory) use (&$registeredId, &$registeredFactory, $definition): DefinitionInterface {
                $registeredId      = $id;
                $registeredFactory = $factory;

                return $definition;
            });

        return $kernel;
    }

    public function test_register_defines_logger_interface(): void
    {
        $registeredId = '';
        $module       = new LoggerModule();
        $kernel       = $this->makeKernel($registeredId);

        $module->register($kernel);

        $this->assertSame(LoggerInterface::class, $registeredId);
    }

    public function test_register_uses_logger_factory(): void
    {
        $registeredFactory = null;
        $module            = new LoggerModule();
        $kernel            = $this->makeKernel(registeredFactory: $registeredFactory);

        $module->register($kernel);

        $this->assertInstanceOf(LoggerFactory::class, $registeredFactory);
    }

    public function test_config_returns_log_level_key(): void
    {
        $module = new LoggerModule();
        $config = $module->config(Environment::Production);

        $this->assertArrayHasKey('logger.log_level', $config);
    }

    public function test_config_uses_log_level_env_var_when_set(): void
    {
        putenv('LOG_LEVEL=warning');

        try {
            $module = new LoggerModule();
            $config = $module->config(Environment::Production);
            $this->assertSame('warning', $config['logger.log_level']);
        } finally {
            putenv('LOG_LEVEL');
        }
    }

    public function test_config_defaults_to_debug_in_development(): void
    {
        putenv('LOG_LEVEL');

        $module = new LoggerModule();
        $config = $module->config(Environment::Development);

        $this->assertSame('debug', $config['logger.log_level']);
    }

    public function test_config_defaults_to_info_in_production(): void
    {
        putenv('LOG_LEVEL');

        $module = new LoggerModule();
        $config = $module->config(Environment::Production);

        $this->assertSame('info', $config['logger.log_level']);
    }

    public function test_config_defaults_to_info_in_staging(): void
    {
        putenv('LOG_LEVEL');

        $module = new LoggerModule();
        $config = $module->config(Environment::Staging);

        $this->assertSame('info', $config['logger.log_level']);
    }

    public function test_config_defaults_to_info_in_testing(): void
    {
        putenv('LOG_LEVEL');

        $module = new LoggerModule();
        $config = $module->config(Environment::Testing);

        $this->assertSame('info', $config['logger.log_level']);
    }

    public function test_env_var_takes_precedence_over_environment_in_development(): void
    {
        putenv('LOG_LEVEL=error');

        try {
            $module = new LoggerModule();
            $config = $module->config(Environment::Development);
            $this->assertSame('error', $config['logger.log_level']);
        } finally {
            putenv('LOG_LEVEL');
        }
    }
}
