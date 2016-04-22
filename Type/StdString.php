<?php
namespace Poirot\Std\Type;

if (!class_exists('\SplString')) {
    require_once __DIR__.'/fixes/NSplString.php';
    class_alias('\Poirot\Std\Type\NSplString', '\SplString');
}

final class StdString 
    extends \SplString
{
    /**
     * Sanitize Underscore To Camelcase
     *
     * @return string
     */
    function camelCase()
    {
        $Pascal = lcfirst((string)$this->PascalCase());
        return new StdString($Pascal);
    }

    /**
     * Sanitize Underscore To Camelcase
     *
     * @return string
     */
    function PascalCase()
    {
        $key = (string) $this;
        return new StdString(str_replace(' ', '', ucwords(str_replace('_', ' ', $key))));
    }

    /**
     * Sanitize CamelCase To under_score
     *
     * @return string
     */
    function under_score()
    {
        $key = (string) $this;

        $output = strtolower(preg_replace(['/([a-z\d])([A-Z])/', '/([^_])([A-Z][a-z])/'], '$1_$2', $key));

        return new StdString($output);
    }
}
