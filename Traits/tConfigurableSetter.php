<?php
namespace Poirot\Std\Traits;

use Traversable;

trait tConfigurableSetter
{
    /**
     * [
     *  'service_config',
     *  'listeners',
     *  // ...
     * ]
     */
    protected $_t_configurable_setter_Priorities = array();

    /**
     * Build Object With Provided Options
     *
     * @param array|\Traversable $options        Associated Array
     * @param bool               $throwException Throw Exception On Wrong Option
     *
     * @return array Remained Options (if not throw exception)
     * @throws \Exception
     * @throws \InvalidArgumentException
     */
    function with($options, $throwException = false)
    {
        if (empty($options))
            # nothing to do
            return $this;

        if ($options instanceof Traversable)
            $options = \Poirot\Std\cast($options)->toArray();

        if (!is_array($options))
            throw new \InvalidArgumentException(sprintf(
                'Options must be array or Traversable; given: (%s).'
                , \Poirot\Std\flatten($options)
            ));
        
        if (array_values($options) == $options)
            throw new \InvalidArgumentException(sprintf(
                'Setters Array must be associative array. given: %s'
                , var_export($options, true)
            ));

        if (isset($this->_t_configurable_setter_Priorities)
            && is_array($this->_t_configurable_setter_Priorities)
        ) {
            $sortQuee = $this->_t_configurable_setter_Priorities;
            uksort($options, function($a, $b) use ($sortQuee) {
                // sort array to reach setter priorities
                $ai = array_search($a, $sortQuee);
                $ai = ($ai !== false) ? $ai : 1000;

                $bi = array_search($b, $sortQuee);
                $bi = ($bi !== false) ? $bi : 1000;

                return $ai < $bi ? -1 : ($ai == $bi) ? 0 : 1;
            });
        }

        $remained = array();
        foreach($options as $key => $val) {
            $setter = 'set' . \Poirot\Std\cast((string) $key)->camelCase();
            // It can be public or protected methods
            // usually protected methods can be used as helper 
            // to build class with options
            if (method_exists($this, $setter)) {
                // check for methods
                call_user_func(array($this, $setter), $val);
            } elseif($throwException) {
                throw new \InvalidArgumentException(
                    sprintf(
                        'The option "%s" does not have a matching "%s" setter method',
                        $key, $setter
                    )
                );
            }
            else
                $remained[] = $key;
        }

        return $remained;
    }

    /**
     * List Setters By Priority
     *
     * [
     *  'service_config',
     *  'listeners',
     *  // ...
     * ]
     *
     * application calls setter methods from top ...
     *
     * @param array $propPriorities
     */
    protected function putBuildPriority(array $propPriorities)
    {
        $this->_t_configurable_setter_Priorities = $propPriorities;
    }
}
