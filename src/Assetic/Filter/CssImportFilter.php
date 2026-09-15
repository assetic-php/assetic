<?php

namespace Assetic\Filter;

use Assetic\Contracts\Asset\AssetInterface;
use Assetic\Asset\FileAsset;
use Assetic\Asset\HttpAsset;
use Assetic\Factory\AssetFactory;
use Assetic\Contracts\Filter\DependencyExtractorInterface;
use Assetic\Contracts\Filter\FilterInterface;
use Assetic\Contracts\Filter\HashableInterface;
use Closure;
use ReflectionFunction;
use Throwable;

/**
 * Inlines imported stylesheets.
 *
 * @author Kris Wallsmith <kris.wallsmith@gmail.com>
 */
class CssImportFilter extends BaseCssFilter implements DependencyExtractorInterface, HashableInterface
{
    private $importFilter;

    /** @var callable|null */
    private $importValidator;

    /**
     * Constructor.
     *
     * @param ?FilterInterface $importFilter Filter for each imported asset
     */
    public function __construct(?FilterInterface $importFilter = null)
    {
        $this->importFilter = $importFilter ?: new CssRewriteFilter();
    }

    /**
     * Set an optional validator that authorises each import before it is inlined.
     * The validator receives the import source — a filesystem path assembled from
     * the asset's source root and the `@import` URL, or the URL itself when the
     * `@import` carries a scheme or is protocol-relative — and must return true to
     * allow the import or false to skip it, leaving the raw `@import` statement
     * untouched.
     *
     * This is an opt-in confinement hook for consumers that inline imports from
     * stylesheets they do not control. It applies to every import form the filter
     * handles: local paths resolved relative to the source, and scheme-bearing or
     * protocol-relative targets, which are loaded through the same `file_get_contents()`
     * path. Defaults to null (no restriction) so existing behaviour is unchanged for
     * callers that do not set it.
     */
    public function setImportValidator(?callable $importValidator): self
    {
        $this->importValidator = $importValidator;

        return $this;
    }

    /**
     * Generates a hash for the object.
     *
     * {@see \Assetic\Asset\AssetCache} builds its cache key from the filters applied
     * to an asset, falling back to `serialize()` for any filter that is not hashable.
     * An import validator is typically a closure, and serializing a closure throws,
     * so this filter must hash itself rather than be serialized.
     *
     * @return string Object hash
     */
    public function hash()
    {
        return md5(implode('|', [
            static::class,
            $this->hashComponent($this->importFilter),
            $this->hashComponent($this->importValidator),
        ]));
    }

    /**
     * Reduces a filter or callable held by this filter to a stable string.
     *
     * Serialization is preferred, since it captures the component's configuration,
     * but it fails for closures and for objects holding one. Object identity is
     * never used as a fallback: `spl_object_hash()` and friends are not stable
     * between requests and would give the asset cache a new key every time,
     * defeating it.
     *
     * @param mixed $component
     * @return string
     */
    private function hashComponent($component)
    {
        if (null === $component) {
            return '';
        }

        if ($component instanceof HashableInterface) {
            return $component->hash();
        }

        if ($component instanceof Closure) {
            return $this->hashClosure($component);
        }

        if (is_array($component) && isset($component[0]) && is_object($component[0])) {
            // Callable array of [object, method]
            return get_class($component[0]) . '::' . (string) ($component[1] ?? '');
        }

        try {
            return serialize($component);
        } catch (Throwable $e) {
            return is_object($component) ? get_class($component) : gettype($component);
        }
    }

    /**
     * Reduces a closure to a stable string.
     *
     * A closure cannot be serialized, so it is identified by where it was declared
     * plus the variables bound into it. The bound variables carry the closure's
     * configuration — for an import validator, the set of paths it authorises — so
     * two validators sharing a declaration but confining imports differently still
     * hash differently, and neither can be served the other's cached output. Both
     * halves are stable between requests, which keeps the cache usable.
     *
     * @return string
     */
    private function hashClosure(Closure $closure)
    {
        $reflection = new ReflectionFunction($closure);

        return implode(':', [
            Closure::class,
            (string) $reflection->getFileName(),
            (string) $reflection->getStartLine(),
            $this->hashComponent($reflection->getStaticVariables()),
        ]);
    }

    public function filterLoad(AssetInterface $asset)
    {
        $importFilter = $this->importFilter;
        $importValidator = $this->importValidator;
        $sourceRoot = $asset->getSourceRoot();
        $sourcePath = $asset->getSourcePath();

        $callback = function ($matches) use ($importFilter, $importValidator, $sourceRoot, $sourcePath) {
            if (!$matches['url'] || null === $sourceRoot) {
                return $matches[0];
            }

            $importRoot = $sourceRoot;

            if (false !== strpos($matches['url'] ?: '', '://')) {
                // absolute
                list($importScheme, $tmp) = explode('://', $matches['url'], 2);
                list($importHost, $importPath) = explode('/', $tmp, 2);
                $importRoot = $importScheme . '://' . $importHost;
            } elseif (0 === strpos($matches['url'] ?: '', '//')) {
                // protocol-relative
                list($importHost, $importPath) = explode('/', substr($matches['url'], 2), 2);
                $importRoot = '//' . $importHost;
            } elseif ('/' == $matches['url'][0]) {
                // root-relative
                $importPath = substr($matches['url'], 1);
            } elseif (null !== $sourcePath) {
                // document-relative
                $importPath = $matches['url'];
                if ('.' != $sourceDir = dirname($sourcePath)) {
                    $importPath = $sourceDir . '/' . $importPath;
                }
            } else {
                return $matches[0];
            }

            $importSource = $importRoot . '/' . $importPath;

            // Authorise every import form before dispatching. Scheme-bearing and
            // protocol-relative targets are resolved through the same
            // `file_get_contents()` path as local ones, so the validator is applied
            // here rather than in the local-file branch alone.
            if (null !== $importValidator && !$importValidator($importSource)) {
                return $matches[0];
            }

            if (false !== strpos($importSource ?: '', '://') || 0 === strpos($importSource ?: '', '//')) {
                $import = new HttpAsset($importSource, array($importFilter), true);
            } elseif ('css' != pathinfo($importPath ?: '', PATHINFO_EXTENSION) || !file_exists($importSource)) {
                // ignore non-css and non-existant imports
                return $matches[0];
            } else {
                $import = new FileAsset($importSource, array($importFilter), $importRoot, $importPath);
            }

            $import->setTargetPath($sourcePath);

            return $import->dump();
        };

        $content = $asset->getContent();
        $lastHash = md5($content);

        do {
            $content = $this->filterImports($content, $callback);
            $hash = md5($content);
        } while ($lastHash != $hash && $lastHash = $hash);

        $asset->setContent($content);
    }

    public function getChildren(AssetFactory $factory, $content, $loadPath = null)
    {
        // todo
        return [];
    }
}
