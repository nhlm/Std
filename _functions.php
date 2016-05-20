<?php
namespace
{
    !defined('DS')   and define('DS', DIRECTORY_SEPARATOR);
    !defined('VOID') and define('VOID', "\0");

    /**
     * Return Unmodified Argument
     *
     * usage:
     *
     *    __(new Classes())->callMethods();
     *
     * @param mixed $var
     * @return mixed
     */
    function __($var)
    {
        return $var;
    }
}

namespace Poirot\Std\Invokable
{
    use Poirot\Std\Interfaces\Pact\ipInvokableCallback;

    /**
     * Resolve Arguments Matched With Callable Arguments
     *
     * @param callable           $callable
     * @param array|\Traversable $parameters Params to match with function arguments
     * 
     * @return \Closure
     */
    function resolveArguments(/*callable*/ $callable, $parameters = array())
    {
        $matchedArguments = array();
        
        $reflection = reflectCallable($callable);
        foreach ($reflection->getParameters() as $argument)
        {
            /** @var \ReflectionParameter $argument */
            
            if ($argument->isDefaultValueAvailable())
                $argValue = $argument->getDefaultValue();

            ## resolve argument value match with name given in parameters list
            $argName = $argument->getName();
            if (array_key_exists($argName, $parameters)) {
                ### use value of given parameters
                $argValue = $parameters[$argName];
                unset($parameters[$argName]);
            }
            
            if (!isset($argValue))
                throw new \InvalidArgumentException(sprintf(
                    'Callable (%s) has no match found on parameter (%s) from (%s) list.'
                    , $reflection->getName(), $argument->getName(), \Poirot\Std\flatten($parameters)
                ));

            $matchedArguments[$argument->getName()] = $argValue;
        }
        
        
        $callbackResolved = function() use ($callable, $matchedArguments) {
            return call_user_func_array($callable, $matchedArguments);
        };
        
        return $callbackResolved;
    }
    
    /**
     * Factory Reflection From Given Callable
     *
     * $function
     *   'function_name' | \closure
     *   'classname::method'
     *   [className_orObject, 'method_name']
     *
     * @param $callable
     *
     * @throws \ReflectionException
     * @return \ReflectionFunction|\ReflectionMethod
     */
    function reflectCallable($callable)
    {
        if (!is_callable($callable))
            throw new \InvalidArgumentException(sprintf(
                'Argument provided is not callable; given: (%s).'
                , \Poirot\Std\flatten($callable)
            ));


        if ($callable instanceof ipInvokableCallback)
            $callable = $callable->getCallable();

        if (is_array($callable))
            ## [className_orObject, 'method_name']
            $reflection = new \ReflectionMethod($callable[0], $callable[1]);

        if (is_string($callable)) {
            if (strpos($callable, '::'))
                ## 'classname::method'
                $reflection = new \ReflectionMethod($callable);
            else
                ## 'function_name'
                $reflection = new \ReflectionFunction($callable);
        }

        if (method_exists($callable, '__invoke')) {
            ## Closure and Invokable
            if ($callable instanceof \Closure)
                $reflection = new \ReflectionFunction($callable);
            else
                $reflection = new \ReflectionMethod($callable, '__invoke');
        }

        if (!isset($reflection))
            throw new \ReflectionException;

        return $reflection;
    }
}

namespace Poirot\Std
{
    use Closure;
    use ReflectionFunction;

    use Poirot\Std\Type\StdArray;
    use Poirot\Std\Type\StdString;
    use Poirot\Std\Type\StdTravers;
    
    /**
     * Cast Given Value Into SplTypes
     * SplTypes Contains Some Utility For That Specific Type
     *
     * ! when you want to force cast to string is necessary to
     *   use type casting cast((string) 10)
     *  
     * @param mixed $type
     *
     * @throws \UnexpectedValueException
     * @return StdString|StdArray|StdTravers|\SplType
     */
    function cast($type)
    {
        switch(1) {
            case isStringify($type):
                $return = new StdString($type);
                break;
            case is_array($type):
                $return = new StdArray($type);
                break;
            case ($type instanceof \Traversable):
                $return = new StdTravers($type);
                break;

            default: throw new \UnexpectedValueException(sprintf(
                'Type (%s) is unexpected.', gettype($type)
            ));
        }

        return $return;
    }

    /**
     * With null|false Data Return Default Value
     * Elsewhere Return Data
     *
     * @param null|false|mixed $data
     * @param mixed            $default
     *
     * @return mixed
     */
    function emptyCoalesce($data, $default)
    {
        return ($data === null || $data === false) ? $default : $data;
    }

    /**
     * Check Variable/Object Is String
     *
     * @param mixed $var
     *
     * @return bool
     */
    function isStringify($var)
    {
        return ( (!is_array($var)) && (
            (!is_object($var) && @settype($var, 'string') !== false) ||
            (is_object($var)  && method_exists($var, '__toString' ))
        ));
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
        if ($value instanceof Closure) {
            $closureReflection = new ReflectionFunction($value);
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
