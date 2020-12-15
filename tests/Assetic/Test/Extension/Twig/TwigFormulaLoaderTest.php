<?php namespace Assetic\Test\Extension\Twig;

use Assetic\Contracts\Asset\AssetInterface;
use Assetic\Contracts\Factory\Resource\ResourceInterface;
use Assetic\Factory\AssetFactory;
use Assetic\AssetManager;
use Assetic\FilterManager;
use Assetic\Extension\Twig\AsseticExtension;
use Assetic\Extension\Twig\TwigFormulaLoader;
use PHPUnit\Framework\TestCase;
use Twig\Environment;
use Twig\Loader\ArrayLoader;

class TwigFormulaLoaderTest extends TestCase
{
    private $am;
    private $fm;
    /**
     * @var TwigFormulaLoader
     */
    private $loader;

    protected function setUp(): void
    {
        if (!class_exists('Twig\Environment')) {
            $this->markTestSkipped('Twig is not installed.');
        }

        $this->am = $this->getMockBuilder(AssetManager::class)->getMock();
        $this->fm = $this->getMockBuilder(FilterManager::class)->getMock();

        $factory = new AssetFactory(__DIR__.'/templates');
        $factory->setAssetManager($this->am);
        $factory->setFilterManager($this->fm);

        $twig = new Environment(new ArrayLoader([]));
        $twig->addExtension(new AsseticExtension($factory, [
            'some_func' => [
                'filter' => 'some_filter',
                'options' => ['output' => 'css/*.css'],
            ],
        ]));

        $this->loader = new TwigFormulaLoader($twig);
    }

    protected function tearDown(): void
    {
        $this->am = null;
        $this->fm = null;
    }

    public function testMixture()
    {
        $asset = $this->getMockBuilder(AssetInterface::class)->getMock();

        $expected = [
            'mixture' => [
                ['foo', 'foo/*', '@foo'],
                [],
                [
                    'output'  => 'packed/mixture',
                    'name'    => 'mixture',
                    'debug'   => false,
                    'combine' => null,
                    'vars'    => [],
                ],
            ],
        ];

        $resource = $this->getMockBuilder(ResourceInterface::class)->getMock();
        $resource->expects($this->once())
            ->method('getContent')
            ->will($this->returnValue(file_get_contents(__DIR__.'/templates/mixture.twig')));
        $this->am->expects($this->any())
            ->method('get')
            ->with('foo')
            ->will($this->returnValue($asset));

        $formulae = $this->loader->load($resource);
        $this->assertEquals($expected, $formulae);
    }

    public function testFunction()
    {
        $expected = [
            'my_asset' => [
                ['path/to/asset'],
                ['some_filter'],
                [
                    'output' => 'css/*.css',
                    'name' => 'my_asset'
                ],
            ],
        ];

        $resource = $this->getMockBuilder(ResourceInterface::class)->getMock();
        $resource->expects($this->once())
            ->method('getContent')
            ->will($this->returnValue(file_get_contents(__DIR__.'/templates/function.twig')));

        $formulae = $this->loader->load($resource);
        $this->assertEquals($expected, $formulae);
    }

    public function testUnclosedTag()
    {
        $resource = $this->getMockBuilder(ResourceInterface::class)->getMock();
        $resource->expects($this->once())
            ->method('getContent')
            ->will($this->returnValue(file_get_contents(__DIR__.'/templates/unclosed_tag.twig')));

        $formulae = $this->loader->load($resource);
        $this->assertEquals([], $formulae);
    }

    public function testEmbeddedTemplate()
    {
        $expected = [
            'image' => [
                ['images/foo.png'],
                [],
                [
                    'name'    => 'image',
                    'debug'   => true,
                    'vars'    => [],
                    'output'  => 'images/foo.png',
                    'combine' => false,
                ],
            ],
        ];

        $resource = $this->getMockBuilder(ResourceInterface::class)->getMock();
        $resource->expects($this->once())
            ->method('getContent')
            ->will($this->returnValue(file_get_contents(__DIR__.'/templates/embed.twig')));

        $formulae = $this->loader->load($resource);
        $this->assertEquals($expected, $formulae);
    }
}
