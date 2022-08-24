<?php
namespace BlitzPHP\Database;

use BlitzPHP\Contracts\Database\ResultInterface;
use PDO;

abstract class BaseResult implements ResultInterface
{
    /**
     * Details de la requete
     *
     * @var array
     */
    private $details = [
        'num_rows'      => 0,
		'affected_rows' => 0,
		'insert_id'     => -1
    ];

    /**
     * Database query object
     *
     * @var object|resource
     */
    protected $query;

    /**
     * @var BaseConnection
     */
    protected $db;

    /**
     * @var integer
     */
    private $currentRow = 0;


    /**
     * Constructor
     *
     * @param BaseConnection $db
     * @param object|resource $query
     */
    public function __construct(BaseConnection &$db, &$query)
    {
        $this->query = &$query;
        $this->db = &$db;

        // Service::event()->trigger('db.query', $this);
    }

    /**
     * Verifie si on utilise un objet pdo pour la connexion à la base de donnees
     */
    protected function isPdo(): bool
    {
        return $this->db->isPdo();
    }
    

    /**
     * Fetch multiple rows from a select query.
     *
     * @param int|string $type
     * @alias result()
     */
    public function all($type = PDO::FETCH_OBJ): array
    {
       return $this->result($type);
    }

    /**
     * {@inheritDoc}
     */
    public function first($type = PDO::FETCH_OBJ)
    {
        $records = $this->result($type);

        return empty($records) ? null : $records[0];
    }
    /**
     * Recupere le premier resultat d'une requete en BD
     *
     * @param int|string $type
     * @return mixed
     * @alias first()
     */
    public function one($type = PDO::FETCH_OBJ)
    {
        return $this->first($type);
    }
    
    /**
     * Recupere le dernier element des resultats d'une requete en BD
     *
     * @param int|string $type
     * @return mixed Row
     */
    public function last($type = PDO::FETCH_OBJ)
    {
        $records = $this->all($type);

        if (empty($records)) {
            return null;
        }

        return $records[count($records) - 1];
    }

    /**
	 * {@inheritDoc}
	 */
	public function next($type = PDO::FETCH_OBJ)
	{
        $records = $this->result($type);

		if (empty($records)) {
			return null;
		}

		return isset($records[$this->currentRow + 1]) ? $records[++ $this->currentRow] : null;
	}

    /**
	 * {@inheritDoc}
	 */
	public function previous($type = PDO::FETCH_OBJ)
	{
		$records = $this->result($type);

		if (empty($records)) {
			return null;
		}

		if (isset($records[$this->currentRow - 1])) {
			-- $this->currentRow;
		}

		return $records[$this->currentRow];
	}

    /**
     * {@inheritDoc}
     */
    public function row(int $index, $type = PDO::FETCH_OBJ)
    {
        $records = $this->result($type);

        if (empty($records[$index])) {
            return null;
        }

        return $records[$this->currentRow = $index];
    }

    /**
	 * {@inheritDoc}
	 */
	public function countField(): int
	{
        if ($this->isPdo()) {
            return $this->query->columnCount();
        }

        return $this->_countField();
	}

    /**
     * {@inheritDoc}
     */
    public function result($type = PDO::FETCH_OBJ) : array
    {
        $data = [];

        if ($type === PDO::FETCH_OBJ OR $type === 'object') {
            $data = $this->resultObject();
        }
        else if ($type === PDO::FETCH_ASSOC OR $type === 'array') {
            $data = $this->resultArray();
        }
        else if (is_int($type) && $this->isPdo()) {
            $this->query->setFetchMode($type);
            $data = $this->query->fetchAll();
            $this->query->closeCursor();
        }
        else if (is_string($type))
        {
            if (is_subclass_of($type, Entity::class)) {
                $records = $this->resultArray();

                foreach ($records As $key => $value) {
                    if (!isset($data[$key])) {
                        // $data[$key] = Hydrator::hydrate($value, $type);
                    }
                }
            }
            else if ($this->isPdo()) {
                $this->query->setFetchMode(PDO::FETCH_CLASS, $type);
                $data = $this->query->fetchAll();
                $this->query->closeCursor();
            }
            else {
                $data = $this->_result($type);
            }
        }

        $this->details['num_rows'] = count($data);

        return $data;
    }

