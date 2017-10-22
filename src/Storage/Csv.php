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
 * CSV storage
 *
 * @package     GSC\Storage
 */
class CSV Implements \GSC\Storage\StorageInterface
{
    /**
     * The reading length
     *
     * @const integer           The reading length
     */
    const LENGTH = 1024;

    /**
     * The CSV delimiter
     *
     * @const string            The CSV delimiter
     */
    const DELIMITER = "\t";

    /**
     * The CSV enclosure
     *
     * @const string            The CSV enclosure
     */
    const ENCLOSURE = '"';

    /**
     * The CSV escaping char
     *
     * @const string
     */
    const ESCAPE = '\\';

    /**
     * The CSV file handle
     *
     * @var resource            The CSV file handle
     */
    protected $_handle;

    /**
     * Create the CSV object, given the params
     *
     * @param array $config         The storage configuration
     * @throw \Exception            When the configuration is not admittable
     */
    public function __construct(array $config)
    {
        if (empty($config['path'])) {
            throw new \Exception('You MUST set a file path in configuration');
        }

        // Try to open in rw, else in r only
        if ( ! $this->_handle = fopen($config['path'], 'at+')) {
            if ( ! $this->_handle = fopen($config['path'], 'rt')) { // Will give error on writing
                throw new \Exception('Unable to open the CSV file ' . $config['path'] . '.');
            }
        }
    }

    /**
     * Return the first inserted date
     *
     * @return string               The first inserted date
     */
    public function firstDate()
    {
        rewind($this->_handle);
        if ($record = fgetcsv($this->_handle, self::LENGTH, self::DELIMITER, self::ENCLOSURE, self::ESCAPE)) {
            return $record[0];
        }
    }

    /**
     * Return the last inserted date
     *
     * There's no simple way, in PHP, to read the last line of a file.
     * Here the last line is found starting from an arbitrary position.
     *
     * @return string               The last inserted date
     */
    public function lastDate()
    {
        $stat = fstat($this->_handle);
        if ($stat['size']) {
            rewind($this->_handle);
            $arbitrary_value = 10;
            $offset = $stat['size'] - $arbitrary_value * self::LENGTH;
            fseek($this->_handle, $offset, SEEK_SET);
            while ($line = fgets($this->_handle, self::LENGTH)) {
                $record = $line;
            }
            $record = str_getcsv($record, self::DELIMITER, self::ENCLOSURE, self::ESCAPE);

            return $record[0];
        }
    }

    /**
     * Store a record
     *
     * @param array                 The record
     * @throw \Exception            When the array is not well formed
     */
    public function insert(array $data)
    {
        if (false === fputcsv($this->_handle, array_values($data), self::DELIMITER, self::ENCLOSURE, self::ESCAPE)) {
            throw new \Exception('Unable to write to the CSV file.');
        }
    }

     /**
      * Read a list of record, selected by date interval
      *
      * @param string $start_date   The lowest date
      * @param string $end_date     The highest date
      * @return array               The data table
      */
    public function select($start_date, $end_date)
    {
        rewind($this->_handle);
        $data = array();
        while ($record = fgetcsv($this->_handle, self::LENGTH, self::DELIMITER, self::ENCLOSURE, self::ESCAPE)) {
            if ($record[0] < $start_date) {
                continue;
            }
            if ($record[0] > $end_date) {
                break;
            }
            $data[] = $record;
        }

        return $data;
    }
}
