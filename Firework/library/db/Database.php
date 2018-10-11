<?php
/**
 * Created by PhpStorm.
 * User: zzpzero
 * Date: 2018/4/24
 * Time: 10:10
 */

namespace Firework\library\db;

use Firework\library\db\grammar\Grammar;

require_once("Builder.php");

class Database extends Builder
{
    /**
     * @var bool
     */
    private $_debug = false;

    /**
     * @var null|\PDO
     */
    private $_pdo = null;

    /**
     * @param \PDO $pdo
     * @param Grammar $grammar
     */
    public function __construct(\PDO $pdo, Grammar $grammar = null)
    {
        parent::__construct();
        $this->_pdo = $pdo;
        $this->setGrammar($grammar);
    }

    /**
     * @return null|\PDO
     */
    public function pdo()
    {
        return $this->_pdo;
    }

    /**
     * @param bool $debug
     * @return $this
     */
    public function debug($debug = false)
    {
        if (func_num_args() > 0) {
            $this->_debug = $debug;
        }
        return $this->_debug;
    }

    /**
     * @param array $data
     * @return bool|int
     */
    public function insert(Array $data = array())
    {
        //set data
        $this->set($data);
        //get the insert sql
        $SQL = $this->getInsertSql();
        //begin transaction
        $this->beginTransaction();
        //execute the sql
        $this->exec($SQL);
        //get the inserted Id
        $lastInsertId = $this->lastInsertId();
        //commit
        $res = $this->commit();
        if ($res == false) {
            //if failure, roll back the sql;
            $this->rollBack();
        }
        return intval($lastInsertId);
    }

    /**
     * @return int
     */
    public function delete()
    {
        //get the delete sql
        $SQL = $this->getDeleteSql();

        return $this->exec($SQL);
    }

    /**
     * @param array $data
     * @return int
     */
    public function update(Array $data)
    {
        //set data
        $this->set($data);
        //get the update sql;
        $SQL = $this->getUpdateSql();

        return $this->exec($SQL);
    }

    /**
     * @param int $fetch_type
     * @return bool|mixed
     */
    public function getOne($fetch_type = \PDO::FETCH_ASSOC)
    {
        $this->limit(1);
        //get the select sql
        $SQL = $this->getSelectSql();
        //query
        $st = $this->query($SQL);
        if ($st !== false) {
            //return the result
            return $st->fetch($fetch_type);
        }
        return false;
    }

    /**
     * @param int $fetch_type
     * @return array
     */
    public function getAll($fetch_type = \PDO::FETCH_ASSOC)
    {
        //get the select sql
        $SQL = $this->getSelectSql();
        //query
        $st = $this->query($SQL);
        if ($st) {
            return $st->fetchAll($fetch_type);
        }
        return array();
    }


    /**
     * (PHP 5 &gt;= 5.1.0, PECL pdo &gt;= 0.1.0)<br/>
     * Initiates a transaction
     * @link http://php.net/manual/en/pdo.begintransaction.php
     * @return bool <b>TRUE</b> on success or <b>FALSE</b> on failure.
     */
    public function beginTransaction()
    {
        return $this->pdo()->beginTransaction();
    }

    /**
     * (PHP 5 &gt;= 5.1.0, PECL pdo &gt;= 0.1.0)<br/>
     * Commits a transaction
     * @link http://php.net/manual/en/pdo.commit.php
     * @return bool <b>TRUE</b> on success or <b>FALSE</b> on failure.
     */
    public function commit()
    {
        return $this->pdo()->commit();
    }

    /**
     * (PHP 5 &gt;= 5.1.0, PECL pdo &gt;= 0.2.0)<br/>
     * Executes an SQL statement, returning a result set as a PDOStatement object
     * @link http://php.net/manual/en/pdo.query.php
     * @param string $statement <p>
     * The SQL statement to prepare and execute.
     * </p>
     * <p>
     * Data inside the query should be properly escaped.
     * </p>
     * @return \PDOStatement <b>PDO::query</b> returns a PDOStatement object, or <b>FALSE</b>
     * on failure.
     */
    public function query($statement)
    {
        $res = false;
        $this->trace(function () use ($statement, &$res) {
            $res = $this->pdo()->query($statement);
            $this->logSql($statement);
            if ($this->_debug) {
                print_r(sprintf("[SQL Query]: %s %s\r\n", $statement, ($res == true ? '[true]' : '[false]')));
            }
        });
        if ($res === false) {
            $errorInfo = $this->errorInfo();
            die(isset($errorInfo[2]) ? $errorInfo[2] : "");
        }
        return $res;
    }

