<?php

namespace BlitzPHP\Database\MySQL;

use BlitzPHP\Database\BaseConnection;
use BlitzPHP\Exceptions\DatabaseException;
use mysqli;
use PDO;
use PDOException;

/**
 * Connexion MySQL
 */
class Connection extends BaseConnection
{
    protected $error = [
        'message' => '',
        'code' => 0
    ];

	/**
     * DELETE hack flag
     *
     * Whether to use the MySQL "delete hack" which allows the number
     * of affected rows to be shown. Uses a preg_replace when enabled,
     * adding a bit more processing to all queries.
     *
     * @var bool
     */
    public $deleteHack = true;

	/**
     * {@inheritDoc}
     */
    public $escapeChar = '`';
	
    /**
	 * Connect to the database.
	 *
	 * @param boolean $persistent
	 *
	 * @return mixed
	 * @throws DatabaseException
	 */
	public function connect(bool $persistent = false)
	{
        $db = null;

        switch ($this->driver)
        {
            case 'mysqli':
                $db = new mysqli(
                    $this->host,
                    $this->username,
                    $this->password,
                    true === $this->withDatabase ? $this->database : null,
                    $this->port
                );

                if ($db->connect_error) {
                    throw new DatabaseException('Connection error: '.$db->connect_error);
                }

                break;
            case 'pdomysql':
            case 'pdo_mysql':
                $this->dsn = true === $this->withDatabase ? sprintf(
                    'mysql:host=%s;port=%d;dbname=%s',
                    $this->host,
                    $this->port,
                    $this->database
				) : sprintf(
                    'mysql:host=%s;port=%d',
                    $this->host,
                    $this->port
				);
				$db = new PDO($this->dsn, $this->username, $this->password);
				$this->commands[] = 'SET SQL_MODE=ANSI_QUOTES';

				break;
            default:
                # code...
                break;
        }
		
        if (!empty($this->charset)) {
            $this->commands[] = "SET NAMES '{$this->charset}'" . (!empty($this->collation) ? " COLLATE '{$this->collation}'" : '');
        }
        $this->type = strpos($this->driver, 'pdo') !== false ? 'pdo' : $this->driver;

		return self::pushConnection('mysql', $this, $db);
	}

	/**
	 * {@inheritdoc}
	 */
	public function reconnect()
	{
		$this->close();
		$this->initialize();
	}

    /**
	 * {@inheritDoc}
	 */
	protected function _close()
	{
		if ($this->isPdo()) {
			return $this->conn = null;
		}

		$this->conn->close();
	}

    /**
	 * {@inheritDoc}
	 */
	public function setDatabase(string $databaseName): bool
	{
		if ($databaseName === '') {
			$databaseName = $this->database;
		}
		if (empty($this->conn)) {
			$this->initialize();
        }

        if (! $this->isPdo()) {
            if ($this->conn->select_db($databaseName)) {
                $this->database = $databaseName;

                return true;
            }
        
			return false;
        }

		return true;
	}

    /**
	 * The name of the platform in use (MySQLi, mssql, etc)
	 */
	public function getPlatform(): string
	{
		if (isset($this->dataCache['platform'])) {
			return $this->dataCache['platform'];
		}

		if (empty($this->conn)) {
			$this->initialize();
        }

		return $this->dataCache['platform'] = !$this->isPdo() ? 'mysql' : $this->conn->getAttribute(PDO::ATTR_DRIVER_NAME);
	}

	/**
	 * Returns a string containing the version of the database being used.
	 */
	public function getVersion(): string
	{
		if (isset($this->dataCache['version'])) {
			return $this->dataCache['version'];
		}

		if (empty($this->conn)) {
			$this->initialize();
        }

		return $this->dataCache['version'] = !$this->isPdo() ? $this->conn->server_version : $this->conn->getAttribute(PDO::ATTR_SERVER_VERSION);
	}

    /**
	 * Executes the query against the database.
	 * 
	 * @return mixed
	 */
	public function execute(string $sql, array $params = [])
	{
		$sql = $this->prepQuery($sql);

        $error = null;
        $result = false;
		$time = microtime(true);

        if (!$this->isPdo()) {
            $result = $this->conn->query($sql);
            if (!$result) {
                $this->error['code'] = $this->conn->errno;
                $this->error['message'] = $error = $this->conn->error;
            }
        }
        else {
            try {
                $result = $this->conn->prepare($sql);

                if (!$result) {
                    $error = $this->conn->errorInfo();
                }
                else {
                    foreach ($params As $key => $value) {
                        $result->bindValue(
                            is_int($key) ? $key + 1 : $key,
                            $value,
                            is_int($value) || is_bool($value) ? PDO::PARAM_INT : PDO::PARAM_STR
                        );
                    }
                    $result->execute();
                }
            }
            catch (PDOException $ex) {
                $this->error['code'] = $ex->getCode();
                $this->error['message'] = $error = $ex->getMessage();
            }
        }

        if ($error !== null) {
			$error = "Database Error: " . $error . "\nSQL: ".$sql;
			if (on_dev()) {
				throw new DatabaseException($error);
			}
			logger()->error($error);
        }

        $this->lastQuery = [
			'sql'      => $sql,
			'start'    => $time,
			'duration' => microtime(true) - $time,
        ];
        $this->stats['queries'][] = &$this->lastQuery;

        return $result;
	}

