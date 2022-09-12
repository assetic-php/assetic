<?php namespace Assetic\Asset;

use Assetic\Asset\MockAsset;
use Assetic\Asset\AssetInterface;
use Assetic\Filter\FilterInterface;

/**
 * Class NoopAsset
 *
 * This class returns the content, unmodified, as a way to skip 
 * or ignore an Asset during optimization.
 */
class NoopAsset extends MockAsset
{
    public function dump(FilterInterface $additionalFilter = null)
    {
      return $this->getContent();
    }
}
