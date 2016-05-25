<?php
namespace Poirot\Std;

use ErrorException;

/*
 *
ErrorStack::handleBegin(function($errno, $errstr = '', $errfile = '', $errline = 0) {
    ## this will print error string to output
    var_dump($errstr);
});

ErrorStack::handleBegin(E_USER_WARNING);
ErrorStack::rise('This is user warning error.', E_USER_WARNING);
$userError = ErrorStack::handleDone();
var_dump($userError);

# Error Happen
echo $not_defined_variable;

# return ErrorException or null if not any error
$error = ErrorStack::handleDone();
if ($error)
    throw $error;

// == Chain Exception Handlers ==========================================================

ErrorStack::handleException(function ($e) {
    ## then accrued exception rise to php default
    throw $e;
});

ErrorStack::handleException(function ($e) {
    echo 'next we see this<br/>';

    ## pass to next error handler by throwing exception
    throw $e;
});

ErrorStack::handleException(function ($e) {
    echo 'this error will appear once.<br/>';

    ## pass to next error handler by throwing exception
    throw $e;
});

## rise Exception
throw new \Exception;

*/
final class ErrorStack
{
    const ERR_DEF_SEVERITY  = E_ALL;

    const ERR_TYP_ERROR     = 'error';
    const ERR_TYP_EXCEPTION = 'exception';

    protected static $_STACK = array(
        # [
            # 'error_level' => int,
            # 'callable'    => null|callable,
            # 'has_error'   => null|ErrorException,
            # 'error_type'  => "error"|"exception"
        # ]
    );

    /**
     * Check if this error handler is active
     *
     * @return bool
     */
    static function hasHandling()
    {
        return (bool) self::getStackNum();
    }

    /**
     * Get the current nested level
     *
     * @return int
     */
    static function getStackNum()
    {
        return count(self::$_STACK);
    }

    /**
     * Used for defining your own way of handling errors during runtime,
     * for example in applications in which you need to do cleanup of -
     * data/files when a critical error happens, or when you need to -
     * trigger an error under certain conditions
     *
     * !! In Case that callable not passed as argument the error can
     *    retrieved with ::handleDone as \ErrorException
     *
     * Usage:
     *  handleError(callable)
     *  handleError(E_ALL, callable)
     *
     *  callable: func(\ErrorException $errorExc)
     *
     * @param int $errorLevel
     * @param callable|null $callable
     */
    static function handleError($errorLevel = null, /* callable */ $callable = null)
    {
        ## in case that handleError(callable) invoked
        if (is_callable($errorLevel)) {
            $callable   = $errorLevel;
            $errorLevel = null;
        }

        ($errorLevel !== null ) ?: $errorLevel = self::ERR_DEF_SEVERITY;

        // ..

        ## append error stack retrieved by self::_HandleErrors
        self::$_STACK[] = array(
            'error_type'  => self::ERR_TYP_ERROR,
            'callable'    => $callable,
            'error_level' => $errorLevel,
            'has_error'   => null,
        );

        ## define stack error handler
        $self = new self;
        set_error_handler(
            function($errno, $errstr = '', $errfile = '', $errline = 0) use ($self) {
                call_user_func(array($self, '_handleErrors'), $errno, $errstr, $errfile, $errline);
            }
            , $errorLevel
        );
    }

    /**
     * Used for defining your own way of handling errors during runtime,
     * for example in applications in which you need to do cleanup of -
     * data/files when a critical error happens, or when you need to -
     * trigger an error under certain conditions
     *
     * callable: func(\Exception $e)
     *
     * @param callable $callable
     */
    static function handleException(/*callable*/ $callable)
    {
        $self = new self;

        # append error stack retrieved by self::_HandleErrors
        self::$_STACK[] = array(
            'error_type'  => $self::ERR_TYP_EXCEPTION,
            'callable'    => $callable,
            'error_level' => null,
            'has_error'   => null,
        );

        ## define stack exception handler
        set_exception_handler(function($exception) use ($self) {
            call_user_func(array($self, '_handleErrors'), $exception);
        });
    }

