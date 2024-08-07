<?php

namespace Assetic\Test\Extension\Twig;

use PHPUnit\Framework\TestCase;
use Assetic\Contracts\Asset\AssetInterface;
use Assetic\Contracts\Filter\FilterInterface;
use Assetic\Contracts\ValueSupplierInterface;
use Assetic\Factory\AssetFactory;
use Assetic\Extension\Twig\AsseticExtension;
use Assetic\Asset\AssetCollection;
use Assetic\Asset\FileAsset;
use Assetic\FilterManager;
use Assetic\AssetManager;
use Twig\Environment;
use Twig\Loader\FilesystemLoader;
use Twig\Error\SyntaxError;
use PHPUnit\Framework\MockObject\MockObject;

class AsseticExtensionTest extends TestCase
{
    /**
     * @var MockObject|AssetManager
     */
    private $am;

    /**
     * @var MockObject|FilterManager
     */
    private $fm;

    /**
     * @var AssetFactory
     */
    private $factory;

    /**
     * @var Environment
     */
    private $twig;

    /**
     * @var MockObject|ValueSupplierInterface
     */
    private $valueSupplier;

    protected function setUp(): void
    {
        if (!class_exists('\Twig\Environment')) {
            $this->markTestSkipped('Twig is not installed.');
        }

        $this->am = $this->getMockBuilder(AssetManager::class)->getMock();
        $this->fm = $this->getMockBuilder(FilterManager::class)->getMock();

        $this->valueSupplier = $this->getMockBuilder(ValueSupplierInterface::class)->getMock();

        $this->factory = new AssetFactory(__DIR__ . '/templates');
        $this->factory->setAssetManager($this->am);
        $this->factory->setFilterManager($this->fm);

        $this->twig = new Environment(new FilesystemLoader(__DIR__ . '/templates'));
        $this->twig->addExtension(new AsseticExtension($this->factory, [], $this->valueSupplier));
    }

    protected function tearDown(): void
    {
        $this->am = null;
        $this->fm = null;
        $this->factory = null;
        $this->twig = null;
        $this->valueSupplier = null;
    }

    public function testReference()
    {
        $asset = $this->getMockBuilder(AssetInterface::class)->getMock();
        $this->am->expects($this->any())
            ->method('get')
            ->with('foo')
            ->will($this->returnValue($asset));

        $xml = $this->renderXml('reference.twig');
        $this->assertEquals(1, count($xml->asset));
        $this->assertStringStartsWith('css/', (string) $xml->asset['url']);
    }

    public function testGlob()
    {
        $xml = $this->renderXml('glob.twig');
        $this->assertEquals(1, count($xml->asset));
        $this->assertStringStartsWith('css/', (string) $xml->asset['url']);
    }

    public function testAbsolutePath()
    {
        $xml = $this->renderXml('absolute_path.twig');
        $this->assertEquals(1, count($xml->asset));
        $this->assertStringStartsWith('css/', (string) $xml->asset['url']);
    }

    public function testFilters()
    {
        $filter = $this->getMockBuilder(FilterInterface::class)->getMock();

        $this->fm->expects($this->exactly(2))
            ->method('get')
            ->withConsecutive(
                ['foo'],
                ['bar']
            )
            ->willReturnOnConsecutiveCalls(
                $this->returnValue($filter),
                $this->returnValue($filter)
            );

        $xml = $this->renderXml('filters.twig');
        $this->assertCount(1, $xml->asset);
        $this->assertStringStartsWith('css/', (string) $xml->asset['url']);
    }

    public function testOptionalFilter()
    {
        $filter = $this->getMockBuilder(FilterInterface::class)->getMock();

        $this->fm->expects($this->once())
            ->method('get')
            ->with('foo')
            ->will($this->returnValue($filter));

        $xml = $this->renderXml('optional_filter.twig');
        $this->assertEquals(1, count($xml->asset));
        $this->assertStringStartsWith('css/', (string) $xml->asset['url']);
    }

