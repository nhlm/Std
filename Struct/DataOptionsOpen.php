<?php
namespace Poirot\Std\Struct;

use ArrayIterator;

use Poirot\Std\Interfaces\Struct\iDataOptions;
use Poirot\Std\Struct\aDataOptions\PropObject;

class DataOptionsOpen 
    extends aDataOptions
    implements iDataOptions
{
    /**
     * @var array
     */
    protected $properties = array();

    /**
     * Proxy for [set/get]Options()
     *
     * @param $method
     * @param $arguments
     *
     * @throws \ErrorException
     * @return $this|bool|mixed
     */
    function __call($method, $arguments)
    {
        $return = null;
        foreach(array('set', 'get', 'is') as $action)
            if (strpos($method, $action) === 0) {
                ## method setter/getter/is found
                $return = true;
                break;
            }

        if ($return === null) {
            $debugTrace = debug_backtrace();
            // TODO test debug backtrace existance
            throw new \ErrorException(sprintf(
                'Call to undefined method (%s).'
                , $method
            ), 0, 1, $debugTrace[1]['file'], $debugTrace[1]['line']);
        }


        // Option Name:
        $name = $method;
        $name = substr($name, -(strlen($name)-strlen($action))); // x for set/get
        $name = $this->_normalize($name, 'external');

        // Take Action:
        switch ($action) {
            case 'set':
                // init option value:
                if (empty($arguments))
                    $arguments[0] = null;

                $this->__set($name, $arguments[0]);
                $return = $this;
                break;

            case 'is':
                // TODO now property can catch with both get[Prop] & is[Prop]
            case 'get':
                $return = $this->__get($name);
                break;
        }

        return $return;
    }

    /**
     * - VOID values will unset attribute
     * @param string $key
     * @param mixed $value
     * @throws \Exception
     */
    function __set($key, $value)
    {
        $key = $this->_normalize($key, 'external');

        if ($setter = $this->_getSetterIfHas($key))
            ## using setter method
            $this->$setter($value);

        if (in_array('set'.$this->_normalize($key, 'internal'), $this->doWhichMethodIgnored()))
            throw new \Exception(sprintf(
                'The Property (%s) is not Writable.'
                , $key
            ));

        if ($value === null)
            unset($this->properties[$key]);
        else
            $this->properties[$key] = $value;
    }

    /**
     * @param string $key
     *
     * @throws \Exception
     * @return mixed
     */
    function __get($key)
    {
        $key = $this->_normalize($key, 'external');

        $return = null;
        if ($getter = $this->_getGetterIfHas($key))
            ## get from getter method
            $return = $this->$getter();
        elseif (array_key_exists($key, $this->properties)
            ## not ignored
            && !in_array('get'.$this->_normalize($key, 'internal'), $this->doWhichMethodIgnored())
        )
            $return = $this->properties[$key];

        return $return;
    }

    /**
     * @param string $key
     * @return void
     */
    function __unset($key)
    {
        $key = $this->_normalize($key, 'external');

        if ($setter = $this->_getSetterIfHas($key))
            try{
                ## some times it can be set to null because of argument type definition
                $this->__set($key, null);
            } catch (\Exception $e) {}
        else {
            if (array_key_exists($key, $this->properties))
                unset($this->properties[$key]);
        }
    }

    /**
     * Get Options Properties Information
     *
     */
    protected function _getProperties()
    {
        // DO_LEAST_PHPVER_SUPPORT v5.5
        if (version_compare(phpversion(), '5.5.0') < 0)
            ## php version not support yield
            return $this->_fix_getProperties();

        return $this->_gen_getProperties();
    }

    protected function _fix_getProperties()
    {
        $methodProps  = parent::_fix_getProperties();

        $props = array();

        // Methods as Options:
        foreach($methodProps as $k => $p)
            $props[$k] = $p;

        // Property Open Options:
        foreach(array_keys($this->properties) as $propertyName)
        {
            if (in_array($propertyName, $props))
                continue;

            foreach(array('set', 'get', 'is') as $prefix) {
                # check for ignorant
                $method = $prefix . $this->_normalize($propertyName, 'internal');
                if (in_array($method, $this->doWhichMethodIgnored()))
                    ## it will use as internal option method
                    continue;

                // mark readable/writable for property
                (isset($props[$propertyName])) ?: $props[$propertyName] = new PropObject($propertyName);
                ($prefix == 'set')
                    ? $props[$propertyName]->setWritable()
                    : $props[$propertyName]->setReadable()
                ;
            }
        }

        return new ArrayIterator($props);
    }

    protected function _gen_getProperties()
    {
        // DO_LEAST_PHPVER_SUPPORT v7.0 yield from
        $yielded = array();
        foreach (parent::_gen_getProperties() as $k => $p) {
            $yielded[] = $k;
            yield $k => $p;
        }

        // Property Open Options:

        foreach(array_keys($this->properties) as $propertyName)
        {
            if (in_array($propertyName, $yielded))
                continue;

            $property = null;

            foreach(array('set', 'get', 'is') as $prefix) {
                # check for ignorant
                $method = $prefix . $this->_normalize($propertyName, 'internal');
                if (in_array($method, $this->doWhichMethodIgnored()))
                    ## it will use as internal option method
                    continue;

                // mark readable/writable for property
                $property = new PropObject($propertyName);
                ($prefix == 'set')
                    ? $property->setWritable()
                    : $property->setReadable()
                ;
            }

            if ($property !== null)
                yield $property->getKey() => $property;
        }
    }
}
