<?php
namespace Poirot\Std\Hydrator;

use Traversable;

class HydrateGetters
    implements \IteratorAggregate
{
    const HYDRATE_IGNORE = 'ignore';

    /** @var object */
    protected $givenObject;

    protected $ignoredMethods = array();
    protected $excludeNullValues = true;

    protected $_c_ignored_docblock;
    protected $_c_reflection;


    /**
     * // TODO ignore null values
     *
     * HydrateGetters constructor.
     *
     * @param object   $object
     * @param []string $ignoredMethodByName
     */
    function __construct($object, array $ignoredMethodByName = null)
    {
        if (!is_object($object))
            throw new \InvalidArgumentException(sprintf(
                'Hydration give an Object as Argument; given: (%s).'
                , \Poirot\Std\flatten($object)
            ));


        if ($ignoredMethodByName !== null)
            $this->setIgnoredMethods($ignoredMethodByName);

        $this->givenObject = $object;
    }


    /**
     * Ignore Some Method To Considered As Option Property
     *
     * $methodName:
     * 'isFulfilled', 'getSomeMethodName'
     *
     * @param []string $methodName
     *
     * @return $this
     */
    function setIgnoredMethods(array $methodName)
    {
        $ignoredMethods = func_get_args();
        foreach($ignoredMethods as $im)
            $this->ignoredMethods[] = (string) $im;

        return $this;
    }

    /**
     * Get Ignored Methods By Name
     *
     * @return []string
     */
    function getIgnoredMethods()
    {
        return $this->ignoredMethods + $this->_getIgnoredByDocBlock();
    }

    /**
     * Exclude Null Values From Hydration
     *
     * @param bool $flag
     *
     * @return $this
     */
    function setExcludeNullValues($flag = true)
    {
        $this->excludeNullValues = (bool) $flag;
        return $this;
    }

    /**
     * Is Null Values Excluded
     *
     * @return boolean
     */
    function isExcludeNullValues()
    {
        return $this->excludeNullValues;
    }

    // Implement IteratorAggregate

    /**
     * Retrieve an external iterator
     * @link http://php.net/manual/en/iteratoraggregate.getiterator.php
     * @return Traversable An instance of an object implementing <b>Iterator</b> or
     * <b>Traversable</b>
     * @since 5.0.0
     */
    function getIterator()
    {
        return new \ArrayIterator($this->_getGetterProperties());
    }


    // ..

    function _getGetterProperties()
    {
        $ref            = $this->_newReflection();
        $methods        = $ref->getMethods(\ReflectionMethod::IS_PUBLIC);
        $ignoredMethods = $this->getIgnoredMethods();

        $properties = [];
        foreach($methods as $method)
        {
            if (!(
                    ('get'   == substr($method->getName(), 0, 3) && $prefix = 'get')
                ||  ('is'    == substr($method->getName(), 0, 2) && $prefix = 'is' )
            ))
                // It's Not Getter Method
                continue;

            if (in_array($method->getName(), $ignoredMethods))
                // Method is Ignored
                continue;


            ## getAttributeName -> AttributeName
            $propertyName = substr($method->getName(), strlen($prefix));
            $propertyName = $this->_normalizeMethodNameToUnderScore($propertyName);

            try {
                $args = \Poirot\Std\Invokable\resolveArgsForReflection($method, array());
            } catch (\Exception $e) {
                // Ensure That GetterMethod Is Not Resolvable!!
                $args = null;
            }


            if ($args !== null) {
                $value = $method->invokeArgs($this->givenObject, $args);

                if ($this->isExcludeNullValues() && $value === null )
                    // Null Value not included in list
                    continue;

                $properties[$propertyName] = $value;
            }
        }

        return $properties;
    }

    /**
     * Attain Ignored Methods From DockBlock
     *
     * @ignore
     *
     * @return []string
     */
    function _getIgnoredByDocBlock()
    {
        if ($this->_c_ignored_docblock)
            // DocBlock Parsed To Cache Previously !!
            return $this->_c_ignored_docblock;


        $ignoredMethods = array();


        $ref = $this->_newReflection();

        // ignored methods from Class DocComment:
        $classDocComment = $ref->getDocComment();
        if ($classDocComment !== false && preg_match_all('/.*[\n]?/', $classDocComment, $lines)) {
            $lines = $lines[0];
            $regex = '/.+(@method).+((?P<method_name>\b\w+)\(.*\))\s@'.self::HYDRATE_IGNORE.'\s/';
            foreach($lines as $line) {
                if (preg_match($regex, $line, $matches))
                    $ignoredMethods[] = $matches['method_name'];
            }
        }

        // ignored methods from Method DocBlock
        $methods = $ref->getMethods(\ReflectionMethod::IS_PUBLIC);
        foreach($methods as $m) {
            $mc = $m->getDocComment();
            if ($mc !== false && preg_match('/@'.self::HYDRATE_IGNORE.'\s/', $mc, $matches))
                $ignoredMethods[] = $m->getName();
        }

        return $this->_c_ignored_docblock = $ignoredMethods;
    }

    protected function _newReflection()
    {
        if (!$this->_c_reflection)
            $this->_c_reflection = new \ReflectionClass($this->givenObject);

        return $this->_c_reflection;
    }

    protected function _normalizeMethodNameToUnderScore($name)
    {
        return (string) \Poirot\Std\cast($name)->under_score();
    }
}
