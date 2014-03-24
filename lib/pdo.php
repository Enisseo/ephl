<?php
/**
 * The easy-to-use PDO classes.
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
 * The PDO base class.
 *
 * <p>This is an entry point for all PDO operations.</p>
 */
class PdoDatabase implements Database
{
	protected $connection = null;
	protected $dns = null;
	protected $name = null;
	protected $password = null;

	public function __construct($dns = 'mysql:host=127.0.0.1', $name = 'root', $password = '')
	{
		$this->dns = $dns;
		$this->name = $name;
		$this->password = $password;
	}

	public function connect()
	{
		if (is_null($this->connection))
		{
			$this->connection = new PDO($this->dns, $this->name, $this->password);
		}
		return $this->connection;
	}

	/**
	 * @return PdoQuery
	 */
	public function query($query)
	{
		if ($query instanceof PdoQuery)
		{
			$query->setConnection($this->connect());
			return $query;
		}

		$connect = $this->connect();
		$q = new PdoQuery($connect);
		$q->is($query);
		return $q;
	}

	/**
	 * @return PdoSelect
	 */
	public function select()
	{
		$connect = $this->connect();
		$query = new PdoSelect($connect);
		if (func_num_args() > 0)
		{
			$args = func_get_args();
			$query->fields((count($args) == 1 && is_array($args[0]))? $args[0]: $args);
		}
		return $query;
	}

	/**
	 * @return PdoInsert
	 */
	public function insert()
	{
		$connect = $this->connect();
		$query = new PdoInsert($connect);
		if (func_num_args() == 1)
		{
			$query->set(func_get_arg(0));
		}
		return $query;
	}

	/**
	 * @return PdoDelete
	 */
	public function delete()
	{
		$connect = $this->connect();
		return new PdoDelete($connect);
	}

	/**
	 * @return PdoUpdate
	 */
	public function update()
	{
		$connect = $this->connect();
		$query = new PdoUpdate($connect);
		if (func_num_args() == 1)
		{
			$query->table(func_get_arg(0));
		}
		return $query;
	}

	/**
	 * @return PdoTransaction
	 */
	public function transaction()
	{
		$connect = $this->connect();
		return new PdoTransaction($connect);
	}
}

class PdoTransaction extends PdoDatabase implements DatabaseTransaction
{
	public function __construct(&$connection)
	{
		$this->connection =& $connection;
	}

	public function start()
	{
		$this->connection->beginTransaction();
	}

	public function commit()
	{
		$this->connection->commit();
	}

	public function rollback()
	{
		$this->connection->rollBack();
	}
}

class PdoQuery implements DatabaseQuery
{
	protected $connection = null;
	protected $query = null;
	protected $parameters = array();

	public function __construct(&$connection)
	{
		$this->connection = $connection;
	}

	public function setConnection(&$connection)
	{
		$this->connection = $connection;
	}

	/**
	 * @return PdoQuery
	 */
	public function is($query)
	{
		$this->query = $query;
		return $this;
	}

	/**
	 * @return PdoQuery
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

	public function execute()
	{
		$statement = $this->connection->prepare($this->query);
		foreach ($this->parameters as $param => $value)
		{
			$statement->bindValue($param, $value);
		}
		if (!$statement->execute())
		{
			trigger_error(join(', ', $statement->errorInfo()) . ' (' . $this->query . ')', E_USER_WARNING);
		}
		return $statement;
	}
}

class PdoSelect extends PdoQuery implements DatabaseSelect
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
	 * @return PdoSelect
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
	 * @return PdoSelect
	 */
	public function addFields()
	{
		$args = func_get_args();
		$this->fields = array_merge(is_null($this->fields)? array('*'): $this->fields, $args);
		return $this;
	}

	/**
	 * @return PdoSelect
	 */
	public function from($table)
	{
		$this->from = $table;
		return $this;
	}

	/**
	 * @return PdoSelect
	 */
	public function leftJoin($table, $where = null)
	{
		$this->join[] = array('LEFT JOIN', $table, $where);
		return $this;
	}

	/**
	 * @return PdoSelect
	 */
	public function innerJoin($table, $where = null)
	{
		$this->join[] = array('INNER JOIN', $table, $where);
		return $this;
	}

	/**
	 * @return PdoSelect
	 */
	public function rightJoin($table, $where = null)
	{
		$this->join[] = array('RIGHT JOIN', $table, $where);
		return $this;
	}

	/**
	 * @return PdoSelect
	 */
	public function where()
	{
		$args = func_get_args();
		$this->where = array_merge($this->where, (count($args) == 1 && is_array($args[0]))? $args[0]: $args);
		return $this;
	}

	/**
	 * @return PdoSelect
	 */
	public function whereEquals($fieldsValues)
	{
		foreach ($fieldsValues as $field => $value)
		{
			$fieldUniqId = ':' . $field . substr(md5(uniqid()), 0, 8);
			$this->where[] = $field . ' = ' . $fieldUniqId;
			$this->with($fieldUniqId, $value);
		}
		return $this;
	}

