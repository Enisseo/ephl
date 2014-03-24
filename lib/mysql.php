<?php
/**
 * The easy-to-use MySQL classes.
 *
 * @author Enisseo
 */

if (!@include_once(dirname(__FILE__) . DIRECTORY_SEPARATOR . 'database.php'))
{
	interface Database {};
	interface DatabaseTransaction extends Database {};
	interface DatabaseQuery {};
	interface DatabaseSelect extends DatabaseQuery {};
	interface DatabaseInsert extends DatabaseQuery {};
	interface DatabaseDelete extends DatabaseQuery {};
	interface DatabaseUpdate extends DatabaseQuery {};
}

/**
 * The MySQL base class.
 *
 * <p>This is an entry point for all MySQL operations.</p>
 */
class Mysql implements Database
{
	protected $connection = null;
	protected $host = null;
	protected $name = null;
	protected $password = null;
	protected $schema = null;

	public function __construct($host = '127.0.0.1', $name = 'root', $password = '', $schema = '')
	{
		$this->host = $host;
		$this->name = $name;
		$this->password = $password;
		$this->schema = $schema;
	}

	public function connect()
	{
		if (is_null($this->connection))
		{
			$this->connection = mysql_connect($this->host, $this->name, $this->password);
			mysql_select_db($this->schema, $this->connection);
		}
		return $this->connection;
	}

	/**
	 * @return MysqlQuery
	 */
	public function query($query)
	{
		if ($query instanceof MysqlQuery)
		{
			$query->setConnection($this->connect(), $this->schema);
			return $query;
		}

		$connect = $this->connect();
		$q = new MysqlQuery($connect, $this->schema);
		$q->is($query);
		return $q;
	}

	/**
	 * @return MysqlSelect
	 */
	public function select()
	{
		$connect = $this->connect();
		$query = new MysqlSelect($connect, $this->schema);
		if (func_num_args() > 0)
		{
			$args = func_get_args();
			$query->fields((count($args) == 1 && is_array($args[0]))? $args[0]: $args);
		}
		return $query;
	}

	/**
	 * @return MysqlInsert
	 */
	public function insert()
	{
		$connect = $this->connect();
		$query = new MysqlInsert($connect, $this->schema);
		if (func_num_args() == 1)
		{
			$query->set(func_get_arg(0));
		}
		return $query;
	}

	/**
	 * @return MysqlDelete
	 */
	public function delete()
	{
		$connect = $this->connect();
		return new MysqlDelete($connect, $this->schema);
	}

	/**
	 * @return MysqlUpdate
	 */
	public function update()
	{
		$connect = $this->connect();
		$query = new MysqlUpdate($connect, $this->schema);
		if (func_num_args() == 1)
		{
			$query->table(func_get_arg(0));
		}
		return $query;
	}

	/**
	 * @return MysqlTransaction
	 */
	public function transaction()
	{
		$connect = $this->connect();
		return new MysqlTransaction($connect);
	}
}

class MysqlTransaction extends Mysql implements DatabaseTransaction
{
	public function __construct(&$connection)
	{
		$this->connection =& $connection;
	}

	public function start()
	{
		$this->query('SET AUTOCOMMIT = 0')->execute();
		$this->query('START TRANSACTION')->execute();
	}

	public function commit()
	{
		$this->query('COMMIT')->execute();
		$this->query('START TRANSACTION')->execute();
	}

	public function rollback()
	{
		$this->query('ROLLBACK')->execute();
		$this->query('SET AUTOCOMMIT = 1')->execute();
	}
}

class MysqlQuery implements DatabaseQuery
{
	protected $connection = null;
	protected $schema = null;
	protected $query = null;
	protected $parameters = array();

	public function __construct(&$connection, $schema)
	{
		$this->connection = $connection;
		$this->schema = $schema;
	}

	public function setConnection(&$connection, $schema)
	{
		$this->connection = $connection;
		$this->schema = $schema;
	}

	/**
	 * @return MysqlQuery
	 */
	public function is($query)
	{
		$this->query = $query;
		return $this;
	}

