<?php

// Prints Drupal watchdog messages to STDOUT in CSV format.
// Usage: drush -r $D7 -l <site-name> scr dump-drupal-logs.php

// Adapted from $D7/modules/dblog/dblog.admin.inc & // https://code.iamkate.com/php/creating-downloadable-csv-files/
// DJM, 2021-05-12


// Set up the Drupal environment
define('DRUPAL_ROOT', getenv('D7'));
require_once DRUPAL_ROOT . '/includes/bootstrap.inc';
drupal_bootstrap(DRUPAL_BOOTSTRAP_FULL);


// Cache the severity levels
$severity = watchdog_severity_levels();


// Get a file handle for STDOUT
$stdout = fopen('php://stdout', 'w');


// Set up the database query
$query = db_select('watchdog', 'w')->extend('PagerDefault')->extend('TableSort');
$query->leftJoin('users', 'u', 'w.uid = u.uid');
$query
  ->fields('w', array('wid', 'uid', 'severity', 'type', 'timestamp', 'message', 'variables', 'link'))
  ->addField('u', 'name');
$result = $query
  ->limit(50000000)
  ->orderBy('wid')
  ->execute();


// Output the column headings
fputcsv($stdout, array('Type', 'Severity', 'Timestamp', 'Message', 'User'));

// Iterate over the watchdog records
foreach ($result as $dblog) {

  fputcsv($stdout, array(
    t($dblog->type),
    $severity[$dblog->severity],
    format_date($dblog->timestamp, 'short'),
    format_dblog_message(array('event' => $dblog)),
    theme('username', array('account' => $dblog)),
  ));

  // var_dump($row);

}


function format_dblog_message($variables) {
  $output = '';
  $event = $variables['event'];
  // Check for required properties.
  if (isset($event->message) && isset($event->variables)) {
    // Messages without variables or user specified text.
    if ($event->variables === 'N;') {
      $output = $event->message;
    }
    // Message to translate with injected variables.
    else {
      $output = t($event->message, unserialize($event->variables));
    }
    // If the output is expected to be a link, strip all the tags and
    // special characters by using filter_xss() without any allowed tags.
    // If not, use filter_xss_admin() to allow some tags.
    if ($variables['link'] && isset($event->wid)) {
      // Truncate message to 56 chars after stripping all the tags.
      // $output = truncate_utf8(filter_xss($output, array()), 56, TRUE, TRUE);
      $output = filter_xss($output, array());
      $output = l($output, 'admin/reports/event/' . $event->wid, array('html' => TRUE));
    }
    else {
      // Prevent XSS in log detail pages.
      $output = filter_xss_admin($output);
    }
  }
  return $output;
}

