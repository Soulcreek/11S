<?php
// Quick diagnostic endpoint. Appends a timestamp to db-test-debug.log and echoes a token.
file_put_contents(__DIR__ . '/db-test-debug.log', "diag-run: " . date('c') . "\n", FILE_APPEND);
echo "DIAG_OK";
?>
