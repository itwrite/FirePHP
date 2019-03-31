<?php
/**
 * Created by PhpStorm.
 * User: Peter
 * Date: 2019/3/4
 * Time: 10:46
 */

namespace Jasmine\library;

use Jasmine\App;
use Jasmine\helper\Config;
use Jasmine\library\db\Database;

/**
 *
 * @method $this debug($debug = true)
 * @method $this join($table, $on = '', $type = '')
 * @method $this where($field, $operator = '', $value = '', $boolean = 'and')
 *
 * @method $this whereIn($field, Array $values, $boolean = 'and')
 * @method $this whereNotIn($field, Array $values, $boolean = 'and')
 * @method $this whereBetween($field, Array $values, $boolean = 'and')
 * @method $this whereLike($field, $value, $boolean = 'and')
 * @method $this order($field = '')
 * @method $this group($field = '')
 * @method $this having($field, $operator = '', $value = '', $boolean = 'and')
 * @method $this limit($offset = 0, $page_size = 10)
 * @method $this set($field, $value = '')
 * @method $this roll($option = '')
 *
 * @method int insert(Array $data, $is_replace = false)
 * @method int delete()
 * @method int update(Array $data)
 * @method int count()
 *
 * @method string getLastSql()
 *
 * @method \PDOStatement|false query($statement)
 * @method \PDOStatement|false exec($statement)
 * Class Model
 */
class Model
{
    private $db = null;
    protected $pk = '';
    protected $table_prefix = "";
    protected $table_name = "";
    protected $table_alias = "";

    function __construct()
    {
        /**
         * 使用框架的pdo连接数据库
         */
        $this->db = App::init()->getDb();

        $arr = explode('\\', get_class($this));
        $class_name = array_pop($arr);
        unset($arr);

        $this->table_prefix = empty($this->table_prefix) ? Config::get('db.table_prefix', '') : $this->table_prefix;

        $this->table_name = empty($this->table_name) ? $class_name : $this->table_name;
    }

    /**
     * Desc:
     * User: Peter
     * Date: 2019/3/4
     * Time: 11:34
     *
     * @return string
     */
    function getTableFullName()
    {
        return implode(' ', [$this->table_prefix . $this->table_name, $this->table_alias]);
    }

    /**
     *
     * User: Peter
     * Date: 2019/3/19
     * Time: 11:47
     *
     * @param string $table
     * @return $this
     */
    function table($table)
    {
        $table = preg_replace('/\s+/', ' ', trim($table));
        $arr = explode(' ', $table);
        $this->table_name = $arr[0];
        $this->table_alias = isset($arr[1]) ? $arr[1] : $this->table_alias;
        unset($arr);
        return $this;
    }

    /**
     *
     * User: Peter
     * Date: 2019/3/19
     * Time: 11:23
     *
     * @param string $alias
     * @return $this
     */
    function alias($alias)
    {
        $this->table_alias = $alias;
        return $this;
    }

    /**
     * Desc:
     * User: Peter
     * Date: 2019/3/4
     * Time: 11:34
     *
     * @return Database|null
     */
    function getDb()
    {
        return $this->db;
    }

    /**
     *
     * User: Peter
     * Date: 2019/3/19
     * Time: 11:26
     *
     * @return string
     */
    function getPk()
    {
        return $this->pk;
    }

    /**
     *
     * User: Peter
     * Date: 2019/3/31
     * Time: 19:31
     *
     * @param int $id
     * @param int $fetch_type
     * @return bool|mixed
     * @throws \ErrorException
     */
    function find($id=0,$fetch_type = \PDO::FETCH_ASSOC)
    {
        $this->getDb()->getFrom()->clear()->table($this->getTableFullName());
        if(is_string($id) || is_numeric($id)){
            $this->where($this->getPk(),'=',$id);
        }
        return $this->db->find($fetch_type);
    }

    /**
     *
     * User: Peter
     * Date: 2019/3/19
     * Time: 12:49
     *
     * @param string $fields
     * @param int $fetch_type
     * @return array
     */
    function select($fields = '*', $fetch_type = \PDO::FETCH_ASSOC)
    {
        $this->getDb()->getFrom()->clear()->table($this->getTableFullName());
        return call_user_func_array([$this->db, __FUNCTION__], func_get_args());
    }

    /**
     *
     * User: Peter
     * Date: 2019/3/19
     * Time: 12:50
     *
     * @param string $option
     * @return $this
     */
    function rollback($option = '')
    {
        call_user_func_array([$this, __FUNCTION__], func_get_args());
        return $this;
    }

    /**
     *
     * User: Peter
     * Date: 2019/3/19
     * Time: 11:46
     *
     * @param $name
     * @param $arguments
     * @return $this|mixed
     * @throws \ErrorException
     */
    function __call($name, $arguments)
    {
        // Implement __call() method.
        if (!method_exists($this, $name) && method_exists($this->db, $name)) {
            if (in_array($name, explode(',', 'insert,delete,update,count,getLastSql,query,exec'))) {
                $this->getDb()->getFrom()->clear()->table($this->getTableFullName());
                return call_user_func_array([$this->db, $name], $arguments);
            } elseif (in_array($name, explode(',', 'debug,fields,join,where,whereIn,whereNotIn,whereBetween,whereLike,order,group,limit,having,set,clear'))) {
                call_user_func_array([$this->db, $name], $arguments);
                return $this;
            } else {
                throw new \ErrorException('method is not exists:' . $name);
            }
        }
        return $this;
    }
}