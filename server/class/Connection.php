<?php

require_once("gobang/player.php");
/**
 * WebSocket Connection class
 *
 * @author Nico Kaiser <nico@kaiser.me>
 * @author Neekey who modified this for online_gobang <ni184775761@gmail.com>
 */
class Connection
{
    /**
     * @var Class Main server class
     */
    private $server;

    /**
     * @var Socket Client socket
     */
    private $socket;

    /**
     * @var Boolen If handshake is made
     */
    private $handshaked = false;

    /**
     * @var player after handshake is made , this connection became a player
     */
    private $player = null;

    
    public function __construct($server, $socket)
    {
        $this->server = $server;
        $this->socket = $socket;

        $this->log('Connected');
    }

    /**
     * Do handshake
     *
     * @param string date from client
     */
    private function handshake($data)
    {
        $this->log('Performing handshake');
        
        $lines = preg_split("/\r\n/", $data);

        // Check header
        if (! preg_match('/\AGET (\S+) HTTP\/1.1\z/', $lines[0], $matches))
        {
            $this->log('Invalid request: ' . $lines[0]);
            socket_close($this->socket);
            return false;
        }

        // Get the file path if exist
        $path = $matches[1];

        foreach ($lines as $line)
        {
            $line = chop($line);
            if (preg_match('/\A(\S+): (.*)\z/', $line, $matches))
            {
                $headers[$matches[1]] = $matches[2];
            }
        }

        // Get the last string key
        $key3 = '';
        preg_match("#\r\n(.*?)\$#", $data, $match) && $key3 = $match[1];

        $origin = $headers['Origin'];
        $host = $headers['Host'];
        
        $status = '101 Web Socket Protocol Handshake';
        if (array_key_exists('Sec-WebSocket-Key1', $headers))
        {
            // for draft-76
            $def_header = array(
                'Sec-WebSocket-Origin' => $origin,
                'Sec-WebSocket-Location' => "ws://{$host}{$path}"
            );
            $digest = $this->securityDigest($headers['Sec-WebSocket-Key1'], $headers['Sec-WebSocket-Key2'], $key3);
        } 
        else
        {
            // for draft-75
            $def_header = array(
                'WebSocket-Origin' => $origin,
                'WebSocket-Location' => "ws://{$host}{$path}");
            $digest = '';
        }

        // Header sent to client
        $header_str = '';
        
        foreach ($def_header as $key => $value)
        {
            $header_str .= $key . ': ' . $value . "\r\n";
        }

        $upgrade = "HTTP/1.1 ${status}\r\n" .
            "Upgrade: WebSocket\r\n" .
            "Connection: Upgrade\r\n" .
            "${header_str}\r\n$digest";

        $this->server->send($this->socket, $upgrade);
        
        $this->handshaked = true;
        $this->log('Handshake sent');
        print_r($this->server->allsockets);
        return true;
    }

    /**
     * Receive data from Sever layer
     *
     * @param string date 
     */
    public function onData($data)
    {
        if ($this->handshaked) {
            $this->handle($data);
        } else {
            if($this->handshake($data))
            {
                $this->player = new player($this, $this->server->gobang);
                $this->server->gobang->add_player($this->player);
            }
            else $this->handshaked = false;
        }
    }

    /**
     * Handle the received data
     * this function will be involved if handshake has been made
     *
     * @param string date
     */
    private function handle($data)
    {
        $chunks = explode(chr(255), $data);

        for ($i = 0; $i < count($chunks) - 1; $i++) {
            $chunk = $chunks[$i];
            if (substr($chunk, 0, 1) !== chr(0)) {
                $this->log('Data incorrectly framed. Dropping connection');
                socket_close($this->socket);
                return false;
            }
            $this->player->ondate(substr($chunk, 1));
        }
        return true;
    }

    /**
     * Send data to client
     *
     * @param string date
     */
    public function send($data)
    {
        if (! @socket_write($this->socket, chr(0) . $data . chr(255), strlen($data) + 2)) {
            @socket_close($this->socket);
            $this->socket = false;
        }
    }

    /**
     * Disconnect the connection
     *
     */
    public function onDisconnect()
    {
        $this->log('Disconnected', 'info');
        socket_close($this->socket);
        print_r($this->server->allsockets);
    }

    /**
     * Return a boolen value to show if this connection is a player
     *
     * @return boolen 
     */
    public function is_player()
    {
        return $this->player === null ? false : true;
    }

    /**
     * Return player
     *
     * @return player 
     */
    public function get_player()
    {
        return $this->player;
    }
    
    /**
     * Caculate the digest
     *
     * @param string $key1 value of filed Sec-WebSocket-Key1 from client header
     * @param string $key2 value of filed Sec-WebSocket-Key2 from client header
     * @param string $key3 the last value of the client header
     */
    private function securityDigest($key1, $key2, $key3)
    {
        return md5(
            pack('N', $this->keyToBytes($key1)) .
            pack('N', $this->keyToBytes($key2)) .
            $key3, true);
    }

    /**
     * WebSocket draft 76 handshake by Andrea Giammarchi
     * see http://webreflection.blogspot.com/2010/06/websocket-handshake-76-simplified.html
     */
    private function keyToBytes($key)
    {
        return preg_match_all('#[0-9]#', $key, $number) && preg_match_all('# #', $key, $space) ?
            implode('', $number[0]) / count($space[0]) :
            '';
    }

    /**
     * Log a message
     *
     * @param string $message The message
     * @param string $type The type of the message
     */
    public function log($message, $type = 'info')
    {
        socket_getpeername($this->socket, $addr, $port);
        $this->server->log('[client ' . $addr . ':' . $port . '] ' . $message, $type);
    }
}