<?php

namespace Meritum\Logger\Test;

use Meritum\Logger\Logger;
use PHPUnit\Framework\TestCase;
use Stringable;

final class LoggerTest extends TestCase
{
    private function makeStream(): mixed
    {
        return fopen('php://memory', 'r+');
    }

    private function readStream(mixed $stream): string
    {
        rewind($stream);
        return stream_get_contents($stream);
    }

    private function decodeEntry(string $output, int $line = 0): array
    {
        $lines = array_values(array_filter(explode(PHP_EOL, $output)));
        return json_decode($lines[$line], true);
    }

    // Constructor

    public function test_constructor_throws_on_non_resource(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        new Logger('not a resource');
    }

    public function test_constructor_throws_on_invalid_log_level(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('Minimum log level [verbose] is not valid');
        new Logger($this->makeStream(), 'verbose');
    }

    public function test_constructor_accepts_case_insensitive_level(): void
    {
        $logger = new Logger($this->makeStream(), 'WARNING');
        $this->assertInstanceOf(Logger::class, $logger);
    }

    public function test_constructor_defaults_to_debug_level(): void
    {
        $stream = $this->makeStream();
        $logger = new Logger($stream);

        $logger->debug('test');

        $this->assertNotEmpty($this->readStream($stream));
    }

    // log() validation

    public function test_log_throws_on_non_string_level(): void
    {
        $logger = new Logger($this->makeStream());

        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('Log level must be a string');
        $logger->log(42, 'message');
    }

    public function test_log_throws_on_invalid_level(): void
    {
        $logger = new Logger($this->makeStream());

        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('Log level [verbose] is not valid');
        $logger->log('verbose', 'message');
    }

    public function test_log_accepts_case_insensitive_level(): void
    {
        $stream = $this->makeStream();
        $logger = new Logger($stream);

        $logger->log('INFO', 'message');

        $entry = $this->decodeEntry($this->readStream($stream));
        $this->assertSame('info', $entry['level']);
    }

    public function test_log_accepts_stringable_message(): void
    {
        $stream = $this->makeStream();
        $logger = new Logger($stream);

        $message = new class implements Stringable {
            public function __toString(): string { return 'stringable message'; }
        };

        $logger->info($message);

        $entry = $this->decodeEntry($this->readStream($stream));
        $this->assertSame('stringable message', $entry['message']);
    }

    // JSON output structure

    public function test_log_entry_contains_required_fields(): void
    {
        $stream = $this->makeStream();
        $logger = new Logger($stream);

        $logger->info('hello');

        $entry = $this->decodeEntry($this->readStream($stream));
        $this->assertArrayHasKey('timestamp', $entry);
        $this->assertArrayHasKey('level', $entry);
        $this->assertArrayHasKey('severity', $entry);
        $this->assertArrayHasKey('message', $entry);
        $this->assertArrayHasKey('context', $entry);
    }

    public function test_log_entry_has_correct_level_and_message(): void
    {
        $stream = $this->makeStream();
        $logger = new Logger($stream);

        $logger->error('something broke');

        $entry = $this->decodeEntry($this->readStream($stream));
        $this->assertSame('error', $entry['level']);
        $this->assertSame('something broke', $entry['message']);
    }

    public function test_log_entry_includes_context(): void
    {
        $stream = $this->makeStream();
        $logger = new Logger($stream);

        $logger->info('with context', ['user_id' => 42]);

        $entry = $this->decodeEntry($this->readStream($stream));
        $this->assertSame(['user_id' => 42], $entry['context']);
    }

    public function test_log_entry_always_includes_context_when_empty(): void
    {
        $stream = $this->makeStream();
        $logger = new Logger($stream);

        $logger->info('no context');

        $entry = $this->decodeEntry($this->readStream($stream));
        $this->assertSame([], $entry['context']);
    }

    public function test_log_entry_timestamp_is_rfc3339_extended(): void
    {
        $stream = $this->makeStream();
        $logger = new Logger($stream);

        $logger->info('ts check');

        $entry = $this->decodeEntry($this->readStream($stream));
        $this->assertMatchesRegularExpression(
            '/^\d{4}-\d{2}-\d{2}T\d{2}:\d{2}:\d{2}\.\d+[+-]\d{2}:\d{2}$/',
            $entry['timestamp']
        );
    }

    public function test_each_entry_is_on_its_own_line(): void
    {
        $stream = $this->makeStream();
        $logger = new Logger($stream);

        $logger->info('first');
        $logger->info('second');

        $output = $this->readStream($stream);
        $lines  = array_filter(explode(PHP_EOL, $output));
        $this->assertCount(2, $lines);
    }

    // Severity mapping

    public function test_severity_map_emergency(): void
    {
        $this->assertSeverity('emergency', 'critical');
    }

    public function test_severity_map_alert(): void
    {
        $this->assertSeverity('alert', 'critical');
    }

    public function test_severity_map_critical(): void
    {
        $this->assertSeverity('critical', 'critical');
    }

    public function test_severity_map_error(): void
    {
        $this->assertSeverity('error', 'error');
    }

    public function test_severity_map_warning(): void
    {
        $this->assertSeverity('warning', 'warning');
    }

    public function test_severity_map_notice(): void
    {
        $this->assertSeverity('notice', 'info');
    }

    public function test_severity_map_info(): void
    {
        $this->assertSeverity('info', 'info');
    }

    public function test_severity_map_debug(): void
    {
        $this->assertSeverity('debug', 'debug');
    }

    private function assertSeverity(string $level, string $expectedSeverity): void
    {
        $stream = $this->makeStream();
        $logger = new Logger($stream);

        $logger->log($level, 'test');

        $entry = $this->decodeEntry($this->readStream($stream));
        $this->assertSame($expectedSeverity, $entry['severity']);
    }

    // Min level filtering

    public function test_logs_at_minimum_level(): void
    {
        $stream = $this->makeStream();
        $logger = new Logger($stream, 'warning');

        $logger->warning('at min');

        $this->assertNotEmpty($this->readStream($stream));
    }

    public function test_logs_above_minimum_level(): void
    {
        $stream = $this->makeStream();
        $logger = new Logger($stream, 'warning');

        $logger->error('above min');

        $this->assertNotEmpty($this->readStream($stream));
    }

    public function test_suppresses_below_minimum_level(): void
    {
        $stream = $this->makeStream();
        $logger = new Logger($stream, 'warning');

        $logger->info('below min');
        $logger->debug('below min');
        $logger->notice('below min');

        $this->assertEmpty($this->readStream($stream));
    }
}
