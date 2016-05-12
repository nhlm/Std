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
     * !! With this The classes that extend this abstract have to
     *    implement desired parse methods
     *
     * @param array|mixed $optionsResource
     * @param array       $_
     *        usually pass as argument into ::with if self instanced
     *
     * @throws \InvalidArgumentException if resource not supported
     * @return array
     */
    static function parseWith($optionsResource, array $_ = null);

    /**
     * Is Configurable With Given Resource
     * 
     * @param mixed $optionsResource
     * 
     * @return boolean
     */
    static function isConfigurableWith($optionsResource);
}