    /**
     * {@inheritDoc}
     */
    public function resultObject(): array
    {
        if ($this->isPdo()) {
            $data = $this->query->fetchAll(PDO::FETCH_OBJ);
            
            $this->query->closeCursor();

            return $data;
        }

        return $this->_resultObject();
    }

    /**
     * {@inheritDoc}
     */
    public function resultArray(): array
    {
        if ($this->isPdo()) {
            $data = $this->query->fetchAll(PDO::FETCH_ASSOC);
            $this->query->closeCursor();

            return $data;
        }
        
        return $this->_resultArray();
    }

    /**
     * {@inheritDoc}
     */
    public function unbufferedRow($type = PDO::FETCH_OBJ)
    {
        if ($type === 'array' || $type === PDO::FETCH_ASSOC) {
            return $this->fetchAssoc();
        }

        if ($type === 'object' || $type === PDO::FETCH_OBJ) {
            return $this->fetchObject();
        }

        return $this->fetchObject($type);
    }
    
    /**
     * Returns the result set as an array.
     *
     * @return mixed
     */
    protected function fetchAssoc()
    {
        if ($this->isPdo()) {
            return $this->query->fetch(PDO::FETCH_ASSOC);
        }

        return $this->_fetchAssoc();
    }
    
    /**
     * Returns the result set as an object.
     * 
     * @return object
     */
    protected function fetchObject(string $className = 'stdClass')
    {
        if (is_subclass_of($className, Entity::class)) {
            return empty($data = $this->fetchAssoc()) ? false : (new $className())->setAttributes($data);
        }

        if ($this->isPdo()) {
            $this->query->setFetchMode(PDO::FETCH_CLASS, $className);
        
            return $this->query->fetch();
        }

        return $this->_fetchObject($className);
    }

    /**
     * {@inheritDoc}
     */
    public function freeResult()
    {
        if ($this->isPdo()) {

            return;
        }

        $this->_freeResult();
    }

    /**
     * Recupere les details de la requete courrante
     *
     * @return array
     */
    public function details(): array
    {
        if (!$this->query) {
            return $this->details;
        }

        $last = $this->db->getLastQuery();

        return $this->details = array_merge((array) $last, [
            'affected_rows' => $this->affectedRows(),
            'num_rows'      => $this->numRows(),
            'insert_id'     => $this->insertID(),
        ]);
    }

	/**
	 * Returns the total number of rows affected by this query.
	 *
	 * @return int
	 */
	public function affectedRows() : int
	{
		return $this->db->affectedRows();
	}

	/**
	 * Returns the number of rows in the result set.
	 */
	public function numRows(): int
	{
		return $this->db->numRows();
	}

	/**
     * Return the last id generated by autoincrement
     *
     * @return int|string
     */
    public function insertID()
    {
        return $this->db->insertID();
    }
	/**
	 * Return the last id generated by autoincrement
	 *
	 * @alias self::insertID()
	 * @return int|null
	 */
	public function lastId()
	{
		return $this->insertID();
	}

    protected function _resultObject(): array
    {
        return array_map(static fn($data) => (object) $data, $this->resultArray());
    }

    /**
     * Returns the result set as an array.
     *
     * Overridden by driver classes.
     *
     * @return mixed
     */
    abstract protected function _fetchAssoc();
    
    /**
     * Returns the result set as an object.
     *
     * Overridden by child classes.
     * 
     * @return object
     */
    abstract protected function _fetchObject(string $className = 'stdClass');

    /**
	 * Gets the number of fields in the result set.
	 */
    abstract protected function _countField(): int;

    abstract protected function _result($type): array;

    /**
     * Retourne un table contenant les resultat de la requete sous forme de tableau associatif
     */
    abstract protected function _resultArray(): array;

    /**
     * Frees the current result.
     */
    abstract protected function _freeResult();
}
