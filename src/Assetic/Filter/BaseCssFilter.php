<?php

namespace Assetic\Filter;

use Assetic\Util\CssUtils;

/**
 * An abstract filter for dealing with CSS.
 *
 * @author Kris Wallsmith <kris.wallsmith@gmail.com>
 */
abstract class BaseCssFilter extends BaseFilter
{
    /**
     * @see CssUtils::filterReferences()
     */
    protected function filterReferences($content, $callback, $limit = -1, &$count = 0)
    {
        return CssUtils::filterReferences($content, $callback, $limit, $count);
    }

    /**
     * @see CssUtils::filterUrls()
     */
    protected function filterUrls($content, $callback, $limit = -1, &$count = 0)
    {
        return CssUtils::filterUrls($content, $callback, $limit, $count);
    }

    /**
     * @see CssUtils::filterImports()
     */
    protected function filterImports($content, $callback, $limit = -1, &$count = 0, $includeUrl = true)
    {
        return CssUtils::filterImports($content, $callback, $limit, $count, $includeUrl);
    }

    /**
     * @see CssUtils::filterIEFilters()
     */
    protected function filterIEFilters($content, $callback, $limit = -1, &$count = 0)
    {
        return CssUtils::filterIEFilters($content, $callback, $limit, $count);
    }
}
