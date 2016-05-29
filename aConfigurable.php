<?php
namespace Poirot\Std;

use Poirot\Std\Interfaces\Pact\ipConfigurable;

abstract class aConfigurable
    implements ipConfigurable
{
    /**
     * Construct
     *
     * @param array|\Traversable $options
     */
    function __construct($options = null)
    {
        if (!empty($options) && $options !== null)
            $this->with($options);
    }

    /**
     * Build Object With Provided Options
     *
     * @param array|\Traversable $options        Associated Array
     * @param bool               $throwException Throw Exception On Wrong Option
     *
     * @return $this
     * @throws \Exception
     * @throws \InvalidArgumentException
     */
    abstract function with($options, $throwException = false);

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
     * @param array|mixed $optionsResource
     * @param array       $_
     *        usually pass as argument into ::with if self instanced
     * 
     * @throws \InvalidArgumentException if resource not supported
     * @return array
     */
    static function parseWith($optionsResource, array $_ = null)
    {
        if (!static::isConfigurableWith($optionsResource))
            throw new \InvalidArgumentException(sprintf(
                'Invalid Resource provided; given: (%s).'
                , \Poirot\Std\flatten($optionsResource)
            ));
        
        // ..
        

        return $optionsResource;
    }

    /**
     * Is Configurable With Given Resource
     *
     * @param mixed $optionsResource
     *
     * @return boolean
     */
    static function isConfigurableWith($optionsResource)
    {
        return is_array($optionsResource) || $optionsResource instanceof \Traversable;
    }
}
