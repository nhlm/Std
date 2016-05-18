<?php
namespace Poirot\Std\Interfaces\Pact;

/**
 * Use Case:
 * Some times we have to resolve arguments to 
 * callable bind with this invokable instead of 
 * __invoke method itself.
 * 
 */
interface ipInvokableCallback 
    extends ipInvokable
{
    /**
     * Set Callable Closure For __invoke
     *
     * - closure callable Must bind to $this
     *
     * @param callable $callable
     *
     * @return $this
     */
    function setCallable(/*callable*/ $callable);

    /**
     * Get Callable
     *
     * @return callable
     */
    function getCallable();
}
