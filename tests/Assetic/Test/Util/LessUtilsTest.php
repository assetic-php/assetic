<?php

namespace Assetic\Test\Util;

use PHPUnit\Framework\TestCase;
use Assetic\Util\LessUtils;

class LessUtilsTest extends TestCase
{
    public function testFilterUrls()
    {
        $content = 'body { background: url(../images/bg.gif); }';

        $matches = [];
        $actual = LessUtils::filterUrls($content, function ($match) use (&$matches) {
            $matches[] = $match['url'];
        });

        $this->assertEquals(array('../images/bg.gif'), $matches);
    }

    public function testExtractImports()
    {
        // These don't work yet (todo):
        // @import url("fineprint.css") print;
        // @import url("bluish.css") projection, tv;
        // @import url('landscape.css') screen and (orientation:landscape);

        $content = <<<CSS
@import 'custom.css';
@import "common.css" screen, projection;
body { background: url(../images/bg.gif); }
CSS;

        $expected = array('common.css', 'custom.css');
        $actual = LessUtils::extractImports($content);

        $this->assertEquals($expected, array_intersect($expected, $actual), '::extractImports() returns all expected URLs');
        $this->assertEquals([], array_diff($actual, $expected), '::extractImports() does not return unexpected URLs');
    }

    public function testFilterCommentless()
    {
        $content = 'A/*B*/C/*D*/E';

        $filtered = '';
        $result = LessUtils::filterCommentless($content, function ($part) use (&$filtered) {
            $filtered .= $part;

            return $part;
        });

        $this->assertEquals('ACE', $filtered);
        $this->assertEquals($content, $result);
    }

    public function testFilterCommentlessLess()
    {
        $content = "ACE // foo /* bar */\nbla";

        $filtered = '';
        $result = LessUtils::filterCommentless($content, function ($part) use (&$filtered) {
            $filtered .= $part;

            return $part;
        });

        $this->assertEquals("ACE \nbla", $filtered);
        $this->assertEquals($content, $result);
    }

    public function testExtractImportsWithOption()
    {
        $content = <<<LESS
@import () "empty";
@import (reference) "foo.less";
@import (inline) "not-less-compatible.css";
@import (less) "foo.css";
@import (css) "bar.less";
@import (once) "once.less";
@import (once) url("once_with_url.less");
LESS;

        $expected = array('empty', 'foo.less', 'not-less-compatible.css', 'foo.css', 'bar.less', 'once.less', 'once_with_url.less');
        $actual = LessUtils::extractImports($content);

        $this->assertEquals($expected, array_intersect($expected, $actual), '::extractImports() returns all expected URLs');
        $this->assertEquals([], array_diff($actual, $expected), '::extractImports() does not return unexpected URLs');
    }
}
