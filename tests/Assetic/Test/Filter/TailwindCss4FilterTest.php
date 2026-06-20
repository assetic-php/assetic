<?php

namespace Assetic\Test\Filter;

use Assetic\Asset\FileAsset;
use Assetic\Filter\TailwindCssFilter;

/**
 * @group integration
 */
class TailwindCss4FilterTest extends FilterTestCase
{
    /** @var TailwindCssFilter|null */
    private $filter;

    protected function setUp(): void
    {
        $tailwindCssBin = $this->findExecutable('tailwindcss', 'TAILWINDCSS_BIN');

        if (!$tailwindCssBin) {
            $this->markTestSkipped('Unable to find `tailwindcss` executable.');
        }

        $this->filter = new TailwindCssFilter($tailwindCssBin);
    }

    protected function tearDown(): void
    {
        $this->filter = null;
    }

    public function testFilterLoad()
    {
        $fileAsset = new FileAsset(__DIR__ . '/fixtures/tailwindcss4/css/style.css');
        $fileAsset->load();

        $this->filter->setWorkingDirectory(__DIR__ . '/fixtures/tailwindcss4');
        $this->filter->filterLoad($fileAsset);
        $contents = $fileAsset->getContent();

        // Detect boilerplate TailwindCSS styling
        $expected = <<<'STYLE'
        @layer base {
          *, ::after, ::before, ::backdrop, ::file-selector-button {
            box-sizing: border-box;
            margin: 0;
            padding: 0;
            border: 0 solid;
          }
        STYLE;

        $this->assertStringContainsString($expected, $contents);

        // Detect class defined in HTML
        $expected = <<<'STYLE'
          .text-gray-800 {
            color: var(--color-gray-800);
          }
        STYLE;

        $this->assertStringContainsString($expected, $contents);
    }

    public function testFilterLoadWithMinification()
    {
        $fileAsset = new FileAsset(__DIR__ . '/fixtures/tailwindcss4/css/style.css');
        $fileAsset->load();

        $this->filter->setWorkingDirectory(__DIR__ . '/fixtures/tailwindcss4');
        $this->filter->minify();
        $this->filter->filterLoad($fileAsset);
        $contents = $fileAsset->getContent();

        // Detect boilerplate TailwindCSS styling
        $expected = <<<'STYLE'
        @layer base{*,:after,:before,::backdrop{box-sizing:border-box;border:0 solid;margin:0;padding:0}::file-selector-button{box-sizing:border-box;border:0 solid;margin:0;padding:0}
        STYLE;

        $this->assertStringContainsString($expected, $contents);

        // Detect class defined in HTML
        $expected = <<<'STYLE'
        .text-gray-800{color:var(--color-gray-800)}
        STYLE;

        $this->assertStringContainsString($expected, $contents);
    }
}
