<?php
namespace Poirot\Std\Interfaces\Pact;

use Poirot\Std\Exceptions\exUnexpectedValue;


interface ipValidator
{
    /**
     * Assert Validate Entity
     *
     * @throws exUnexpectedValue
     */
    function assertValidate();
}
