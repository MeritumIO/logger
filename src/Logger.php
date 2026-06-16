<?php

namespace Meritum\Logger;

use Stringable;
use Psr\Log\LoggerTrait;
use Psr\Log\LoggerInterface;

final class Logger implements LoggerInterface
{
    use LoggerTrait;

    /**
     * Ordered severity-ascending — shouldLog() relies on index position for level comparison
     */
    private const array LOG_LEVELS = ['debug', 'info', 'notice', 'warning', 'error', 'critical', 'alert', 'emergency'];

    /**
     * Reduces PSR-3's 8 levels to a 5-value severity scheme for structured log sinks
     * The original PSR-3 level is always preserved in the 'level' field of the log entry
     */
    private const array SEVERITY_MAP = [
        'emergency' => 'critical',
        'alert'     => 'critical',
        'critical'  => 'critical',
        'error'     => 'error',
        'warning'   => 'warning',
        'notice'    => 'info',
        'info'      => 'info',
        'debug'     => 'debug',
    ];

    /**
     * @var resource
     */
    private $output;

    /**
     * Minimum log level
     */
    private string $logLevel;

    /**
     * @param resource $output
     */
    public function __construct(mixed $output, string $logLevel = 'debug')
    {
        if (!is_resource($output)) {
            throw new \InvalidArgumentException('Output parameter must be a valid resource');
        }

        $logLevel = strtolower($logLevel);

        if (!$this->isValidLevel($logLevel)) {
            throw new \InvalidArgumentException(sprintf(
                'Minimum log level [%s] is not valid. Valid levels: %s',
                $logLevel,
                implode(', ', self::LOG_LEVELS)
            ));
        }

        $this->output   = $output;
        $this->logLevel = $logLevel;
    }

    public function log($level, string|Stringable $message, array $context = []): void
    {
        if (!is_string($level) && !$level instanceof Stringable) {
            throw new \InvalidArgumentException('Log level must be a string');
        }

        $level = strtolower((string) $level);

        if (!$this->isValidLevel($level)) {
            throw new \InvalidArgumentException(sprintf(
                'Log level [%s] is not valid. Valid levels: %s',
                $level,
                implode(', ', self::LOG_LEVELS)
            ));
        }

        $this->writeLog($level, $message, $context);
    }

    /**
     * @param mixed[] $context
     */
    private function writeLog(string $level, Stringable|string $message, array $context = []): void
    {
        if (!$this->shouldLog($level)) {
            return;
        }

        $logEntry = [
            'timestamp' => new \DateTimeImmutable()->format(\DateTimeInterface::RFC3339_EXTENDED),
            'level'     => $level,
            'severity'  => self::SEVERITY_MAP[$level],
            'message'   => (string) $message,
            'context'   => $context,
        ];

        $json = json_encode($logEntry, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE | JSON_THROW_ON_ERROR);

        fwrite($this->output, $json . PHP_EOL);
    }

    private function shouldLog(string $level): bool
    {
        $minIndex     = array_search($this->logLevel, self::LOG_LEVELS, true);
        $currentIndex = array_search($level, self::LOG_LEVELS, true);

        return $currentIndex >= $minIndex;
    }

    private function isValidLevel(string $level): bool
    {
        return in_array($level, self::LOG_LEVELS, true);
    }
}
