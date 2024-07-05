# Changelog

## 3.1.1 (2024-07-05)

### Changes

- Conform codebase to PSR-12 code style guidelines
- Update wikimedia/less.php from 3.0 to 5.x for tests by @Krinkle in https://github.com/assetic-php/assetic/pull/38

### Bug fixes

- 8 character color hex codes (with alpha channel) are no longer minified to 3 character hex codes in `StylesheetFilter`, these are instead minified to 4 characters.
- The `MockAsset` asset has been switched to using contracts for typehints and return types.

### Community updates

- Combine and consistently format the changelog by @bennothommo in https://github.com/assetic-php/assetic/pull/39

## 3.1.0 (2024-04-04)

### New features

- Added support for TypeScript v5
- Added support for `symfony/console` v7

### Changes

- Re-added TwigFormulaLoader
- Skipped tests for Google Closure Compiler REST API filter, it has been deprecated by Google

## 3.0.2 (2022-07-07)

### Changes

- The Assetic Twig Extension is now compatible with Twig version 3

## 3.0.1 (2022-05-10)

### Bug fixes

- Fixed issue with StylesheetMinifyFilter where a universal selector (`*`) could not immediately follow a comment.

## 3.0.0 (2022-02-22)

Assetic v3 is another refresh of the project in order to keep the core lean and up to date with modern standards. More deprecated / abandoned filters have been removed and replaced with modern maintained alternatives.

New filters are still recommended to be added in external packages and the core is to be kept as minimal as possible.

### New features

- Support for PHP 8.0 & PHP 8.1
- Support for `symfony/process: v6.x`
- Added `Assetic\Filter\JavaScriptMinifierFilter`
- Added `Assetic\Filter\CSSMinFilter`
- Added `Assetic\Filter\StylesheetMinfyFilter`

### Backwards-compatibility breaks

- Minimum PHP version required is now PHP 7.3
- Removed support for the unmaintained `mrclay/minify`, `patchwork/jsqueeze`, & `natxet/CssMin` packages in favour of `wikimedia/minify`.

### Removed filters

