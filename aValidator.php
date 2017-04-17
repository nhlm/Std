<?php
namespace Poirot\Std;

use Poirot\Std\Exceptions\exUnexpectedValue;
use Poirot\Std\Interfaces\Pact\ipValidator;


abstract class aValidator
    implements ipValidator
{
    /**
     * Do Assertion Validate and Return An Array Of Errors
     *
     * @return exUnexpectedValue[]
     */
    abstract function doAssertValidate();


    /**
     * Assert Validate Entity
     *
     * @throws exUnexpectedValue
     */
    final function assertValidate()
    {
        $exceptions = $this->doAssertValidate();
        if (!is_array($exceptions))
            throw new \RuntimeException(sprintf(
                'Unknown Validation Assertion Value Of Type Array (%s).'
                , flatten($exceptions)
            ));

        if (empty($exceptions))
            return;


        // Chain Exception:

        $_f__chainExceptions = function (exUnexpectedValue $ex, &$list) use (&$_f__chainExceptions)
        {
            if (empty($list))
                return $ex;

            $exception = array_pop($list);

            $r = new exUnexpectedValue(
                $exception->getMessage()
                , $exception->getParameterName()
                , $exception->getCode()
                , $_f__chainExceptions($ex, $list)
            );

            return $r;
        };

        $ex = $_f__chainExceptions(new exUnexpectedValue('Validation Error', ''), $exceptions);
        throw $ex;
    }
}
