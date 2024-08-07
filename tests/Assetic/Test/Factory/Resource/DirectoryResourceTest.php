<?php

namespace Assetic\Test\Factory\Resource;

use PHPUnit\Framework\TestCase;
use Assetic\Contracts\Factory\Resource\ResourceInterface;
use Assetic\Factory\Resource\DirectoryResource;

class DirectoryResourceTest extends TestCase
{
    public function testIsFresh()
    {
        $resource = new DirectoryResource(__DIR__);
        $this->assertTrue($resource->isFresh(time() + 5));
        $this->assertFalse($resource->isFresh(0));
    }

    /**
     * @dataProvider getPatterns
     */
    public function testGetContent($pattern)
    {
        $resource = new DirectoryResource(__DIR__, $pattern);
        $content = $resource->getContent();

        $this->assertIsString($content);
    }

    public function getPatterns()
    {
        return array(
            array(null),
            array('/\.php$/'),
            array('/\.foo$/'),
        );
    }

    /**
     * @dataProvider getPatternsAndEmpty
     */
    public function testIteration($pattern, $empty)
    {
        $resource = new DirectoryResource(__DIR__, $pattern);

        $count = 0;
        foreach ($resource as $r) {
            ++$count;
            $this->assertInstanceOf(ResourceInterface::class, $r);
        }

        if ($empty) {
            $this->assertEmpty($count);
        } else {
            $this->assertNotEmpty($count);
        }
    }

    public function getPatternsAndEmpty()
    {
        return array(
            array(null, false),
            array('/\.php$/', false),
            array('/\.foo$/', true),
        );
    }

    public function testRecursiveIteration()
    {
        $resource = new DirectoryResource(realpath(__DIR__ . '/..'), '/^' . preg_quote(basename(__FILE__)) . '$/');

        $this->assertCount(1, $resource);
    }

    /**
     * @dataProvider getPaths
     */
    public function testTrailingSlash($path)
    {
        $resource = new DirectoryResource($path);
        $this->assertStringEndsWith(DIRECTORY_SEPARATOR, (string) $resource, 'path ends with a slash');
    }

    public function getPaths()
    {
        return array(
            array(__DIR__),
            array(__DIR__ . DIRECTORY_SEPARATOR),
        );
    }

    public function testInvalidDirectory()
    {
        $resource = new DirectoryResource(__DIR__ . 'foo');
        $this->assertEquals(0, iterator_count($resource), 'works for non-existent directory');
    }

    public function testFollowSymlinks()
    {
        // Create the symlink if it doesn't already exist yet (if someone broke the entire testsuite perhaps)
        if (!is_dir(__DIR__ . '/Fixtures/dir3')) {
            symlink(__DIR__ . '/Fixtures/dir2', __DIR__ . '/Fixtures/dir3');
        }

        $resource = new DirectoryResource(__DIR__ . '/Fixtures');

        $this->assertCount(7, $resource);
    }

    protected function tearDown(): void
    {
        if (is_dir(__DIR__ . '/Fixtures/dir3') && is_link(__DIR__ . '/Fixtures/dir3')) {
            if (defined('PHP_WINDOWS_VERSION_MAJOR')) {
                rmdir(__DIR__ . '/Fixtures/dir3');
            } else {
                unlink(__DIR__ . '/Fixtures/dir3');
            }
        }
    }
}
