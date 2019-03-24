<?php
/**
 * Created by PhpStorm.
 * User: zzpzero
 * Date: 2018/1/5
 * Time: 19:16
 */

namespace Jasmine\helper;

class Debug {
    /**
     * set debug true
     */
    static public function open(){
        error_reporting(E_ALL ^ E_NOTICE);
        ini_set('display_errors', 'On');
        Config::set('app.debug',true);
    }

    /**
     * set debug false
     */
    static public function close(){
        error_reporting(0);
        ini_set('display_errors', 'Off');
        Config::set('app.debug',false);
    }

    /**
     * log depend on debug
     */
    static public function error(){
        if(Config::get('app.debug')){
            $args = func_get_args();
            $arr = explode(' ', microtime());
            $prefix = "[".date('H:i:s').substr($arr[0],1)."] ";
            $rn = "\r\n";
            foreach ($args as $arg) {
                if(is_array($arg)){
                    print_r($prefix);
                    var_export($arg);
                    print_r($rn);
                }elseif(is_string($arg) || is_numeric($arg)){
                    print_r($prefix.$arg.$rn);
                }else{
                    print_r($prefix);
                    var_export($arg);
                    print_r($rn);
                }
            }
        }
    }
}