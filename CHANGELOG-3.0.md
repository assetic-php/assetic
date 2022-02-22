v3.0.0 (2022-02-22)
------------------

Assetic v3 is another refresh of the project in order to keep the core lean and
up to date with modern standards. More deprecated / abandoned filters have been
removed and replaced with modern maintained alternatives.

New filters are still recommended to be added in external packages and the core
is to be kept as minimal as possible.

### New features

* Support for PHP 8.0 & PHP 8.1
* Support for `symfony/process: v6.x`
* Added `Assetic\Filter\JavaScriptMinifierFilter`
* Added `Assetic\Filter\CSSMinFilter`
* Added `Assetic\Filter\StylesheetMinfyFilter`

### BC breaks

- Minimum PHP version required is now PHP 7.3
- Removed support for the unmaintained `mrclay/minify`, `patchwork/jsqueeze`, & `natxet/CssMin` packages in favour of `wikimedia/minify`.

# Filters Removed:
- `JSMinFilter` (use `JavaScriptMinifierFilter` instead)
- `JSMinPlusFilter` (use `JavaScriptMinifierFilter` instead)
- `JSqueezeFilter` (use `JavaScriptMinifierFilter` instead)
- `CssMinFilter` (use `CssImportFilter` combined with the `CSSMinFilter` instead)
    - Abandoned for all intents and purposes; maintainer has not interacted with the project for four years at least, not even able to maintain the package in the first place (https://github.com/natxet/CssMin/issues/23#issuecomment-257352700).
    - Several long standing unresolved bugs in relatively common CSS use cases
- `MinifyCssCompressorFilter` (use replacement for `CssMinFilter` above or use `StylesheetMinifyFilter` instead)
    - Abandoned, see: https://github.com/mrclay/minify/commit/0bc376980248f421c50b039beb869dd46cd9b043
    - Considered replacing with https://github.com/tubalmartin/YUI-CSS-compressor-PHP-port (last touched in 2018) but it is not fully supported in PHP 7.4: https://github.com/tubalmartin/YUI-CSS-compressor-PHP-port/issues/57 & is abandoned for all intents and purporses; maintainer has not interacted with the project for four years at least and maintainer seems to no longer be working with PHP in general.
