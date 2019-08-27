<?php

namespace LicenseManagerForWooCommerce\Abstracts;

use LicenseManagerForWooCommerce\Interfaces\ResourceRepository as RepositoryInterface;

defined('ABSPATH') || exit;

abstract class ResourceRepository implements RepositoryInterface
{
    /**
     * @var array
     */
    protected static $instances = array();

    /**
     * @var string
     */
    protected $table;

    /**
     * @var string
     */
    protected $primaryKey;

    /**
     * Retrieves the only instance of the called class.
     *
     * @return $this
     */
    public static function instance()
    {
        $class = get_called_class();

        if (!array_key_exists($class, self::$instances)) {
            self::$instances[$class] = new $class();
        }

        return self::$instances[$class];
    }

    /**
     * Adds a new entry to the table.
     *
     * @param array $data
     *
     * @return mixed|void
     */
    public function insert($data)
    {
        global $wpdb;

        $meta = array(
            'created_at' => gmdate('Y-m-d H:i:s'),
            'created_by' => get_current_user_id()
        );

        $insert = $wpdb->insert($this->table, array_merge($data, $meta));

        if (!$insert) {
            return false;
        }

        return $this->find($wpdb->insert_id);
    }

    /**
     * Retrieves a single table row by its ID.
     *
     * @param int $id
     *
     * @return mixed|void
     */
    public function find($id)
    {
        global $wpdb;

        $result = $wpdb->get_row(
            $wpdb->prepare(
                "SELECT * FROM {$this->table} WHERE {$this->primaryKey} = %d;",
                $id
            )
        );

        if (!$result) {
            return false;
        }

        return $this->createResourceModel($result);
    }

    /**
     * Retrieves a single table row by the query parameter.
     *
     * @param array $query
     *
     * @return mixed|void
     */
    public function findBy($query)
    {
        if (!$query || !is_array($query) || count($query) <= 0) {
            return false;
        }

        global $wpdb;

        $sqlQuery = "SELECT * FROM {$this->table} WHERE 1=1 ";

        foreach ($query as $columnName => $value) {
            if (is_array($value)) {
                $valuesIn = implode(', ', array_map('absint', $value));
                $sqlQuery .= "AND {$columnName} IN ({$valuesIn}) ";
            }

            if (is_string($value)) {
                $sqlQuery .= "AND {$columnName} = '{$value}' ";
            }

            if (is_numeric($value)) {
                $value = absint($value);
                $sqlQuery .= "AND {$columnName} = {$value} ";
            }
        }

        $sqlQuery .= ';';

        $result = $wpdb->get_row($sqlQuery);

        if (!$result) {
            return false;
        }

        return $this->createResourceModel($result);
    }

    /**
     * Retrieves all table rows as an array.
     *
     * @return mixed|void
     */
    public function findAll()
    {
        global $wpdb;

        $returnValue = array();
        $result = $wpdb->get_results("SELECT {$this->primaryKey} FROM {$this->table};");

        if (!$result) {
            return false;
        }

        foreach ($result as $row) {
            $returnValue[] = $this->find($row->id);
        }

        return $returnValue;
    }

    /**
     * Retrieves multiple table rows as an array, filtered by the query.
     *
     * @param array       $query
     * @param null|string $orderBy
     * @param null|string $sort
     *
     * @return mixed|void
     */
    public function findAllBy($query, $orderBy = null, $sort = null)
    {
        if (!$query || !is_array($query) || count($query) <= 0) {
            return false;
        }

        global $wpdb;

        $result   = array();
        $sqlQuery = "SELECT * FROM {$this->table} WHERE 1=1 ";

        foreach ($query as $columnName => $value) {
            if (is_array($value)) {
                $valuesIn = implode(', ', array_map('absint', $value));
                $sqlQuery .= "AND {$columnName} IN ({$valuesIn}) ";
            }

            if (is_string($value)) {
                $sqlQuery .= "AND {$columnName} = '{$value}' ";
            }

            if (is_numeric($value)) {
                $value = absint($value);
                $sqlQuery .= "AND {$columnName} = {$value} ";
            }
        }

        if ($orderBy && is_string($orderBy)) {
            $sqlQuery .= "ORDER BY {$orderBy} ";
        }

        if ($sort && is_string($sort)) {
            $sqlQuery .= "{$sort} ";
        }

        $sqlQuery .= ';';

        foreach ($wpdb->get_results($sqlQuery) as $row) {
            $result[] = $this->createResourceModel($row);
        }

        return $result;
    }

