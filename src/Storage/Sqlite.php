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
 * SQLite storage
 *
 * @package     GSC\Storage
 */
class Sqlite extends DbAbstract Implements \GSC\Storage\StorageInterface
{
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

        // Build the table if not exists
        parent::__construct($config);
    }
}
