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
    function resolveCallableWithArgs(/*callable*/$callable, $parameters)
    {
        if ($parameters instanceof \Traversable)
            $parameters = \Poirot\Std\cast($parameters)->toArray();


        $reflection       = reflectCallable($callable);
        $matchedArguments = resolveArgsForReflection($reflection, $parameters);

        $callbackResolved = function() use ($callable, $matchedArguments) {
            return call_user_func_array($callable, $matchedArguments);
        };

        return $callbackResolved;
    }

    /**
     * Resolve Arguments Matched With Reflection Method/Function
     *
     * @param \ReflectionMethod|\ReflectionFunction $reflectFunc
     * @param array|\Traversable                    $parameters  Params to match with function arguments
     * @param bool                                  $inDepth     Resolve argument in depth from parameters; if false just
     *                                                           resolve arguments by name
     *
     * @return array Of Matching Arguments
     */
    function resolveArgsForReflection(/*callable*/$reflectFunc, $parameters, $inDepth = true)
    {
        if (!($reflectFunc instanceof \ReflectionFunction || $reflectFunc instanceof \ReflectionMethod))
            throw new \InvalidArgumentException(sprintf(
                '(%s) is not reflection.'
                , \Poirot\Std\flatten($reflectFunc)
            ));

        $arguments = array();
        foreach ($parameters as $key => $val) {
            if (is_string($key)) {
                // Create Map Of "field_name" to "fieldName" and "FieldName" to Resolve To Callable
                $res = (string) \Poirot\Std\cast($key)->camelCase();
                if (!isset($parameters[$res]))
                    $arguments[strtolower($res)] = $val;

                $arguments[strtolower($key)] = $val;
            }

            $arguments[$key] = $val;
        }


        $matchedArguments = array();
        foreach ($reflectFunc->getParameters() as $reflectArgument)
        {
            /** @var \ReflectionParameter $reflectArgument */
            $argValue = $notSet = uniqid(); // maybe null value is default

            if ($reflectArgument->isDefaultValueAvailable())
                $argValue = $reflectArgument->getDefaultValue();

            $argName = strtolower($reflectArgument->getName());
            if (array_key_exists($argName, $arguments)) {
                ## resolve argument value match with name given in parameters list
                $argValue = $arguments[$argName];
                unset($arguments[$argName]);
            } elseif($inDepth) {
                ## in depth argument resolver
                $av = null;
                foreach ($arguments as $k => $v) {
                    if ( ( $class = $reflectArgument->getClass() ) && is_object($v) && $class->isInstance($v) )
                        $av = $v;

                    if ( $reflectArgument->isArray() && is_array($v) )
                        $av = $v;

                    if ( $reflectArgument->isCallable() && is_callable($v) )
                        $av = $v;

                    if ($av !== null) {
                        unset($arguments[$k]);
                        break;
                    }
                }

                ($av === null) ?: $argValue = $av;
            }

            if ($argValue === $notSet)
                throw new \InvalidArgumentException(sprintf(
                    'Callable (%s) has no match found on parameter (%s) from (%s) list.'
                    , ($reflectFunc instanceof \ReflectionMethod)
                    ? $reflectFunc->getDeclaringClass()->getName().'::'.$reflectFunc->getName()
                    : $reflectFunc->getName()
                    , $reflectArgument->getName()
                    , \Poirot\Std\flatten($parameters)
                ));

            $matchedArguments[$reflectArgument->getName()] = $argValue;
        }

        return $matchedArguments;
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
     *  '[:subdomain{{static}}.]localhost.:tld{{\s+}}'
     *  : variable {delimiter}
     *    delimiter .. localhost\.(?P<tld>\s+)
     *  [optional]
     *
     * TODO: optional in optional;
     *
     * @param string $criteria
     * @param string $expectedLiteral
     *
     * @return array
     */
    function parseCriteria($criteria, $expectedLiteral = null)
    {
        $TOKENS  = preg_quote('\:~<>');

        ($expectedLiteral !== null) ?: $expectedLiteral = '/@+-.';
        $LITERAL = preg_quote($expectedLiteral).'A-Za-z0-9_';

        $currentPos = 0;
        $length     = strlen($criteria);

        $parts      = array();
        $levelParts = array(&$parts);
        $level      = 0;

        while ($currentPos < $length)
        {
            ## the tokens are .:~<>
            preg_match("(\G(?P<_literal_>[$LITERAL]*)(?P<_token_>[$TOKENS]|$))"
                , $criteria
                , $matches
                , 0
                , $currentPos
            );

            if (empty($matches)) break;

            $currentPos += strlen($matches[0]);

            if (!empty($matches['_literal_']))
                $levelParts[$level][] = array('_literal_' => $matches['_literal_']);

            # Deal With Token:
            if (!isset($matches['_token_']) || $matches['_token_'] == '')
                continue;

            $Token = $matches['_token_'];
            if ($Token === '~') {
                // ^ match any character that is not in list
                $pmatch = preg_match("/~(?P<_delimiter_>[^~]+)~/"
                    , $criteria
                    , $matches
                    , 0
                    , 0
                );
                if (! $pmatch )
                    throw new \RuntimeException('Miss usage of ~ ~ token.');

                $val['_regex_'] = $matches['_delimiter_'];
                $levelParts[$level][] = $val;

                $currentPos += strlen($matches[0])+2/*{}*/;
            }
            elseif ($Token === ':') {
                // TODO better expression
                // currently match everything between {{ \w+}}/:identifier_type{{\w+ }}
                // /validate/resend/:validation_code{{\w+}}/:identifier_type{{\w+}}
                // ^ match any character that is not in list
                $pmatch = preg_match("(\G(?P<_name_>[^$TOKENS]+)(?:~(?P<_delimiter_>[^~]+)~)?:?)"
                    , $criteria
                    , $matches
                    , 0
                    , $currentPos
                );

                if (! $pmatch && !(isset($matches['_name_']) && isset($matches['_delimiter_'])) )
                    throw new \RuntimeException(
                        'Found empty parameter name or delimiter on (%s).'
                        , $criteria
                    );

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
                $levelParts[$level][]   = array('_literal_' => $criteria[$nextChar]);
            }

            elseif ($Token === '<') {
                if (!isset($va)) {
                    $va = array();
                    $n = 0;
                }

                $n++;
                $va[$n] = array();
                $levelParts[$level][]   = array('_optional_' => &$va[$n]);
                $level++;
                $levelParts[$level] = &$va[$n];
            }

            elseif ($Token === '>') {
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
     * @param bool $matchExact
     *
     * @return string
     */
    function buildRegexFromParsed(array $parts, $matchExact = null)
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
                case '_regex_':
                    $regex .= $definition_value;
                    break;
                case '_token_':
                case '_literal_':
                    $regex .= preg_quote($definition_value);
                    break;

                case '_parameter_':
                    $groupName = '?P<' . $definition_value . '>';

                    if (isset($parsed[$definition_value])) {
                        // Delimiter: localhost:port{{\d+}}
                        $regex .= '(' . $groupName . $parsed[$definition_value] . ')';
                    } else{
                        $regex .= '(' . $groupName . '[^.]+)';
                    }

                    break;

                case '_optional_':
                    $regex .= '(?:' . buildRegexFromParsed($definition_value, null) . ')?';
                    break;
            }
        }

        if ($matchExact !== null) {
            $regex = ( (bool) $matchExact )
                ? "(^{$regex}$)" ## exact match
                // TODO /me match on /members request with matchWhole Option false
                //      so i add trailing slash but not tested completely
                : "(^{$regex})"; ## only start with criteria "/pages[/other/paths]"
        }

        return $regex;
    }

    /**
     * Build String Representation From Given Parts and Params
     *
     * @param array              $parts
     * @param array|\Traversable $params
     *
     * @return string
     */
    function buildStringFromParsed(array $parts, $params = array())
    {
        if ($params instanceof \Traversable)
            $params = \Poirot\Std\cast($params)->toArray();

        if (!is_array($params))
            throw new \InvalidArgumentException(sprintf(
                'Parameters must be an array or Traversable; given: (%s).'
                , \Poirot\Std\flatten($params)
            ));


        // regard to recursive function call
        $isOptional = false;
        $args = func_get_args();
        if ($args && isset($args[2]))
            $isOptional = $args[2];

        # Check For Presented Values in Optional Segment
        // consider this "/[@:username{{\w+}}][-:userid{{\w+}}]"
        // if username not presented as params injected then first part include @ as literal
        // not rendered.
        // optional part only render when all parameter is present
        if ($isOptional) {
            foreach ($parts as $parsed) {
                if (!isset($parsed['_parameter_']))
                    continue;

                ## need parameter
                $neededParameterName = $parsed['_parameter_'];
                if (!(isset($params[$neededParameterName]) && $params[$neededParameterName] !== '') )
                    return '';
            }
        }


        $return    = '';
        // [0 => ['_literal_' => 'localhost'], 1=>['_optional' => ..] ..]
        foreach ($parts as $parsed) {
            $definition_name  = key($parsed);
            $definition_value = $parsed[$definition_name];
            // $parsed can also have extra parsed data options
            // _parameter_ String(3) => tld \
            // tld String(4)         => .com
            switch ($definition_name)
            {
                case '_literal_':
                    $return .= $definition_value;
                    break;

                case '_parameter_':
                    if (!isset($params[$definition_value])) {
                        if ($isOptional)  return '';

                        throw new \InvalidArgumentException(sprintf(
                            'Missing parameter (%s).'
                            , $definition_value
                        ));
                    }

                    $return .= $params[$definition_value];
                    break;

                case '_optional_':
                    $optionalPart = buildStringFromParsed($definition_value, $params, true);
                    if ($optionalPart !== '')
                        $return .= $optionalPart;

                    break;
            }
        }

        return $return;
    }
}

