<?php

namespace Assetic\Util;

/**
 * Less Utils.
 *
 * @author Kris Wallsmith <kris.wallsmith@gmail.com>
 */
abstract class LessUtils extends CssUtils
{
    public const REGEX_IMPORTS         = '/@import(?:-once)? (?:\([a-z]*\) )?(?:url\()?(\'|"|)(?P<url>[^\'"\)\n\r]*)\1\)?;?/';
    public const REGEX_IMPORTS_NO_URLS = '/@import(?:-once)? (?:\([a-z]*\) )?(?!url\()(\'|"|)(?P<url>[^\'"\)\n\r]*)\1;?/';
    public const REGEX_COMMENTS        = '/((?:\/\*[^*]*\*+(?:[^\/][^*]*\*+)*\/)|\/\/[^\n]+)/';
}
