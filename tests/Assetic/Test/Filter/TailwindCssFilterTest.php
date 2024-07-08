<?php

namespace Assetic\Test\Filter;

use Assetic\Asset\FileAsset;
use Assetic\Filter\TailwindCssFilter;

/**
 * @group integration
 */
class TailwindCssFilterTest extends FilterTestCase
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
        $fileAsset = new FileAsset(__DIR__ . '/fixtures/tailwindcss/css/style.css');
        $fileAsset->load();

        $this->filter->setConfigPath(__DIR__ . '/fixtures/tailwindcss/tailwind.config.js');
        $this->filter->setWorkingDirectory(__DIR__ . '/fixtures/tailwindcss');
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
              color: rgb(31 41 55 / var(--tw-text-opacity));
            }
            STYLE;

        $this->assertStringContainsString($expected, $contents);
    }

    public function testFilterLoadWithMinification()
    {
        $fileAsset = new FileAsset(__DIR__ . '/fixtures/tailwindcss/css/style.css');
        $fileAsset->load();

        $this->filter->setConfigPath(__DIR__ . '/fixtures/tailwindcss/tailwind.config.js');
        $this->filter->setWorkingDirectory(__DIR__ . '/fixtures/tailwindcss');
        $this->filter->minify();
        $this->filter->filterLoad($fileAsset);
        $contents = $fileAsset->getContent();

        // Detect boilerplate TailwindCSS styling
        $expected = <<<'STYLE'
            *,::backdrop,:after,:before{--tw-border-spacing-x:0;--tw-border-spacing-y:0;--tw-translate-x:0;--tw-translate-y:0;
            STYLE;

        $this->assertStringContainsString($expected, $contents);

        // Detect class defined in HTML
        $expected = <<<'STYLE'
            .text-gray-800{--tw-text-opacity:1;color:rgb(31 41 55/var(--tw-text-opacity))}
            STYLE;

        $this->assertStringContainsString($expected, $contents);
    }
}
