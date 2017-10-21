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
 * Abstract class for database based storages
 *
 * @package     GSC\Storage
 */
class Mysql extends DbAbstract Implements \GSC\Storage\StorageInterface
{
    /**
     * Create the database object
     *
     * @param array $config             Contains the database path
     * @throw \Exception                When the configuration does not contains a valid path
     */
    public function __construct(array $config)
    {
        // The database configuration
        if (empty($config['pass'])) {
            throw new Exception('You MUST provide at least the database password.');
        }
        if (empty($config['host'])) {
            $config['host'] = 'localhost'; // Default value
        }
        if (empty($config['port'])) {
            $config['port'] = 3306; // Default value
        }
        if (empty($config['name'])) {
            $config['name'] = 'google_search_console_dump';
        }
        if (empty($config['user'])) {
            $config['user'] = 'google_search_console_dump';
        }
        
        // Create the PDO object
        $dsn = 'mysql:host=' . $config['host'] . ';port=' . $config['port'] . ';dbname=' . $config['name'];
        $this->_db = new \PDO(
            $dsn,
            $config['user'],
            $config['pass'],
            array()
        );
        $this->_db->setAttribute(\PDO::ATTR_ERRMODE, \PDO::ERRMODE_EXCEPTION);

        // Build the table if not exists
        parent::__construct(array());
    }
}