    /**
     * Returns the last error code and message.
     * Must return this format: ['code' => string|int, 'message' => string]
     * intval(code) === 0 means "no error".
     *
     * @return array<string, int|string>
     */
    public function error(): array
    {
        return $this->error;
    }


	/**
     * Prep the query. If needed, each database adapter can prep the query string
     */
    protected function prepQuery(string $sql): string
    {
        // mysqli_affected_rows() returns 0 for "DELETE FROM TABLE" queries. This hack
        // modifies the query so that it a proper number of affected rows is returned.
        if ($this->deleteHack === true && preg_match('/^\s*DELETE\s+FROM\s+(\S+)\s*$/i', $sql)) {
            return trim($sql) . ' WHERE 1=1';
        }

        return $sql;
    }
	
	/**
	 * {@inheritDoc}
	 */
	protected function _escapeString(string $str): string
	{
		if (is_bool($str)) {
			return (string) $str;
		}

		if (! $this->conn) {
			$this->initialize();
		}

        if (! $this->isPdo()) {
            return "'".$this->conn->real_escape_string($str)."'";
        }
 
		return $this->conn->quote($str);
    }

    /**
	 * Escape Like String Direct
	 * There are a few instances where MySQLi queries cannot take the
	 * additional "ESCAPE x" parameter for specifying the escape character
	 * in "LIKE" strings, and this handles those directly with a backslash.
	 *
	 * @param  string|string[] $str Input string
	 * @return string|string[]
	 */
	public function escapeLikeStringDirect($str)
	{
		if (is_array($str)) {
			foreach ($str as $key => $val) {
				$str[$key] = $this->escapeLikeStringDirect($val);
			}

			return $str;
		}

		$str = $this->_escapeString($str);

		// Escape LIKE condition wildcards
		return str_replace(
			[$this->likeEscapeChar, '%', '_'], 
			['\\' . $this->likeEscapeChar, '\\' . '%', '\\' . '_'], 
			$str
		);
	}

    /**
	 * {@inheritDoc}
	 * 
	 * @uses escapeLikeStringDirect().
	 */
	protected function _listTables(bool $prefixLimit = false): string
	{
		$sql = 'SHOW TABLES FROM ' . $this->escapeIdentifiers($this->database);

		if ($prefixLimit !== false AND $this->prefix !== '') {
			return $sql . " LIKE '" . $this->escapeLikeStringDirect($this->prefix) . "%'";
		}

		return $sql;
	}

	/**
	 * {@inheritDoc}
	 */
	protected function _listColumns(string $table = ''): string
	{
		return 'SHOW COLUMNS FROM ' . $this->protectIdentifiers($this->prefixTable($table), true, null, false);
	}

	/**
	 * Returns an array of objects with field data
	 *
	 * @return \stdClass[]
	 * @throws DatabaseException
	 */
	protected function _fieldData(string $table): array
	{
		$table = $this->protectIdentifiers($this->prefixTable($table), true, null, false);

		if (($query = $this->query('SHOW COLUMNS FROM ' . $table)) === false) {
			throw new DatabaseException('No data fied found');
		}
		$query = $query->result(PDO::FETCH_OBJ);

		$retVal = [];
		for ($i = 0, $c = count($query); $i < $c; $i++) {
			$retVal[$i]       = new \stdClass();
			$retVal[$i]->name = $query[$i]->field ?? $query[$i]->Field;

			sscanf(($query[$i]->type ?? $query[$i]->Type), '%[a-z](%d)', $retVal[$i]->type, $retVal[$i]->max_length);

			$retVal[$i]->nullable    = ($query[$i]->null ?? $query[$i]->Null) === 'YES';
			$retVal[$i]->default     = $query[$i]->default ?? $query[$i]->Default;
			$retVal[$i]->primary_key = (int)(($query[$i]->key ?? $query[$i]->Key) === 'PRI');
		}

		return $retVal;
	}

