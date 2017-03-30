<?php
namespace Poirot\Std;

/*
$invokable = new P\Std\InvokableResponder();
$invokable = $invokable->thenWith(function() {
    // this will provide data for next
    return ['name' => 'Payam'];
})->thenWith(function($name, $family) {
    $result = sprintf('Hello %s %s.', $name, $family);
    return $result;
})->setIdentifier('final_result');

echo $invokable->__invoke(['family' => 'Naderi'])['final_result'];
// Hello Payam Naderi.
*/

// TODO inject default params
// - $invokable = new InvokableResponder(function () use ($params) { return $params; });
// - with currently constructor argument preInvokable its not behave perfectly
final class InvokableResponder
{
    /** @var callable */
    protected $_onFailure;

    /** @var callable */
    protected $bootupInvokable;
    /** @var InvokableResponder|null Previous linked */
    protected $prevInvokable;
    /** @var $identifier */
    protected $identifier;


    /**
     * InvokableResponder constructor.
     * 
     * @param callable|null $preInvokable
     */
    function __construct(/*callable*/ $preInvokable = null)
    {
        if ($preInvokable !== null && !is_callable($preInvokable))
            throw new \InvalidArgumentException(sprintf(
                'PreInvokable Must Be Callable; given: (%s).', flatten($preInvokable)
            ));

        ## bind it into latest bind object
        // DO_LEAST_PHPVER_SUPPORT 5.4 closure bindto
        if ($preInvokable instanceof \Closure && version_compare(phpversion(), '5.4.0') > 0) {
            $preInvokable = \Closure::bind(
                $preInvokable
                , $this
                , get_class($this)
            );
        }

        $this->bootupInvokable = $preInvokable;
    }

    /**
     * Invoke Action
     *
     * @param array $result Default result
     *
     * @return array Merged invoked results
     * @throws \Exception
     */
    function __invoke(array $result = array())
    {
        try {
            // make execution list
            $linked   = array();
            $previous = $this;
            while($previous = $previous->prevInvokable)
                // collect invokable from begining
                array_unshift($linked, $previous);


            // ORDER IS MANDATORY:

            foreach ($linked as $invokable)
            {
                // execute and merge each result
                try {
                    $r = \Poirot\Std\Invokable\resolveCallableWithArgs($invokable, $result);
                } catch (\Exception $e) {
                    throw new \Exception($e->getMessage(), null, $e);
                }

                $r = call_user_func($r);
                $result = $this->_mergeResults($r, $result);
            }

            if ($this->bootupInvokable)
            {
                try {
                    $r = \Poirot\Std\Invokable\resolveCallableWithArgs($this->bootupInvokable, $result);
                } catch (\Exception $e) {
                    throw new \Exception($e->getMessage(), null, $e);
                }

                $r = call_user_func($r);
                $result = $this->_mergeResults($r, $result);
            }
            
        } catch(\Exception $e) {
            if ($this->_onFailure)
                return call_user_func($this->_onFailure, $e, $this);

            throw $e;
        }

        return $result;
    }

    /**
     * callable:
     *  mixed function(DataEntity $result, InvokableResponder $self)
     *
     * @param callable            $nextCallable
     * @param callable|null|false $onFailureCallable
     * @param string|null         $identifier
     *
     * @return InvokableResponder if callable not given
     * @throws \Exception
     */
    function thenWith(/*callable*/ $nextCallable = null, /*callable*/ $onFailureCallable = null, $identifier = null)
    {
        $InvokableChain = new self($nextCallable);
        $InvokableChain->prevInvokable = $this;
        $InvokableChain->setIdentifier($identifier);

        if ($onFailureCallable === null)
            // use current failure handler
            $onFailureCallable = $this->_onFailure;

        if ($onFailureCallable !== null)
            $InvokableChain->onFailure($onFailureCallable);
        
        if ($nextCallable === null)
            return $InvokableChain;

        if (!is_callable($nextCallable))
            throw new \Exception(sprintf(
                'The argument given is not callable; given: (%s).'
                , \Poirot\Std\flatten($nextCallable)
            ));
        
        return $InvokableChain;
    }

    /**
     * Set Params Identifier Key
     *
     * @param string $identifier
     *
     * @return $this
     */
    function setIdentifier($identifier)
    {
        $this->identifier = (string) $identifier;
        return $this;
    }

    /**
     * @return string
     */
    protected function _getIdentifier()
    {
        if (empty($this->identifier) && $this->identifier !== '0')
            $this->setIdentifier(get_class($this));

        return $this->identifier;
    }

    /**
     * Handle Exceptions
     *
     * callable:
     *  mixed function(\Exception $exception, InvokableResponder $self)
     *
     * @param callable|false $callable Given false will remove current failure callback
     *
     * @return $this
     * @throws \Exception
     */
    function onFailure(/*callable*/$callable)
    {
        if ($callable === false) {
            $this->_onFailure = $callable;
            return $this;
        }

        if (!is_callable($callable))
            throw new \Exception(sprintf(
                'The argument given is not callable; given: (%s).'
                , \Poirot\Std\flatten($callable)
            ));

        ## bind it into latest bind object
        // DO_LEAST_PHPVER_SUPPORT 5.4 closure bindto
        if ($callable instanceof \Closure && version_compare(phpversion(), '5.4.0') > 0) {
            $callable = \Closure::bind(
                $callable
                , $this
                , get_class($this)
            );
        }

        $this->_onFailure = $callable;
        return $this;
    }


    // ..

    /**
     * @param mixed      $result
     * @param array|null $previousArray
     * 
     * @return array
     */
    function _mergeResults($result, array $previousArray = array())
    {
        if ( !is_array($result) )
            $result = array($this->_getIdentifier() => $result);

        $merge = \Poirot\Std\cast($previousArray);
        $merge = $merge->withMerge($result);
        
        return \Poirot\Std\cast($merge)->toArray();
    }
}