- `JSMinFilter` (use `JavaScriptMinifierFilter` instead)
- `JSMinPlusFilter` (use `JavaScriptMinifierFilter` instead)
- `JSqueezeFilter` (use `JavaScriptMinifierFilter` instead)
- `CssMinFilter` (use `CssImportFilter` combined with the `CSSMinFilter` instead)
  - Abandoned for all intents and purposes; maintainer has not interacted with the project for four years at least, not even able to maintain the package in the first place (https://github.com/natxet/CssMin/issues/23#issuecomment-257352700).
  - Several long standing unresolved bugs in relatively common CSS use cases
- `MinifyCssCompressorFilter` (use replacement for `CssMinFilter` above or use `StylesheetMinifyFilter` instead)
  - Abandoned, see: https://github.com/mrclay/minify/commit/0bc376980248f421c50b039beb869dd46cd9b043
  - Considered replacing with https://github.com/tubalmartin/YUI-CSS-compressor-PHP-port (last touched in 2018) but it is not fully supported in PHP 7.4: https://github.com/tubalmartin/YUI-CSS-compressor-PHP-port/issues/57 & is abandoned for all intents and purporses; maintainer has not interacted with the project for four years at least and maintainer seems to no longer be working with PHP in general.

## 2.0.2 (2022-01-27)

### Bug fixes

- Fixes an issue with aliases when run with the Opcache Preloader from PHP 7.4+
- Fix usage of deprecated behaviours and methods in SCSS PHP filter
  - Adds additional argument for registerFunction method to define argument declarations
  - Uses compileString() method for compilation as opposed to deprecated compile() method
  - Uses addVariables() method for defining variables as opposed to deprecated setVariables() method
  - Parses variables provided in the setVariables() filter method and converts to Scss values, avoiding the deprecated behavior of allowing raw variables

## 2.0.1 (2021-07-01)

### Changes

- Added PHP 8 support
- Removed support for scss_compass, which was never actually finished in the first place

## 2.0.0 (2020-10-11)

### Project update

The project `kriswallsmith/assetic` is currently unmaintained, and has not been updated in 4 years. You can replace `kriswallsmith/assetic` by swapping it out for `"assetic/framework": "~2.0.0"` via composer.

This fork aims to bring Assetic up to date with modern libraries and allow for it to be used in conjunction with modern frameworks.

This update makes the project more opionated, removing duplicate filters in favour of purely PHP-based ones. New filters are to be added in external packages and the core is to be kept as minimal as possible.

As part of this update old, abandoned or redundant utilities have been removed. This is in part to simplify the offering of project as well as to migrate to a simplified tool set of php and javascript based utilities.

### New features

- Support for `>=php 7.2`
- Support for `symfony/process` `v3.4.x` | `v4.x` | `v5.x`

### Backwards-compatibility breaks

- Minimum PHP version required is now PHP 7.2
- Switching from `leafo/lessphp` to `wikimedia/less.php`
  - Due to this switch support has been dropped for `setPreserveComments` & `registerFunction` by the `LessphpFilter`.
- `twig/twig` support is optional now, `twig/extensions` must be required in your requirements if you need it.

### Removed filters

- apc cache (apc is no longer supported in php7.2)
- autoprefixer (autoprefixer cli is deprecated)
- cleancss (code is incompatible with the current API)
- CssEmbed
- Compass
- Dart
- ember precompile (the npm package no longer compiles)
- Gemfile (Ruby is no longer in use in the project)
- GSS
- Packager (throws deprecation notices in php7.4)
- pngout (npm package is abandoned)
- Roole (Roole was a language that compiles to CSS, the project is now dead and has been for at least 6 years, use LESS \ SCSS instead)
- SassFilter/ScssFilter/SassphpFilter (replaced by ScssphpFilter)
- Sprockets (Assetic no longer integrates with Ruby packages)
- UglifyJS version 1 (No longer supported. Use the `Assetic\Filter\UglifyJs2Filter` for version 2 or `UglifyJs3Filter` for version 3 instead)
- Yui compressors

## 1.2.0 (2014-10-14)

### New features

- Added the autoprefixer filter
- Added --no-header option for Coffeescript
- Implemented the extraction of dependencies for the compass filter
- Allow custom functions to be registered on the Lessphp filter
- Added MinifyCssCompressor filter based on `mrclay/minify`
- Added `setVariables` in the ScssPhpFilter
- Improved the support of the compress options for UglifyJS2
- Added CssCacheBustingFilter to apply cache busting on URLs in the CSS
- Added support for `--relative-assets` option for the compass filter

### Bug fixes

- Allow functions.php to be included many times
- Updated the ScssPhpFilter for upstream class renaming

## 1.2.0-alpha1 (2014-07-08)

### BC breaks

- Added `AssetFactory` instance as second argument for `WorkerInterface::process()`
- Removed `LazyAssetManager` from `CacheBustingWorker` constructor
- A new `getSourceDirectory()` method was added on the AssetInterface
- Removed limit and count arguments from CssUtils functions
- Removed the deprecated `PathUtils` class

### New features

- added `CssUtils::filterCommentless()`
- Added `DependencyExtractorInterface` for filters to report other files they import
- Added the support of nib in the stylus filter
- Added `registerFunction` and `setFormatter` to the scssphp filter
- Added the support of flag file for the ClosureCompilerJarFilter
- Added the JsSqueeze filter
- Added support of the define option for uglifyjs (1 & 2) filters
- Added logging for Twig errors in the extractor

### Bug fixes

- Fixed the detection of protocol-relative CSS imports
- Updated AssetCollection to not add several time the same variable in path
- Fixed the merging of the environment variables to keep the configuration of the NODE_PATH working
- Fixed the support of ``{% embed %}`` in the Twig extractor
- Fixed the support of asset variables in GlobAsset

## 1.1.2 (2013-07-18)

- Fixed deep mtime on asset collections
- `CallablesFilter` now implements `DependencyExtractorInterface`
- Fixed detection of "partial" children in subfolders in `SassFilter`
- Restored `PathUtils` for BC

## 1.1.1 (2013-06-01)

- Fixed cloning of asset collections
- Fixed environment var inheritance
- Replaced `AssetWriter::getCombinations()` for BC, even though we don't use it
- Added support for `@import-once` to Less filters

## 1.1.0 (2013-05-15)

- Added LazyAssetManager::getLastModified() for determining "deep" mtime
- Added DartFilter
- Added EmberPrecompile
- Added GssFilter
- Added PhpCssEmbedFilter
- Added RooleFilter
- Added TypeScriptFilter
- Added the possibility to configure additional load paths for less and lessphp
- Added the UglifyCssFilter
- Fixed the handling of directories in the GlobAsset. #256
- Added Handlebars support
- Added Scssphp-compass support
- Added the CacheBustingWorker
- Added the UglifyJs2Filter

## 1.1.0-alpha1 (2012-08-28)

- Added pure php css embed filter
- Added Scssphp support
- Added support for Google Closure language option
- Added a way to set a specific ruby path for CompassFilter and SassFilter
- Ensure uniqueness of temporary files created by the compressor filter. Fixed #61
- Added Compass option for generated_images_path (for generated Images/Sprites)
- Added PackerFilter
- Add the way to contact closure compiler API using curl, if available and allow_url_fopen is off
- Added filters for JSMin and JSMinPlus
- Added the UglifyJsFilter
- Improved the error message in getModifiedTime when a file asset uses an invalid file
- Added support for asset variables:
    > Asset variables allow you to pre-compile your assets for a finite set of known
    > variable values, and then to simply deliver the correct asset version at runtime.
    > For example, this is helpful for assets with language, or browser-specific code.
- Removed the copy-paste of the Symfony2 Process component and use the original one
- Added ability to pass variables into lessphp filter
- Added google closure stylesheets jar filter
- Added the support of `--bare` for the CoffeeScriptFilter

## 1.0.4 (2012-08-28)

- Fixed the Twig tag to avoid a fatal error when left unclosed
- Added the HashableInterface for non-serialiable filters
- Fixed a bug for compass on windows

## 1.0.3 (2012-03-02)

- Added "boring" option to Compass filter
- Fixed accumulation of load paths in Compass filter
- Fixed issues in CssImport and CssRewrite filters

## 1.0.2 (2011-08-26)

- Twig 1.2 compatibility
- Fixed filtering of large LessCSS assets
- Fixed escaping of commands on Windows
- Misc fixes to Compass filter
- Removed default CssEmbed charset

## 1.0.1 (2011-07-15)

- Fixed Twig error handling
- Removed use of STDIN
- Added inheritance of environment variables
- Fixed Compass on Windows
- Improved escaping of commands

## 1.0.0 (2011-07-10)

- Initial release
