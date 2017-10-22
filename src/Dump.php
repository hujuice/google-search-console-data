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
 * This class manage the whole task
 *
 * @package     GSC
 * @api
 */
class Dump
{
    /**
     * Configuration
     *
     * @var array           The application configuration
     */
    protected $_config;

    /**
     * Storage
     *
     * @var \GSC\Storage    The storage system
     */
    protected $_storage;

    /**
     * Google timezone
     *
     * @var \DateTimeZone   The Google preferred timezone
     */
    protected $_googleTz;

    /**
     * The configuration file path (where the secret file also is)
     *
     * @var string          The configuration file path (where the secret file also is)
     */
    protected $_configDir;

    /**
     * Write messages to the system logger and to the stdout
     *
     * @param string $message           The message
     * @param integer $priority         The priority
     */
    public static function logger($message, $priority = \LOG_INFO)
    {
        if (PHP_SAPI == 'cli') {
            echo $message, PHP_EOL;
        }

        if ($priority < 6) {
            syslog($priority, $message);
        }
    }

    /**
     * Dump an exception and die
     *
     * NOTE Be careful when back to PHP 5: http://lt1.php.net/manual/en/function.set-exception-handler.php
     *
     * @param Throwable    The running exception
     */
    public function _exceptionHandler(\Throwable $e)
    {
        if (PHP_SAPI == 'cli') {
            $tmpfile = tempnam("/tmp", "GSC");
            file_put_contents($tmpfile, $e->__toString());
            self::logger($e->getMessage() . ' The complete stack trace is at ' . $tmpfile, \LOG_ERR);

            // Immediately exit
            die();
        } else {
            throw $e;
        }
    }

    /**
     * Build the dump object
     *
     * @param string $config_path       Configuration file path
     * @throw \Exception                When the configuration is incomplete
     */
    public function __construct($config_path)
    {
        // Manage the system logger
        openlog('gscd', LOG_CONS | LOG_ODELAY, LOG_USER);

        // Read the configuration file
        if ( ! $this->_config = parse_ini_file($config_path, true)) {
            self::logger('Unable to read the configuration file ' . $config_path, \LOG_CRIT);
            die('Abort' . PHP_EOL);
        }

        // Set an exception handler
        set_exception_handler(array($this, '_exceptionHandler'));

        // No sense if the site is not specified in configuration
        if (empty($this->_config['google']['site'])) {
            throw new \Exception('The google site MUST be defined in the configuration file.');
        }

        // Google timezone
        $this->_googleTz = new \DateTimeZone('PST');

        // Initialize the storage
        if (empty($this->_config['main']['storage'])) {
            $this->_config['main']['storage'] = 'csv'; // Default value
        }
        if (empty($this->_config[$this->_config['main']['storage']])) {
            $this->_config[$this->_config['main']['storage']] = array(); // Default value
        }
        $this->_storage = new Storage($this->_config['main']['storage'], $this->_config[$this->_config['main']['storage']]);

        // Remember the configuration file dir
        $this->_configDir = dirname($config_path);
    }

    /**
     * Give the first date of the dump
     *
     * @return string                   The first day of the dump
     */
    public function firstDate()
    {
        return $this->_storage->firstDate();
    }

    /**
     * Give the last date of the dump
     *
     * @return string                   The last day of the dump
     */
    public function lastDate()
    {
        return $this->_storage->lastDate();
    }

