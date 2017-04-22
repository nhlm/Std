<?php
namespace Poirot\Std\Struct;

use Poirot\Std\ConfigurableSetter;
use Poirot\Std\Hydrator\HydrateGetters;
use Poirot\Std\Interfaces\Struct\iValueObject;

abstract class aValueObject
    extends ConfigurableSetter
    implements iValueObject
{

    // Implement Getters For Iterator Hydrate:
    // ...



    // Implement Configurable

    /**
     * Build Object With Provided Options
     *
     * @param array $options        Associated Array
     * @param bool  $throwException Throw Exception On Wrong Option
     *
     * @return array Remained Options (if not throw exception)
     * @throws \Exception
     * @throws \InvalidArgumentException
     */
    function with(array $options, $throwException = false)
    {
        parent::with($options, $throwException);
    }


    /**
     * Load Build Options From Given Resource
     *
     * - usually it used in cases that we have to support
     *   more than once configure situation
     *   [code:]
     *     Configurable->with(Configurable::withOf(path\to\file.conf))
     *   [code]
     *
     * !! With this The classes that extend this have to
     *    implement desired parse methods
     *
     * @param array|mixed $optionsResource
     * @param array $_
     *        usually pass as argument into ::with if self instanced
     *
     * @throws \InvalidArgumentException if resource not supported
     * @return array
     */
    static function parseWith($optionsResource, array $_ = null)
    {
        if (!static::isConfigurableWith($optionsResource))
            throw new \InvalidArgumentException(sprintf(
                'Invalid Configuration Resource provided; given: (%s).'
                , \Poirot\Std\flatten($optionsResource)
            ));


        // ..

        if ($optionsResource instanceof \Traversable)
            $optionsResource = \Poirot\Std\cast($optionsResource)->toArray();
        elseif ($optionsResource instanceof \stdClass)
            $optionsResource = \Poirot\Std\toArrayObject($optionsResource);

        return $optionsResource;
    }

    /**
     * Is Configurable With Given Resource
     * @ignore
     *
     * @param mixed $optionsResource
     *
     * @return boolean
     */
    static function isConfigurableWith($optionsResource)
    {
        return is_array($optionsResource)
        || $optionsResource instanceof \Traversable
        || $optionsResource instanceof \stdClass
            ;
    }


    // Implement IteratorAggregate

    /**
     * @ignore Ignore Determine as Property By Hydrate Getter
     *
     * Retrieve an external iterator
     * @link http://php.net/manual/en/iteratoraggregate.getiterator.php
     * @return \Traversable An instance of an object implementing <b>Iterator</b> or
     * <b>Traversable</b>
     * @since 5.0.0
     */
    function getIterator()
    {
        $hydrate = new HydrateGetters($this);
        return $hydrate;
    }
}
