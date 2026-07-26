<?php

namespace Assetic\Filter;

use Assetic\Contracts\Asset\AssetInterface;
use Assetic\Asset\FileAsset;
use Assetic\Asset\HttpAsset;
use Assetic\Factory\AssetFactory;
use Assetic\Contracts\Filter\DependencyExtractorInterface;
use Assetic\Contracts\Filter\FilterInterface;

/**
 * Inlines imported stylesheets.
 *
 * @author Kris Wallsmith <kris.wallsmith@gmail.com>
 */
class CssImportFilter extends BaseCssFilter implements DependencyExtractorInterface
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
     * Set an optional validator that authorises each local file import before it is
     * inlined. The validator receives the resolved import path (assembled from the
     * asset's source root and the `@import` URL) and must return true to allow the
     * import or false to skip it, leaving the raw `@import` statement untouched.
     *
     * This is an opt-in confinement hook for consumers that inline imports from
     * potentially untrusted stylesheets: without it, `@import` targets are resolved
     * relative to the source with `..` traversal allowed, which can disclose any
     * readable `.css` file on the server. Defaults to null (no restriction) so
     * existing behaviour is unchanged for callers that do not set it.
     */
    public function setImportValidator(?callable $importValidator): self
    {
        $this->importValidator = $importValidator;

        return $this;
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
            if (false !== strpos($importSource ?: '', '://') || 0 === strpos($importSource ?: '', '//')) {
                $import = new HttpAsset($importSource, array($importFilter), true);
            } elseif ('css' != pathinfo($importPath ?: '', PATHINFO_EXTENSION) || !file_exists($importSource)) {
                // ignore non-css and non-existant imports
                return $matches[0];
            } elseif (null !== $importValidator && !$importValidator($importSource)) {
                // ignore imports the caller-supplied validator rejects (e.g. a path
                // that escapes the allowed roots via `..` traversal)
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
