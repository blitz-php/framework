<?php

namespace BlitzPHP\Cli\Commands\Database;

use BlitzPHP\Cli\Console\Command;
use BlitzPHP\Config\Database;
use BlitzPHP\Database\Connection\BaseConnection;
use PDO;

/**
 * Obtenir les données de la table si elles existent dans la base de données.
 */
class TableInfo extends Command
{
    /**
     * {@inheritDoc}
     */
    protected $group = 'Database';

    /**
     * {@inheritDoc}
     */
    protected $name = 'db:table';

    /**
     * {@inheritDoc}
     */
    protected $description = 'Récupère les informations sur la table sélectionnée.';

    /**
     * {@inheritDoc}
     */
    protected $usage = <<<'EOL'
        db:table --show
        db:table --metadata
        db:table my_table --metadata
        db:table my_table
        db:table my_table --limit-rows 5 --limit-field-value 10 --desc
    EOL;

    /**
     * {@inheritDoc}
     */
    protected $arguments = [
        'table' => 'Le nom de la table dont on veut avoir les infos',
    ];

    /**
     * {@inheritDoc}
     */
    protected $options = [
        '--show'              => 'Liste les noms de toutes les tables de la base de données.',
        '--metadata'          => 'Récupère la liste contenant les informations du champ.',
        '--desc'              => 'Trie les lignes du tableau dans l\'ordre DESC.',
        '--limit-rows'        => 'Limite le nombre de lignes. Par défaut : 10.',
        '--limit-field-value' => 'Limite la longueur des valeurs des champs. Par défaut : 15.',
    ];

    /**
     * @phpstan-var  list<list<string|int>> Table Data.
     */
    private array $tbody;

    private BaseConnection $db;

    /**
     * @var bool Trier les lignes du tableau dans l'ordre DESC ou non.
     */
    private bool $sortDesc = false;

    private string $prefix = '';

    public function execute(array $params)
    {
        $this->db     = Database::connect();
        $this->prefix = $this->db->getPrefix();

        $tables = $this->db->listTables();

        if (array_key_exists('desc', $params)) {
            $this->sortDesc = true;
        }

        if ($tables === []) {
            $this->error('La base de données n\'a aucune table!');

            return;
        }

        if (true === $this->option('show')) {
            $this->showAllTables($tables);

            return;
        }

        $tableName       = $this->argument('table');
        $limitRows       = (int) $this->option('limit-rows', 10);
        $limitFieldValue = (int) $this->option('limit-field-value', 15);

        if (! in_array($tableName, $tables, true)) {
            $tabs = $tables;
            $tables = [];
            foreach ($tabs as $key => $tab) {
                $tables[$key + 1] = $tab;
            }

            $tableNameNo = $this->choice("Voici les tables disponible dans votre base de données. \n Quelle table souhaitez-vous afficher?", $tables);
            $tableName = $tables[$tableNameNo];
        }

        if (true === $this->option('metadata')) {
            $this->showFieldMetaData($tableName);

            return;
        }

        $this->showDataOfTable($tableName, $limitRows, $limitFieldValue);
    }

    private function removeDBPrefix(): void
    {
        $this->db->setPrefix('');
    }

    private function restoreDBPrefix(): void
    {
        $this->db->setPrefix($this->prefix);
    }

    private function showDataOfTable(string $tableName, int $limitRows, int $limitFieldValue)
    {
        $this->newLine()->io->blackBgYellow("Données de la table \"{$tableName}\":", true);
       
        $this->removeDBPrefix();
        $thead = $this->db->getFieldNames($tableName);
        $this->restoreDBPrefix();

        // Si on a un champ id, on trie en fonction de lui.
        $sortField = null;
        if (in_array('id', $thead, true)) {
            $sortField = 'id';
        }

        $this->tbody = $this->makeTableRows($tableName, $limitRows, $limitFieldValue, $sortField);
       
        $this->table($this->tbody);
    }

    private function showAllTables(array $tables)
    {
        $this->newLine()->io->blackBgYellow('Voici une liste des noms de toutes les tables de base de données :', true);

        $this->tbody = $this->makeTbodyForShowAllTables($tables);

        $this->table($this->tbody);
    }

    private function makeTbodyForShowAllTables(array $tables): array
    {
        $this->removeDBPrefix();

        foreach ($tables  as $id => $tableName) {
            $table = $this->db->protectIdentifiers($tableName);
            /** @var \BlitzPHP\Database\Result\BaseResult $db */
            $db    = $this->db->query("SELECT * FROM {$table}");

            $this->tbody[] = [
                'ID'                       => $id + 1,
                'Nom de la table'          => $tableName,
                'Nombre d\'enregistrement' => $db->numRows(),
                'Nombre de champs'         => $db->countField(),
            ];
        }

        $this->restoreDBPrefix();

        if ($this->sortDesc) {
            krsort($this->tbody);
        }

        return $this->tbody;
    }

    private function makeTableRows(
        string $tableName,
        int $limitRows,
        int $limitFieldValue,
        ?string $sortField = null
    ): array {
        $this->tbody = [];

        $this->removeDBPrefix();
        $builder = $this->db->table($tableName);
        $builder->limit($limitRows);
        if ($sortField !== null) {
            $builder->orderBy($sortField, $this->sortDesc ? 'DESC' : 'ASC');
        }
        $rows = $builder->result(PDO::FETCH_ASSOC);
        $this->restoreDBPrefix();

        foreach ($rows as $row) {
            $row = array_map(
                static fn ($item): string => mb_strlen((string) $item) > $limitFieldValue
                    ? mb_substr((string) $item, 0, $limitFieldValue) . '...'
                    : (string) $item,
                $row
            );
            $this->tbody[] = $row;
        }

        if ($sortField === null && $this->sortDesc) {
            krsort($this->tbody);
        }

        return $this->tbody;
    }

    private function showFieldMetaData(string $tableName): void
    {
        $this->newLine()->io->blackBgYellow("Liste des informations de métadonnées dans la table \"{$tableName}\" :", true);

        $this->removeDBPrefix();
        $fields = $this->db->getFieldData($tableName);
        $this->restoreDBPrefix();

        foreach ($fields as $row) {
            $this->tbody[] = [
                'Nom du champ'      => $row->name,
                'Type'              => $row->type,
                'Taille maximale'   => (string) $row->max_length,
                'Nullable'          => isset($row->nullable) ? $this->setYesOrNo($row->nullable) : 'n/a',
                'Valeur par défaut' => (string) $row->default,
                'Clé primaire'      => isset($row->primary_key) ? $this->setYesOrNo($row->primary_key) : 'n/a',
            ];
        }

        if ($this->sortDesc) {
            krsort($this->tbody);
        }

        $this->table($this->tbody);
    }

    private function setYesOrNo(bool $fieldValue): string
    {
        if ($fieldValue) {
            return 'Oui';
        }

        return 'Non';
    }
}
