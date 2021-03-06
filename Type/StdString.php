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
     * Is Contain UTF-8 Encoding?
     *
     * @return bool
     */
    function isUTF8()
    {
        $string = (string) $this;
        if (function_exists('utf8_decode'))
             $decodedStr = strlen(utf8_decode($string));
        elseif (function_exists('mb_convert_encoding'))
            $decodedStr = strlen(utf8_decode($string));
        else
            $decodedStr = $string;

        
        return strlen($string) != strlen($decodedStr);
    }

    /**
     * // TODO mb_string
     *
     * To Lower
     * @return StdString
     */
    function toLower()
    {
        $key = (string) $this;
        $key = strtolower($key);

        return new self($key);
    }

    /**
     * To Upper
     * @return StdString
     */
    function toUpper()
    {
        $key = (string) $this;
        $key = strtoupper($key);

        return new self($key);
    }

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
     * Slugify Input Text
     *
     * @return string
     */
    function slugify()
    {
        $text = (string) $this;

        // replace non letter or digits by -
        $text = preg_replace('~[^\pL\d]+~u', '-', $text);

        // transliterate
        $text = iconv('utf-8', 'us-ascii//TRANSLIT', $text);

        // remove unwanted characters
        $text = preg_replace('~[^-\w]+~', '', $text);

        // trim
        $text = trim($text, '-');

        // remove duplicate -
        $text = preg_replace('~-+~', '-', $text);

        // lowercase
        $text = strtolower($text);

        if (empty($text))
            return 'n-a';

        return new self($text);
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
