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
    function resolveArguments(/*callable*/ $callable, $parameters)
    {
        if ($parameters instanceof \Traversable)
            $parameters = \Poirot\Std\cast($parameters)->toArray();
        
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

namespace Poirot\Std\Lexer
{
    /**
     * Tokenize Parse String Definition Into Parts
     * 
     * String in form of:
     *  '[:subdomain{static}.]localhost.:tld{\s+}'
     *  : variable {delimiter}
     *    delimiter .. localhost\.(?P<tld>\s+)
     *  [optional]
     * 
     * TODO: optional in optional; 
     * TODO: skip token \[token] add slash
     * 
     * @param string $string 
     * @return array
     */
    function parseDefinition($string)
    {
        $TOKENS = preg_quote('\\.:{\[\]');

        $currentPos = 0;
        $length     = strlen($string);

        $parts      = array();
        $levelParts = array(&$parts);
        $level      = 0;

        while ($currentPos < $length)
        {
            ## the tokens are .:{[]
            preg_match("(\G(?P<_literal_>[A-Za-z0-9]*)(?P<_token_>[$TOKENS]|$))"
                , $string
                , $matches
                , 0
                , $currentPos
            );

            if (empty($matches)) break;

            $currentPos += strlen($matches[0]);

            if (!empty($matches['_literal_']))
                $levelParts[$level][] = array('_literal_' => $matches['_literal_']);

            # Deal With Token:
            if (!isset($matches['_token_']))
                continue;

            $Token = $matches['_token_'];
            if ($Token === ':') {
                $pmatch = preg_match("(\G(?P<_name_>[^$TOKENS]+)(?:{(?P<_delimiter_>[^}]+)})?:?)"
                    , $string
                    , $matches
                    , 0
                    , $currentPos
                );
                if (!$pmatch)
                    throw new \RuntimeException('Found empty parameter name');

                $parameter = $matches['_name_'];
                $val       = array('_parameter_' => $parameter);
                if (isset($matches['_delimiter_']))
                    $val[$parameter] = $matches['_delimiter_'];

                $levelParts[$level][] = $val;
                $currentPos += strlen($matches[0]);
            }

            elseif ($Token === '\\') {
                // Consider next character as Literal
                // localhost\::port
                $nextChar = $currentPos += 1;
                $levelParts[$level][]   = array('_literal_' => $string[$nextChar]);
            }

            elseif ($Token === '[') {
                $va = array();
                $levelParts[$level][]   = array('_optional_' => &$va);

                $level++;
                $levelParts[$level] = &$va;
            }

            elseif ($Token === ']') {
                unset($levelParts[$level]);
                $level--;

                if ($level < 0)
                    throw new \RuntimeException('Found closing bracket without matching opening bracket');
            } else
                // Recognized unused token return immanently
                $levelParts[$level][]   = array('_token_' => $Token);

        } // end while

        if ($level > 0)
            throw new \RuntimeException('Found unbalanced brackets');

        return $parts;
    }

    /**
     * Build the matching regex from parsed parts.
     *
     * @param array $parts
     *
     * @return string
     */
    function buildRegexFromParsed(array $parts)
    {
        $regex = '';

        // [0 => ['_literal_' => 'localhost'], 1=>['_optional' => ..] ..]
        foreach ($parts as $parsed) {
            $definition_name  = key($parsed);
            $definition_value = $parsed[$definition_name];
            // $parsed can also have extra parsed data options
            // _parameter_ String(3) => tld \
            // tld String(4)         => .com
            switch ($definition_name) {
                case '_token_':
                case '_literal_':
                    $regex .= preg_quote($definition_value);
                    break;

                case '_parameter_':
                    $groupName = '?P<' . $definition_value . '>';

                    if (isset($parsed[$definition_value])) {
                        $regex .= '(' . $groupName . $parsed[$definition_value] . ')';
                    } else{
                        $regex .= '(' . $groupName . '[^.]+)';
                    }

                    break;

                case '_optional_':
                    $regex .= '(?:' . buildRegexFromParsed($definition_value) . ')?';
                    break;
            }
        }

        return $regex;
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
