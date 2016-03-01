<?php
namespace {
    !defined('POIROT_CORE_LOADED') and define('POIROT_CORE_LOADED', true);

    !defined('DS') and define('DS', DIRECTORY_SEPARATOR);

    # !! don't store this value, void mean everything nothing
    !defined('VOID') and define('VOID', "\0"/*uniqid('__not_set_value__')*/);
}

namespace Poirot\Std
{
    use Poirot\Std\Type\StdString;

    trait SetterBuilderTrait
    {
        /**
         * @var array List Setters By Priority
         * [
         *  'service_config',
         *  'listeners',
         *  // ...
         * ]
         *
         * application calls setter methods from top ...
         *
         */
        # protected $__setup_array_priority = array();

        /**
         * Setter Setup From Array
         *
         * @param array $setters        Associated Array
         *
         * @param bool  $throwException Throw Exception
         *
         * @throws \Exception
         * @return array Remained Options (if not throw exception)
         */
        function setupFromArray(array $setters, $throwException = false)
        {
            if (empty($setters))
                # nothing to do
                return $this;

            if (array_values($setters) == $setters)
                throw new \InvalidArgumentException(sprintf(
                    'Setters Array must be associative array. given: %s'
                    , var_export($setters, true)
                ));

            if (isset($this->__setup_array_priority)
                && is_array($this->__setup_array_priority)
            ) {
                $sortQuee = $this->__setup_array_priority;
                uksort($setters, function($a, $b) use ($sortQuee) {
                    // sort array to reach setter priorities
                    $ai = array_search($a, $sortQuee);
                    $ai = ($ai !== false) ? $ai : 1000;

                    $bi = array_search($b, $sortQuee);
                    $bi = ($bi !== false) ? $bi : 1000;

                    return $ai < $bi ? -1 : ($ai == $bi) ? 0 : 1;
                });
            }

            $remained = [];
            foreach($setters as $key => $val) {
                $setter = 'set' . sanitize_camelCase($key);
                if (method_exists($this, $setter)) {
                    // check for methods
                    $this->{$setter}($val);
                } elseif($throwException) {
                    throw new \InvalidArgumentException(
                        sprintf(
                            'The option "%s" does not have a matching "%s" setter method',
                            $key, $setter
                        )
                    );
                }
                else
                    $remained[] = $key;
            }

            return $remained;
        }
    }

    /**
     * Cast Given Value Into SplTypes
     * SplTypes Contains Some Utility For That Specific Type
     *
     * @param mixed $type
     *
     * @throws \UnexpectedValueException
     * @return StdString|\SplType
     */
    function cast($type)
    {
        switch(1) {
            case is_string($type): $return = new StdString($type);
                break;

            default: throw new \UnexpectedValueException(sprintf(
                'Type (%s) is unexpected.', gettype($type)
            ));
        }

        return $return;
    }

    /**
     * Check Variable/Object Is String
     *
     * @param mixed $var
     *
     * @return bool
     */
    function is_string($var)
    {
        return (
            (!is_array($var))
            &&
            (
                (!is_object($var) && @settype($var, 'string') !== false)
                ||
                (is_object($var)  && method_exists($var, '__toString' ))
            )
        );
    }

    /**
     * Convert Iterator To An Array
     *
     * filter:
     * // return true mean not present to output array
     * bool function(&$key, &$val);
     *
     * @param \Traversable  $iterator
     * @param \Closure|null $filter
     * @param bool          $deep     Recursively convert values that can be iterated
     *
     * @return array
     */
    function iterator_to_array(\Traversable $iterator, \Closure $filter = null, $deep = true)
    {
        $arr = [];
        foreach($iterator as $key => $val) {
            $flag = false;
            if ($filter !== null)
                $flag = $filter($key, $val);

            if ($flag) continue;

            if ($deep && $val instanceof \Traversable)
                ## deep convert
                $val = iterator_to_array($val, $filter);

            $arr[(string) $key] = $val;
        }

        return $arr;
    }

    /**
     * Merge two arrays together.
     *
     * If an integer key exists in both arrays, the value from the second array
     * will be appended the the first array. If both values are arrays, they
     * are merged together, else the value of the second array overwrites the
     * one of the first array.
     *
     * @param  array $a
     * @param  array $b
     * @return array
     */
    function array_merge(array $a, array $b)
    {
        foreach ($b as $key => $value)
            if (array_key_exists($key, $a)) {
                if (is_int($key)) {
                    if (!in_array($value, $a))
                        $a[] = $value;
                }
                elseif (is_array($value) && is_array($a[$key]))
                    $a[$key] = \Poirot\Std\array_merge($a[$key], $value);
                else
                    $a[$key] = $value;
            } else
                $a[$key] = $value;

        return $a;
    }

    /**
     * Merge two arrays together, reserve previous values
     *
     * @param  array $a
     * @param  array $b
     * @return array
     */
    function array_merge_recursive(array $a, array $b)
    {
        foreach ($b as $key => $value)
            if (array_key_exists($key, $a)) {
                if (is_int($key)) {
                    if (!in_array($value, $a))
                        $a[] = $value;
                }
                elseif (is_array($value) && is_array($a[$key]))
                    $a[$key] = \Poirot\Std\array_merge_recursive($a[$key], $value);
                else {
                    $cv = $a[$key];
                    $a[$key] = [];
                    $pa = &$a[$key];
                    array_push($pa, $cv);
                    array_push($pa, $value);
                }
            } else
                $a[$key] = $value;

        return $a;
    }

    /**
     * Flatten Value
     *
     * @param mixed $value
     *
     * @return string
     */
    function flatten($value)
    {
        if ($value instanceof \Closure) {
            $closureReflection = new \ReflectionFunction($value);
            $value = sprintf(
                '(Closure at %s:%s)',
                $closureReflection->getFileName(),
                $closureReflection->getStartLine()
            );
        } elseif (is_object($value)) {
            $value = sprintf('%s:object(%s)', spl_object_hash($value), get_class($value));
        } elseif (is_resource($value)) {
            $value = sprintf('resource(%s-%s)', get_resource_type($value), $value);
        } elseif (is_array($value)) {
            foreach($value as $k => &$v)
                $v = flatten($v);

            $value = 'Array: ['.implode(', ', $value).']';
        } elseif (is_scalar($value)) {
            $value = sprintf('%s(%s)',gettype($value), $value);
        }

        return $value;
    }
}