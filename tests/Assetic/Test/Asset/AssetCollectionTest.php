<?php

namespace Assetic\Test\Asset;

use PHPUnit\Framework\TestCase;
use Assetic\Contracts\Asset\AssetInterface;
use Assetic\Contracts\Filter\FilterInterface;
use Assetic\Asset\StringAsset;
use Assetic\Asset\FileAsset;
use Assetic\Asset\AssetCollection;
use Assetic\Filter\CallablesFilter;

class AssetCollectionTest extends TestCase
{
    public function testInterface()
    {
        $coll = new AssetCollection();
        $this->assertInstanceOf(AssetInterface::class, $coll, 'AssetCollection implements AssetInterface');
    }

    public function testLoadFilter()
    {
        $filter = $this->getMockBuilder(FilterInterface::class)->getMock();
        $filter->expects($this->once())->method('filterLoad');

        $coll = new AssetCollection(array(new StringAsset('')), array($filter));
        $coll->load();
    }

    public function testDumpFilter()
    {
        $filter = $this->getMockBuilder(FilterInterface::class)->getMock();
        $filter->expects($this->once())->method('filterDump');

        $coll = new AssetCollection(array(new StringAsset('')), array($filter));
        $coll->dump();
    }

    public function testNestedCollectionLoad()
    {
        $content = 'foobar';

        $count = 0;
        $matches = [];
        $filter = new CallablesFilter(function ($asset) use ($content, &$matches, &$count) {
            ++$count;
            if ($content == $asset->getContent()) {
                $matches[] = $asset;
            }
        });

        $innerColl = new AssetCollection(array(new StringAsset($content)));
        $outerColl = new AssetCollection(array($innerColl), array($filter));
        $outerColl->load();

        $this->assertEquals(1, count($matches), '->load() applies filters to leaves');
        $this->assertEquals(1, $count, '->load() applies filters to leaves only');
    }

    public function testMixedIteration()
    {
        $asset = new StringAsset('asset');
        $nestedAsset = new StringAsset('nested');
        $innerColl = new AssetCollection(array($nestedAsset));

        $contents = [];
        $filter = new CallablesFilter(function ($asset) use (&$contents) {
            $contents[] = $asset->getContent();
        });

        $coll = new AssetCollection(array($asset, $innerColl), array($filter));
        $coll->load();

        $this->assertEquals(array('asset', 'nested'), $contents, '->load() iterates over multiple levels');
    }

    public function testLoadDedupBySourceUrl()
    {
        $asset1 = new StringAsset('asset', [], '/some/dir', 'foo.bar');
        $asset2 = new StringAsset('asset', [], '/some/dir', 'foo.bar');

        $coll = new AssetCollection(array($asset1, $asset2));
        $coll->load();

        $this->assertEquals('asset', $coll->getContent(), '->load() detects duplicate assets based on source URL');
    }

    public function testLoadDedupByStrictEquality()
    {
        $asset = new StringAsset('foo');

        $coll = new AssetCollection(array($asset, $asset));
        $coll->load();

        $this->assertEquals('foo', $coll->getContent(), '->load() detects duplicate assets based on strict equality');
    }

    public function testDumpDedupBySourceUrl()
    {
        $asset1 = new StringAsset('asset', [], '/some/dir', 'foo.bar');
        $asset2 = new StringAsset('asset', [], '/some/dir', 'foo.bar');

        $coll = new AssetCollection(array($asset1, $asset2));
        $coll->load();

        $this->assertEquals('asset', $coll->dump(), '->dump() detects duplicate assets based on source URL');
    }

    public function testDumpDedupByStrictEquality()
    {
        $asset = new StringAsset('foo');

        $coll = new AssetCollection(array($asset, $asset));
        $coll->load();

        $this->assertEquals('foo', $coll->dump(), '->dump() detects duplicate assets based on strict equality');
    }

