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
        if ($options !== null)
            $this->with(static::parseWith($options));
    }

    /**
     * Build Object With Provided Options
     *
     * @param array $options        Associated Array
     * @param bool  $throwException Throw Exception On Wrong Option
     *
     * @return $this
     * @throws \Exception
     * @throws \InvalidArgumentException
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
                'Invalid Configuration Resource provided for (%s); given: (%s).'
                , static::class, \Poirot\Std\flatten($optionsResource)
            ));


        // ..
        
       if ($optionsResource instanceof \Traversable)
           $optionsResource = cast($optionsResource)->toArray();
        
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
