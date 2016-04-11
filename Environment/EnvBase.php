<?php
namespace Poirot\Std\Environment;

use Poirot\Std\Struct\aDataOptions;
use Poirot\Std\Type\StdString;

/*

(new EnvProduction)->apply();
# warning error not displayed
echo $not_defined_variable;

*/

abstract class EnvBase extends aDataOptions
{
    protected $displayErrors;
    protected $errorReporting;
    protected $displayStartupErrors;
    protected $htmlErrors;

    /**
     * Setup Php Environment
     *
     * $settings will override default environment values
     *
     * @param EnvBase|array|\Traversable $settings
     */
    final function apply($settings = null)
    {
        if ($settings !== null)
            $this->from($settings);

        # initialize specific environment
        $this->initApply();

        # do apply by current options value
        foreach($this as $prop => $value) {
            $method = 'do'.(new StdString($prop))->camelCase();
            if (method_exists($this, $method))
                $this->{$method}($value);
        }
    }

    protected function initApply()
    {
        // specific system wide setting initialize for extended classes ...
    }

    // ...


    function doDisplayErrors($value)
    {
        ini_set('display_errors', $value);
        return $this;
    }

    /**
     * @param mixed $displayErrors
     * @return $this
     */
    function setDisplayErrors($displayErrors)
    {
        $this->displayErrors = $displayErrors;
        return $this;
    }

    /**
     * @return mixed
     */
    function getDisplayErrors()
    {
        return $this->displayErrors;
    }

    // ..

    function doErrorReporting($value)
    {
        error_reporting($value);
        return $this;
    }

    /**
     * @param int $errorReporting
     * @return $this
     */
    function setErrorReporting($errorReporting)
    {
        $this->errorReporting = $errorReporting;
        return $this;
    }

    /**
     * @return mixed
     */
    function getErrorReporting()
    {
        return $this->errorReporting;
    }

    // ..

    function doDisplayStartupErrors($value)
    {
        ini_set('display_startup_errors', $value);
        return $this;
    }

    /**
     * @param mixed $displayStartupErrors
     * @return $this
     */
    function setDisplayStartupErrors($displayStartupErrors)
    {
        $this->displayStartupErrors = $displayStartupErrors;
        return $this;
    }

    /**
     * @return mixed
     */
    function getDisplayStartupErrors()
    {
        return $this->displayStartupErrors;
    }

    // ..

    function doHtmlErrors($value)
    {
        ini_set('html_errors', $value);
        return $this;
    }

    /**
     * @param mixed $htmlErrors
     * @return $this
     */
    function setHtmlErrors($htmlErrors)
    {
        $this->htmlErrors = $htmlErrors;
        return $this;
    }

    /**
     * @return mixed
     */
    function getHtmlErrors()
    {
        return $this->htmlErrors;
    }
}
