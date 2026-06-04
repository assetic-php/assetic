<?php

namespace Assetic\Asset;

use Assetic\Contracts\Filter\FilterInterface;
use Assetic\Util\VarUtils;

/**
 * Represents an asset loaded via an HTTP request.
 *
 * @author Kris Wallsmith <kris.wallsmith@gmail.com>
 */
class HttpAsset extends BaseAsset
{
    private $sourceUrl;
    private $ignoreErrors;

    /**
     * Constructor.
     *
     * @param string  $sourceUrl    The source URL
     * @param array   $filters      An array of filters
     * @param Boolean $ignoreErrors
     * @param array   $vars
     *
     * @throws \InvalidArgumentException If the first argument is not an URL
     */
    public function __construct($sourceUrl, $filters = [], $ignoreErrors = false, array $vars = [])
    {
        if (0 === strpos($sourceUrl ?: '', '//')) {
            $sourceUrl = 'http:' . $sourceUrl;
        } elseif (false === strpos($sourceUrl ?: '', '://')) {
            throw new \InvalidArgumentException(sprintf('"%s" is not a valid URL.', $sourceUrl));
        }

        $this->sourceUrl = $sourceUrl;
        $this->ignoreErrors = $ignoreErrors;

        list($scheme, $url) = explode('://', $sourceUrl, 2);
        list($host, $path) = explode('/', $url, 2);

        parent::__construct($filters, $scheme . '://' . $host, $path, $vars);
    }

    public function load(?FilterInterface $additionalFilter = null)
    {
        $content = @file_get_contents(
            VarUtils::resolve($this->sourceUrl, $this->getVars(), $this->getValues())
        );

        if (false === $content && !$this->ignoreErrors) {
            throw new \RuntimeException(sprintf('Unable to load asset from URL "%s"', $this->sourceUrl));
        }

        $this->doLoad($content, $additionalFilter);
    }

    public function getLastModified()
    {
        if (false !== @file_get_contents($this->sourceUrl, false, stream_context_create(array('http' => array('method' => 'HEAD'))))) {
            // http_get_last_response_headers() was added in PHP 8.4; older versions fall back
            // to the predefined $http_response_header. The `?? []` coalescing read avoids the
            // PHP 8.5 deprecation of that variable: a bare read is flagged at compile time,
            // a coalescing read is not. Drop the fallback once the minimum PHP version is >= 8.4.
            $headers = function_exists('http_get_last_response_headers')
                ? http_get_last_response_headers()
                : ($http_response_header ?? []);
            foreach ($headers as $header) {
                if (0 === stripos($header, 'Last-Modified: ')) {
                    list(, $mtime) = explode(':', $header, 2);

                    return strtotime(trim($mtime));
                }
            }
        }
    }
}
