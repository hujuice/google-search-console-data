<?php
/**
 * LICENSE
 *
 * This program is free software: you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation, either version 3 of the License, or
 * (at your option) any later version.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with this program.  If not, see <http://www.gnu.org/licenses/>.
 *
 * @author      Sergio Vaccaro
 * @copyright   2017 Istat
 * @license     http://opensource.org/licenses/GPL-3.0 GNU General Public License, version 3 (GPL-3.0)
 * @link        http://www.istat.it/
 * @version     1.0.0
 */

// Namespace
namespace GSC\Storage;

/**
 * Facade class for the storage system.
 *
 * @package     GSC\Storage
 */
class Sqlite Implements \GSC\Storage\StorageInterface
{
    /**
     * The database object
     *
     * @var \PDO            The PDO object
     */
    protected $_db;

    /**
     * The analysis table name
     *
     * @var string                      The table name
     */
    protected $_table_name = 'analysis';

    /**
     * Create the database object
     *
     * @param array $config             Contains the database path
     * @throw \Exception                When the configuration does not contains a valid path
     */
    public function __construct(array $config)
    {
        // The SQLite file path
        if (empty($config['path'])) {
            $config['path'] = '/var/lib/google_search_console_dump/storage.sqlite'; // Default value
        }

        // Create the PDO object
        $dsn = 'sqlite:' . $config['path'];
        $this->_db = new \PDO(
            $dsn,
            null,
            null,
            array()
        );
        $this->_db->setAttribute(\PDO::ATTR_ERRMODE, \PDO::ERRMODE_EXCEPTION);

        // Create the table if not exist
        $sql  = 'CREATE TABLE IF NOT EXISTS ' . $this->_table_name . ' (';
        $columns = array();
        foreach (\GSC\Storage::$analysis as $column_def => $constraint) {
            $columns[] = $column_def . ' ' . $constraint;
        }
        $sql .= implode(', ', $columns);
        $sql .= ')';
        $sth = $this->_db->prepare($sql);
        $sth->execute();
    }

    /**
     * Return the last inserted date
     *
     * @return string               The last inserted date
     */
    public function lastDate()
    {
        $sql = 'SELECT MAX(date) as lastDate FROM ' . $this->_table_name;
        $sth = $this->_db->prepare($sql);
        $sth->execute();
        $result = $sth->fetch();
        return $result['lastDate'];
    }

    /**
     * Store a record
     *
     * @param array                 The record
     * @throw \Exception            When the array is not well formed
     */
    public function insert(array $data)
    {
        // Check the data structure before
        if (array_keys($data) != array_keys(\GSC\Storage::$analysis)) {
            throw new \Exception('The data array MUST have all the following ordered keys: ' . implode(',', array_keys(self::$analysis)) . '.');
        }

        // Write the query
        $sql  = 'INSERT INTO ' . $this->_table_name . ' (';
        $columns = array();
        foreach (array_keys($data) as $column_def) {
            $columns[] = $column_def;
        }
        $sql .= implode(', ', $columns);
        $sql .= ') VALUES (';
        $values = array();
        foreach (array_keys($data) as $column_def) {
            $values[] = ':' . $column_def;
        }
        $sql .= implode(', ', $values);
        $sql .= ')';

        $sth = $this->_db->prepare($sql);
        foreach ($data as $column_def => $value) {
            $sth->bindValue(':' . $column_def, $value);
        }

        // Execute!
        $sth->execute();
    }

    /**
     * Read a list of record, selected by date interval
     *
     * @param string $start_date   The lowest date
     * @param string $end_date     The highest date
     * @param boolean $header      Return the table header as first row
     * @return array               The data table
     */
    public function select($start_date, $end_date, $header = false)
    {
        return array();
    }
}