    public function testIterationFilters()
    {
        $count = 0;
        $filter = new CallablesFilter(function () use (&$count) {
            ++$count;
        });

        $coll = new AssetCollection();
        $coll->add(new StringAsset(''));
        $coll->ensureFilter($filter);

        foreach ($coll as $asset) {
            $asset->dump();
        }

        $this->assertEquals(1, $count, 'collection filters are called when child assets are iterated over');
    }

    public function testSetContent()
    {
        $coll = new AssetCollection();
        $coll->setContent('asdf');

        $this->assertEquals('asdf', $coll->getContent(), '->setContent() sets the content');
    }

    /**
     * @dataProvider getTimestampsAndExpected
     */
    public function testGetLastModified($timestamps, $expected)
    {
        $assets = [];

        for ($i = 0; $i < count($timestamps); $i++) {
            $asset = $this->getMockBuilder(AssetInterface::class)->getMock();
            $asset->expects($this->once())
                ->method('getLastModified')
                ->will($this->returnValue($timestamps[$i]));
            $assets[$i] = $asset;
        }

        $coll = new AssetCollection($assets);

        $this->assertEquals($expected, $coll->getLastModified(), '->getLastModifed() returns the highest last modified');
    }

    public function testGetLastModifiedWithValues()
    {
        $vars = array('locale');
        $asset = new FileAsset(__DIR__ . '/../Fixture/messages.{locale}.js', [], null, null, $vars);

        $coll = new AssetCollection(array($asset), [], null, $vars);
        $coll->setValues(array('locale' => 'en'));
        $mtime = null;
        try {
            $mtime = $coll->getLastModified();
        } catch (\InvalidArgumentException $e) {
            $this->fail("->getLastModified() shouldn't fail for assets with vars");
        }

        $this->assertNotNull($mtime);
    }

    public function getTimestampsAndExpected()
    {
        return array(
            array(array(1, 2, 3), 3),
            array(array(5, 4, 3), 5),
            array(array(3, 8, 5), 8),
            array(array(3, 8, null), 8),
        );
    }

    public function testRecursiveIteration()
    {
        $asset1 = $this->getMockBuilder(AssetInterface::class)->getMock();
        $asset2 = $this->getMockBuilder(AssetInterface::class)->getMock();
        $asset3 = $this->getMockBuilder(AssetInterface::class)->getMock();
        $asset4 = $this->getMockBuilder(AssetInterface::class)->getMock();

        $coll3 = new AssetCollection(array($asset1, $asset2));
        $coll2 = new AssetCollection(array($asset3, $coll3));
        $coll1 = new AssetCollection(array($asset4, $coll2));

        $this->assertCount(4, $coll1, 'iteration with a recursive iterator is recursive');
    }

    public function testRecursiveDeduplication()
    {
        $asset = $this->getMockBuilder(AssetInterface::class)->getMock();

        $coll3 = new AssetCollection(array($asset, $asset));
        $coll2 = new AssetCollection(array($asset, $coll3));
        $coll1 = new AssetCollection(array($asset, $coll2));

        $this->assertCount(1, $coll1, 'deduplication is performed recursively');
    }

    public function testIteration()
    {
        $asset1 = new StringAsset('asset1', [], '/some/dir', 'foo.css');
        $asset2 = new StringAsset('asset2', [], '/some/dir', 'foo.css');
        $asset3 = new StringAsset('asset3', [], '/some/dir', 'bar.css');

        $coll = new AssetCollection(array($asset1, $asset2, $asset3));

        $this->assertCount(2, $coll, 'iterator filters duplicates based on url');
    }

    public function testBasenameCollision()
    {
        $asset1 = new StringAsset('asset1', [], '/some/dir', 'foo/foo.css');
        $asset2 = new StringAsset('asset2', [], '/some/dir', 'bar/foo.css');

        $coll = new AssetCollection(array($asset1, $asset2));

        $urls = [];
        foreach ($coll as $leaf) {
            $urls[] = $leaf->getTargetPath();
        }

        $this->assertEquals(2, count(array_unique($urls)), 'iterator prevents basename collisions');
    }

