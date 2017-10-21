<!doctype html>
<!--[if lt IE 7]>      <html class="no-js lt-ie9 lt-ie8 lt-ie7" lang=""> <![endif]-->
<!--[if IE 7]>         <html class="no-js lt-ie9 lt-ie8" lang=""> <![endif]-->
<!--[if IE 8]>         <html class="no-js lt-ie9" lang=""> <![endif]-->
<!--[if gt IE 8]><!--> <html class="no-js" lang=""> <!--<![endif]-->
    <head>
        <meta charset="utf-8">
        <meta http-equiv="X-UA-Compatible" content="IE=edge,chrome=1">
        <title>Google Search Console Dump data</title>
        <meta name="description" content="">
        <meta name="viewport" content="width=device-width, initial-scale=1">
        <link rel="apple-touch-icon" href="apple-touch-icon.png">

        <link rel="stylesheet" href="css/bootstrap.min.css">
        <style>
            body {
                padding-top: 50px;
                padding-bottom: 20px;
            }
        </style>
        <link rel="stylesheet" href="css/bootstrap-theme.min.css">
        <link rel="stylesheet" href="css/main.css">

        <script src="js/vendor/modernizr-2.8.3-respond-1.4.2.min.js"></script>
    </head>
    <body>
        <!--[if lt IE 8]>
            <p class="browserupgrade">You are using an <strong>outdated</strong> browser. Please <a href="http://browsehappy.com/">upgrade your browser</a> to improve your experience.</p>
        <![endif]-->
    <nav class="navbar navbar-inverse navbar-fixed-top" role="navigation">
      <div class="container">
        <div class="navbar-header">
          <a class="navbar-brand" href="#">Google Search Console Dump data</a>
        </div>
      </div>
    </nav>

    <div class="container">

      <table class="table  table-striped">
<?php
if (empty($_GET['start_date'])) {
    $start_date = 'today -1 day';
} else {
    $start_date = $_GET['start_date'];
}
if (empty($_GET['end_date'])) {
    $end_date = 'today -3 days';
} else {
    $end_date = $_GET['end_date'];
}
echo '<caption>Google Search Console Dump from <code>' . $start_date . '</code> to <code>' . $end_date . '</code></caption>', PHP_EOL;

// =============================================================
//                  Here is the working code
// =============================================================
// Autoloading
// See https://getcomposer.org/doc/01-basic-usage.md#autoloading
$loader = require_once __DIR__ . '/../vendor/autoload.php';
$loader->addPsr4('GSC\\', __DIR__ . '/../src');

// Run!!!
$gscd = new \GSC\Dump(__DIR__ . '/../dump.ini');

$data = $gscd->read($start_date, $end_date, true);
// =============================================================
//                  Here is the working code
// =============================================================

// HTML annoyances
$header = array_shift($data);
echo '<thead><tr>';
foreach ($header as $cell) {
    echo '<th>', $cell, '</th>';
}
echo '</tr></thead>', PHP_EOL;

echo '<tbody>', PHP_EOL;
foreach ($data as $row) {
    echo '<tr>';
    foreach ($row as $cell) {
        echo '<td>', htmlspecialchars($cell), '</td>';
    }
    echo '</tr>', PHP_EOL;
}
echo '</tbody>', PHP_EOL;

?>
      </table>

      <hr>

      <footer>
        <p>&copy; Sergio Vaccaro 2017 </p>
      </footer>
    </div> <!-- /container -->        <script src="//ajax.googleapis.com/ajax/libs/jquery/1.11.2/jquery.min.js"></script>
        <script>window.jQuery || document.write('<script src="js/vendor/jquery-1.11.2.min.js"><\/script>')</script>

        <script src="js/vendor/bootstrap.min.js"></script>

        <script src="js/main.js"></script>

    </body>
</html>
