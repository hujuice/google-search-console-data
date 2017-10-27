<?php
// -------------------------------------------------------------
// Copy to this file somewhere in your htdocs, after grabbing
// some information. Then provide your application path and
// configuration file path.
// -------------------------------------------------------------

// =============================================================
//                  Here is the working code
// =============================================================
// Application path
$application_path = '/usr/local/gsc';
$config_path = $application_path . '/configs/www_example_com.ini';

// Autoloading
// See https://getcomposer.org/doc/01-basic-usage.md#autoloading
$loader = require_once $application_path . '/vendor/autoload.php';
$loader->addPsr4('GSC\\', $application_path . '/src');

// Create the application object
$gscd = new \GSC\Dump($config_path);

// Storage info
$first_date = $gscd->firstDate();
$last_date = $gscd->lastDate();

// Dates
if (empty($_GET['start_date'])) {
    $start_date = 'today -3 day';
} else {
    $start_date = $_GET['start_date'];
}
if (empty($_GET['end_date'])) {
    $end_date = 'today -1 days';
} else {
    $end_date = $_GET['end_date'];
}

// Run!!!
$data = $gscd->read($start_date, $end_date, true);
// =============================================================
//                  /Here is the working code
// =============================================================
?>

<!doctype html>
<!--[if lt IE 7]>      <html class="no-js lt-ie9 lt-ie8 lt-ie7" lang=""> <![endif]-->
<!--[if IE 7]>         <html class="no-js lt-ie9 lt-ie8" lang=""> <![endif]-->
<!--[if IE 8]>         <html class="no-js lt-ie9" lang=""> <![endif]-->
<!--[if gt IE 8]><!--> <html class="no-js" lang=""> <!--<![endif]-->
    <head>
        <meta charset="utf-8">
        <meta http-equiv="X-UA-Compatible" content="IE=edge,chrome=1">
        <title>Google Search Console Data</title>
        <meta name="description" content="">
        <meta name="viewport" content="width=device-width, initial-scale=1">

        <link rel="stylesheet" href="https://maxcdn.bootstrapcdn.com/bootstrap/3.3.7/css/bootstrap.min.css" integrity="sha384-BVYiiSIFeK1dGmJRAkycuHAHRg32OmUcww7on3RYdg4Va+PmSTsz/K68vbdEjh4u" crossorigin="anonymous">
        <style>
            body {
                padding-top: 50px;
                padding-bottom: 20px;
            }
        </style>
    </head>
    <body>
        <!--[if lt IE 8]>
            <p class="browserupgrade">You are using an <strong>outdated</strong> browser. Please <a href="http://browsehappy.com/">upgrade your browser</a> to improve your experience.</p>
        <![endif]-->
    <nav class="navbar navbar-inverse navbar-fixed-top" role="navigation">
      <div class="container">
        <div class="navbar-header">
          <a class="navbar-brand" href="#">Google Search Console Data</a>
        </div>
      </div>
    </nav>

    <div class="container">
    
<?php
// =============================================================
//                  HTML annoyances
// =============================================================
// Notify the Google time (PST)
$google_now = new \DateTime('now', new \DateTimeZone('PST'));
echo '<p class="text-right">Google date, now: ' . $google_now->format('Y-m-d') . '</p>';

echo '<table class="table  table-striped">', PHP_EOL;

// Table caption
echo '<caption>Google Search Console Data from <code>' . $start_date . '</code> to <code>' . $end_date . '</code> (data are available from ' . $first_date . ' to ' . $last_date . ')</caption>', PHP_EOL;

// Table header
$header = array_shift($data);
echo '<thead><tr>';
foreach ($header as $cell) {
    echo '<th>', $cell, '</th>';
}
echo '</tr></thead>', PHP_EOL;

// Table body
echo '<tbody>', PHP_EOL;
foreach ($data as $row) {
    echo '<tr>';
    foreach ($row as $cell) {
        echo '<td>', htmlspecialchars($cell), '</td>';
    }
    echo '</tr>', PHP_EOL;
}
echo '</tbody>', PHP_EOL;
echo '</table>', PHP_EOL;
// =============================================================
//                  /HTML annoyances
// =============================================================
?>

      <hr>

      <footer>
        <p>&copy; Sergio Vaccaro 2017 </p>
      </footer>
    </div> <!-- /container -->

    </body>
</html>
