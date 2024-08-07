<?php

namespace Assetic\Factory\Loader;

use Assetic\Cache\ConfigCache;
use Assetic\Contracts\Factory\Resource\IteratorResourceInterface;
use Assetic\Contracts\Factory\Resource\ResourceInterface;
use Assetic\Contracts\Factory\Loader\FormulaLoaderInterface;

/**
 * Adds a caching layer to a loader.
 *
 * A cached formula loader is a composition of a formula loader and a cache.
 *
 * @author Kris Wallsmith <kris.wallsmith@gmail.com>
 */
class CachedFormulaLoader implements FormulaLoaderInterface
{
    private $loader;
    private $configCache;
    private $debug;

    /**
     * Constructor.
     *
     * When the loader is in debug mode it will ensure the cached formulae
     * are fresh before returning them.
     *
     * @param FormulaLoaderInterface $loader      A formula loader
     * @param ConfigCache            $configCache A config cache
     * @param Boolean                $debug       The debug mode
     */
    public function __construct(FormulaLoaderInterface $loader, ConfigCache $configCache, $debug = false)
    {
        $this->loader = $loader;
        $this->configCache = $configCache;
        $this->debug = $debug;
    }

    public function load(ResourceInterface $resources)
    {
        if (!$resources instanceof IteratorResourceInterface) {
            $resources = array($resources);
        }

        $formulae = [];

        foreach ($resources as $resource) {
            $id = (string) $resource;
            if (!$this->configCache->has($id) || ($this->debug && !$resource->isFresh($this->configCache->getTimestamp($id)))) {
                $formulae += $this->loader->load($resource);
                $this->configCache->set($id, $formulae);
            } else {
                $formulae += $this->configCache->get($id);
            }
        }

        return $formulae;
    }
}
