<?php

namespace Assetic\Test\Filter;

use PHPUnit\Framework\TestCase;
use Assetic\Asset\MockAsset;
use Assetic\Filter\StylesheetMinifyFilter;

class StylesheetMinifyFilterTest extends TestCase
{
    public function testSpaceRemoval()
    {
        $input  = 'body{width: calc(99.9% * 1/1 - 0px); height: 0px;}';
        $output = 'body{width:calc(99.9% * 1/1 - 0px);height:0}';

        $mockAsset = new MockAsset($input);
        $result    = new StylesheetMinifyFilter();
        $result->filterDump($mockAsset);

        $this->assertEquals($output, $mockAsset->getContent());
    }

    public function testEmptyClassPreserve()
    {
        $input = <<<EOS
        .view { /*
            * Text
            */
            /*
            * Links
            */
            /*
            * Table
            */
            /*
            * Table cell
            */
            /*
            * Images
            */ }
EOS;
        $output = '.view{}';

        $mockAsset = new MockAsset($input);
        $result    = new StylesheetMinifyFilter();
        $result->filterDump($mockAsset);

        $this->assertEquals($output, $mockAsset->getContent());
    }

    public function testSpecialCommentPreservation()
    {
        $input  = 'body {/*! Keep me */}';
        $output = 'body{/*! Keep me */}';

        $mockAsset = new MockAsset($input);
        $result = new StylesheetMinifyFilter();
        $result->filterDump($mockAsset);

        $this->assertEquals($output, $mockAsset->getContent());
    }

    public function testUniversalSelectorFollowComment()
    {
        $input  = '/*! Keep me */*{box-sizing:border-box}/* Remove me */.noborder{border:0}';
        $output = '/*! Keep me */*{box-sizing:border-box}.noborder{border:0}';

        $mockAsset = new MockAsset($input);
        $result = new StylesheetMinifyFilter();
        $result->filterDump($mockAsset);

        $this->assertEquals($output, $mockAsset->getContent());
    }

    public function testCommentRemoval()
    {
        $input  = 'body{/* First comment */} /* Second comment */';
        $output = 'body{}';

        $mockAsset = new MockAsset($input);
        $result    = new StylesheetMinifyFilter();
        $result->filterDump($mockAsset);

        $this->assertEquals($output, $mockAsset->getContent());
    }

    public function testCommentPreservationInVar()
    {
        $input  = '--ring-inset: var(--empty, /*!*/ /*!*/);';
        $output = '--ring-inset:var(--empty,/*!*/ /*!*/);';

        $mockAsset = new MockAsset($input);
        $result    = new StylesheetMinifyFilter();
        $result->filterDump($mockAsset);

        $this->assertEquals($output, $mockAsset->getContent());
    }

    public function testUnitPreservationInVar()
    {
        $input  = '--offset-width: 0px';
        $output = '--offset-width:0';

        $mockAsset = new MockAsset($input);
        $result    = new StylesheetMinifyFilter();
        $result->filterDump($mockAsset);

        $this->assertEquals($output, $mockAsset->getContent());
    }

    public function testAttributeSelectorsWithLess()
    {
        $input = <<<EOS
            [class^="icon-"]:before,
            [class*=" icon-"]:before {
                speak: none;
            }
            /* makes the font 33% larger relative to the icon container */
            .icon-large:before {
                speak: initial;
            }
EOS;

        $output = <<<EOS
[class^="icon-"]:before,[class*=" icon-"]:before{speak:none}.icon-large:before{speak:initial}
EOS;

        $mockAsset = new MockAsset($input);
        $result    = new StylesheetMinifyFilter();
        $result->filterDump($mockAsset);

        $this->assertEquals($output, $mockAsset->getContent());
    }

    public function testHexMinification()
    {
        $asset = new \Assetic\Asset\StringAsset('body { color: #00000088; background: #ffffff; border: #012345; box-shadow: 0 0 0 1px #01234567; }');
        $asset->load();

        $filter = new StylesheetMinifyFilter();
        $filter->filterDump($asset);

        $this->assertEquals('body{color:#0008;background:#fff;border:#012345;box-shadow:0 0 0 1px #01234567}', $asset->getContent());
    }
}
