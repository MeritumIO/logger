# meritum/logger

[![CI](https://github.com/MeritumIO/logger/actions/workflows/ci.yml/badge.svg)](https://github.com/MeritumIO/logger/actions/workflows/ci.yml)
[![Coverage Status](https://coveralls.io/repos/github/MeritumIO/logger/badge.svg?branch=main)](https://coveralls.io/github/MeritumIO/logger?branch=main)
[![Packagist Version](https://img.shields.io/packagist/v/meritum/logger)](https://packagist.org/packages/meritum/logger)

Minimal PSR-3 logger that writes newline-delimited JSON to stdout. Designed for containerized environments where structured log output is consumed by a log aggregator (GCP Cloud Logging, AWS CloudWatch, Datadog, etc.).

## Installation

```bash
composer require meritum/logger
```

## Requirements

- PHP 8.4+
- [`georgeff/kernel`](https://github.com/MikeGeorgeff/kernel) ^2.0

## Usage

### Standalone

Instantiate `Logger` directly with a writable resource and an optional minimum log level:

```php
use Meritum\Logger\Logger;

$logger = new Logger(STDOUT);
$logger->info('Application started');
$logger->error('Something went wrong', ['exception' => 'RuntimeException']);
```

The second parameter sets the minimum log level. Messages below the minimum are silently discarded:

```php
$logger = new Logger(STDOUT, 'warning');

$logger->debug('ignored');   // suppressed
$logger->info('ignored');    // suppressed
$logger->warning('logged');  // written
$logger->error('logged');    // written
```

The default minimum level is `debug`, which passes all messages through.

### With the Kernel

Add `LoggerModule` to your kernel to register `Psr\Log\LoggerInterface` as a shared service:

```php
use Georgeff\Kernel\Kernel;
use Georgeff\Kernel\Environment\Production;
use Meritum\Logger\LoggerModule;
use Psr\Log\LoggerInterface;

$kernel = new Kernel(new Production());
$kernel->addModule(new LoggerModule());
$kernel->boot();

$logger = $kernel->getContainer()->get(LoggerInterface::class);
```

The minimum log level is read from the `LOG_LEVEL` environment variable, and defaults to `info` in every environment when it isn't set. To get debug output locally, set `LOG_LEVEL=debug`.

`LOG_LEVEL` is read through the kernel's `Env` helper, which converts `true`/`false`/`null` and JSON values to native types. Anything that doesn't come out as a string throws `InvalidArgumentException` at boot. A string that isn't a valid PSR-3 level throws `InvalidArgumentException` when the logger is first resolved.

### Overriding the binding

If you need a different logger implementation — a file-based logger, a test double, or a third-party PSR-3 library — replace the binding with `override()`. Overrides are applied after every module has registered, so it works from the bootstrap or any module, regardless of order. Calling `define(LoggerInterface::class, ...)` instead throws a `DefinitionException`, since `LoggerModule` already defines it:

```php
use Psr\Log\LoggerInterface;

$kernel->addModule(new LoggerModule());
$kernel->override(LoggerInterface::class, fn() => new MyCustomLogger())->share();
```

## Log output

Each message is written as a single JSON object followed by a newline. The envelope always contains these fields:

| Field | Type | Description |
|---|---|---|
| `timestamp` | string | RFC 3339 extended (e.g. `2026-06-10T14:32:01.123+00:00`) |
| `level` | string | PSR-3 level name (`debug`, `info`, `notice`, `warning`, `error`, `critical`, `alert`, `emergency`) |
| `severity` | string | Reduced 4-value severity for structured log sinks (`debug`, `info`, `warning`, `critical`) |
| `message` | string | The log message |
| `context` | object | Contextual data; always present, empty object when no context is provided |

Example output:

```json
{"timestamp":"2026-06-10T14:32:01.123+00:00","level":"error","severity":"error","message":"Database connection failed","context":{"host":"db.internal","port":5432}}
```

### Severity mapping

PSR-3 defines 8 log levels. The `severity` field maps these to a reduced set aligned with common structured log sinks:

| PSR-3 level | Severity |
|---|---|
| `emergency` | `critical` |
| `alert` | `critical` |
| `critical` | `critical` |
| `error` | `error` |
| `warning` | `warning` |
| `notice` | `info` |
| `info` | `info` |
| `debug` | `debug` |

The `level` field always carries the original PSR-3 value, so no information is lost.

## Log levels

Valid levels in ascending severity order:

`debug` → `info` → `notice` → `warning` → `error` → `critical` → `alert` → `emergency`

An invalid level passed to either the constructor or `log()` throws `\InvalidArgumentException`.

## License

MIT — see [LICENSE](LICENSE).
