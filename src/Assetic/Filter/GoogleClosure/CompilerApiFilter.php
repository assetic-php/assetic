<?php

namespace Assetic\Filter\GoogleClosure;

use Assetic\Contracts\Asset\AssetInterface;

/**
 * Filter for the Google Closure Compiler API.
 *
 * @link https://developers.google.com/closure/compiler/
 * @author Kris Wallsmith <kris.wallsmith@gmail.com>
 */
class CompilerApiFilter extends BaseCompilerFilter
{
    public const CLOSURE_COMPILER_API = 'https://closure-compiler.appspot.com/compile';

    private $proxy;
    private $proxyFullUri;

    public function setProxy($proxy)
    {
        $this->proxy = $proxy;
    }

    public function setProxyFullUri($proxyFullUri)
    {
        $this->proxyFullUri = $proxyFullUri;
    }

    public function filterDump(AssetInterface $asset)
    {
        $query = array(
            'js_code'       => $asset->getContent(),
            'output_format' => 'json',
            'output_info'   => 'compiled_code',
        );

        if (null !== $this->compilationLevel) {
            $query['compilation_level'] = $this->compilationLevel;
        }

        if (null !== $this->jsExterns) {
            $query['js_externs'] = $this->jsExterns;
        }

        if (null !== $this->externsUrl) {
            $query['externs_url'] = $this->externsUrl;
        }

        if (null !== $this->excludeDefaultExterns) {
            $query['exclude_default_externs'] = $this->excludeDefaultExterns ? 'true' : 'false';
        }

        if (null !== $this->formatting) {
            $query['formatting'] = $this->formatting;
        }

        if (null !== $this->useClosureLibrary) {
            $query['use_closure_library'] = $this->useClosureLibrary ? 'true' : 'false';
        }

        if (null !== $this->warningLevel) {
            $query['warning_level'] = $this->warningLevel;
        }

        if (null !== $this->language) {
            $query['language'] = $this->language;
        }

        if (preg_match('/1|yes|on|true/i', ini_get('allow_url_fopen'))) {
            $contextOptions = array('http' => array(
                'method'  => 'POST',
                'header'  => 'Content-Type: application/x-www-form-urlencoded',
                'content' => http_build_query($query),
            ));
            if (null !== $this->timeout) {
                $contextOptions['http']['timeout'] = $this->timeout;
            }
            if ($this->proxy) {
                $contextOptions['http']['proxy'] = $this->proxy;
                $contextOptions['http']['request_fulluri'] = (bool) $this->proxyFullUri;
            }
            $context = stream_context_create($contextOptions);

            $response = file_get_contents(static::CLOSURE_COMPILER_API, false, $context);
            $data = json_decode($response);
        } elseif (defined('CURLOPT_POST') && !in_array('curl_init', explode(',', ini_get('disable_functions')))) {
            $ch = curl_init(static::CLOSURE_COMPILER_API);
            curl_setopt($ch, CURLOPT_POST, true);
            curl_setopt($ch, CURLOPT_HTTPHEADER, array('Content-type: application/x-www-form-urlencoded'));
            curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
            curl_setopt($ch, CURLOPT_POSTFIELDS, http_build_query($query));
            curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, 15);
            if (null !== $this->timeout) {
                curl_setopt($ch, CURLOPT_TIMEOUT, $this->timeout);
            }
            if ($this->proxy) {
                curl_setopt($ch, CURLOPT_HTTPPROXYTUNNEL, true);
                curl_setopt($ch, CURLOPT_PROXY, $this->proxy);
            }
            $response = curl_exec($ch);
            curl_close($ch);

            $data = json_decode($response);
        } else {
            throw new \RuntimeException("There is no known way to contact closure compiler available");
        }

        if (isset($data->serverErrors) && 0 < count($data->serverErrors)) {
            // @codeCoverageIgnoreStart
            throw new \RuntimeException(sprintf('The Google Closure Compiler API threw some server errors: ' . print_r($data->serverErrors, true)));
            // @codeCoverageIgnoreEnd
        }

        if (isset($data->errors) && 0 < count($data->errors)) {
            // @codeCoverageIgnoreStart
            throw new \RuntimeException(sprintf('The Google Closure Compiler API threw some errors: ' . print_r($data->errors, true)));
            // @codeCoverageIgnoreEnd
        }

        $asset->setContent($data->compiledCode);
    }
}
