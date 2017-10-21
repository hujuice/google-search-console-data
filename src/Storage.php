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
namespace GSC;

/**
 * Facade class for the storage system.
 *
 * @package     GSC
 */
class Storage
{
    /**
     * The storage object
     *
     * @var \GSC\Storage\StorageInterface   The abstracted object
     */
    protected $_storage;

    /**
     * The table columns structure
     *
     * Note that types are established according to ANSI SQL
     *
     * @link http://www.moreprocess.com/database/sql/data-types-in-sql-defined-by-ansiiso
     * @var array                   The list of the columns
     */
    public static $analysis = array(
        'date'          => 'DATE',
        'query'         => 'VARCHAR(255)',
        'page'          => 'VARCHAR(255)',
        'country'       => 'CHAR(3)',
        'device'        => 'VARCHAR(15)',
        'clicks'        => 'INT',
        'impressions'   => 'INT',
        'position'      => 'REAL'
    );

    /**
     * Build the storage object
     *
     * @param string $type          The storage type
     * @param array $config         The storage configuration
     * @throw \Exception            When the declared storage type is not admittable
     */
    public function __construct($type, array $config)
    {
        // Create the storage class
        $class_name = '\\GSC\\Storage\\' . ucfirst(strtolower($type));
        $this->_storage = new $class_name($config);

        // Verify that the storage implements the appropriate interface
        if ( ! is_a($this->_storage, '\GSC\Storage\StorageInterface')) {
            throw new \Exception('There\'s no storage type called ' . $class_name . '.');
        }
    }

    /**
     * Try to call storage methods
     *
     * @param string $name              The wanted method
     * @param array $args               The passing arguments
     * @return mixed                    The method return value
     */
    public function __call($name, $args)
    {
        return call_user_func_array(array($this->_storage, $name), $args);
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
        if (array_keys($data) != array_keys(self::$analysis)) {
            throw new \Exception('The data array MUST have all the following ordered keys: ' . implode(', ', array_keys(self::$analysis)) . '.');
        }

        return $this->_storage->insert($data);
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
        $table = array();
        if ($header) {
            $table[] = array_keys(\GSC\Storage::$analysis);
        }

        return $table + $this->_storage->select($start_date, $end_date);
    }
}
