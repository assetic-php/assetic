<?php

namespace Assetic\Test\Filter;

use PHPUnit\Framework\TestCase;
use Assetic\Asset\FileAsset;
use Assetic\Filter\CssImportFilter;
use Assetic\Filter\CssRewriteFilter;

class CssImportFilterTest extends TestCase
{
    /**
     * @dataProvider getFilters
     */
    public function testImport($filter1, $filter2)
    {
        $asset = new FileAsset(__DIR__ . '/fixtures/cssimport/main.css', [], __DIR__ . '/fixtures/cssimport', 'main.css');
        $asset->setTargetPath('foo/bar.css');
        $asset->ensureFilter($filter1);
        $asset->ensureFilter($filter2);

        $expected = <<<CSS
/* main.css */
/* import.css */
body { color: red; }

/* more/evenmore/deep1.css */
/* more/evenmore/deep2.css */
body {
    background: url(../more/evenmore/bg.gif);
}


body { color: black; }

CSS;

        $this->assertEquals($expected, $asset->dump(), '->filterLoad() inlines CSS imports');
    }

    /**
     * The order of these two filters is only interchangeable because one acts on
     * load and the other on dump. We need a more scalable solution.
     */
    public function getFilters()
    {
        return array(
            array(new CssImportFilter(), new CssRewriteFilter()),
            array(new CssRewriteFilter(), new CssImportFilter()),
        );
    }

    public function testImportValidatorCanRejectImports()
    {
        $asset = new FileAsset(__DIR__ . '/fixtures/cssimport/main.css', [], __DIR__ . '/fixtures/cssimport', 'main.css');
        $asset->load();

        $filter = new CssImportFilter();
        $filter->setImportValidator(function ($path) {
            return false;
        });
        $filter->filterLoad($asset);

        // Rejected imports are left as raw @import statements and never inlined.
        $this->assertStringNotContainsString('body { color: red; }', $asset->getContent());
        $this->assertStringContainsString('@import "import.css";', $asset->getContent());
    }

    public function testImportValidatorReceivesResolvedPathAndCanAllow()
    {
        $asset = new FileAsset(__DIR__ . '/fixtures/cssimport/main.css', [], __DIR__ . '/fixtures/cssimport', 'main.css');
        $asset->load();

        $seen = [];
        $filter = new CssImportFilter();
        $filter->setImportValidator(function ($path) use (&$seen) {
            $seen[] = $path;
            return true;
        });
        $filter->filterLoad($asset);

        // Allowed imports inline as normal, and the validator sees a resolved path.
        $this->assertStringContainsString('body { color: red; }', $asset->getContent());
        $this->assertNotEmpty($seen);
        $this->assertStringContainsString('import.css', implode('|', $seen));
    }

    public function testNonCssImport()
    {
        $asset = new FileAsset(__DIR__ . '/fixtures/cssimport/noncssimport.css', [], __DIR__ . '/fixtures/cssimport', 'noncssimport.css');
        $asset->load();

        $filter = new CssImportFilter();
        $filter->filterLoad($asset);

        $this->assertEquals(file_get_contents(__DIR__ . '/fixtures/cssimport/noncssimport.css'), $asset->getContent(), '->filterLoad() skips non css');
    }

    /**
     * @dataProvider getFilters
     */
    public function testCommentedImport($filter1, $filter2)
    {
        $asset = new FileAsset(__DIR__ . '/fixtures/cssimport/commentedimport.css', [], __DIR__ . '/fixtures/cssimport', 'commentedimport.css');
        $asset->setTargetPath('foo/bar.css');
        $asset->ensureFilter($filter1);
        $asset->ensureFilter($filter2);

        $expected = <<<CSS
/* commentedimport.css */
/*@import "import.css";*/
/* more/evenmore/deep1.css */
/* more/evenmore/deep2.css */
body {
    background: url(../more/evenmore/bg.gif);
}


body { color: black; }

CSS;

        $this->assertEquals($expected, $asset->dump(), '->filterLoad() inlines CSS imports');
    }
}
