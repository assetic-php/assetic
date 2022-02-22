<?php namespace Assetic\Filter;

use Assetic\Contracts\Asset\AssetInterface;
use Wikimedia\Minify\CSSMin;

/**
 * Filters assets through WikiMedia\Minify's CSSMin CSS minifier.
 *
 * @see https://github.com/wikimedia/minify/blob/master/src/CSSMin.php
 */
class CSSMinFilter extends BaseFilter
{
    public function filterDump(AssetInterface $asset)
    {
        $asset->setContent(CSSMin::minify($asset->getContent()));
    }
}