    public function testTargetNameGeneration()
    {
        $path = '/testing/dir.ectory/path/file.ext';

        $coll = new AssetCollection(array(new StringAsset('asset1'), new StringAsset('asset2')));
        $coll->setTargetPath($path);

        foreach ($coll as $asset) {
            $this->assertEquals(dirname($path), dirname($asset->getTargetPath()));
        }
    }

    public function testEmptyMtime()
    {
        $coll = new AssetCollection();
        $this->assertNull($coll->getLastModified(), '->getLastModified() returns null on empty collection');
    }

    public function testLeafManipulation()
    {
        $coll = new AssetCollection(array(new StringAsset('asdf')));

        foreach ($coll as $leaf) {
            $leaf->setTargetPath('asdf');
        }

        foreach ($coll as $leaf) {
            $this->assertEquals('asdf', $leaf->getTargetPath(), 'leaf changes persist between iterations');
        }
    }

    public function testRemoveLeaf()
    {
        $coll = new AssetCollection(array(
            $leaf = new StringAsset('asdf'),
        ));

        $this->assertTrue($coll->removeLeaf($leaf));
    }

    public function testRemoveRecursiveLeaf()
    {
        $coll = new AssetCollection(array(
            new AssetCollection(array(
                $leaf = new StringAsset('asdf'),
            )),
        ));

        $this->assertTrue($coll->removeLeaf($leaf));
    }

    public function testRemoveInvalidLeaf()
    {
        $this->expectException('InvalidArgumentException');

        $coll = new AssetCollection();
        $coll->removeLeaf(new StringAsset('asdf'));
    }

    public function testReplaceLeaf()
    {
        $coll = new AssetCollection(array(
            $leaf = new StringAsset('asdf'),
        ));

        $this->assertTrue($coll->replaceLeaf($leaf, new StringAsset('foo')));
    }

    public function testReplaceRecursiveLeaf()
    {
        $coll = new AssetCollection(array(
            new AssetCollection(array(
                $leaf = new StringAsset('asdf'),
            )),
        ));

        $this->assertTrue($coll->replaceLeaf($leaf, new StringAsset('foo')));
    }

    public function testReplaceInvalidLeaf()
    {
        $this->expectException('InvalidArgumentException');

        $coll = new AssetCollection();
        $coll->replaceLeaf(new StringAsset('foo'), new StringAsset('bar'));
    }

    public function testClone()
    {
        $coll1 = new AssetCollection();
        $coll1->ensureFilter(new CallablesFilter());

        $coll2 = clone $coll1;
        $coll2->ensureFilter(new CallablesFilter());

        $this->assertCount(1, $coll1->getFilters());
        $this->assertCount(2, $coll2->getFilters());
    }

    public function testNewFilterOnCloneDoesNotPropagateToPrototype()
    {
        $coll1 = new AssetCollection();
        $coll1->add(new StringAsset(""));

        $coll2 = clone $coll1;
        $coll2->ensureFilter(new CallablesFilter());

        // Internally, this will set up the "clones" for collection #2 with one filter
        foreach ($coll2 as $asset) {
        }

        // The clones on collection #1 must not be affected
        foreach ($coll1 as $asset) {
            $this->assertCount(0, $asset->getFilters());
        }
    }

    public function testClearingFiltersWorksAfterCollectionHasBeenIterated()
    {
        $coll = new AssetCollection();
        $coll->add(new StringAsset(""));
        $coll->ensureFilter(new CallablesFilter());

        /* This iteration is necessary to internally prime the collection's
           "clones" with one filter. */
        foreach ($coll as $asset) {
            $this->assertCount(1, $asset->getFilters());
        }

        $coll->clearFilters();

        foreach ($coll as $asset) {
            $this->assertCount(0, $asset->getFilters());
        }
    }
}
