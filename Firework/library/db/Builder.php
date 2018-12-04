<?php
/**
 * Created by PhpStorm.
 * User: zzpzero
 * Date: 2018/4/24
 * Time: 10:10
 */

namespace Firework\library\db;

use Firework\library\db\grammar\Grammar;
use Firework\library\db\query\From;
use Firework\library\db\query\Group;
use Firework\library\db\query\Having;
use Firework\library\db\query\Join;
use Firework\library\db\query\Limit;
use Firework\library\db\query\Order;
use Firework\library\db\query\schema\Eloquent;
use Firework\library\db\query\Select;
use Firework\library\db\query\Set;
use Firework\library\db\query\Where;

require_once("grammar/Grammar.php");

require_once("query/From.php");
require_once("query/Group.php");
require_once("query/Having.php");
require_once("query/Join.php");
require_once("query/Limit.php");
require_once("query/Order.php");
require_once("query/Select.php");
require_once("query/Set.php");
require_once("query/Where.php");

class Builder
{
    /**
     *
     */
    function __construct()
    {
        $this->setGrammar(new Grammar());
        $this->Select = new Select();
        $this->From = new From();
        $this->Join = new Join();
        $this->Set = new Set();
        $this->Where = new Where();
        $this->Order = new Order();
        $this->Group = new Group();
        $this->Having = new Having();
        $this->Limit = new Limit();
    }

    /**
     * @var Grammar|null
     */
    protected $Grammar = null;

    /**
     * @return Grammar|null
     */
    function getGrammar()
    {
        if ($this->Grammar == null) {
            $this->Grammar = new Grammar();
        }
        return $this->Grammar;
    }

    /**
     * @param Grammar $grammar
     * @return $this
     */
    function setGrammar(Grammar $grammar)
    {
        if ($grammar instanceof Grammar) {
            $this->Grammar = $grammar;
        }
        return $this;
    }

    /**
     * @var Select|null
     */
    protected $Select = null;

    /**
     * @return Select|null
     */
    function getSelect()
    {
        return $this->Select;
    }

    /**
     * @var From|null
     */
    protected $From = null;

    /**
     * @return From|null
     */
    function getFrom()
    {
        return $this->From;
    }

    /**
     * @var Join|null
     */
    protected $Join = null;

    /**
     * @return Join|null
     */
    function getJoin()
    {
        return $this->Join;
    }

    /**
     * @var Set|null
     */
    protected $Set = null;

    /**
     * @return Set|null
     */
    function getSet()
    {
        return $this->Set;
    }

    /**
     * @var Where|null
     */
    protected $Where = null;

    /**
     * @return Where|null
     */
    function getWhere()
    {
        return $this->Where;
    }

    /**
     * @var Order|null
     */
    protected $Order = null;

    /**
     * @return Order|null
     */
    function getOrder()
    {
        return $this->Order;
    }

    /**
     * @var Group|null
     */
    protected $Group = null;

    /**
     * @return Group|null
     */
    function getGroup()
    {
        return $this->Group;
    }

    /**
     * @var Having|null
     */
    protected $Having = null;

    /**
     * @return Having|null
     */
    function getHaving()
    {
        return $this->Having;
    }

    /**
     * @var Limit|null
     */
    protected $Limit = null;

    /**
     * @return Limit|null
     */
    function getLimit()
    {
        return $this->Limit;
    }

    /**
     * @param mixed $fields
     * @return $this
     */
    function select($fields = '*')
    {
        if ($fields instanceof \Closure) {
            $this->getSelect()->field(function () use ($fields) {
                return call_user_func($fields, (new self()));
            });
        } else {
            $this->getSelect()->field($fields);
        }
        return $this;
    }

    /**
     * @param $table
     * @return $this
     */
    function table($table)
    {
        $this->getFrom()->table($table);
        return $this;
    }

    /**
     * @param $table
     * @param string $on
     * @param string $type
     * @return $this
     */
    function join($table, $on = '', $type = '')
    {
        call_user_func_array(array($this->getJoin(), 'join'), func_get_args());
        return $this;
    }

