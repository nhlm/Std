<?php
namespace Poirot\Std\Hydrator;

use Poirot\Http\HttpMessage\Request\Plugin\ParseRequestData;
use Poirot\Http\Interfaces\iHttpRequest;
use Poirot\Std\ConfigurableSetter;

class aHydrateEntity
    extends ConfigurableSetter
    implements \IteratorAggregate
{
    /**
     * Construct
     *
     * @param array|\Traversable $options
     * @param array|\Traversable $defaults
     */
    function __construct($options = null, $defaults = null)
    {
        if ($defaults !== null)
            $this->with( static::parseWith($defaults) );

        parent::__construct($options);
    }


    // Implement Hydrate Setter(s):
    // ..


    // Implement Hydrate Getter(s):
    // ..


    // Implement Configurable

    /**
     * @inheritdoc
     *
     * @param array|\Traversable|iHttpRequest $optionsResource
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
                'Invalid Configuration Resource provided; given: (%s).'
                , \Poirot\Std\flatten($optionsResource)
            ));


        // ..

        if ($optionsResource instanceof iHttpRequest)
            # Parse and assert Http Request
            $optionsResource = ParseRequestData::_($optionsResource)->parseBody();

        return parent::parseWith($optionsResource);
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
        return $optionsResource instanceof iHttpRequest || parent::isConfigurableWith($optionsResource);
    }


    // Implement IteratorAggregate

    /**
     * @ignore Ignore from getter hydrator
     *
     * Retrieve an external iterator
     * @link http://php.net/manual/en/iteratoraggregate.getiterator.php
     * @return \Traversable An instance of an object implementing <b>Iterator</b> or
     * <b>Traversable</b>
     * @since 5.0.0
     */
    function getIterator()
    {
        $hydrator = new HydrateGetters($this);
        $hydrator->setExcludeNullValues();

        return $hydrator;
    }
}
