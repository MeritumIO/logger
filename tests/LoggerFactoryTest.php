<?php

namespace Meritum\Logger\Test;

use Meritum\Logger\Logger;
use Meritum\Logger\LoggerFactory;
use PHPUnit\Framework\TestCase;
use Psr\Container\ContainerInterface;
use Psr\Log\LoggerInterface;

final class LoggerFactoryTest extends TestCase
{
    private function makeContainer(array $config): ContainerInterface
    {
        return new class($config) implements ContainerInterface {
            public function __construct(private readonly array $config) {}

            public function get(string $id): mixed
            {
                return match ($id) {
                    'kernel.config' => $this->config,
                    default         => throw new \RuntimeException("Service not found: {$id}"),
                };
            }

            public function has(string $id): bool
            {
                return $id === 'kernel.config';
            }
        };
    }

    public function test_returns_logger_interface(): void
    {
        $factory = new LoggerFactory();

        $this->assertInstanceOf(LoggerInterface::class, $factory($this->makeContainer(['logger.log_level' => 'info'])));
    }

    public function test_returns_logger_instance(): void
    {
        $factory = new LoggerFactory();

        $this->assertInstanceOf(Logger::class, $factory($this->makeContainer(['logger.log_level' => 'info'])));
    }

    public function test_accepts_valid_log_level_from_config(): void
    {
        $factory = new LoggerFactory();

        foreach (['debug', 'info', 'notice', 'warning', 'error', 'critical', 'alert', 'emergency'] as $level) {
            $logger = $factory($this->makeContainer(['logger.log_level' => $level]));
            $this->assertInstanceOf(Logger::class, $logger);
        }
    }

    public function test_defaults_to_info_when_log_level_not_in_config(): void
    {
        $factory = new LoggerFactory();

        $this->assertInstanceOf(Logger::class, $factory($this->makeContainer([])));
    }

    public function test_throws_on_invalid_log_level_in_config(): void
    {
        $factory = new LoggerFactory();

        $this->expectException(\InvalidArgumentException::class);
        $factory($this->makeContainer(['logger.log_level' => 'verbose']));
    }
}
