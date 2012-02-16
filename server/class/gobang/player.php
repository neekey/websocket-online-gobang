<?php
/**
 * Description of player
 *
 * @author Neekey(NiYunjian) <ni184775761@gmail.comL>
 */
class player {

    /**
     * @var Connection Client connection
     */
    private $connection;

    /**
     * @var gobang 
     */
    public $gobang;
    
    /**
     * @var int Player state 0:idle , 1:ready, 2:gaming, 3:gameover
     */
    private $state = 0;

    /**
     * @var mixed 
     */
    public $game = null;

    public $competitor = null;

    public $name = null;

    function  __construct($connection, $gobang) {
        $this->connection = $connection;
        $this->gobang = $gobang;
    }

    /**
     * Send msg to other player
     * 
     * @param string msg to send
     */
    function send_message($type, $value = "")
    {
        $msg = $type . "\n\t" . $value;
        //$this->connection->log("[send_message]" . $msg);
        $this->connection->send($msg);
    }

    function chat($msg)
    {
        $this->game->chat($this, $msg);
    }

    /**
     * Receive date form connection and pass to gobang
     *
     * @param string $date 
     */
    function ondate($date)
    {
        $this->gobang->receive_msg($date, $this);
    }

    /**
     * Change player state
     *
     * @param int $state the state to changed to
     */
    function change_state($state)
    {
        if($this->state !== ($state))
        {
           $this->state = intval($state);
        }
    }

    /**
     * Send back the player state to client
     */
    function change_state_result()
    {
        $this->gobang->server->log("[change_state_result]: player state $this->state");
        $this->send_message(1, $this->state);
    }

    /**
     * Return the player state
     */
    function get_state()
    {
        return $this->state;
    }

    function set_game($game, $competitor)
    {
        $this->game = $game;
        $this->competitor = $competitor;
    }

    function set_name($name)
    {
        $this->name = $name;
        $this->send_message("9", "1");
    }
}
?>
