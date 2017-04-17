<?php
namespace Poirot\Std\Exceptions;


class exUnexpectedValue
    extends \UnexpectedValueException
{
    const ERCODE_REQUIRED = 10;

    protected $parameter;


    /**
     * exUnexpectedValue constructor.
     *
     * @param string $message
     * @param string $parameter
     * @param int    $code
     * @param exUnexpectedValue|null $previous
     */
    function __construct($message = '', $parameter = null, $code = 0, exUnexpectedValue $previous = null)
    {
        parent::__construct(sprintf($message, $parameter), $code, $previous);

        $this->parameter = $parameter;
    }


    /**
     * Get Parameter Name
     *
     * @return string
     */
    function getParameterName()
    {
        return $this->parameter;
    }


    // ...

    static function paramIsRequired($parameterName)
    {
        return new static('Parameter %s Is Required.', $parameterName, static::ERCODE_REQUIRED);
    }
}
