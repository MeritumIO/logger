# Changelog

All notable changes to `meritum/logger` are documented here.

---

## [2.0.0] — 2026-09-30

2.0 migrates to `georgeff/kernel` ^2.0.

### Changed
- **Breaking:** migrated to `georgeff/kernel` ^2.0 — `LoggerModule` now implements `Georgeff\Kernel\Contract\ConfigurableModuleInterface`, so it can only be added to a 2.0 kernel
- **Breaking:** the default minimum log level is now `info` in every environment when `LOG_LEVEL` isn't set. In 1.x, `Environment::Development` defaulted to `debug`; that was inconsistent (`Environment::Local`, added later, never matched it) and depended on the environment rather than on configuration. Set `LOG_LEVEL=debug` explicitly to keep debug output in development
- `LOG_LEVEL` is now read through `Georgeff\Kernel\Support\Env`. Values that `Env` converts to a non-string (`true`/`false`/`null` and their variants, or JSON objects/arrays) throw `\InvalidArgumentException` at boot with a message naming `LOG_LEVEL`. In 1.x the same values reached `Logger` as raw strings and failed later, as an invalid level, when the logger was first resolved
- `LoggerFactory` reads the log level from `Config\ConfigInterface::class` instead of the removed `'kernel.config'` container array
- Replacing the `LoggerInterface` binding now requires `override()`: kernel 2.0's `define()` throws `DefinitionException` for an id that's already defined, so defining `LoggerInterface` in a module registered after `LoggerModule` (the 1.x approach) no longer works
