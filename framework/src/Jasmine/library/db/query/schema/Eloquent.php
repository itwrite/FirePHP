<?php
/**
 * Created by PhpStorm.
 * User: zzpzero
 * Date: 2017/11/15
 * Time: 18:15
 */

namespace Jasmine\library\db\query\schema;

abstract class Eloquent
{
    /**
     * @var array
     */
    protected $data = array();

    /**
     * @var array
     */
    protected $cache = array();


    /**
     * @return array
     */
    function data()
    {
        return $this->data;
    }

    /**
     * @return $this
     */
    function rollback()
    {
        /**
         * 推出最后一个
         * 剩下的重置到当前data
         */
        array_pop($this->cache);

        $data = array_pop($this->cache);
        $this->data = is_array($data) ? $data : array();

        return $this;
    }

    /**
     * @return $this
     */
    function clear()
    {
        if (count($this->data) > 0) {
            $this->cache[] = $this->data;
            $this->data = array();
        }
        return $this;
    }

    /**
     * 重置数据
     * User: Peter
     * Date: 2019/3/19
     * Time: 11:25
     *
     * @return $this
     */
    function reset(){
        $this->data = [];
        return $this;
    }
}