    public function testOutputPattern()
    {
        $xml = $this->renderXml('output_pattern.twig');
        $this->assertEquals(1, count($xml->asset));
        $this->assertStringStartsWith('css/packed/', (string) $xml->asset['url']);
        $this->assertStringEndsWith('.css', (string) $xml->asset['url']);
    }

    public function testOutput()
    {
        $xml = $this->renderXml('output_url.twig');
        $this->assertEquals(1, count($xml->asset));
        $this->assertEquals('explicit_url.css', (string) $xml->asset['url']);
    }

    public function testMixture()
    {
        $asset = $this->getMockBuilder(AssetInterface::class)->getMock();
        $this->am->expects($this->any())
            ->method('get')
            ->with('foo')
            ->will($this->returnValue($asset));

        $xml = $this->renderXml('mixture.twig');
        $this->assertEquals(1, count($xml->asset));
        $this->assertEquals('packed/mixture', (string) $xml->asset['url']);
    }

    public function testDebug()
    {
        $filter = $this->getMockBuilder(FilterInterface::class)->getMock();

        $this->fm->expects($this->once())
            ->method('get')
            ->with('bar')
            ->will($this->returnValue($filter));

        $xml = $this->renderXml('debug.twig');
        $this->assertEquals(2, count($xml->asset));
        $this->assertStringStartsWith('css/packed_', (string) $xml->asset[0]['url']);
        $this->assertStringEndsWith('.css', (string) $xml->asset[0]['url']);
    }

    public function testCombine()
    {
        $filter = $this->getMockBuilder(FilterInterface::class)->getMock();

        $this->fm->expects($this->once())
            ->method('get')
            ->with('bar')
            ->will($this->returnValue($filter));

        $xml = $this->renderXml('combine.twig');
        $this->assertEquals(1, count($xml->asset));
        $this->assertEquals('css/packed.css', (string) $xml->asset[0]['url']);
    }

    public function testImage()
    {
        $xml = $this->renderXml('image.twig');
        $this->assertEquals(1, count($xml->image));
        $this->assertStringEndsWith('.png', (string) $xml->image[0]['url']);
    }

    public function testFilterFunction()
    {
        $filter = $this->getMockBuilder(FilterInterface::class)->getMock();

        $this->fm->expects($this->once())
            ->method('get')
            ->with('some_filter')
            ->will($this->returnValue($filter));

        $this->twig = new Environment(new FilesystemLoader(__DIR__ . '/templates'));
        $this->twig->addExtension(new AsseticExtension($this->factory, array(
            'some_func' => array(
                'filter' => 'some_filter',
                'options' => array('output' => 'css/*.css'),
            ),
        )));

        $xml = $this->renderXml('function.twig');
        $this->assertEquals(1, count($xml->asset));
        $this->assertStringEndsWith('.css', (string) $xml->asset[0]['url']);
    }

    public function testVariables()
    {
        $this->valueSupplier->expects($this->once())
            ->method('getValues')
            ->will($this->returnValue(array('foo' => 'a', 'bar' => 'b')));

        $xml = $this->renderXml('variables.twig');
        $this->assertEquals(2, $xml->url->count());
        $this->assertEquals("js/7d0828c.a.b_foo_1.js", (string) $xml->url[0]);
        $this->assertEquals("js/7d0828c.a.b_variable_input._2.js", (string) $xml->url[1]);
    }

    public function testMultipleSameVariableValues()
    {
        $vars = array('locale');
        $asset = new FileAsset(__DIR__ . '/../Fixture/messages.{locale}.js', [], null, null, $vars);

        $coll = new AssetCollection(array($asset), [], null, $vars);

        $coll->setTargetPath('output.{locale}.js');

        $coll->setValues(array('locale' => 'en'));
        foreach ($coll as $asset) {
            $this->assertEquals('output.{locale}_messages._1.js', $asset->getTargetPath(), 'targetPath must not contain several time the same variable');
        }
    }

    public function testUnclosedTag()
    {
        $this->expectException(SyntaxError::class);
        $this->renderXml('unclosed_tag.twig');
    }

    private function renderXml($name, $context = [])
    {
        return new \SimpleXMLElement($this->twig->loadTemplate($name)->render($context));
    }
}
