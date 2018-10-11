<?php
/**
 * Created by PhpStorm.
 * User: zzpzero
 * Date: 2017/11/15
 * Time: 18:15
 */

namespace Firework\library\db\query\schema;

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
        $tables = $this->cache[count($this->cache) - 1];
        if (!is_null($tables) && is_array($tables) && count($tables) > 0) {
            $this->data = $tables;
        }
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
}