    /**
     * (PHP 5 &gt;= 5.1.0, PECL pdo &gt;= 0.1.0)<br/>
     * Execute an SQL statement and return the number of affected rows
     * @link http://php.net/manual/en/pdo.exec.php
     * @param string $statement <p>
     * The SQL statement to prepare and execute.
     * </p>
     * <p>
     * Data inside the query should be properly escaped.
     * </p>
     * @return int <b>PDO::exec</b> returns the number of rows that were modified
     * or deleted by the SQL statement you issued. If no rows were affected,
     * <b>PDO::exec</b> returns 0.
     * </p>
     * This function may
     * return Boolean <b>FALSE</b>, but may also return a non-Boolean value which
     * evaluates to <b>FALSE</b>. Please read the section on Booleans for more
     * information. Use the ===
     * operator for testing the return value of this
     * function.
     * <p>
     * The following example incorrectly relies on the return value of
     * <b>PDO::exec</b>, wherein a statement that affected 0 rows
     * results in a call to <b>die</b>:
     * <code>
     * $db->exec() or die(print_r($db->errorInfo(), true));
     * </code>
     */
    public function exec($statement)
    {
        $res = false;
        $this->trace(function () use ($statement, &$res) {
            $res = $this->pdo()->exec($statement);
            $this->logSql($statement);
            if ($this->_debug) {
                print_r(sprintf("[SQL Execute:]%s", $statement), ($res == true ? '[true]' : '[false]'));
            }
        });
        if ($res === false) {
            $errorInfo = $this->errorInfo();
            die(isset($errorInfo[2]) ? $errorInfo[2] : "");
        }
        return $res;
    }

    /**
     * (PHP 5 &gt;= 5.1.0, PECL pdo &gt;= 0.1.0)<br/>
     * Fetch the SQLSTATE associated with the last operation on the database handle
     * @link http://php.net/manual/en/pdo.errorcode.php
     * @return mixed an SQLSTATE, a five characters alphanumeric identifier defined in
     * the ANSI SQL-92 standard. Briefly, an SQLSTATE consists of a
     * two characters class value followed by a three characters subclass value. A
     * class value of 01 indicates a warning and is accompanied by a return code
     * of SQL_SUCCESS_WITH_INFO. Class values other than '01', except for the
     * class 'IM', indicate an error. The class 'IM' is specific to warnings
     * and errors that derive from the implementation of PDO (or perhaps ODBC,
     * if you're using the ODBC driver) itself. The subclass value '000' in any
     * class indicates that there is no subclass for that SQLSTATE.
     * </p>
     * <p>
     * <b>PDO::errorCode</b> only retrieves error codes for operations
     * performed directly on the database handle. If you create a PDOStatement
     * object through <b>PDO::prepare</b> or
     * <b>PDO::query</b> and invoke an error on the statement
     * handle, <b>PDO::errorCode</b> will not reflect that error.
     * You must call <b>PDOStatement::errorCode</b> to return the error
     * code for an operation performed on a particular statement handle.
     * </p>
     * <p>
     * Returns <b>NULL</b> if no operation has been run on the database handle.
     */
    public function errorCode()
    {
        return $this->pdo()->errorCode();
    }

    /**
     * (PHP 5 &gt;= 5.1.0, PECL pdo &gt;= 0.1.0)<br/>
     * Fetch extended error information associated with the last operation on the database handle
     * @link http://php.net/manual/en/pdo.errorinfo.php
     * @return array <b>PDO::errorInfo</b> returns an array of error information
     * about the last operation performed by this database handle. The array
     * consists of the following fields:
     * <tr valign="top">
     * <td>Element</td>
     * <td>Information</td>
     * </tr>
     * <tr valign="top">
     * <td>0</td>
     * <td>SQLSTATE error code (a five characters alphanumeric identifier defined
     * in the ANSI SQL standard).</td>
     * </tr>
     * <tr valign="top">
     * <td>1</td>
     * <td>Driver-specific error code.</td>
     * </tr>
     * <tr valign="top">
     * <td>2</td>
     * <td>Driver-specific error message.</td>
     * </tr>
     * </p>
     * <p>
     * If the SQLSTATE error code is not set or there is no driver-specific
     * error, the elements following element 0 will be set to <b>NULL</b>.
     * </p>
     * <p>
     * <b>PDO::errorInfo</b> only retrieves error information for
     * operations performed directly on the database handle. If you create a
     * PDOStatement object through <b>PDO::prepare</b> or
     * <b>PDO::query</b> and invoke an error on the statement
     * handle, <b>PDO::errorInfo</b> will not reflect the error
     * from the statement handle. You must call
     * <b>PDOStatement::errorInfo</b> to return the error
     * information for an operation performed on a particular statement handle.
     */
    public function errorInfo()
    {
        return $this->pdo()->errorInfo();
    }

    /**
     * (PHP 5 &gt;= 5.1.0, PECL pdo &gt;= 0.2.1)<br/>
     * Quotes a string for use in a query.
     * @link http://php.net/manual/en/pdo.quote.php
     * @param string $string <p>
     * The string to be quoted.
     * </p>
     * @param int $parameter_type [optional] <p>
     * Provides a data type hint for drivers that have alternate quoting styles.
     * </p>
     * @return string a quoted string that is theoretically safe to pass into an
     * SQL statement. Returns <b>FALSE</b> if the driver does not support quoting in
     * this way.
     */
    public function quote($string, $parameter_type = \PDO::PARAM_STR)
    {
        return call_user_func_array(array($this->pdo(), __FUNCTION__), func_get_args());
    }

