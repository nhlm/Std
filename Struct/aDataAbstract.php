<?php
namespace Poirot\Std\Struct;

use Poirot\Std\Interfaces\Struct\iData;

abstract class aDataAbstract 
    implements iData
{
    /**
     * AbstractStruct constructor.
     *
     * @param null|array|\Traversable $data
     */
    function __construct($data = null)
    {
        if ($data !== null)
            $this->import($data);
    }

    /**
     * Do Set Data From
     * @param array|\Traversable $data
     */
    abstract protected function doSetFrom($data);

    /**
     * Retrieve an external iterator
     * @link http://php.net/manual/en/iteratoraggregate.getiterator.php
     * @return \Traversable An instance of an object implementing <b>Iterator</b> or
     * <b>Traversable</b>
     * @since 5.0.0
     */
    abstract function getIterator();

    /**
     * Set Struct Data From Array
     *
     * @param array|\Traversable|null $data
     *
     * @throws \InvalidArgumentException
     * @return $this
     */
    function import($data)
    {
        if ($data === null)
            return $this;

        if (!(is_array($data) || $data instanceof \Traversable))
            throw new \InvalidArgumentException(sprintf(
                'Data must be instance of \Traversable or array. given: (%s)'
                , \Poirot\Std\flatten($data)
            ));

        $this->doSetFrom($data);

        return $this;
    }

    /**
     * Empty from all values
     * @return $this
     */
    function clean()
    {
        foreach($this as $k => $v)
            $this->del($k);

        return $this;
    }

    /**
     * Is Empty?
     * @return bool
     */
    function isEmpty()
    {
        $isEmpty = true;
        foreach($this as $v) {
            $isEmpty = false;
            break;
        }

        return $isEmpty;
    }
}
