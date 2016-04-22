<?php
namespace Poirot\Std\Environment;

/**
 * - Enabling E_NOTICE, E_STRICT Error Messages
 *
 */

class EnvDevelopment
    extends EnvBase
{
    protected $displayErrors  = 1;
    /** PHP 5.3 or later, the default value is E_ALL & ~E_NOTICE & ~E_STRICT & ~E_DEPRECATED */
    protected $errorReporting = E_ALL;
    protected $displayStartupErrors = 1;
}
