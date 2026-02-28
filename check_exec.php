<?php
echo function_exists('exec') ? 'EXEC_ENABLED' : 'EXEC_DISABLED';
echo "\n";
echo `mysql --version` ? 'MYSQL_CLI_FOUND' : 'MYSQL_CLI_MISSING';
?>