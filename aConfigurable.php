<?php
namespace Poirot\Std;

use Poirot\Std\Interfaces\Pact\ipConfigurable;

abstract class aConfigurable
    implements ipConfigurable
{
    /**
     * Construct
     *
     * @param array $setter
     */
    function __construct(array $setter = null)
    {
        if (!empty($setter) && $setter !== null)
            $this->with($setter);
    }
    
    /**
     * Build Object With Provided Options
     *
     * @param array $options Associated Array
     * @param bool $throwException Throw Exception On Wrong Option
     *
     * @throws \Exception
     * @return $this
     */
    abstract function with(array $options, $throwException = false);

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
        return is_array($optionsResource);
    }
}
