<?php
/**
 * Generic database classes.
 *
 * @author Enisseo
 */
 
/**
 * The database base class.
 *
 * <p>This is an entry point for all database operations.</p>
 */
interface Database
{
	/**
	 * @return DatabaseQuery
	 */
	public function query($query);

	/**
	 * @return DatabaseSelect
	 */
	public function select();

	/**
	 * @return DatabaseInsert
	 */
	public function insert();

	/**
	 * @return DatabaseDelete
	 */
	public function delete();

	/**
	 * @return DatabaseUpdate
	 */
	public function update();
	
	/**
	 * @return DatabaseTransaction
	 */
	public function transaction();
}

interface DatabaseTransaction extends Database
{
	public function start();
	
	public function commit();
	
	public function rollback();
}

interface DatabaseQuery
{
	/**
	 * @return DatabaseQuery
	 */
	public function is($query);

	/**
	 * @return DatabaseQuery
	 */
	public function with($param, $value = null);

	/**
	 * @return mixed the result of the query.
	 */
	public function execute();
}

interface DatabaseSelect extends DatabaseQuery
{
	/**
	 * @return DatabaseSelect
	 */
	public function fields();
	
	/**
	 * @return DatabaseSelect
	 */
	public function addFields();

	/**
	 * @return DatabaseSelect
	 */
	public function from($table);

	/**
	 * @return DatabaseSelect
	 */
	public function leftJoin($table, $where = null);

	/**
	 * @return DatabaseSelect
	 */
	public function innerJoin($table, $where = null);

	/**
	 * @return DatabaseSelect
	 */
	public function rightJoin($table, $where = null);

	/**
	 * @return DatabaseSelect
	 */
	public function where();

	/**
	 * @return DatabaseSelect
	 */
	public function whereEquals();

	/**
	 * @return DatabaseSelect
	 */
	public function orderBy($field, $order = 'ASC');

	/**
	 * @return DatabaseSelect
	 */
	public function groupBy($field, $having = '');

	/**
	 * @return DatabaseSelect
	 */
	public function having($having);
	
	/**
	 * @return DatabaseSelect
	 */
	public function limit($limit, $offset = 0);

	/**
	 * @return array
	 */
	public function fetchFirst($parameters = array());

	/**
	 * @return array
	 */
	public function fetchArray($parameters = array(), $max = 0, $from = 0);

	/**
	 * @return array
	 */
	public function fetchLists($parameters = array(), $max = 0, $from = 0);

	/**
	 * @return array
	 */
	public function fetchArrayByKey($keyField, $parameters = array(), $max = 0, $from = 0);
	
	/**
	 * This is an alias of DatabaseSelect::fetchArrayByKey
	 * @return array
	 */
	public function fetchBy($keyField, $parameters = array(), $max = 0, $from = 0);

	/**
	 * @return array
	 */
	public function fetchKeyValue($parameters = array(), $max = 0, $from = 0);

	/**
	 * @return array
	 */
	public function fetchArrayOf($field, $parameters = array(), $max = 0, $from = 0);

	/**
	 * @return array
	 */
	public function fetchByGroup($keyField, $parameters = array(), $max = 0, $from = 0);

	/**
	 * @return array
	 */
	public function fetchValue($parameters = array());
}

interface DatabaseInsert extends DatabaseQuery
{
	/**
	 * @return DatabaseInsert
	 */
	public function into($table);

	/**
	 * @return DatabaseInsert
	 */
	public function set($data);
	
	/**
	 * @return int
	 */
	public function executeAndGetInsertedId();
}

interface DatabaseDelete extends DatabaseQuery
{
	/**
	 * @return DatabaseDelete
	 */
	public function from($table);

	/**
	 * @return DatabaseDelete
	 */
	public function where($where);

	/**
	 * @return DatabaseDelete
	 */
	public function whereEquals();
}

interface DatabaseUpdate extends DatabaseQuery
{
	/**
	 * @return DatabaseUpdate
	 */
	public function table($table);

	/**
	 * @return DatabaseUpdate
	 */
	public function where($where);

	/**
	 * @return DatabaseUpdate
	 */
	public function whereEquals($fieldsValues);

	/**
	 * @return DatabaseUpdate
	 */
	public function set($data);
}
