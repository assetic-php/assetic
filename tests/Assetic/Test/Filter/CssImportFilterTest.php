<?php

namespace Assetic\Test\Filter;

use PHPUnit\Framework\TestCase;
use Assetic\Asset\AssetCache;
use Assetic\Asset\FileAsset;
use Assetic\Cache\ArrayCache;
use Assetic\Contracts\Filter\HashableInterface;
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

    public function testImportValidatorAuthorisesSchemeBearingImports()
    {
        $asset = new FileAsset(__DIR__ . '/fixtures/cssimport/schemeimport.css', [], __DIR__ . '/fixtures/cssimport', 'schemeimport.css');
        $asset->load();

        $seen = [];
        $filter = new CssImportFilter();
        $filter->setImportValidator(function ($path) use (&$seen) {
            $seen[] = $path;
            return false;
        });
        $filter->filterLoad($asset);

        // A target carrying a scheme resolves to the URL itself rather than a path
        // under the source root, and is dispatched to the remote loader. The
        // validator is consulted for it like any other import form.
        $this->assertContains('file:///etc/hostname', $seen);
        $this->assertContains('//example.com/remote.css', $seen);

        // Rejected imports are left as raw @import statements and never loaded.
        $this->assertStringContainsString('@import url("file:///etc/hostname");', $asset->getContent());
        $this->assertStringContainsString('@import url("//example.com/remote.css");', $asset->getContent());
        $this->assertStringContainsString('body { color: blue; }', $asset->getContent());
    }

    public function testImportsAreUnaffectedWhenNoValidatorIsSet()
    {
        $asset = new FileAsset(__DIR__ . '/fixtures/cssimport/main.css', [], __DIR__ . '/fixtures/cssimport', 'main.css');
        $asset->load();

        $filter = new CssImportFilter();
        $filter->filterLoad($asset);

        // Default behaviour is unchanged for callers that set no validator.
        $this->assertStringContainsString('body { color: red; }', $asset->getContent());
    }

    public function testIsHashableSoTheAssetCacheNeverSerializesIt()
    {
        $filter = new CssImportFilter();
        $filter->setImportValidator(function ($path) {
            return true;
        });

        $this->assertInstanceOf(HashableInterface::class, $filter);
        $this->assertNotEmpty($filter->hash());
    }

    public function testAssetCacheDumpsAnAssetFilteredWithAnImportValidator()
    {
        $filter = new CssImportFilter();
        $filter->setImportValidator(function ($path) {
            return true;
        });

        $asset = new FileAsset(__DIR__ . '/fixtures/cssimport/main.css', [$filter], __DIR__ . '/fixtures/cssimport', 'main.css');
        $cached = new AssetCache($asset, new ArrayCache());

        // The cache key is built from the asset's filters, serializing any that are
        // not hashable. Serializing a closure throws, so a filter holding an import
        // validator has to hash itself or this dump fails outright.
        $this->assertStringContainsString('body { color: red; }', $cached->dump());
    }

    public function testHashIsStableBetweenEquivalentInstances()
    {
        $bare = new CssImportFilter();

        $validated = new CssImportFilter();
        $validated->setImportValidator(function ($path) {
            return true;
        });

        // A stable hash is what keeps the asset cache usable across requests.
        $this->assertSame($bare->hash(), (new CssImportFilter())->hash());
        $this->assertSame($validated->hash(), $validated->hash());
        $this->assertNotSame($bare->hash(), $validated->hash());
    }

    public function testHashDistinguishesValidatorsByBoundConfiguration()
    {
        $allowsA = new CssImportFilter();
        $allowsA->setImportValidator($this->createRootValidator('/allowed/a'));

        $allowsB = new CssImportFilter();
        $allowsB->setImportValidator($this->createRootValidator('/allowed/b'));

        // Validators declared in the same place but confining imports to different
        // roots must not share a cache key, or one filter is served output the other
        // produced under looser rules.
        $this->assertNotSame($allowsA->hash(), $allowsB->hash());

        // An equivalent configuration still hashes alike, so the cache stays warm.
        $this->assertSame($allowsA->hash(), (new CssImportFilter())
            ->setImportValidator($this->createRootValidator('/allowed/a'))
            ->hash());
    }

    public function testHashDistinguishesValidatorsByDeclarationSite()
    {
        $permissive = new CssImportFilter();
        $permissive->setImportValidator(function ($path) {
            return true;
        });

        $restrictive = new CssImportFilter();
        $restrictive->setImportValidator(function ($path) {
            return false;
        });

        $this->assertNotSame($permissive->hash(), $restrictive->hash());
    }

    private function createRootValidator($root)
    {
        return function ($path) use ($root) {
            return strpos($path, $root) === 0;
        };
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