	/**
	 * @return MysqlQuery
	 */
	public function with($param, $value = null)
	{
		if (!is_array($param))
		{
			$param = array($param => $value);
		}
		$this->parameters = array_merge($this->parameters, $param);
		return $this;
	}

	protected function escape($value)
	{
		switch (true)
		{
			case is_null($value): return 'NULL';
			case is_array($value):
				foreach ($value as &$v)
				{
					$v = $this->escape($v);
				}
				return '(' . join(', ', $value) . ')';
			//case is_numeric($value): return $value; //Problem with int stored in VARCHAR
			case is_bool($value): return $value? '1': '0';
			case is_int($value): return $value;
			case is_float($value): return $value;
			case is_string($value):
			default: return '\''.mysql_real_escape_string($value, $this->connection).'\'';
		}
	}

	protected function escapeField($field)
	{
		if (strpos('`', $field) !== false) return $field;
		$fieldEscaped = '';
		$parts = preg_split('/\s+AS\s+/', trim($field), 2);
		if (preg_match('/^[a-zA-Z0-9_\-]+(\.[a-zA-Z0-9_\-]+)?$/', $parts[0]))
		{
			$fParts = preg_split('/\./', $parts[0], 2);
			if (count($fParts) == 1)
			{
				$fieldEscaped .= '`' . $fParts[0] . '`';
			}
			else
			{
				$fieldEscaped .= '`' . $fParts[0] . '`.';
				if ($fParts[1] != '*')
				{
					$fieldEscaped .= '`' . $fParts[1] . '`';
				}
				else
				{
					$fieldEscaped .= $fParts[1];
				}
			}
		}
		else
		{
			$fieldEscaped .= $parts[0];
		}
		if (count($parts) == 2)
		{
			$fieldEscaped .= ' AS `' . $parts[1] . '`';
		}
		return $fieldEscaped;
	}

	protected function escapeTable($table)
	{
		if (strpos('`', $table) !== false) return $field;
		$parts = preg_split('/\s+(AS\s+)?/', trim($table), 2);
		if (count($parts) == 2)
		{
			return '`' . $parts[0] . '` `' . $parts[1] . '`';
		}
		return '`' .$parts[0] . '`';
	}

	public function execute()
	{
		$sql = $this->query;
		foreach ($this->parameters as $param => $value)
		{
			$sql = str_replace($param, $this->escape($value), $sql);
		}
		$result = mysql_query($sql, $this->connection);
		if ($error = mysql_error($this->connection))
		{
			trigger_error($error . ' (' . $sql . ')', E_USER_WARNING);
		}
		return $result;
	}
}

class MysqlSelect extends MysqlQuery implements DatabaseSelect
{
	protected $fields = null;
	protected $from = null;
	protected $join = array();
	protected $where = array();
	protected $limit = 0;
	protected $offset = 0;
	protected $orders = array();
	protected $group = null;

	/**
	 * @return MysqlSelect
	 */
	public function fields()
	{
		$args = func_get_args();
		switch (count($args))
		{
			case 0: $this->fields = null; break;
			case 1: $this->fields = is_array($args[0])? $args[0]: array($args[0]); break;
			default: $this->fields = $args; break;
		}
		return $this;
	}

	/**
	 * @return MysqlSelect
	 */
	public function addFields()
	{
		$args = func_get_args();
		$this->fields = array_merge(is_null($this->fields)? array('*'): $this->fields, $args);
		return $this;
	}

	/**
	 * @return MysqlSelect
	 */
	public function from($table)
	{
		$this->from = $table;
		return $this;
	}

	/**
	 * @return MysqlSelect
	 */
	public function leftJoin($table, $where = null)
	{
		$this->join[] = array('LEFT JOIN', $table, $where);
		return $this;
	}

	/**
	 * @return MysqlSelect
	 */
	public function innerJoin($table, $where = null)
	{
		$this->join[] = array('INNER JOIN', $table, $where);
		return $this;
	}

	/**
	 * @return MysqlSelect
	 */
	public function rightJoin($table, $where = null)
	{
		$this->join[] = array('RIGHT JOIN', $table, $where);
		return $this;
	}

