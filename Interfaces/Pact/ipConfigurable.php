<?php
namespace Poirot\Std\Interfaces\Pact;

interface ipConfigurable
{
    /**
     * Build Object With Provided Options
     *
     * @param array $options        Associated Array
     * @param bool  $throwException Throw Exception On Wrong Option
     *
     * @throws \Exception
     * @return $this
     */
    function with(array $options, $throwException = false);

    /**
     * Load Build Options From Given Resource
     *
     * - usually it used in cases that we have to support
     *   more than once configure situation
     *   [code:]
     *     Configurable->with(Configurable::withOf(path\to\file.conf))
     *   [code]
     *
     *
     * @param array|mixed $resource
     *
     * @throws \InvalidArgumentException if resource not supported
     * @return array
     */
    static function withOf($resource);
}
