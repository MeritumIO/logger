# meritum/logger

Minimal PSR-3 logger that writes newline-delimited JSON to stdout. Designed for containerized environments where structured log output is consumed by a log aggregator (GCP Cloud Logging, AWS CloudWatch, Datadog, etc.).

## Installation

```bash
composer require meritum/logger
```

## Requirements

- PHP 8.4+

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
use Meritum\Logger\LoggerModule;

$kernel = new Kernel(Environment::Production);
$kernel->addModule(new LoggerModule());
$kernel->boot();

$logger = $kernel->getContainer()->get(LoggerInterface::class);
```

The minimum log level is resolved in this order:

1. `LOG_LEVEL` environment variable, if set
2. `debug` in `Environment::Development`
3. `info` in all other environments

### Overriding the binding

If you need a different logger implementation — a file-based logger, a test double, or a third-party PSR-3 library — define `LoggerInterface` in a module registered after `LoggerModule`:

```php
use Psr\Log\LoggerInterface;
use Psr\Container\ContainerInterface;

$kernel->addModule(new LoggerModule());
$kernel->addModule(new class implements ModuleInterface {
    public function register(KernelInterface $kernel): void
    {
        $kernel->define(LoggerInterface::class, function (ContainerInterface $c): LoggerInterface {
            return new MyCustomLogger();
        })->share();
    }
});
```

The last definition wins, so `LoggerModule` does not need to be removed.

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