	/**
	 * @return MysqlSelect
	 */
	public function where()
	{
		$args = func_get_args();
		$this->where = array_merge($this->where, (count($args) == 1 && is_array($args[0]))? $args[0]: $args);
		return $this;
	}

	/**
	 * @return MysqlSelect
	 */
	public function whereEquals()
	{
		$fieldsValues = array();
		if (func_num_args() == 2)
		{
			$fieldsValues = array(func_get_arg(0) => func_get_arg(1));
		}
		else
		{
			$fieldsValues = func_get_arg(0);
			if (is_string($fieldsValues))
			{
				$this->where[] = $this->escapeField($fieldsValues) . ' != \'\'';
				return $this;
			}
		}
		foreach ($fieldsValues as $field => $value)
		{
			$fieldUniqId = ':' . $field . substr(md5(uniqid()), 0, 8);
			$this->where[] = $this->escapeField($field) . ' = ' . $fieldUniqId;
			$this->with($fieldUniqId, $value);
		}
		return $this;
	}

	/**
	 * @return MysqlSelect
	 */
	public function orderBy($field, $order = 'ASC')
	{
		$this->orders[$field] = strtoupper($order);
		return $this;
	}

	/**
	 * @return MysqlSelect
	 */
	public function groupBy($field, $having = '')
	{
		$this->group = is_array($field)? $field: array($field => $having);
		return $this;
	}

	/**
	 * @return MysqlSelect
	 */
	public function having($having)
	{
		$this->having = $having;
		return $this;
	}

	/**
	 * @return MysqlSelect
	 */
	public function limit($limit, $offset = 0)
	{
		$this->limit = $limit;
		$this->offset = $offset;
		return $this;
	}

	public function execute()
	{
		$fields = '*';
		if (!empty($this->fields))
		{
			$fields = array();
			foreach ($this->fields as $field)
			{
				$fields[] = $this->escapeField($field);
			}
			$fields = join(', ', $fields);
		}

		$joins = array();
		foreach ($this->join as $joinData)
		{
			list($type, $table, $clauses) = $joinData;
			$joinOn = array();
			if (!empty($clauses))
			{
				if (is_array($clauses))
				{
					foreach ($clauses as $clause)
					{
						if (!empty($clause))
						{
							$joinOn[] = $clause;
						}
					}
				}
				else
				{
					$joinOn[] = $clauses;
				}
			}
			$joins[] = sprintf('%s %s' . (!empty($joinOn)? ' ON %s': '%s'),
				$type, $this->escapeTable($table), join(' AND ', $joinOn));
		}

		$groups = array();
		if (!empty($this->group))
		{
			foreach ($this->group as $group => $having)
			{
				$groups[] = $this->escapeField($group) . (empty($having)? '': (' HAVING ' . $having));
			}
		}

		$orders = array();
		if (!empty($this->orders))
		{
			foreach ($this->orders as $field => $order)
			{
				if ($order == 'ASC' || $order == 'DESC')
				{
					$orders[] = $this->escapeField($field) . ' ' . $order;
				}
			}
		}

		$where = array();
		foreach ($this->where as $clause)
		{
			if (!empty($clause))
			{
				$where[] = '(' . $clause . ')';
			}
		}

		$this->query = sprintf('SELECT ' . $fields . ' FROM %s' .
			(!empty($joins)? ' %s': '%s') .
			(!empty($where)? ' WHERE %s': '%s') .
			(!empty($groups)? ' GROUP BY %s': '%s') .
			(!empty($orders)? ' ORDER BY %s': '%s') .
			(!empty($this->limit) || !empty($this->offset)? ' LIMIT ' . $this->offset . ', ' . $this->limit: ''),
			$this->escapeTable($this->from),
			join(' ', $joins),
			join(' AND ', $where),
			join(', ', $groups),
			join(', ', $orders));

		return parent::execute();
	}

	/**
	 * @return array
	 */
	public function fetchFirst($parameters = array())
	{
		$record = $this->fetchArray($parameters, 1, 0);
		if (empty($record))
		{
			return null;
		}
		return $record[0];
	}

