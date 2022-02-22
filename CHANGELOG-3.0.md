v3.0.0 (2022-02-28)
------------------

### Project update

Assetic v3 is another refresh of the project in order to keep the core lean and
up to date with modern standards. More deprecated / abandoned filters have been
removed and replaced with modern maintained alternatives.

New filters are still recommended to be added in external packages and the core
is to be kept as minimal as possible.

### New features

* Support for `>=php 7.2`
* Support for `symfony/process` `v3.4.x` | `v4.x` | `v5.x` | `v6.x`

### BC breaks

- Minimum PHP version required is now PHP 7.2.9
- Removed support for the unmaintained `mrclay/minify` and `patchwork/jsqueeze` packages in favour of `wikimedia/minify`.

# Filters Removed:
- JSMinFilter (use JavaScriptMinifierFilter instead)
- JSMinPlusFilter (use JavaScriptMinifierFilter instead)
- JSqueezeFilter (use JavaScriptMinifierFilter instead)
- MinifyCssCompressorFilter (https://github.com/mrclay/minify/commit/0bc376980248f421c50b039beb869dd46cd9b043, deprecated in dependency.)
    - Replacement options:
        - https://github.com/tubalmartin/YUI-CSS-compressor-PHP-port (last touched in 2018)
        - https://github.com/natxet/CssMin (last touched in 2018)
        - https://github.com/wikimedia/minify/blob/master/src/CSSMin.php (last touched in 2021, maintained by large company)
        - https://github.com/natxet/CssMin (already implemented, not a lot of confidence in it)
