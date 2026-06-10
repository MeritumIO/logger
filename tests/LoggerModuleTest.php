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
        return new class($registeredId, $registeredFactory) implements KernelInterface {
            public function __construct(
                private string &$registeredId,
                private mixed &$registeredFactory,
            ) {}

            public function define(string $id, callable $factory): DefinitionInterface
            {
                $this->registeredId      = $id;
                $this->registeredFactory = $factory;

                return new class implements DefinitionInterface {
                    public static function for(string $id, callable $factory): static { return new static(); }
                    public function share(): static { return $this; }
                    public function alias(string $alias): static { return $this; }
                    public function tag(string $tag): static { return $this; }
                    public function getId(): string { return ''; }
                    public function getFactory(): callable { return fn() => null; }
                    public function isShared(): bool { return false; }
                    public function getAliases(): array { return []; }
                    public function getTags(): array { return []; }
                };
            }

            public function boot(): void {}
            public function shutdown(): void {}
            public function isBooting(): bool { return false; }
            public function isBooted(): bool { return false; }
            public function isShutdown(): bool { return false; }
            public function getEnvironment(): string { return ''; }
            public function isDebug(): bool { return false; }
            public function onBooting(callable $callback): static { return $this; }
            public function onBooted(callable $callback): static { return $this; }
            public function onShutdown(callable $callback): static { return $this; }
            public function afterShutdown(callable $callback): static { return $this; }
            public function addDefinition(string $id, callable $factory, bool $shared = false, array $aliases = [], array $tags = []): static { return $this; }
            public function tag(string $id, array $tags): static { return $this; }
            public function decorate(string $id, callable $decorator): static { return $this; }
            public function addModule(\Georgeff\Kernel\Module\ModuleInterface $module): static { return $this; }
            public function addRepository(\Georgeff\Kernel\Module\ModuleRepositoryInterface $repository): static { return $this; }
            public function getContainer(): \Psr\Container\ContainerInterface { throw new \RuntimeException('not implemented'); }
            public function getStartTime(): float { return 0.0; }
        };
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
