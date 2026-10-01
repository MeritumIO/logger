<?php

namespace Meritum\Logger\Test;

use Georgeff\Kernel\Config\ConfigInterface;
use Meritum\Logger\Logger;
use Meritum\Logger\LoggerFactory;
use PHPUnit\Framework\TestCase;
use Psr\Container\ContainerInterface;
use Psr\Log\LoggerInterface;

final class LoggerFactoryTest extends TestCase
{
    /**
     * @param array<string, mixed> $config
     */
    private function makeContainer(array $config): ContainerInterface
    {
        $config = new class($config) implements ConfigInterface {
            /**
             * @param array<string, mixed> $config
             */
            public function __construct(private readonly array $config) {}

            public function all(): array { return $this->config; }
            public function isEmpty(): bool { return [] === $this->config; }
            public function has(string $name): bool { return array_key_exists($name, $this->config); }
            public function get(string $name, mixed $default = null): mixed { return $this->has($name) ? $this->config[$name] : $default; }
            public function branch(string $name): ConfigInterface { throw new \RuntimeException('not implemented'); }
        };

        return new class($config) implements ContainerInterface {
            public function __construct(private readonly ConfigInterface $config) {}

            public function get(string $id): mixed
            {
                return match ($id) {
                    ConfigInterface::class => $this->config,
                    default                => throw new \RuntimeException("Service not found: {$id}"),
                };
            }

            public function has(string $id): bool
            {
                return $id === ConfigInterface::class;
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
