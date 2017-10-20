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
 * @package     GSC
 * @api
 */
interface StorageInterface
{
    /**
     * Build the storage object, given the params
     *
     * @param array $config         The storage configuration
     * @throw \Exception            When the configuration is not admittable
     */
    public function __construct(array $config);

    /**
     * Return the last inserted date
     *
     * @return string               The last inserted date
     */
    public function lastDate();

    /**
     * Store a record
     *
     * @param array                 The record
     * @throw \Exception            When the array is not well formed
     */
     public function insert(array $data);

     /**
      * Read a list of record, selected by date interval
      *
      * @param string $start_date   The lowest date
      * @param string $end_date     The highest date
      * @return array               The data table
      */
    public function select($start_date, $end_date, $header = false);
}
