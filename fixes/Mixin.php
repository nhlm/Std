<?php
namespace Poirot\Std;

/**
 * Note: Call by reference not working as expected
 *
    $changeMe = 'I`m Bad.';
    $openCall = new Mixin();

    $_F_make = function(&$changeMe) {
        $changeMe = 'We make you good.';
    };

    $openCall->addMethod('makeMe', $_F_make);

    $openCall->makeMe($changeMe);
 *
 */

class Mixin
{
    ## just determine that fixed class loaded in debugs
    protected $IS_FIX = true;

    /**
     * Construct
     *
     * @param null  $bindTo
     * @param array $methods ['method_name' => \Closure]
     *
     * @throws \Exception
     */
    function __construct($bindTo = null, array $methods = array())
    {
        if (!is_object($bindTo) && $bindTo !== null)
            throw new \InvalidArgumentException(sprintf(
                'Invalid argument BindTo for OpenCall Construct, must given Object instead "%s" given.'
                , \Poirot\Std\flatten($bindTo)
            ));

        if ($bindTo)
            $this->bindTo($bindTo);

        foreach($methods as $m => $f)
            $this->addMethod($m, $f);
    }

    ## @see tMixin;
    ## Code Clone <begin> =================================================================
    private $_t__methods = array();
    private $_t__bindTo;

    /**
     * Bind Current Methods to Given Object
     *
     * @param $class
     *
     * @throws \Exception
     * @return $this
     */
    function bindTo($class)
    {
        if (!is_object($class))
            throw new \Exception(sprintf(
                'Given class must be an object (%s) given.'
                , \Poirot\Std\flatten($class)
            ));

        $this->_t__bindTo = $class;

        return $this;
    }

    /**
     * Get Given Bind Object
     *
     * @return object
     */
    function getBindTo()
    {
        if (!$this->_t__bindTo)
            $this->bindTo($this);

        return $this->_t__bindTo;
    }

    /**
     * Attach Method To This Class
     *
     * @param string   $methodName
     * @param \Closure $methodCallable
     *
     * @return $this
     */
    function addMethod($methodName, \Closure $methodCallable)
    {
        $this->_t__methods[$methodName] = $methodCallable;

        return $this;
    }

    /**
     * Has Method Name Exists?
     *
     * @param string $methodName
     *
     * @return bool
     */
    function hasMethod($methodName)
    {
        if (isset($this->_t__methods[$methodName]))
            return true;

        # check bind object
        $return = false;

        $bind = $this->getBindTo();
        if (!$bind instanceof self) {
            $return = method_exists($bind, $methodName);
        }

        return $return;
    }

    /**
     * Get Method Closure
     *
     * !! if you need args called by reference use get method
     *
     * @param string $methodName
     *
     * @throws \Exception method not found
     * @return \Closure
     */
    function getMethod($methodName)
    {
        if (!$this->hasMethod($methodName))
            throw new \Exception(sprintf(
                'Method (%s) not found.'
                , $methodName
            ));

        if (isset($this->_t__methods[$methodName])) {
            ## from bind closure
            $methodCallable = $this->_t__methods[$methodName];
        } else {
            ## proxy calls to bind object methods
            $bindTo = $this->getBindTo();
            $methodCallable = function() use($methodName, $bindTo) {
                return call_user_func_array(array($bindTo, $methodName), func_get_args());
            };
        }

        ## bind it into latest bind object
        // DO_LEAST_PHPVER_SUPPORT 5.4 closure bindto
        if (version_compare(phpversion(), '5.4.0') > 0) {
            $methodCallable = \Closure::bind(
                $methodCallable
                , $this->getBindTo()
                , get_class($this->getBindTo())
            );
        }

        return $methodCallable;
    }

    /**
     * List Registered Methods
     *
     * @return array[string]
     */
    function listMethods()
    {
        return array_keys($this->_t__methods);
    }

    /**
     * Proxy Call To Registered Methods
     *
     * !! if closure bindto not supported it will
     *    bind object at last args
     *    (.. , $bindObject)
     *
     * !! has issue with call by reference args
     *
     * @param $methodName
     * @param array $args
     *
     * @return mixed
     */
    function __call($methodName, array $args)
    {
        $methodCallable = $this->getMethod($methodName);
        array_push($args, $this->getBindTo());
        return call_user_func_array($methodCallable, $args);
    }
    ## Code Clone <end> ===================================================================
}
