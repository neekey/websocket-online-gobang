<?php

require_once("socket.php");
require_once("connection.php");
require_once("gobang/gobang.php");
/**
 * Simple WebSockets server
 *
 * @author Nico Kaiser <nico@kaiser.me>
 * @author Neekey who modified this for online_gobang <ni184775761@gmail.com>
 */

/**
 * This is the main Server class
 */
class Server extends Socket
{
    /**
     * @var Socket Holds the client Connection Array
     */
    public $clients = array();

    public $gobang;

    public function __construct($host = 'localhost', $port = 8000, $max = 100)
    {
        parent::__construct($host, $port, $max);
        $this->gobang = new gobang($this);
        $this->log('Server created');
    }

    /**
     * Start the server to do polling
     */
    public function run()
    {
        while (true)
        {
            $changed_sockets = $this->allsockets;
            @socket_select($changed_sockets, $write = NULL, $exceptions = NULL, NULL);

            foreach ($changed_sockets as $socket)
            {
                // Master socket
                if ($socket === $this->master)
                {
                    if (($ressource = socket_accept($this->master)) < 0)
                    {
                        $this->log('Socket error: ' . socket_strerror(socket_last_error($ressource)));
                        continue;
                    }
                    else
                    {
                        // Creat an new Connection object for the new socket
                        // and take the socket as the index
                        $client = new Connection($this, $ressource);
                        $this->clients[$ressource] = $client;
                        $this->allsockets[] = $ressource;
                    }
                }
                // Client sockets
                else
                {
                    $client = $this->clients[$socket];
                    $bytes = @socket_recv($socket, $data, 4096, 0);
                    if ($bytes === 0)
                    {
                        $client->onDisconnect();
                        if($client->is_player())
                        {
                            $this->gobang->remove_player($client->get_player());
                        }
                        unset($this->clients[$socket]);
                        $index = array_search($socket, $this->allsockets);
                        unset($this->allsockets[$index]);
                        unset($client);
                    }
                    else
                    {
                        $client->onData($data);
                    }
                }
            }
        }
    }
}
