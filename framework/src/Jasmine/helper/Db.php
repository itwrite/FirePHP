<?php
/**
 * Created by PhpStorm.
 * User: itwri
 * Date: 2019/3/24
 * Time: 19:37
 */

namespace Jasmine\helper;


use Jasmine\App;
use Jasmine\library\db\Database;

/**
 * @method Database table($table)
 * Class Db
 * @package Jasmine\helper
 */
class Db
{
    static public function __callStatic($name, $arguments)
    {
        // Implement __callStatic() method.
        if(is_callable([App::init()->getDb(),$name])){
            return call_user_func_array([App::init()->getDb(),$name],$arguments);
        }
    }
}