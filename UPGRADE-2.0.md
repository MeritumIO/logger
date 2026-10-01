# Upgrading from 1.x to 2.0

2.0 migrates `meritum/logger` onto `georgeff/kernel` ^2.0. The `Logger` class itself (constructor, PSR-3 methods, JSON output, severity mapping) is unchanged; standalone use needs no changes. What affects you here is `LoggerModule` and the kernel upgrade. **Read [`georgeff/kernel`'s own `UPGRADE-2.0.md`](https://github.com/MikeGeorgeff/kernel/blob/main/UPGRADE-2.0.md) first**; this guide only covers what's specific to `meritum/logger`.

See `CHANGELOG.md` for the full list of changes.

## Requirements

- [ ] **`georgeff/kernel` ^2.0.** `composer.json` now requires `"georgeff/kernel": "^2.0"`. `LoggerModule` implements kernel 2.0's `Contract\ConfigurableModuleInterface`, so it can't be added to a 1.x kernel.

## 1. Development no longer defaults to `debug`

When `LOG_LEVEL` isn't set, the minimum level is now `info` in every environment. In 1.x, `Environment::Development` defaulted to `debug`.

- [ ] If you relied on debug output in development without setting `LOG_LEVEL`, set it explicitly in that environment:

  ```bash
  LOG_LEVEL=debug
  ```

- [ ] Production, staging and testing already defaulted to `info`, so no change is needed there.

## 2. An invalid `LOG_LEVEL` fails earlier

`LOG_LEVEL` is now read through the kernel's `Support\Env` helper, which converts some values to native types. If the result isn't a string, `LoggerModule` throws `\InvalidArgumentException` at boot.

- [ ] No change is needed if `LOG_LEVEL` is already a PSR-3 level name (`debug`, `info`, `notice`, `warning`, `error`, `critical`, `alert`, `emergency`). Values like `true`, `false`, `null`, or JSON such as `["debug"]` were never valid levels. In 1.x they failed when the logger was first resolved; now they fail at boot, with a message naming `LOG_LEVEL`.

## 3. Replacing the `LoggerInterface` binding needs `override()`

Kernel 2.0's `define()` throws `DefinitionException` when an id is already defined. In 1.x, you replaced the logger by defining `LoggerInterface` again in a module registered after `LoggerModule`; that now fails at boot.

- [ ] Switch to `override()`. Overrides are applied after every module has registered, so registration order no longer matters:

  ```php
  // Before
  $kernel->define(LoggerInterface::class, fn() => new MyCustomLogger())->share();

  // After
  $kernel->override(LoggerInterface::class, fn() => new MyCustomLogger())->share();
  ```

- [ ] If you only need to wrap the logger rather than replace it (as `meritum/structured-logging` does), `decorate()` still works unchanged.

## Verifying the upgrade

- [ ] `composer test` — full suite passes
- [ ] `composer analyze` — PHPStan clean at `level: max`
- [ ] Grep your own codebase for `define(LoggerInterface::class` — any match needs section 3.
- [ ] Check every environment's `LOG_LEVEL` setting, especially development (sections 1 and 2).
- [ ] Also run through [`georgeff/kernel`'s own verification checklist](https://github.com/MikeGeorgeff/kernel/blob/main/UPGRADE-2.0.md#verifying-the-upgrade) for base-kernel-level changes.
