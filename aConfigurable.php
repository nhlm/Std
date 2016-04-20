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
        if ($setter !== null)
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
     *
     * @throws \InvalidArgumentException if resource not supported
     * @return array
     */
    static function withOf($optionsResource)
    {
        if (!is_array($optionsResource))
            throw new \InvalidArgumentException(sprintf(
                'Resource must be an array, given: (%s).'
                , \Poirot\Std\flatten($optionsResource)
            ));

        return $optionsResource;
    }
}
