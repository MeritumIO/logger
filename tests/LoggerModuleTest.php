<?php

namespace Meritum\Logger\Test;

use Georgeff\Kernel\Config\ConfigInterface;
use Georgeff\Kernel\Contract\EnvironmentInterface;
use Georgeff\Kernel\Environment\Development;
use Georgeff\Kernel\Environment\Local;
use Georgeff\Kernel\Environment\Production;
use Georgeff\Kernel\Environment\Staging;
use Georgeff\Kernel\Environment\Testing;
use Georgeff\Kernel\Kernel;
use Meritum\Logger\Logger;
use Meritum\Logger\LoggerModule;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;
use Psr\Log\LoggerInterface;

final class LoggerModuleTest extends TestCase
{
    protected function setUp(): void
    {
        putenv('LOG_LEVEL');
    }

    protected function tearDown(): void
    {
        putenv('LOG_LEVEL');
    }

    private function bootKernel(): Kernel
    {
        $kernel = new Kernel(new Testing());
        $kernel->addModule(new LoggerModule());
        $kernel->boot();

        return $kernel;
    }

    public function test_registers_logger_interface(): void
    {
        $logger = $this->bootKernel()->getContainer()->get(LoggerInterface::class);

        $this->assertInstanceOf(Logger::class, $logger);
    }

    public function test_logger_is_shared(): void
    {
        $container = $this->bootKernel()->getContainer();

        $this->assertSame($container->get(LoggerInterface::class), $container->get(LoggerInterface::class));
    }

    public function test_log_level_is_exposed_through_kernel_config(): void
    {
        putenv('LOG_LEVEL=warning');

        $config = $this->bootKernel()->getContainer()->get(ConfigInterface::class);

        $this->assertSame('warning', $config->get('logger.log_level'));
    }

    /**
     * @return array<string, array{EnvironmentInterface}>
     */
    public static function environments(): array
    {
        return [
            'local'       => [new Local()],
            'development' => [new Development()],
            'staging'     => [new Staging()],
            'testing'     => [new Testing()],
            'production'  => [new Production()],
        ];
    }

    #[DataProvider('environments')]
    public function test_config_defaults_to_info_in_every_environment(EnvironmentInterface $env): void
    {
        $config = (new LoggerModule())->config($env);

        $this->assertSame('info', $config['logger.log_level']);
    }

    #[DataProvider('environments')]
    public function test_config_uses_log_level_env_var_when_set(EnvironmentInterface $env): void
    {
        putenv('LOG_LEVEL=error');

        $config = (new LoggerModule())->config($env);

        $this->assertSame('error', $config['logger.log_level']);
    }

    /**
     * @return array<string, array{string, string}>
     */
    public static function nonStringLogLevels(): array
    {
        return [
            'bool'  => ['true', 'bool'],
            'null'  => ['null', 'null'],
            'array' => ['["debug"]', 'array'],
        ];
    }

    #[DataProvider('nonStringLogLevels')]
    public function test_config_throws_when_log_level_is_not_a_string(string $value, string $type): void
    {
        putenv("LOG_LEVEL={$value}");

        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage("The LOG_LEVEL environment variable must be a string, {$type} given");

        (new LoggerModule())->config(new Production());
    }

    public function test_non_string_log_level_fails_boot(): void
    {
        putenv('LOG_LEVEL=true');

        $this->expectException(\InvalidArgumentException::class);

        $this->bootKernel();
    }
}
