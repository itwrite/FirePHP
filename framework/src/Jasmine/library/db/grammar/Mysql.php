<?php
/**
 * Created by PhpStorm.
 * User: zzpzero
 * Date: 2018/4/24
 * Time: 16:57
 */

namespace Jasmine\library\db\grammar;


class Mysql extends Grammar
{
    /**
     * @var array
     */
    protected $operators = array(
        '=', '<', '>', '<=', '>=', '<>', '!=',
        'in', 'not in',
        'like', 'not like', 'between',
        '&', '|', '^', '<<', '>>',
        'rlike', 'regexp', 'not regexp',
    );

    /**
     * @param string|array $value
     * @return string
     */
    protected function wrap($value)
    {
        if (is_array($value)) {
            return implode(',', array_map(function ($val) {
                return $this->wrap($val);
            }, $value));
        }
        if (is_string($value) && strpos(strtolower($value), ',') !== false) {
            return $this->wrap(explode(',', $value));
        }

        $arr = explode(' ', preg_replace('/\s+/', ' ', trim($value)), 2);

        $value = $arr[0];
        $alias = isset($arr[1]) ? " {$arr[1]}" : '';

        $wrapped = array();

        $segments = explode('.', $value);

        foreach ($segments as $key => $segment) {

            $segment = str_replace('`', '', $segment);

            $wrapped[] = $segment == '*' ? $segment : "`{$segment}`";
        }

        return implode('.', $wrapped) . $alias;
    }
}