<?php
namespace Poirot\Std\Environment;

/**
 * - Display Errors Off
 * - Advised to use error logging in place of error displaying on production
 *
 */

class EnvProduction
    extends EnvBase
{
    protected $displayErrors  = 0;
    protected $errorReporting = 0;
    protected $displayStartupErrors = 0;
}
