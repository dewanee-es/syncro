<?php
print "    ____                      \n";
print "   / __/_ _____  ___________  \n";
print "  _\ \/ // / _ \/ __/ __/ _ \ \n";
print " /___/\_, /_//_/\__/_/  \___/ \n";
print "<=== /___/ ==================>\n\n";                  

error_reporting(E_ALL);
ini_set("display_errors", 1);

require __DIR__.'/autoload.php';

use Syncro\SyncroApplication;

$application = new SyncroApplication();
$application->run();