    /**
     * Get Current Accrued Error If Has
     *
     * @return null|\Exception|\ErrorException
     */
    static function getAccruedErr()
    {
        if (!self::hasHandling())
            return null;

        $stack = self::$_STACK[self::getStackNum()-1];
        return $stack['has_error'];
    }

    /**
     * Stopping The Error Handling Stack
     *
     * !! It can using only for error not exceptions
     *
     * @return null|ErrorException Last error if has
     */
    static function handleDone()
    {
        $return = null;

        if (!self::hasHandling())
            ## there is no error
            return $return;

        $stack = array_pop(self::$_STACK);
        if ($stack['has_error'])
            $return = $stack['has_error'];

        # restore error handler
        (($stack['error_type']) == self::ERR_TYP_ERROR)
            ? restore_error_handler()
            : restore_exception_handler()
        ;

        return $return;
    }

    /**
     * Stop all active handler and clean stack
     *
     */
    static function clean()
    {
        restore_error_handler();
        restore_exception_handler();

        self::$_STACK = array();
    }

    /**
     * Generates a user-level error
     *
     * @param string $message
     * @param int    $errorType
     *
     * @return bool
     */
    static function rise($message, $errorType = E_USER_NOTICE)
    {
        return trigger_error($message, $errorType);
    }

    /**
     * Get PHP Error Code To String
     * @param int $code
     * @return string
     */
    static function errCodeToString($code)
    {
        switch ($code) {
            case E_ERROR:             return 'E_ERROR';
            case E_WARNING:           return 'E_WARNING';
            case E_PARSE:             return 'E_PARSE';
            case E_NOTICE:            return 'E_NOTICE';
            case E_CORE_ERROR:        return 'E_CORE_ERROR';
            case E_CORE_WARNING:      return 'E_CORE_WARNING';
            case E_COMPILE_ERROR:     return 'E_COMPILE_ERROR';
            case E_COMPILE_WARNING:   return 'E_COMPILE_WARNING';
            case E_USER_ERROR:        return 'E_USER_ERROR';
            case E_USER_WARNING:      return 'E_USER_WARNING';
            case E_USER_NOTICE:       return 'E_USER_NOTICE';
            case E_STRICT:            return 'E_STRICT';
            case E_RECOVERABLE_ERROR: return 'E_RECOVERABLE_ERROR';
            case E_DEPRECATED:        return 'E_DEPRECATED';
            case E_USER_DEPRECATED:   return 'E_USER_DEPRECATED';
        }

        return 'Unknown PHP error';
    }

    // ...

    /**
     * Handle Both Exception And Errors That Happen Within
     * handleBegin/handleDone
     *
     * @private
     */
    static protected function _handleErrors($errno, $errstr = '', $errfile = '', $errline = 0)
    {
        $Stack = & self::$_STACK[self::getStackNum()-1];

        if (! $errno instanceof \Exception) {
            if (interface_exists('Throwable') && $errno instanceof \Throwable)
                VOID;
            else {
                ## handle errors
                $errno = new ErrorException(
                    $errstr, $errno, 1, $errfile, $errline
                );
            }
        }


        $Stack['has_error'] = $errno;


        // ...

        if ($Stack['callable'] === null)
            return;

        $currLevel = self::getStackNum();
        try {
            ## call user error handler callable
            ## exception will passed as errno on exception catch
            call_user_func($Stack['callable'], $errno, $errstr, $errfile, $errline);
            
        } catch (\Exception $e) {
            ## during handling an error if any exception happen it must handle with parent handler
            if (self::getStackNum() == $currLevel)
                ## close current handler if not, the handleDone may be called from within handler callable
                self::handleDone();

            if ($Stack['error_type'] == self::ERR_TYP_ERROR)
                ## Just throw exception, it might handled with exception handlers
                throw $e;

            $isHandled = false;
            while (self::hasHandling()) {
                $Stack = & self::$_STACK[self::getStackNum()-1];
                if ($Stack['error_type'] == self::ERR_TYP_EXCEPTION) {
                    $isHandled = true;
                    self::_handleErrors($e);
                    break;
                }

                self::handleDone();
            }

            if (!$isHandled)
                ## throw exception if it not handled
                throw $e;
        }
    }
}