    /**
     * (PHP 5 &gt;= 5.1.0, PECL pdo &gt;= 0.2.0)<br/>
     * Retrieve a statement attribute
     * @link http://php.net/manual/en/pdostatement.getattribute.php
     * @param int $attribute
     * @return mixed the attribute value.
     */
    public function getAttribute($attribute)
    {
        return $this->pdo()->getAttribute($attribute);
    }

    /**
     * (PHP 5 &gt;= 5.1.3, PECL pdo &gt;= 1.0.3)<br/>
     * Return an array of available PDO drivers
     * @link http://php.net/manual/en/pdo.getavailabledrivers.php
     * @return array <b>PDO::getAvailableDrivers</b> returns an array of PDO driver names. If
     * no drivers are available, it returns an empty array.
     */
    public function getAvailableDrivers()
    {
        return $this->pdo()->getAvailableDrivers();
    }

    /**
     * (PHP 5 &gt;= 5.1.0, PECL pdo &gt;= 0.1.0)<br/>
     * Set an attribute
     * @link http://php.net/manual/en/pdo.setattribute.php
     * @param int $attribute
     * @param mixed $value
     * @return bool <b>TRUE</b> on success or <b>FALSE</b> on failure.
     */
    public function setAttribute($attribute, $value)
    {
        return $this->pdo()->setAttribute($attribute, $value);
    }

    /**
     * (PHP 5 &gt;= 5.3.3, Bundled pdo_pgsql)<br/>
     * Checks if inside a transaction
     * @link http://php.net/manual/en/pdo.intransaction.php
     * @return bool <b>TRUE</b> if a transaction is currently active, and <b>FALSE</b> if not.
     */
    function inTransaction()
    {
        return $this->pdo()->inTransaction();
    }

    /**
     * (PHP 5 &gt;= 5.1.0, PECL pdo &gt;= 0.1.0)<br/>
     * Returns the ID of the last inserted row or sequence value
     * @link http://php.net/manual/en/pdo.lastinsertid.php
     * @param string $name [optional] <p>
     * Name of the sequence object from which the ID should be returned.
     * </p>
     * @return string If a sequence name was not specified for the <i>name</i>
     * parameter, <b>PDO::lastInsertId</b> returns a
     * string representing the row ID of the last row that was inserted into
     * the database.
     * </p>
     * <p>
     * If a sequence name was specified for the <i>name</i>
     * parameter, <b>PDO::lastInsertId</b> returns a
     * string representing the last value retrieved from the specified sequence
     * object.
     * </p>
     * <p>
     * If the PDO driver does not support this capability,
     * <b>PDO::lastInsertId</b> triggers an
     * IM001 SQLSTATE.
     */
    public function lastInsertId($name = null)
    {
        return $this->pdo()->lastInsertId($name);
    }

    /**
     * (PHP 5 &gt;= 5.1.0, PECL pdo &gt;= 0.1.0)<br/>
     * Rolls back a transaction
     * @link http://php.net/manual/en/pdo.rollback.php
     * @return bool <b>TRUE</b> on success or <b>FALSE</b> on failure.
     */
    public function rollBack()
    {
        return $this->pdo()->rollBack();
    }

    /**
     * @param $callback
     * @return $this
     */
    public function trace($callback)
    {
        if ($callback instanceof \Closure) {
            $time_arr = explode(' ', microtime(false));
            $start_time = $time_arr[0] + $time_arr[1];
            $start_memory = memory_get_usage();
            if ($this->_debug) {
                print_r(sprintf("[Start Time:%s, Memory:%skb]\r\n", date("H:i:s", intval($time_arr[1])) . substr($time_arr[0], 1), strval($start_memory)));
            }
            //callback
            call_user_func_array($callback, array());
            //end time
            $time_arr = explode(' ', microtime(false));
            $end_time = $time_arr[0] + $time_arr[1];
            $send_memory = memory_get_usage();
            if ($this->_debug) {
                $runtime = number_format($end_time - $start_time, 10);
                $used_memory = number_format((memory_get_usage() - $start_memory) / 1024, 2);
                print_r(sprintf("[End Time:%s, Memory:%skb, Runtime:%s]\r\n", date("H:i:s", intval($time_arr[1])) . substr($time_arr[0], 1), strval($send_memory), $runtime, $used_memory));
            }
        }
        return $this;
    }

    /**
     * @var array
     */
    protected $logSQLs = array();

    /**
     * pop out the last one SQL;
     * @return string
     */
    public function getLastSql()
    {
        $SQL = array_pop(array_values($this->logSQLs));
        return $SQL ? $SQL : "";
    }

    /**
     * @param string $sql
     * @return $this
     */
    protected function logSql($sql)
    {
        $this->logSQLs[] = $sql;
        return $this;
    }
}