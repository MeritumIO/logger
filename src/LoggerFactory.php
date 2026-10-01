<?php

namespace Meritum\Logger;

use Psr\Log\LoggerInterface;
use Psr\Container\ContainerInterface;
use Georgeff\Kernel\Config\ConfigInterface;

final class LoggerFactory
{
    public function __invoke(ContainerInterface $container): LoggerInterface
    {
        /** @var ConfigInterface */
        $config = $container->get(ConfigInterface::class);

        /** @var string $level **/
        $level  = $config->get('logger.log_level', 'info');

        $resource = fopen('php://stdout', 'w');

        assert(false !== $resource);

        return new Logger($resource, $level);
    }
}
