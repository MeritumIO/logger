<?php

namespace Meritum\Logger;

use Psr\Log\LoggerInterface;
use Georgeff\Kernel\Support\Env;
use Georgeff\Kernel\KernelInterface;
use Georgeff\Kernel\Contract\EnvironmentInterface;
use Georgeff\Kernel\Contract\ConfigurableModuleInterface;

final class LoggerModule implements ConfigurableModuleInterface
{
    public function register(KernelInterface $kernel): void
    {
        $kernel->define(LoggerInterface::class, new LoggerFactory())->share();
    }

    public function config(EnvironmentInterface $env): array
    {
        $level = Env::get('LOG_LEVEL', 'info');

        if (! is_string($level)) {
            throw new \InvalidArgumentException(sprintf(
                'The LOG_LEVEL environment variable must be a string, %s given',
                get_debug_type($level)
            ));
        }

        return [
            'logger.log_level' => $level,
        ];
    }
}
