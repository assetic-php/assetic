<?php namespace Assetic\Filter;

use Assetic\Contracts\Asset\AssetInterface;
use Wikimedia\Minify\JavaScriptMinifier;

/**
 * Filters assets through WikiMedia\Minify's JavaScriptMinifier.
 *
 * @see https://github.com/wikimedia/minify/blob/master/src/JavaScriptMinifier.php
 */
class JavaScriptMinifierFilter extends BaseFilter
{
    public function filterDump(AssetInterface $asset)
    {
        $asset->setContent(JavaScriptMinifier::minify($asset->getContent()));
    }
}