    /**
     * Updates a single table row by its ID.
     *
     * @param int   $id
     * @param array $data
     *
     * @return mixed|void
     */
    public function update($id, $data)
    {
        global $wpdb;

        $meta = array(
            'updated_at' => gmdate('Y-m-d H:i:s'),
            'updated_by' => get_current_user_id()
        );

        $updated = $wpdb->update(
            $this->table,
            array_merge($data, $meta),
            array('id' => $id)
        );

        if (!$updated) {
            return false;
        }

        return $this->find($id);
    }

    /**
     * Updates one or multiple table rows by the query.
     *
     * @param array $query
     * @param array $data
     *
     * @return mixed|void
     */
    public function updateBy($query, $data)
    {
        if (!$query || !is_array($query) || count($query) <= 0) {
            return false;
        }

        global $wpdb;

        $meta = array(
            'updated_at' => gmdate('Y-m-d H:i:s'),
            'updated_by' => get_current_user_id()
        );

        $updated = $wpdb->update(
            $this->table,
            array_merge($data, $meta),
            $query
        );

        if (!$updated) {
            return false;
        }

        return $updated;
    }

    /**
     * Removes multiple table rows by their ID's.
     *
     * @param array $ids
     *
     * @return mixed|void
     */
    public function delete($ids)
    {
        global $wpdb;

        $ids = implode(', ', array_map('absint', $ids));
        $sqlQuery = "DELETE FROM {$this->table} WHERE {$this->primaryKey} IN ({$ids});";

        return $wpdb->query($sqlQuery);
    }

    /**
     * Deletes one or more table rows by the query parameter.
     *
     * @param array $query
     *
     * @return mixed|void
     */
    public function deleteBy($query)
    {
        if (!$query || !is_array($query) || count($query) <= 0) {
            return false;
        }

        global $wpdb;

        $sqlQuery = "DELETE FROM {$this->table} WHERE 1=1 ";

        foreach ($query as $columnName => $value) {
            if (is_array($value)) {
                $valuesIn = implode(', ', array_map('absint', $value));
                $sqlQuery .= "AND {$columnName} IN ({$valuesIn}) ";
            }

            if (is_string($value) || is_integer($value)) {
                $sqlQuery .= "AND {$columnName} = {$value} ";
            }
        }

        $sqlQuery .= ';';

        return $wpdb->query($sqlQuery);
    }

    /**
     * Retrieves the total count of table entries.
     *
     * @return int|mixed
     */
    public function count()
    {
        global $wpdb;

        $count = $wpdb->get_var("SELECT COUNT(*) FROM {$this->table};");

        return intval($count);
    }

    /**
     * Retrieves the total count of table entries, filtered by the query parameter.
     *
     * @param array $query
     *
     * @return mixed|void
     */
    public function countBy($query)
    {
        if (!$query || !is_array($query) || count($query) <= 0) {
            return false;
        }

        global $wpdb;

        $sqlQuery = "SELECT COUNT(*) FROM {$this->table} WHERE 1=1 ";

        foreach ($query as $columnName => $value) {
            if (is_array($value)) {
                $valuesIn = implode(', ', array_map('absint', $value));
                $sqlQuery .= "AND {$columnName} IN ({$valuesIn}) ";
            }

            if (is_string($value) || is_integer($value)) {
                $sqlQuery .= "AND {$columnName} = {$value} ";
            }
        }

        $sqlQuery .= ';';

        $count = $wpdb->get_var($sqlQuery);

        return intval($count);
    }

    /**
     * Performs a general query on the table.
     *
     * @param string $sqlQuery
     * @param string $output
     *
     * @return array|object|null
     */
    public function query($sqlQuery, $output = OBJECT)
    {
        global $wpdb;

        return $wpdb->get_results($sqlQuery, $output);
    }

    /**
     * Truncates the table.
     *
     * @return mixed|void
     */
    public function truncate()
    {
        global $wpdb;

        $wpdb->query("TRUNCATE TABLE {$this->table};");
    }
}