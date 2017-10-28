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
     * The type declaration is different for version of PHP below 7
     * In PHP 7+, the required type is \Throwable, so the method should be
     * public function _exceptionHandler(\Throwable $e)
     * In PHP 5 the required type should be \Exception, so the method should be
     * public function _exceptionHandler(\Exception $e)
     * http://php.net/manual/en/function.set-exception-handler.php#refsect1-function.set-exception-handler-parameters
     * For the moment, the type declaration has been removed
     *
     * @param Throwable    The running exception
     */
    public function _exceptionHandler($e)
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
        // Set the timezone to the Google timezone
        date_default_timezone_set('PST8PDT');

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
        $google_now = new \DateTime('now');
        self::logger('Please, note that Google date is now ' . $google_now->format('Y-m-d') . '.', \LOG_INFO);

        // Set the starting date
        if ($last = $this->_storage->lastDate()) {
            $start_date = new \DateTime($last . ' + 1 day');
        } else {
            $start_date = new \DateTime('today -90 days');
        }
        self::logger('Start date ' . $start_date->format('Y-m-d') . ' ' . date_default_timezone_get() . '.', \LOG_INFO);

        // Skip if the starting date is today, error if after today
        $today = new \DateTime('today');
        $interval = $start_date->diff($today)->format('%r%a');
        if (0 == $interval) {
            self::logger('The dump is already updated: nothing to do.', \LOG_NOTICE);
            return 0;
        } elseif (0 >= $interval) {
            throw new \Exception('The last database day is AFTER today.');
        }

        // Google API client initialization
        if (empty($this->_config['google']['max_days'])) {
            $this->_config['google']['max_days'] = 10; // Default value
        }
        if (empty($this->_config['google']['limit'])) {
            $this->_config['google']['limit'] = 5000; // Default value
        }
        putenv('GOOGLE_APPLICATION_CREDENTIALS=' . $secret_file);
        $client = new \Google_Client();
        $client->useApplicationDefaultCredentials();
        $client->addScope(\Google_Service_Webmasters::WEBMASTERS_READONLY);
        $service = new \Google_Service_Webmasters($client);
        $request = new \Google_Service_Webmasters_SearchAnalyticsQueryRequest();
        $request->setDimensions(array('query', 'page', 'country', 'device'));
        $request->setRowLimit($this->_config['google']['limit']);

        // Prepare the iteration parameters
        $date = $start_date;
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
            self::logger('Requesting data for ' . $date_as_string . ' ' . date_default_timezone_get() . '.', \LOG_INFO);
            $data = $service->searchanalytics->query($this->_config['google']['site'], $request)->getRows();
            if ($num_record = count($data)) {
                self::logger($num_record . ' record(s) obtained. Storing...', \LOG_INFO);
                foreach($data as $row) {
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

                self::logger('Data for ' . $date_as_string . ' ' . date_default_timezone_get() . ' stored.', \LOG_NOTICE);

            } else {
                self::logger('No records for ' . $date_as_string . ' ' . date_default_timezone_get() . '. Stop.', \LOG_NOTICE);
                // Retrieve data for days after an empty day has no sense
                break;
            }

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
        $start_date = new \DateTime($start_date);
        $end_date = new \DateTime($end_date);

        // Request!
        return $this->_storage->select($start_date, $end_date, $header);
    }
}