	/**
	 * @return array
	 */
	public function fetchArray($parameters = array(), $max = 0, $from = 0)
	{
		$this->parameters = array_merge($this->parameters, $parameters);
		$this->limit = intval($max);
		$this->offset = intval($from);
		$res = $this->execute();
		$result = array();
		while ($arr = mysql_fetch_assoc($res))
		{
			$result[] = $arr;
		}
		return $result;
	}

	/**
	 * @return array
	 */
	public function fetchLists($parameters = array(), $max = 0, $from = 0)
	{
		$this->parameters = array_merge($this->parameters, $parameters);
		$this->limit = intval($max);
		$this->offset = intval($from);
		$res = $this->execute();
		$result = array();
		while ($arr = mysql_fetch_assoc($res))
		{
			$result[] = array_values($arr);
		}
		return $result;
	}

	/**
	 * @return array
	 */
	public function fetchArrayByKey($keyField, $parameters = array(), $max = 0, $from = 0)
	{
		$this->parameters = array_merge($this->parameters, $parameters);
		$this->limit = intval($max);
		$this->offset = intval($from);
		$res = $this->execute();
		$result = array();
		while ($arr = mysql_fetch_assoc($res))
		{
			$result[$arr[$keyField]] = $arr;
		}
		return $result;
	}

	/**
	 * This is an alias of MysqlSelect::fetchArrayByKey
	 * @return array
	 */
	public function fetchBy($keyField, $parameters = array(), $max = 0, $from = 0)
	{
		return $this->fetchArrayByKey($keyField, $parameters = array(), $max, $from);
	}

	/**
	 * @return array
	 */
	public function fetchKeyValue($parameters = array(), $max = 0, $from = 0)
	{
		$this->parameters = array_merge($this->parameters, $parameters);
		$this->limit = intval($max);
		$this->offset = intval($from);
		$res = $this->execute();
		$result = array();
		$niceKeyField = str_replace('`', '', preg_replace('/^(.*\s+AS\s+)/i', '', $this->fields[0]));
		$niceValueField = str_replace('`', '', preg_replace('/^(.*\s+AS\s+)/i', '', $this->fields[1]));
		while ($arr = mysql_fetch_assoc($res))
		{
			$result[$arr[$niceKeyField]] = $arr[$niceValueField];
		}
		return $result;
	}

	/**
	 * @return array
	 */
	public function fetchArrayOf($field, $parameters = array(), $max = 0, $from = 0)
	{
		$this->parameters = array_merge($this->parameters, $parameters);
		$this->limit = intval($max);
		$this->offset = intval($from);
		$res = $this->execute();
		$result = array();
		while ($arr = mysql_fetch_assoc($res))
		{
			$result[] = $arr[$field];
		}
		return $result;
	}

	/**
	 * @return array
	 */
	public function fetchByGroup($keyField, $parameters = array(), $max = 0, $from = 0)
	{
		$this->parameters = array_merge($this->parameters, $parameters);
		$this->limit = intval($max);
		$this->offset = intval($from);
		$res = $this->execute();
		$result = array();
		while ($arr = mysql_fetch_assoc($res))
		{
			if (!isset($result[$arr[$keyField]]))
			{
				$result[$arr[$keyField]] = array();
			}
			$result[$arr[$keyField]][] = $arr;
		}
		return $result;
	}

	/**
	 * @return array
	 */
	public function fetchValue($parameters = array())
	{
		$record = $this->fetchFirst($parameters);
		if (empty($record))
		{
			return null;
		}
		return array_shift($record);
	}
}

class MysqlInsert extends MysqlQuery implements DatabaseInsert
{
	protected $table = null;
	protected $set = array();

	/**
	 * @return MysqlInsert
	 */
	public function into($table)
	{
		$this->table = $table;
		return $this;
	}

	/**
	 * @return MysqlInsert
	 */
	public function set($data)
	{
		$this->set = $data;
		return $this;
	}

