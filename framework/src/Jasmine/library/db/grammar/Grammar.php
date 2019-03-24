<?php
/**
 * Created by PhpStorm.
 * User: zzpzero
 * Date: 2018/4/24
 * Time: 10:58
 */

namespace Jasmine\library\db\grammar;


use Jasmine\library\db\Builder;
use Jasmine\library\db\query\capsule\Condition;
use Jasmine\library\db\query\capsule\Expression;
use Jasmine\library\db\query\capsule\JoinObject;
use Jasmine\library\db\query\From;
use Jasmine\library\db\query\Group;
use Jasmine\library\db\query\Having;
use Jasmine\library\db\query\Join;
use Jasmine\library\db\query\Limit;
use Jasmine\library\db\query\Order;
use Jasmine\library\db\query\Select;
use Jasmine\library\db\query\Set;
use Jasmine\library\db\query\Where;

require_once(__DIR__ . "/../query/capsule/Expression.php");
require_once(__DIR__ . "/../query/schema/Eloquent.php");
require_once(__DIR__ . "/../query/From.php");
require_once(__DIR__ . "/../query/Where.php");
require_once(__DIR__ . "/../query/capsule/Condition.php");

/**
 * default Mysql
 * Class Grammar
 * @package Jasmine\library\db\grammar
 */
class Grammar
{

    /**
     * @var array
     */
    protected $operators = array(
        '=', '<', '>', '<=', '>=', '<>', '!=', 'in', 'not in',
        'like', 'not like', 'between', 'ilike',
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

            $wrapped[] = $segment == '*' ? $segment : "{$segment}";
        }