   	/**
	 * Returns an array of objects with index data
	 *
	 * @return \stdClass[]
	 * @throws DatabaseException
	 * @throws \LogicException
	 */
	public function _indexData(string $table): array
	{
		$table = $this->protectIdentifiers($this->prefixTable($table), true, null, false);

		if (($query = $this->query('SHOW INDEX FROM ' . $table)) === false) {
			throw new DatabaseException('No index data found');
		}

		if (! $indexes = $query->result(PDO::FETCH_ASSOC)) {
			return [];
		}

		$keys = [];

		foreach ($indexes as $index) {
			if (empty($keys[$index['Key_name']])) {
				$keys[$index['Key_name']]       = new \stdClass();
				$keys[$index['Key_name']]->name = $index['Key_name'];

				if ($index['Key_name'] === 'PRIMARY') {
					$type = 'PRIMARY';
				}
				elseif ($index['Index_type'] === 'FULLTEXT') {
					$type = 'FULLTEXT';
				}
				elseif ($index['Non_unique']) {
					if ($index['Index_type'] === 'SPATIAL') {
						$type = 'SPATIAL';
					}
					else {
						$type = 'INDEX';
					}
				}
				else {
					$type = 'UNIQUE';
				}

				$keys[$index['Key_name']]->type = $type;
			}

			$keys[$index['Key_name']]->fields[] = $index['Column_name'];
		}

		return $keys;
	}

	/**
	 * Returns an array of objects with Foreign key data
	 *
	 * @return \stdClass[]
	 * @throws DatabaseException
	 */
	public function _foreignKeyData(string $table): array
	{
		$sql = '
				SELECT
					tc.CONSTRAINT_NAME,
					tc.TABLE_NAME,
					kcu.COLUMN_NAME,
					rc.REFERENCED_TABLE_NAME,
					kcu.REFERENCED_COLUMN_NAME
				FROM information_schema.TABLE_CONSTRAINTS AS tc
				INNER JOIN information_schema.REFERENTIAL_CONSTRAINTS AS rc
					ON tc.CONSTRAINT_NAME = rc.CONSTRAINT_NAME
				INNER JOIN information_schema.KEY_COLUMN_USAGE AS kcu
					ON tc.CONSTRAINT_NAME = kcu.CONSTRAINT_NAME
				WHERE
					tc.CONSTRAINT_TYPE = ' . $this->escape('FOREIGN KEY') . ' AND
					tc.TABLE_SCHEMA = ' . $this->escape($this->database) . ' AND
					tc.TABLE_NAME = ' . $this->escape($this->prefixTable($table));

		if (($query = $this->query($sql)) === false) {
			throw new DatabaseException('No foreign keys found for table '.$table);
		}

		$query = $query->result(PDO::FETCH_OBJ);

		$retVal = [];
		foreach ($query as $row) {
			$obj                      = new \stdClass();
			$obj->constraint_name     = $row->CONSTRAINT_NAME;
			$obj->table_name          = $row->TABLE_NAME;
			$obj->column_name         = $row->COLUMN_NAME;
			$obj->foreign_table_name  = $row->REFERENCED_TABLE_NAME;
			$obj->foreign_column_name = $row->REFERENCED_COLUMN_NAME;

			$retVal[] = $obj;
		}

		return $retVal;
	}

	/**
	 * {@inheritDoc}
	 */
	protected function _disableForeignKeyChecks(): string
	{
		return 'SET FOREIGN_KEY_CHECKS=0';
	}

	/**
	 * {@inheritDoc}
	 */
	protected function _enableForeignKeyChecks(): string
	{
		return 'SET FOREIGN_KEY_CHECKS=1';
    }

	/**
	 * Insert ID
	 */
	public function insertID(): int
	{
		if (! $this->isPdo()) {
			return $this->conn->insert_id;
		}
		
		return $this->conn->lastInsertId();
	}

	/**
	 * {@inheritDoc}
	 */
	public function affectedRows(): int
	{
		if (! $this->isPdo()) {
			return $this->conn->affected_rows ?? 0;
		}

		return $this->queryResult->rowCount();
	}

	/**
     * Renvoi le nombre de ligne retourné par la requete
	 */
	public function numRows(): int
	{
		if (! $this->isPdo()) {
			return $this->queryResult->num_rows ?? 0;
		}
		
		return $this->queryResult->rowCount();
	}

	/**
	 * {@inheritDoc}
	 */
	protected function _transBegin(): bool
	{
        if (! $this->isPdo()) {
            $this->conn->autocommit(false);
            return $this->conn->begin_transaction();
        }

        return $this->conn->beginTransaction();
	}

	/**
	 * {@inheritDoc}
	 */
	protected function _transCommit(): bool
	{
        if ($this->conn->commit()) {
			if (! $this->isPdo()) {
				$this->conn->autocommit(true);
			}
            
			return true;
        }

		return false;
	}

	/**
	 * {@inheritDoc}
	 */
	protected function _transRollback(): bool
	{
		if ($this->conn->rollback()) {
            if (! $this->isPdo()) {
                $this->conn->autocommit(true);
            }

			return true;
		}

		return false;
	}
}
