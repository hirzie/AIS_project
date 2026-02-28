<?php
/**
 * Public Access Point for Kiosk Display
 * Allows accessing the display without login via /AIS/display.php
 */

// Define the target module file
$targetFile = __DIR__ . '/modules/kiosk/display.php';

// Change directory to the module location so its relative includes (like ../../config/database.php) work
chdir(dirname($targetFile));

// Include the actual display file using absolute path to prevent inclusion loop
require $targetFile;
