<?php

/**
 * Socket class
 *
 * @author Moritz Wutz <moritzwutz@gmail.com>
 * @author Nico Kaiser <nico@kaiser.me>
 * @version 0.2 modified by Neekey <ni184775761@gmail.com>
 * @
 */

/**
 * This is the main socket class
 */
class Socket
{
    /**
     * @var Socket Holds the master socket
     */
    public $master;

    /**
     * @var array Holds all connected sockets
     */
    public $allsockets = array();

    public function __construct($host = 'localhost', $port = 80, $max = 100)
    {
        ob_implicit_flush(true);
        $this->createSocket($host, $port, $max);
    }

    /**
     * Create a socket on given host/port
     * 
     * @param string $host The host/bind address to use
     * @param int $port The actual port to bind on
     * @param int $max The max num port to listen to
     */
    private function createSocket($host, $port, $max)
    {
        if (($this->master = socket_create(AF_INET, SOCK_STREAM, SOL_TCP)) < 0)
        {
            die("socket_create() failed, reason: " . socket_strerror($this->master));
        }

        $this->log("Socket {$this->master} created.");

        socket_set_option($this->master, SOL_SOCKET, SO_REUSEADDR, 1);
        #socket_set_option($master,SOL_SOCKET,SO_KEEPALIVE,1);

        if (($ret = socket_bind($this->master, $host, $port)) < 0)
        {
            die("socket_bind() failed, reason: " . socket_strerror($ret));
        }

        $this->log("Socket bound to {$host}:{$port}.");

        if (($ret = socket_listen($this->master, $max)) < 0)
        {
            die("socket_listen() failed, reason: " . socket_strerror($ret));
        }

        $this->log('Start listening on Socket.');

        # add master socket to allsockets array
        $this->allsockets[] = $this->master;
    }

    /**
     * Sends a message over the socket
     * @param socket $client The destination socket
     * @param string $msg The message
     */
    public function send($client, $msg)
    {
        socket_write($client, $msg, strlen($msg));
    }

    /**
     * Log a message
     *
     * @param string $message The message
     * @param string $type The type of the message
     */
    public function log($message, $type = 'info')
    {
        echo date('Y-m-d H:i:s') . ' [' . ($type ? $type : 'error') . '] ' . $message . PHP_EOL;
    }
}
