<?php

namespace Assetic\Test\Filter;

use Assetic\Asset\FileAsset;
use Assetic\Filter\TailwindCssFilter;

/**
 * @group integration
 */
class TailwindCss3FilterTest extends FilterTestCase
{
    /** @var TailwindCssFilter|null */
    private $filter;

    protected function setUp(): void
    {
        $tailwindCssBin = $this->findExecutable('tailwindcss3', 'TAILWINDCSS_BIN');

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
        $fileAsset = new FileAsset(__DIR__ . '/fixtures/tailwindcss3/css/style.css');
        $fileAsset->load();

        $this->filter->setConfigPath(__DIR__ . '/fixtures/tailwindcss3/tailwind.config.js');
        $this->filter->setWorkingDirectory(__DIR__ . '/fixtures/tailwindcss3');
        $this->filter->filterLoad($fileAsset);
        $contents = $fileAsset->getContent();

        // Detect boilerplate TailwindCSS styling
        $expected = <<<'STYLE'
        ::backdrop {
          --tw-border-spacing-x: 0;
          --tw-border-spacing-y: 0;
          --tw-translate-x: 0;
          --tw-translate-y: 0;
        STYLE;

        $this->assertStringContainsString($expected, $contents);

        // Detect class defined in HTML
        $expected = <<<'STYLE'
        .text-gray-800 {
          --tw-text-opacity: 1;
          color: rgb(31 41 55 / var(--tw-text-opacity, 1));
        }
        STYLE;

        $this->assertStringContainsString($expected, $contents);
    }

    public function testFilterLoadWithMinification()
    {
        $fileAsset = new FileAsset(__DIR__ . '/fixtures/tailwindcss3/css/style.css');
        $fileAsset->load();

        $this->filter->setConfigPath(__DIR__ . '/fixtures/tailwindcss3/tailwind.config.js');
        $this->filter->setWorkingDirectory(__DIR__ . '/fixtures/tailwindcss3');
        $this->filter->minify();
        $this->filter->filterLoad($fileAsset);
        $contents = $fileAsset->getContent();

        // Detect boilerplate TailwindCSS styling
        $expected = <<<'STYLE'
        *,:after,:before{--tw-border-spacing-x:0;--tw-border-spacing-y:0;--tw-translate-x:0;--tw-translate-y:0;
        STYLE;

        $this->assertStringContainsString($expected, $contents);

        // Detect class defined in HTML
        $expected = <<<'STYLE'
        .text-gray-800{--tw-text-opacity:1;color:rgb(31 41 55/var(--tw-text-opacity,1))}
        STYLE;

        $this->assertStringContainsString($expected, $contents);
    }
}