	public function execute()
	{
		$columns = array();
		$values = array();
		foreach ($this->set as $key => $value)
		{
			$columns[] = $this->escapeField($key);
			$values[] = str_replace('%', '%%', sprintf('%s', $this->escape($value)));
		}
		$this->query = sprintf('INSERT INTO %s (' .
			join(', ', $columns) . ') VALUES (' .
			join(', ', $values) . ')',
			$this->escapeTable($this->table));
		return parent::execute();
	}

	/**
	 * @return int
	 */
	public function executeAndGetInsertedId()
	{
		//TODO: Transaction
		$this->execute();
		return mysql_insert_id($this->connection);
		//TODO: End transaction
	}
}

class MysqlDelete extends MysqlQuery implements DatabaseDelete
{
	protected $table = null;
	protected $where = array();

	/**
	 * @return MysqlDelete
	 */
	public function from($table)
	{
		$this->table = $table;
		return $this;
	}

	/**
	 * @return MysqlDelete
	 */
	public function where($where)
	{
		$this->where[] = $where;
		return $this;
	}

	/**
	 * @return MysqlDelete
	 */
	public function whereEquals()
	{
		$fieldsValues = array();
		if (func_num_args() == 2)
		{
			$fieldsValues = array(func_get_arg(0) => func_get_arg(1));
		}
		else
		{
			$fieldsValues = func_get_arg(0);
			if (is_string($fieldsValues))
			{
				$this->where[] = $this->escapeField($fieldsValues) . ' != \'\'';
				return $this;
			}
		}
		foreach ($fieldsValues as $field => $value)
		{
			$fieldUniqId = ':' . $field . substr(md5(uniqid()), 0, 8);
			$this->where[] = $this->escapeField($field) . ' = ' . $fieldUniqId;
			$this->with($fieldUniqId, $value);
		}
		return $this;
	}

	public function execute()
	{
		$where = array();
		foreach ($this->where as $clause)
		{
			if (!empty($clause))
			{
				$where[] = '(' . $clause . ')';
			}
		}
		$where = join(' AND ', $where);
		$this->query = sprintf('DELETE FROM %s ' .
			(!empty($this->where)? ' WHERE %s': ''),
			$this->escapeTable($this->table), $where);
		return parent::execute();
	}
}

class MysqlUpdate extends MysqlQuery implements DatabaseUpdate
{
	protected $table = null;
	protected $set = array();
	protected $where = array();

	/**
	 * @return MysqlUpdate
	 */
	public function table($table)
	{
		$this->table = $table;
		return $this;
	}

	/**
	 * @return MysqlUpdate
	 */
	public function where($where)
	{
		$this->where[] = $where;
		return $this;
	}

	/**
	 * @return MysqlUpdate
	 */
	public function whereEquals($fieldsValues)
	{
		$fieldsValues = array();
		if (func_num_args() == 2)
		{
			$fieldsValues = array(func_get_arg(0) => func_get_arg(1));
		}
		else
		{
			$fieldsValues = func_get_arg(0);
			if (is_string($fieldsValues))
			{
				$this->where[] = $this->escapeField($fieldsValues) . ' != \'\'';
				return $this;
			}
		}
		foreach ($fieldsValues as $field => $value)
		{
			$fieldUniqId = ':' . $field . substr(md5(uniqid()), 0, 8);
			$this->where[] = $this->escapeField($field) . ' = ' . $fieldUniqId;
			$this->with($fieldUniqId, $value);
		}
		return $this;
	}

	/**
	 * @return MysqlUpdate
	 */
	public function set($data)
	{
		$this->set = $data;
		return $this;
	}

	public function execute()
	{
		$set = array();
		if (is_array($set))
		{
			foreach ($this->set as $key => $value)
			{
				$set[] = sprintf('%s = %s', $this->escapeField($key), $this->escape($value));
			}
		}
		else
		{
			$set[] = $set;
		}

		$where = array();
		foreach ($this->where as $clause)
		{
			if (!empty($clause))
			{
				$where[] = '(' . $clause . ')';
			}
		}
		$where = join(' AND ', $where);
		$this->query = sprintf('UPDATE %s SET ' . join(', ', $set) .
			(!empty($this->where)? ' WHERE %s': ''),
			$this->escapeTable($this->table), $where);
		return parent::execute();
	}
}