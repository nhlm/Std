<?php
namespace Poirot\Std\Struct;

/*
$mean = new MeanData();
$mean->{ (string) this will converted to string by php };

$mean->test = [];

$test = &$mean->test;  // called with & sign
var_dump($test);            // array(0) { }
$test[] = 'insert item';    // if called with & now $test is reference of $mean->test
var_dump($test);            // array(1) { [0]=> string(11) "insert item" }
var_dump($mean->test); // array(1) { [0]=> string(11) "insert item" }
*/

use ArrayIterator;

class DataMean 
    extends aDataAbstract
{
    protected $properties = array();

    /**
     * Set Struct Data From Array
     *
     * @param array|\Traversable $data
     */
    protected function doSetFrom($data)
    {
        foreach($data as $k => $v)
            $this->__set($k, $v);
    }

    /**
     * Retrieve an external iterator
     * @link http://php.net/manual/en/iteratoraggregate.getiterator.php
     * @return \Traversable An instance of an object implementing <b>Iterator</b> or
     * <b>Traversable</b>
     * @since 5.0.0
     */
    public function getIterator()
    {
        // DO_LEAST_PHPVER_SUPPORT
        if (version_compare(phpversion(), '5.5.0') < 0)
            ## php version not support yield
            return $this->_fix_getIterator();

        return $this->_getIterator();
        
        
    }

    // DO_LEAST_PHPVER_SUPPORT v5.5 yield
    protected function _getIterator()
    {
        foreach(array_keys($this->properties) as $key)
            yield $key => $this->__get($key);
    }

    // DO_LEAST_PHPVER_SUPPORT v5.5 yield
    protected function _fix_getIterator()
    {
        $data = array();
        foreach ($data as $k => $v)
            $data[] = array($k => $v);

        return new ArrayIterator($data);
    }

    // Mean Implementation:

    /**
     * NULL value for a property considered __isset false
     * @param string $key
     * @param mixed $value
     * @return void
     */
    function __set($key, $value)
    {
        if ($value === null)
            return $this->del($key);

        $this->properties[$key] = $value;
    }

    /**
     * @param string $key
     * @return mixed
     */
    function &__get($key)
    {
        if (!$this->has($key))
            $this->properties[$key] = null;

        $x = &$this->properties[$key];
        return $x;
    }

    /**
     * NULL value for a property considered __isset false
     * @param string $key
     * @return bool
     */
    function has($key)
    {
        return (array_key_exists($key, $this->properties)) && $this->properties[$key] !== null;
    }

    /**
     * NULL value for a property considered __isset false
     * @param string $key
     * @return bool
     */
    function __isset($key)
    {
        return $this->has($key);
    }

    /**
     * NULL value for a property considered __isset false
     * @param string $key
     * @return void
     */
    function del($key)
    {
        if ($this->__isset($key))
            unset($this->properties[$key]);
    }

    /**
     * NULL value for a property considered __isset false
     * @param string $key
     * @return void
     */
    function __unset($key)
    {
        $this->del($key);
    }

    /**
     * Count elements of an object
     * @link http://php.net/manual/en/countable.count.php
     * @return int The custom count as an integer.
     * </p>
     * <p>
     * The return value is cast to an integer.
     * @since 5.1.0
     */
    public function count()
    {
        return count($this->properties);
    }
}
