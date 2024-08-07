<?php

namespace Assetic\Test\Asset;

use PHPUnit\Framework\TestCase;
use Assetic\Contracts\Asset\AssetInterface;
use Assetic\Contracts\Cache\CacheInterface;
use Assetic\Contracts\Filter\FilterInterface;
use Assetic\Asset\AssetCache;

class AssetCacheTest extends TestCase
{
    private $inner;
    private $cache;
    /** @var AssetCache */
    private $asset;

    protected function setUp(): void
    {
        $this->inner = $this->getMockBuilder(AssetInterface::class)->getMock();
        $this->cache = $this->getMockBuilder(CacheInterface::class)->getMock();

        $this->asset = new AssetCache($this->inner, $this->cache);
    }

    protected function tearDown(): void
    {
        $this->inner = null;
        $this->cache = null;
        $this->asset = null;
    }

    public function testLoadFromCache()
    {
        $content = 'asdf';
        $filter = $this->getMockBuilder(FilterInterface::class)->getMock();

        $this->inner->expects($this->once())
            ->method('getFilters')
            ->will($this->returnValue(array($filter)));
        $this->cache->expects($this->once())
            ->method('has')
            ->with($this->isType('string'))
            ->will($this->returnValue(true));
        $this->cache->expects($this->once())
            ->method('get')
            ->with($this->isType('string'))
            ->will($this->returnValue($content));
        $this->inner->expects($this->once())
            ->method('setContent')
            ->with($content);

        $this->asset->load($filter);
    }

    public function testLoadToCache()
    {
        $content = 'asdf';

        $this->inner->expects($this->once())
            ->method('getFilters')
            ->will($this->returnValue([]));
        $this->cache->expects($this->once())
            ->method('has')
            ->with($this->isType('string'))
            ->will($this->returnValue(false));
        $this->inner->expects($this->once())->method('load');
        $this->inner->expects($this->once())
            ->method('getContent')
            ->will($this->returnValue($content));
        $this->cache->expects($this->once())
            ->method('set')
            ->with($this->isType('string'), $content);

        $this->asset->load();
    }

    public function testDumpFromCache()
    {
        $content = 'asdf';

        $this->inner->expects($this->once())
            ->method('getFilters')
            ->will($this->returnValue([]));
        $this->cache->expects($this->once())
            ->method('has')
            ->with($this->isType('string'))
            ->will($this->returnValue(true));
        $this->cache->expects($this->once())
            ->method('get')
            ->with($this->isType('string'))
            ->will($this->returnValue($content));

        $this->assertEquals($content, $this->asset->dump(), '->dump() returns the cached value');
    }

    public function testDumpToCache()
    {
        $content = 'asdf';

        $this->inner->expects($this->once())
            ->method('getFilters')
            ->will($this->returnValue([]));
        $this->cache->expects($this->once())
            ->method('has')
            ->with($this->isType('string'))
            ->will($this->returnValue(false));
        $this->inner->expects($this->once())
            ->method('dump')
            ->will($this->returnValue($content));
        $this->cache->expects($this->once())
            ->method('set')
            ->with($this->isType('string'), $content);

        $this->assertEquals($content, $this->asset->dump(), '->dump() returns the dumped value');
    }

    public function testEnsureFilter()
    {
        $filter = $this->getMockBuilder(FilterInterface::class)->getMock();
        $this->inner->expects($this->once())->method('ensureFilter');
        $this->asset->ensureFilter($filter);
    }

    public function testGetFilters()
    {
        $this->inner->expects($this->once())
            ->method('getFilters')
            ->will($this->returnValue([]));

        $this->assertIsArray($this->asset->getFilters(), '->getFilters() returns the inner asset filters');
    }

    public function testGetContent()
    {
        $this->inner->expects($this->once())
            ->method('getContent')
            ->will($this->returnValue('asdf'));

        $this->assertEquals('asdf', $this->asset->getContent(), '->getContent() returns the inner asset content');
    }

    public function testSetContent()
    {
        $this->inner->expects($this->once())
            ->method('setContent')
            ->with('asdf');

        $this->asset->setContent('asdf');
    }

    public function testGetSourceRoot()
    {
        $this->inner->expects($this->once())
            ->method('getSourceRoot')
            ->will($this->returnValue('asdf'));

        $this->assertEquals('asdf', $this->asset->getSourceRoot(), '->getSourceRoot() returns the inner asset source root');
    }

    public function testGetSourcePath()
    {
        $this->inner->expects($this->once())
            ->method('getSourcePath')
            ->will($this->returnValue('asdf'));

        $this->assertEquals('asdf', $this->asset->getSourcePath(), '->getSourcePath() returns the inner asset source path');
    }

    public function testGetLastModified()
    {
        $this->inner->expects($this->once())
            ->method('getLastModified')
            ->will($this->returnValue(123));

        $this->assertEquals(123, $this->asset->getLastModified(), '->getLastModified() returns the inner asset last modified');
    }
}