	/**
	 * @return PdoSelect
	 */
	public function orderBy($field, $order = 'ASC')
	{
		$this->orders[$field] = strtoupper($order);
		return $this;
	}

	/**
	 * @return PdoSelect
	 */
	public function groupBy($field, $having = '')
	{
		$this->group = is_array($field)? $field: array($field => $having);
		return $this;
	}

	/**
	 * @return PdoSelect
	 */
	public function having($having)
	{
		$this->having = $having;
		return $this;
	}

	/**
	 * @return PdoSelect
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
			$fields = join(', ', $this->fields);
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
				$type, $table, join(' AND ', $joinOn));
		}

		$groups = array();
		if (!empty($this->group))
		{
			foreach ($this->group as $group => $having)
			{
				$groups[] = $group . (empty($having)? '': (' HAVING ' . $having));
			}
		}

		$orders = array();
		if (!empty($this->orders))
		{
			foreach ($this->orders as $field => $order)
			{
				if ($order == 'ASC' || $order == 'DESC')
				{
					$orders[] = $field . ' ' . $order;
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
			$this->from,
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
		$result = $res->fetchAll(PDO::FETCH_ASSOC);
		$res->closeCursor();
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
		$result = $res->fetchAll(PDO::FETCH_NUM);
		$res->closeCursor();
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
		while ($arr = $res->fetch(PDO::FETCH_ASSOC))
		{
			$result[$arr[$keyField]] = $arr;
		}
		$res->closeCursor();
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
		while ($arr = $res->fetch(PDO::FETCH_ASSOC))
		{
			$result[$arr[$niceKeyField]] = $arr[$niceValueField];
		}
		$res->closeCursor();
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
		while ($arr = $res->fetch(PDO::FETCH_ASSOC))
		{
			$result[] = $arr[$field];
		}
		$res->closeCursor();
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
		while ($arr = $res->fetch(PDO::FETCH_ASSOC))
		{
			if (!isset($result[$arr[$keyField]]))
			{
				$result[$arr[$keyField]] = array();
			}
			$result[$arr[$keyField]][] = $arr;
		}
		$res->closeCursor();
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

class PdoInsert extends PdoQuery implements DatabaseInsert
{
	protected $table = null;
	protected $set = array();

	/**
	 * @return PdoInsert
	 */
	public function into($table)
	{
		$this->table = $table;
		return $this;
	}

	/**
	 * @return PdoInsert
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
		foreach ($this->set as $field => $value)
		{
			$fieldUniqId = ':' . $field . substr(md5(uniqid()), 0, 8);
			$columns[] = $field;
			$values[] = $fieldUniqId;
			$this->parameters[$fieldUniqId] = $value;
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
		return $this->connection->lastInsertId();
		//TODO: End transaction
	}
}

class PdoDelete extends PdoQuery implements DatabaseDelete
{
	protected $table = null;
	protected $where = array();

	/**
	 * @return PdoDelete
	 */
	public function from($table)
	{
		$this->table = $table;
		return $this;
	}

	/**
	 * @return PdoDelete
	 */
	public function where($where)
	{
		$this->where[] = $where;
		return $this;
	}

	/**
	 * @return PdoDelete
	 */
	public function whereEquals($fieldsValues)
	{
		foreach ($fieldsValues as $field => $value)
		{
			$fieldUniqId = ':' . $field . substr(md5(uniqid()), 0, 8);
			$this->where[] = $field . ' = ' . $fieldUniqId;
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
			$this->table, $where);
		return parent::execute();
	}
}

class PdoUpdate extends PdoQuery implements DatabaseUpdate
{
	protected $table = null;
	protected $set = array();
	protected $where = array();

	/**
	 * @return PdoUpdate
	 */
	public function table($table)
	{
		$this->table = $table;
		return $this;
	}

	/**
	 * @return PdoUpdate
	 */
	public function where($where)
	{
		$this->where = $where;
		return $this;
	}

	/**
	 * @return PdoUpdate
	 */
	public function whereEquals($fieldsValues)
	{
		foreach ($fieldsValues as $field => $value)
		{
			$fieldUniqId = ':' . $field . substr(md5(uniqid()), 0, 8);
			$this->where[] = $field . ' = ' . $fieldUniqId;
			$this->with($fieldUniqId, $value);
		}
		return $this;
	}

	/**
	 * @return PdoUpdate
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
			foreach ($this->set as $field => $value)
			{
				$fieldUniqId = ':' . $field . substr(md5(uniqid()), 0, 8);
				$this->parameters[$fieldUniqId] = $value;
				$set[] = sprintf('%s = %s', $field, $fieldUniqId);
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
			$this->table, $where);
		return parent::execute();
	}
}