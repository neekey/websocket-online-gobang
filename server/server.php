<?php
require_once 'class/server.php';
/**
 * Create a server and run
 *
 * @author Neekey(NiYunjian) <ni184775761@gmail.com>
 */

/**
 * default value for host, port and max
 * @example you can change by $server = new Server($host, $port, $max)
 */
#$host = "localhost";
#$port = 8000;
#$max = 100;

$server = new Server();
$server->run();

?>
