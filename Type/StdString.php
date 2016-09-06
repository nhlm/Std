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
        return new StdString($r);
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

        return new StdString($output);
    }
}
