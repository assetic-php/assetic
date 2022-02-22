<?php namespace Assetic\Test\Filter;

use PHPUnit\Framework\TestCase;
use Assetic\Asset\FileAsset;
use Assetic\Filter\JavaScriptMinifierFilter;

/**
 * @group integration
 */
class JavaScriptMinifierFilterTest extends TestCase
{
    protected function setUp(): void
    {
        if (!class_exists('\Wikimedia\Minify\JavaScriptMinifier')) {
            $this->markTestSkipped('wikimedia/minify is not installed.');
        }
    }

    public function testRelativeSourceUrlImportImports()
    {
        $asset = new FileAsset(__DIR__.'/fixtures/javascript/js.js');
        $asset->load();

        $filter = new JavaScriptMinifierFilter();
        $filter->filterDump($asset);

        $this->assertEquals('var a="abc";;;var bbb="u";', $asset->getContent());
    }
}
