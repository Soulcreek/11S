<?php 
echo "🎮 11Seconds - PHP Test";
echo "<br>PHP Version: " . phpversion();
echo "<br>Server: " . $_SERVER['SERVER_SOFTWARE'] ?? 'Unknown';
echo "<br>Document Root: " . $_SERVER['DOCUMENT_ROOT'] ?? 'Unknown';
echo "<br>Request URI: " . $_SERVER['REQUEST_URI'] ?? 'Unknown';
phpinfo();
?>
