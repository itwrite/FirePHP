<?php
/**
 * Created by PhpStorm.
 * User: zzpzero
 * Date: 2018/1/25
 * Time: 22:10
 */

namespace Jasmine\helper;


use Jasmine\library\util\Arr;

class Globals
{
    /**
     * @param $key
     * @param null $default
     * @return mixed
     */
    static public function get($key = '', $default = null)
    {
        if (func_num_args() < 1 || is_null($key)) return self::all();

        return Arr::get($GLOBALS, $key, $default);
    }

    /**
     * @param $key
     * @param string $value
     */
    static public function set($key, $value = '')
    {
        //the key is null, do nothing
        if (is_null($key)) return;

        //the value is null, remove it
        if (is_null($value)) Arr::forget($GLOBALS, $key);

        //the key is an array, all set into the config by foreach
        else if (is_array($key)) foreach ($key as $k => $v) {
            Arr::set($GLOBALS, $k, $v);
        }

        //else all set into config anyway
        else Arr::set($GLOBALS, $key, $value);
    }

    /**
     * get all configure
     * @return array
     */
    static public function all()
    {
        return $GLOBALS;
    }
}