    /**
     * Execute the dump
     *
     * @return integer                  The number of inserted days
     * @throw \Exception                When the storage is inconsistent
     */
    public function dump()
    {
        // No sense if the secret file is not specified in configuration
        if (empty($this->_config['google']['secret'])) {
            throw new \Exception('The google secret file MUST be defined in the configuration file.');
        }

        // Secret file
        $secret_file = realpath($this->_configDir . '/' . $this->_config['google']['secret']);
        if ( ! is_readable($secret_file)) {
            throw new \Exception('The google secret file is not readable.');
        }

        // Start
        self::logger('Google Search Console Dump started.', \LOG_INFO);
        $google_now = new \DateTime('now', $this->_googleTz);
        self::logger('Please, note that Google date is now ' . $google_now->format('Y-m-d') . '.', \LOG_INFO);

        // Set the starting date
        if ($last = $this->_storage->lastDate()) {
            $start_date = new \DateTime($last . ' + 1 day', $this->_googleTz);
        } else {
            $start_date = new \DateTime('today -90 days', $this->_googleTz);
        }
        self::logger('Start date ' . $start_date->format('Y-m-d') . ' PST.', \LOG_INFO);

        // Skip if the starting date is today, error if after today
        $today = new \DateTime('today', $this->_googleTz);
        $interval = $start_date->diff($today)->format('%r%a');
        if (0 == $interval) {
            self::logger('The dump is already updated: nothing to do.', \LOG_NOTICE);
            return 0;
        } elseif (0 >= $interval) {
            throw new \Exception('The last database day is AFTER today.');
        }

        // Google API client initialization
        putenv('GOOGLE_APPLICATION_CREDENTIALS=' . $secret_file);
        $client = new \Google_Client();
        $client->useApplicationDefaultCredentials();
        $client->addScope(\Google_Service_Webmasters::WEBMASTERS_READONLY);
        $service = new \Google_Service_Webmasters($client);
        $request = new \Google_Service_Webmasters_SearchAnalyticsQueryRequest();
        $request->setDimensions(array('query', 'page', 'country', 'device'));
        if (empty($this->_config['google']['limit'])) {
            $this->_config['google']['limit'] = 5000; // Default value
        }
        $request->setRowLimit($this->_config['google']['limit']);

        // Prepare the iteration parameters
        $date = $start_date;
        if (empty($this->_config['google']['max_days'])) {
            $this->_config['google']['max_days'] = 10; // Default value
        }
        self::logger('Will grab data for no more than ' . $this->_config['google']['max_days'] . ' days.', \LOG_INFO);
        self::logger('================================', \LOG_INFO);
        $count = 0;

        // Iterate!
        while (($count < $this->_config['google']['max_days']) && ($date->diff($today)->format('%r%a') > 0)) {

            // Prepare the query
            $date_as_string = $date->format('Y-m-d');
            $request->setStartDate($date_as_string);
            $request->setEndDate($date_as_string);

            // Query!
            self::logger('Requesting data for ' . $date_as_string . ' PST.', \LOG_INFO);
            $query = $service->searchanalytics->query($this->_config['google']['site'], $request);
            self::logger('Data obtained. Storing...', \LOG_INFO);
            foreach($query->getRows() as $row) {
                $this->_storage->insert(array(
                    'date'          => $date_as_string,
                    'query'         => $row->keys[0],
                    'page'          => $row->keys[1],
                    'country'       => $row->keys[2],
                    'device'        => $row->keys[3],
                    'clicks'        => $row->clicks,
                    'impressions'   => $row->impressions,
                    'position'      => $row->position
                ));
            }

            self::logger('Data for ' . $date_as_string . ' PST stored.', \LOG_NOTICE);

            $date->add(new \DateInterval('P1D'));
            $count++;
        }

        self::logger('================================', \LOG_INFO);
        self::logger('Data for ' . $count . ' days has been grabbed.', \LOG_INFO);
        return $count;
    }

    /**
     * Read the dump
     *
     * @param string $start_date    The start date, in a format accepted by strtotime()
     * @param string $end_date      The end date, in a format accepted by strtotime()
     * @param boolean $header      Return the table header as first row
     * @return array                The wanted table
     */
    public function read($start_date = 'today -30 days', $end_date = 'today -1 day', $header = false)
    {
        $start_date = new \DateTime($start_date, $this->_googleTz);
        $end_date = new \DateTime($end_date, $this->_googleTz);

        // Check if $end_date is consequent to $start_date
        $interval = $start_date->diff($end_date);
        if ('-' == $interval->format('%r')) {
            return array();
        }

        // Request!
        return $this->_storage->select($start_date->format('Y-m-d'), $end_date->format('Y-m-d'), $header);
    }
}
