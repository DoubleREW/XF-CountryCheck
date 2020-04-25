<?php

/**
 * Class CountryCheck_Listener_LoadClassModel
 */
class CountryCheck_Listener_LoadClassModel
{
    /**
     * Model overrides.
     * @see XenForo_Application::resolveDynamicClass()
     *
     * @param string $class Name of original class
     * @param array $extend In list of classes that will extend the original
     */
    public static function loadClassModel($class, &$extend)
    {
        // extend the user model to include that extra information we need
        if ($class == 'XenForo_Model_SpamPrevention')
        {
            $extend[] = 'CountryCheck_Model_SpamPrevention';
        }
    }
}