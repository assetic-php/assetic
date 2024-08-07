<?php

namespace Assetic\Test\Filter\GoogleClosure;

use PHPUnit\Framework\TestCase;
use Assetic\Asset\StringAsset;
use Assetic\Filter\GoogleClosure\CompilerApiFilter;

/**
 * @group integration
 * @group http
 */
class CompilerApiFilterTest extends TestCase
{
    public function testRoundTrip()
    {
        $this->markTestSkipped("Google Closure Compiler REST API has been deprecated");

        $input = <<<EOF
(function() {
function unused(){}
function foo(bar)
{
    var foo = 'foo';

    return foo + bar;
}
alert(foo("bar"));
})();
EOF;

        $expected = <<<EOF
(function() {
  alert("foobar");
})();

EOF;

        $asset = new StringAsset($input);
        $asset->load();

        $filter = new CompilerApiFilter();
        $filter->setCompilationLevel(CompilerApiFilter::COMPILE_SIMPLE_OPTIMIZATIONS);
        $filter->setJsExterns('');
        $filter->setExternsUrl('');
        $filter->setExcludeDefaultExterns(true);
        $filter->setFormatting(CompilerApiFilter::FORMAT_PRETTY_PRINT);
        $filter->setUseClosureLibrary(false);
        $filter->setWarningLevel(CompilerApiFilter::LEVEL_VERBOSE);

        $filter->filterLoad($asset);
        $filter->filterDump($asset);

        $this->assertEquals($expected, $asset->getContent());

        $input = <<<EOF
(function() {
    var int = 123;
    console.log(int);
})();
EOF;

        $expected = <<<EOF
(function() {
  console.log(123);
})();

EOF;

        $asset = new StringAsset($input);
        $asset->load();

        $filter->setLanguage(CompilerApiFilter::LANGUAGE_ECMASCRIPT5);

        $filter->filterLoad($asset);
        $filter->filterDump($asset);

        $this->assertEquals($expected, $asset->getContent());
    }
}
