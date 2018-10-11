<?php
/**
 * Created by PhpStorm.
 * User: zzpzero
 * Date: 2018/4/24
 * Time: 14:50
 */

namespace Firework\library\db\query;


use Firework\library\db\query\capsule\Expression;
use Firework\library\db\query\schema\Eloquent;

class Set extends Eloquent{
    /**
     * @param $field
     * @param string $value
     * @return $this
     */
    function set($field, $value = '')
    {
        if (is_array($field)) {
            foreach ($field as $f => $v) {
                $this->set($f, $v);
            }
        } elseif (is_string($field) && strlen($field) > 0) {
            if ($value instanceof \Closure) {
                $value = call_user_func($value);
                $this->data[$field] = isset($value)?(($value instanceof Expression)?$value:(string)$value):'';
            } else {
                $this->data[$field] = $value;
            }
        }
        return $this;
    }

    /**
     * @param $field
     * @param string $value
     * @return $this
     */
    function raw($field, $value = ''){
        $this->set($field,new Expression($value));
        return $this;
    }
}