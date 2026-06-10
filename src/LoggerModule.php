<?php

namespace Meritum\Logger;

use Psr\Log\LoggerInterface;
use Georgeff\Kernel\Environment;
use Georgeff\Kernel\KernelInterface;
use Georgeff\Kernel\Module\ConfigurableModuleInterface;

final class LoggerModule implements ConfigurableModuleInterface
{
    public function register(KernelInterface $kernel): void
    {
        $kernel->define(LoggerInterface::class, new LoggerFactory())->share();
    }

    public function config(Environment $env): array
    {
        return [
            'logger.log_level' => $this->getLogLevel($env),
        ];
    }

    private function getLogLevel(Environment $env): string
    {
        $level = getenv('LOG_LEVEL');

        if (false !== $level) {
            return $level;
        }

        return $env === Environment::Development ? 'debug' : 'info';
    }
}
