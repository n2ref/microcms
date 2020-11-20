<?php
namespace Microcms;


/**
 * Class Registry
 */
class Registry {


    private static $options = [];


    /**
     * @param string $option_name
     * @param mixed  $data
     */
    public static function set($option_name, $data) {
        self::$options[$option_name] = $data;
    }


    /**
     * @param string $option_name
     * @return mixed
     */
    public static function get($option_name) {

        if (self::isRegistered($option_name)) {
            return self::$options[$option_name];
        }

        return null;
    }


    /**
     * @param string $option_name
     * @return bool
     */
    public static function isRegistered($option_name) {

        return isset(self::$options[$option_name]);
    }
}