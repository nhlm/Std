<?php
namespace Poirot\Std\Struct;

use Poirot\Std\Interfaces\Struct\iDataEntity;

/*
$data = [ 'name' => 'no_name' ];

$dp   = new P\Std\Struct\DataPointerArray($data);
$dp->set('name', 'Creativity');

$data[]= 'An Other Member'; // manipulating data array itself change content of entity

$x = &$dp->get('name');
$x = 'Power';

print_r($data);                      // Array ( [name] => Power [0] => An Other Member )
print_r(P\Std\cast($dp)->toArray()); // Array ( [name] => Power [0] => An Other Member )
*/


/*
session_start();
$_SESSION['realm'] = [];
$dp   = new P\Std\Struct\DataPointerArray($_SESSION['realm']);
$dp->set('name', 'Creativity');

print_r($_SESSION);
print_r(P\Std\cast($dp)->toArray());
*/

final class DataPointerArray 
    implements iDataEntity
{
    protected $pointer;
    
    /**
     * DataPointerArray constructor.
     * @param array $data
     */
    function __construct(array &$data)
    {
        $this->pointer = &$data;
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
        // TODO implement with yield
        $p = &$this->__attainInternalPointer();
        return new \ArrayIterator($p);
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
        $p = &$this->__attainInternalPointer();
        return count($p);
    }

    /**
     * Set Struct Data From Array
     *
     * @param array|\Traversable|null $data
     * @return $this
     * @throws \Exception
     */
    function import($data)
    {
        throw new \Exception('Not Implemented.');
    }

    /**
     * Empty from all values
     * @return $this
     */
    function clean()
    {
        $p = &$this->__attainInternalPointer();
        $p = array();
        return $this;
    }

    /**
     * Is Empty?
     * @return bool
     */
    function isEmpty()
    {
        return $this->count() === 0;
    }

    /**
     * NULL value for a property considered __isset false
     * @param mixed $key
     * @return bool
     */
    function has($key)
    {
        $p = &$this->__attainInternalPointer();
        return isset($p[$key]);
    }

    /**
     * NULL value for a property considered __isset false
     * @param mixed $key
     * @return $this
     */
    function del($key)
    {
        if ($this->has($key)) {
            $p = &$this->__attainInternalPointer();
            unset($p[$key]);
        }

        return $this;
    }

    /**
     * Set Entity
     *
     * - values that set to null must be unset from entity
     *
     * @param mixed $key Entity Key
     * @param mixed|null $value Entity Value
     *                          NULL value for a property considered __isset false
     *
     * @return $this
     */
    function set($key, $value)
    {
        $p = &$this->__attainInternalPointer();
        $p[$key] = $value;
        return $this;
    }

    /**
     * Get Entity Value
     *
     * @param mixed $key Entity Key
     * @param null $default Default If Not Value/Key Exists
     * 
     * @return mixed|null NULL value for a property considered __isset false
     */
    function &get($key, $default = null)
    {
        if (!$this->has($key))
            return $default;

        $p = &$this->__attainInternalPointer();
        return $p[$key];
    }

    function &__attainInternalPointer()
    {
        return $this->pointer;
    }
}