namespace Poirot\Std
{
    use Closure;
    use ReflectionFunction;

    use Poirot\Std\Type\StdArray;
    use Poirot\Std\Type\StdString;
    use Poirot\Std\Type\StdTravers;

    const CODE_NUMBERS       = 2;
    const CODE_STRINGS_LOWER = 4;
    const CODE_STRINGS_UPPER = 8;
    const CODE_STRINGS       = CODE_STRINGS_LOWER | CODE_STRINGS_UPPER;


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
            case is_array($type):
                $return = new StdArray($type);
                break;
            case ($type instanceof \Traversable):
                $return = new StdTravers($type);
                break;
            case isStringify($type):
                // Stringify has low priority than \Traversable
                // TODO and object may has both \Traversable or Stringify ...
                $return = new StdString($type);
                break;

            default: throw new \UnexpectedValueException(sprintf(
                'Type (%s) is unexpected.', gettype($type)
            ));
        }

        return $return;
    }

    /**
     * // TODO $contains is string contains chars. to use by shuffler
     * Generate Random Strings
     *
     * @param int $length
     * @param int $contains
     *
     * @return string
     */
    function generateShuffleCode($length = 8, $contains = CODE_NUMBERS | CODE_STRINGS)
    {
        $characters = null;

        if (($contains & CODE_NUMBERS) == CODE_NUMBERS)
            $characters .= '0123456789';

        if (($contains & CODE_STRINGS_LOWER) == CODE_STRINGS_LOWER)
            $characters .= 'abcdefghijklmnopqrstuvwxyz';

        if (($contains & CODE_STRINGS_UPPER) == CODE_STRINGS_UPPER)
            $characters .= 'ABCDEFGHIJKLMNOPQRSTUVWXYZ';

        if ($characters === null)
            throw new \InvalidArgumentException('Invalid Contains Argument Provided; Does Not Match Any Condition.');


        $randomString = '';
        for ($i = 0; $i < $length; $i++)
            $randomString .= $characters[rand(0, strlen($characters) - 1)];

        return $randomString;
    }

    /**
     * Generate a unique identifier
     *
     * @param int $length
     *
     * @return string
     * @throws \Exception
     */
    function generateUniqueIdentifier($length = 40)
    {
        try {
            // Converting bytes to hex will always double length. Hence, we can reduce
            // the amount of bytes by half to produce the correct length.
            return bin2hex(random_bytes($length / 2));
        } catch (\TypeError $e) {
            throw new \Exception('Server Error While Creating Unique Identifier.');
        } catch (\Error $e) {
            throw new \Exception('Server Error While Creating Unique Identifier.');
        } catch (\Exception $e) {
            // If you get this message, the CSPRNG failed hard.
            throw new \Exception('Server Error While Creating Unique Identifier.');
        }
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
    function emptyCoalesce($data, $default = null)
    {
        return ($data === null || $data === false) ? $default : $data;
    }

    /**
     * Swap Value Of Two Variable
     *
     * @param mixed $a
     * @param mixed $b
     *
     * @return void
     */
    function swap(&$a, &$b)
    {
        list($a, $b) = array($b, $a);
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
     * Convert Object To Array
     *
     * @param \stdClass|object $data
     *
     * @return array
     */
    function toArrayObject($data)
    {
        if (is_object($data))
            $data = get_object_vars($data);

        if (is_array($data))
            return array_map(__FUNCTION__, $data);
        else
            return $data;
    }

    /**
     * Represent Stringify From Variable
     *
     * @param mixed $var
     *
     * @return string
     * @throws \Exception
     */
    function toStrVar($var)
    {
        $r = null;

        if (is_bool($var))
            $r = ($var) ? 'True' : 'False';
        elseif (is_null($var))
            $r = 'null';
        elseif (isStringify($var))
            $r = (string) $var;
        else
            throw new \Exception(sprintf('Variable (%s) cant convert to string.'));

        return $r;
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
        } elseif ($value === null)
            $value = 'NULL';

        return $value;
    }
}
