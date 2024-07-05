<?php

namespace Assetic\Test\Filter;

use PHPUnit\Framework\TestCase;
use Assetic\Asset\FileAsset;
use Assetic\Filter\CSSMinFilter;

/**
 * @group integration
 * @see https://github.com/wikimedia/minify/blob/master/tests/CSSMinTest.php
 */
class CSSMinFilterTest extends TestCase
{
    protected function setUp(): void
    {
        if (!class_exists('\Wikimedia\Minify\CSSMin')) {
            $this->markTestSkipped('wikimedia/minify is not installed.');
        }
    }

    public function testSimpleCssMinification()
    {
        $asset = new \Assetic\Asset\StringAsset('body { color: #00000088; background: #ffffff; }');
        $asset->load();

        $filter = new CSSMinFilter();
        $filter->filterDump($asset);

        $this->assertEquals('body{color:#00000088;background:#ffffff}', $asset->getContent());
    }

    public function testRelativeSourceUrlImportImports()
    {
        $asset = new FileAsset(__DIR__ . '/fixtures/cssmin/main.css', [new \Assetic\Filter\CssImportFilter()]);
        $asset->load();

        $filter = new CSSMinFilter();
        $filter->filterDump($asset);

        $this->assertEquals('body{color:white}body{background:black}', $asset->getContent());
    }
}