        return implode('.', $wrapped) . $alias;
    }

    /**
     * @param $value
     * @return array|mixed|string
     */
    function wrapValue($value)
    {
        if ($value instanceof Expression) {
            return $value->getValue();
        } elseif (is_string($value)) {
            $value = addslashes($value);
            return "'{$value}'";
        }elseif (is_numeric($value)){
            return $value;
        } elseif (is_array($value)) {
            //return an array
            return array_map(function ($val) {
                return $this->wrapValue($val);
            }, array_values($value));
        } elseif ($value instanceof \Closure) {
            return call_user_func($value, $this);
        }

        return $value ? '' : $value;
    }

    /**
     * @param $field
     * @return string
     */
    function wrapField($field)
    {
        if ($field instanceof Expression) {
            return $field->getValue();
        }
        return $this->wrap($field);
    }

    /**
     * @param $table
     * @return array|mixed|string
     */
    function wrapTable($table)
    {
        if ($table instanceof Expression) {
            return $table->getValue();
        }
        return $this->wrap($table);
    }

    /**
     * @param Condition $condition
     * @return string
     */
    function compileCondition(Condition $condition)
    {

        $value = $condition->getValue();
        $field = $condition->getField();
        $operator = $condition->getOperator();
        if (!in_array($operator, $this->operators, true)) {
            // If the given operator is not found in the list of valid operators we will
            // assume that the developer is just short-cutting the '=' operators and
            // we will set the operators to '=' and set the values appropriately.
            $operator = '=';
        }
        if ($operator == 'between' && is_array($value) && count($value) > 1) {
            return implode(' ', array($this->wrapField($field), strtoupper($operator), $this->wrapValue($value[0]), 'AND', $this->wrapValue($value[0])));
        } else if (is_array($value)) {
            return implode(' ', array($this->wrapField($field), strtoupper($operator), "(" . implode(',', $this->wrapValue($value)) . ")"));
        }

        return implode(' ', array($this->wrapField($field), strtoupper($operator), $this->wrapValue($value)));
    }

    /**
     * @param Select $select
     * @return string
     */
    function compileSelect(Select &$select)
    {
        $SQL = implode(',', array_map(function ($item) {
            return $this->wrapField($item);
        }, $select->data()));

        $select->clear();
        return $SQL;
    }

    /**
     * @param From $from
     * @return string
     */
    function compileFrom(From &$from)
    {
        $SQL = implode(',', array_map(function ($item) {
            return $this->wrapTable($item);
        }, $from->data()));

        $from->clear();
        return $SQL;
    }

    /**
     * @param Join $join
     * @return string
     */
    function compileJoin(Join &$join)
    {
        $sqlArr = array();
        foreach ($join->data() as $item) {
            if ($item instanceof JoinObject) {
                $on = $item->getOn(); //Where
                if ($on instanceof Where) {
                    $on = $this->compileWhere($on);
                }
                $sqlArr[] = implode(' ', array($item->getType(), $this->wrapTable($item->getTable()), 'ON', $on));
            } else {
                $sqlArr[] = $item;
            }

        }
        $join->clear();
        return implode(' ', $sqlArr);
    }

    /**
     * @param Where $where
     * @return string
     */
    function compileWhere(Where &$where)
    {
        $sqlArr = array();
        foreach ($where->data() as $i => $condition) {
            if ($condition instanceof Where || $condition instanceof Condition) {
                if ($i > 0) {
                    $sqlArr[] = $condition->getBoolean() == 'and' ? 'AND' : 'OR';
                }
                if ($condition instanceof Where) {
                    $sqlArr[] = "(" . $this->compileWhere($condition) . ")";
                } else if ($condition instanceof Condition) {
                    $sqlArr[] = $this->compileCondition($condition);
                }
            } else if ($condition instanceof Expression) {
                $sqlArr[] = $condition->getValue();
            } else {
                $sqlArr[] = (string)$condition;
            }
        }
        $where->clear();
        return implode(' ', $sqlArr);
    }


    /**
     * @param Order $order
     * @return string
     */
    protected function compileOrder(Order &$order)
    {
        $SQL = implode(',', array_map(function ($item) {
            return $this->wrapField($item);
        }, $order->data()));

        $order->clear();
        return $SQL;
    }

    /**
     * @param Group $group
     * @return string
     */
    protected function compileGroup(Group &$group)
    {
        $SQL = implode(',', array_map(function ($item) {
            return $this->wrapField($item);
        }, $group->data()));

        $group->clear();
        return $SQL;
    }

    /**
     * @param Having $having
     * @return string
     */
    protected function compileHaving(Having &$having)
    {
        $where = $having->getWhere();
        $SQL = $this->compileWhere($where);

        $having->clear();
        return $SQL;
    }

    /**
     * @param Limit $limit
     * @return string
     */
    protected function compileLimit(Limit &$limit)
    {
        $data = $limit->data();
        $SQL = isset($data[1]) && $data[1] == 0 ? '' : implode(',', $data);

        $limit->clear();
        return $SQL;
    }

    /**
     * @param Set $set
     * @return string
     */
    protected function compileSet(Set &$set)
    {
        $sqlArr = array();
        foreach ($set->data() as $field => $value) {
            $field = $this->wrapField($field);
            $value = $this->wrapValue($value);
            $sqlArr[] = "{$field}={$value}";
        }
        $set->clear();
        return implode(',', $sqlArr);
    }

    /**
     * @param Builder $builder
     * @return string
     */
    function toCountSql(Builder &$builder)
    {
        $select = $builder->getSelect();
        $FIELDS = $this->compileSelect($select);
        $FIELDS = empty($FIELDS) ? "*" : $FIELDS;

        $from = $builder->getFrom();
        $TABLES = $this->compileFrom($from);

        $join = $builder->getJoin();
        $JOINS = $this->compileJoin($join);
        $JOINS = empty($JOINS) ? "" : "{$JOINS}";

        $where = $builder->getWhere();
        $WHERE = $this->compileWhere($where);
        $WHERE = empty($WHERE) ? "" : "WHERE {$WHERE}";

        $order = $builder->getOrder();
        $ORDER_BY = $this->compileOrder($order);
        $ORDER_BY = empty($ORDER_BY) ? "" : "ORDER BY {$ORDER_BY}";

        $group = $builder->getGroup();
        $GROUP_BY = $this->compileGroup($group);
        $GROUP_BY = empty($GROUP_BY) ? "" : "GROUP BY {$GROUP_BY}";

        $having = $builder->getHaving();
        $HAVING = $this->compileHaving($having);
        $HAVING = empty($HAVING) ? "" : "HAVING {$HAVING}";

        return "SELECT COUNT({$FIELDS}) FROM {$TABLES} {$JOINS} {$WHERE} {$ORDER_BY} {$GROUP_BY} {$HAVING}";
    }

    /**
     * @param Builder $builder
     * @return string
     */
    function toSelectSql(Builder &$builder)
    {
        $select = $builder->getSelect();
        $FIELDS = $this->compileSelect($select);
        $FIELDS = empty($FIELDS) ? "*" : $FIELDS;

        $from = $builder->getFrom();
        $TABLES = $this->compileFrom($from);

        $join = $builder->getJoin();
        $JOINS = $this->compileJoin($join);
        $JOINS = empty($JOINS) ? "" : " {$JOINS}";

        $where = $builder->getWhere();
        $WHERE = $this->compileWhere($where);
        $WHERE = empty($WHERE) ? "" : " WHERE {$WHERE}";

        $order = $builder->getOrder();
        $ORDER_BY = $this->compileOrder($order);
        $ORDER_BY = empty($ORDER_BY) ? "" : " ORDER BY {$ORDER_BY}";

        $group = $builder->getGroup();
        $GROUP_BY = $this->compileGroup($group);
        $GROUP_BY = empty($GROUP_BY) ? "" : " GROUP BY {$GROUP_BY}";

        $having = $builder->getHaving();
        $HAVING = $this->compileHaving($having);
        $HAVING = empty($HAVING) ? "" : " HAVING {$HAVING}";

        $limit = $builder->getLimit();
        $LIMIT = $this->compileLimit($limit);
        $LIMIT = empty($LIMIT) ? "" : " LIMIT {$LIMIT}";

        return "SELECT {$FIELDS} FROM {$TABLES}{$JOINS}{$WHERE}{$ORDER_BY}{$GROUP_BY}{$HAVING}{$LIMIT}";
    }

    /**
     * @param Builder $builder
     * @param bool $is_replace
     * @return string
     */
    function toInsertSql(Builder $builder, $is_replace = false)
    {
        $data = $builder->getSet()->data();
        if (!is_array(reset($data))) {
            $data = array($data);
        }

        $FIELDS = implode(',', array_map(function ($field) {
            return $this->wrapField($field);
        }, array_keys(reset($data))));

        $VALUES = implode(',', array_map(function ($value) {
            return "(" . implode(',', array_map(function ($val) {
                    return $this->wrapValue($val);
                }, array_values($value))) . ")";
        }, $data));

        $from = $builder->getFrom();
        $FROM = $this->compileFrom($from);

        return ($is_replace == true ? "REPLACE" : "INSERT") . " INTO {$FROM} ($FIELDS) VALUES $VALUES";
    }

    /**
     * @param Builder $builder
     * @return string
     */
    function toDeleteSql(Builder $builder)
    {
        $TABLES = $this->compileFrom($builder->getFrom());

        $where = $builder->getWhere();
        $WHERE = $this->compileWhere($where);
        $WHERE = empty($WHERE) ? "" : " WHERE {$WHERE}";

        return "DELETE FROM {$TABLES}{$WHERE}";
    }

    /**
     * @param Builder $builder
     * @return string
     */
    function toUpdateSql(Builder $builder)
    {
        $from = $builder->getFrom();
        $TABLES = $this->compileFrom($from);

        $join = $builder->getJoin();
        $JOINS = $this->compileJoin($join);
        $JOINS = empty($JOINS) ? "" : " {$JOINS}";

        $where = $builder->getWhere();
        $WHERE = $this->compileWhere($where);
        $WHERE = empty($WHERE) ? "" : " WHERE {$WHERE}";

        $set = $builder->getSet();
        $SET = $this->compileSet($set);
        return "UPDATE {$TABLES}{$JOINS} SET {$SET}{$WHERE}";
    }
}