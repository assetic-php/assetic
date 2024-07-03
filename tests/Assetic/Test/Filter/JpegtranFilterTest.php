<?php

namespace Assetic\Test\Filter;

use Assetic\Asset\FileAsset;
use Assetic\Filter\JpegtranFilter;

/**
 * @group integration
 */
class JpegtranFilterTest extends FilterTestCase
{
    private $filter;

    protected function setUp(): void
    {
        if (!$jpegtranBin = $this->findExecutable('jpegtran', 'JPEGTRAN_BIN')) {
            $this->markTestSkipped('Unable to find `jpegtran` executable.');
        }

        $this->filter = new JpegtranFilter($jpegtranBin);
    }

    protected function tearDown(): void
    {
        $this->filter = null;
    }

    public function testFilter()
    {
        $asset = new FileAsset(__DIR__ . '/fixtures/home.jpg');
        $asset->load();

        $before = $asset->getContent();
        $this->filter->filterDump($asset);

        $this->assertNotEmpty($asset->getContent(), '->filterLoad() sets content');
        $this->assertNotEquals($before, $asset->getContent(), '->filterDump() changes the content');
        $this->assertMimeType('image/jpeg', $asset->getContent(), '->filterDump() creates JPEG data');
    }
}
