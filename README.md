# Google Search Console data storage #

## Purpose ##
Google Search Console let you access data not older than 90 days.  
The purpose of this application is to access the Google Search Console API and store the data in a local storage system.  
The application is intended mostly to be executed in a command-line environment or as cronjob.

The application also has a simple method to read the stored informations, given a date interval.  
You can write your own analysis logic starting from that.

You can retrieve data for many website simply providing many configrations and secret files couples (see below).

## Installation ##
Once cloned, you need three steps to have the application up and running.

### 1. Install the dependencies ###
    composer install

### 2. Have a Google service account credential file ###
You can manage you API credentials visiting <https://console.developers.google.com/>.  
Your secret file can be saved as `www_example_com-secret.json` (according to your website name) or elsewhere changing the relative configuration value. The file must be in the same directory of the configuration file.

You can have many secret files for one installed application.

### 3. Create a configuration file ###
Copy the `www_example_com.ini.example` file as `www_example_com.ini` (according to your website name) and edit it following your preferences.  
What you really MUST edit is the site and the secret file names in the `[google]` section. All the other values are tuning values.

If you decide to store the data in a file based way (CSV or SQLite), you will also manage the file permissions.  
If you decide to store the data in a MySQL database, you will also prepare an empty database.  
For SQLite or MySQL databases, the applications is smart enough to generate the file structure or the empty table befor the first run.  

You can have many configuration files for one installed application.

## Run ##

### Grab informations ###
To dump some data, simply execute

    php dump.php www_example_com.ini

to retrieve the Google Search Console data.

On *nix systems, you can also enable the execution and run

    chmod +x dump.php
    ./dump.php www_example_com.ini

If you want that, double check the PHP binary path in the [shebang](https://en.wikipedia.org/wiki/Shebang_(Unix) line.

The application will automatically search for the last saved data and continue starting from that date. On first run, the application will start from 90 days before today, that is the maximum given by Google.  
If you run it as cronjob and if you daily grab, e.g., 10 days, you will have your storage filled in 9 days.

### Read the data ###

You can read the data in a very simple way. Take a look to the web based example in `read_example/index.php`.  
If you want more complex data analysis, you should write your own code, based on the very simple data table.

## Notes ##

For a Python solution, please read this: <https://adaptpartners.com/technical-seo/a-tool-for-saving-google-search-console-data-to-bigquery/>.
