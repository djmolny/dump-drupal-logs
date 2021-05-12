# dump-drupal-logs
A PHP script that outputs the entire Drupal `watchdog` table in CSV format.

Adapted from Drupal's `dblog` module, `<drupal-root>/modules/dblog/dblog.admin.inc`

Usage: `drush -r <drupal-root> -l <site-name> scr dump-drupal-logs.php`
