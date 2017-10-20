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


// Autoloading
// See https://getcomposer.org/doc/01-basic-usage.md#autoloading
$loader = require_once __DIR__ . '/vendor/autoload.php';
$loader->addPsr4('GSC\\', __DIR__ . '/src');

// Run!!!
$gscd = new \GSC\Dump(__DIR__ . '/dump.ini');

$gscd->dump();
