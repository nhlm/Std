<?php
namespace Poirot\Std\Type;

if (!class_exists('SplString', false)) {
    require_once __DIR__ . '/fixes/NSplString.php';
    class_alias('\Poirot\Std\Type\NSplString', 'SplString');
}

final class StdString 
    extends \SplString
{
    /**
     * Remove a prefix string from the beginning of a string
     *
     * @param string $prefix
     *
     * @return string
     */
    function stripPrefix($prefix)
    {
        $key = (string) $this;

        if (substr($key, 0, strlen($prefix)) == $prefix)
            $key = substr($key, strlen($prefix));

        return new self($key);
    }

    /**
     * Sanitize Underscore To Camelcase
     *
     * @return string
     */
    function camelCase()
    {
        $Pascal = lcfirst((string)$this->PascalCase());
        return new self($Pascal);
    }

    /**
     * Sanitize Underscore To Camelcase
     *
     * @return string
     */
    function PascalCase()
    {
        $key = (string) $this;
        
        ## prefix and postfix __ remains; like: __this__
        
        $pos = 'prefix';
        $prefix = '';
        $posfix = '';
        for ($i = 0; $i <= strlen($key)-1; $i++) {
            if ($key[$i] == '_')
                $$pos.='_';
            else {
                $posfix = ''; // reset posix, _ may found within string
                $pos  = 'posfix';
            }
        }

        $r = str_replace(' ', '', ucwords(str_replace('_', ' ', $key)));
        $r = $prefix.$r.$posfix;
        return new self($r);
    }

    /**
     * Sanitize CamelCase To under_score
     *
     * @return string
     */
    function under_score()
    {
        $key = (string) $this;

        $output = strtolower(preg_replace(array('/([a-z\d])([A-Z])/', '/([^_])([A-Z][a-z])/'), '$1_$2', $key));

        return new self($output);
    }
}