    /**
     * @param $field
     * @param string $operator
     * @param string $value
     * @param string $boolean
     * @return $this
     */
    function where($field, $operator = '', $value = '', $boolean = 'and')
    {
        call_user_func_array(array($this->getWhere(), 'where'), func_get_args());
        return $this;
    }

    /**
     * @param $field
     * @param array $values
     * @param string $boolean
     * @return $this
     */
    public function whereIn($field, Array $values, $boolean = 'and')
    {
        return $this->where($field, 'in', $values, $boolean);
    }

    /**
     * @param $field
     * @param array $values
     * @param string $boolean
     * @return $this
     */
    public function whereNotIn($field, Array $values, $boolean = 'and')
    {
        return $this->where($field, 'not in', $values, $boolean);
    }

    /**
     * @param $field
     * @param array $values
     * @param string $boolean
     * @return $this
     */
    public function whereBetween($field, Array $values, $boolean = 'and')
    {
        return $this->where($field, 'between', $values, $boolean);
    }

    /**
     * @param $field
     * @param $value
     * @param string $boolean
     * @return $this
     */
    public function whereLike($field, $value, $boolean = 'and')
    {
        return $this->where($field, 'like', $value, $boolean);
    }

    /**
     * @param string $field
     * @return $this
     */
    function order($field = '')
    {
        $this->getOrder()->field($field);
        return $this;
    }

    /**
     * @param string $field
     * @return $this
     */
    function group($field = '')
    {
        $this->getGroup()->field($field);
        return $this;
    }

    /**
     * @param $field
     * @param string $operator
     * @param string $value
     * @param string $boolean
     * @return $this
     */
    function having($field, $operator = '', $value = '', $boolean = 'and')
    {
        call_user_func_array(array($this->getHaving(), 'having'), func_get_args());
        return $this;
    }

    /**
     * @param int $offset
     * @param int $page_size
     * @return $this
     */
    function limit($offset = 0, $page_size = 10)
    {
        if (func_num_args() == 1) {
            $this->Limit->setOffset(0)->setPageSize($offset);
        } else {
            $this->Limit->setOffset($offset)->setPageSize($page_size);
        }
        return $this;
    }

    /**
     * @param array|string|mixed $field
     * @param string|mixed $value
     * @return $this
     */
    function set($field, $value = '')
    {
        $this->getSet()->set($field, $value);
        return $this;
    }

    /**
     * @param string $option
     * @return $this
     */
    function roll($option = '')
    {
        foreach (func_get_args() as $arg) {
            if (is_string($arg) && !empty($arg)) {
                $arr = explode(',', $arg);
                foreach ($arr as $prop) {
                    if (property_exists($this, $prop = ucfirst($prop))) {
                        $target = $this->$prop;
                        if ($target instanceof Eloquent) {
                            $target->rollback();
                        }
                    }
                }
            } elseif (is_array($arg)) {
                foreach ($arg as $v) {
                    $this->roll($v);
                }
            }
        }
        return $this;
    }

    /**
     * @return string
     */
    function getCountSql()
    {
        return $this->getGrammar()->toCountSql($this);
    }

    /**
     * @return string
     */
    function getSelectSql()
    {
        return $this->getGrammar()->toSelectSql($this);
    }

    /**
     * @param bool $is_replace
     * @return string
     */
    function getInsertSql($is_replace = false)
    {
        return $this->getGrammar()->toInsertSql($this, $is_replace);
    }

    /**
     * @return string
     */
    function getDeleteSql()
    {
        return $this->getGrammar()->toDeleteSql($this);
    }

    /**
     * @return string
     */
    function getUpdateSql()
    {
        return $this->getGrammar()->toUpdateSql($this);
    }

    /**
     * @param string $operation
     * @return $this
     */
    function clear($operation = '')
    {
        $operations = explode(',', "select,from,join,where,order,group,having,limit,set");
        if (func_num_args() == 0) {
            $this->clear($operations);
        } elseif (is_array($operation)) {
            $operation = array_values($operation);
            foreach ($operation as $operate) {
                $this->clear($operate);
            }
            return $this;
        } elseif (in_array($operation = strtolower($operation), $operations) && property_exists($this, ucfirst($operation))) {
            $this->{ucfirst($operation)}->clear();
        }
        return $this;
    }
}