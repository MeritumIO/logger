<?php

namespace Meritum\Logger;

use Psr\Log\LoggerInterface;
use Psr\Container\ContainerInterface;

final class LoggerFactory
{
    public function __invoke(ContainerInterface $container): LoggerInterface
    {
        /** @var array<string, mixed> $config */
        $config = $container->get('kernel.config');

        /** @var string $level **/
        $level  = $config['logger.log_level'] ?? 'info';

        return new Logger(\STDOUT, $level);
    }
}
