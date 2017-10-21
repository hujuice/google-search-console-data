# Google Search Console data storage #

## Purpose ##

This programm accesses Google Search Console API to retrieve the stored
informations and save it in a local storage system.
Google Search Console let you access data not older than 90 days. Here
we want to save data permanently.
The application has a simple method to read the stored informations given a date interval.
You can write your own analysis logic starting from that.

## Installation ##
Once cloned, you need three steps to have the application up and running.

### 1. Install the dependencies ###
```
composer install
```

### 2. Have a Google service account credential file ###
You can manage you API credentials visiting https://console.developers.google.com/
Your secret file can be saved as `secret.json` or elsewhere changing the relative configuration value. The path is relative to the project root.

### 3. Create a configuration file ###
Copy the `dump.ini.example` as `dump.ini` and edit it following your preferences.
What you really MUST edit is the site in the `[google]` section. All the other values are tuning values.
If you decide to store the data in a file based way (CSV or SQLite), you will also manage the file permissions.
If you decide to store the data in a MySQL database, you will also prepare an empty database.
The applications is smart enough to generate the file structure or the empty table befor the first run.

## Run ##
Simply execute
```
php dump.php
```
to retrieve the Google Search Console data.

You can read the data in a very simple way. Take a look to the web based example in the `read_example/` directory.
If you want more complex data analysis, you should write your own code, based on the very simple data table.
