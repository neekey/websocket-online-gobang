<?php
require_once("game.php");
/**
 * Gobang class
 *
 * rules, judges, and so on
 * @author Neekey(NiYunjian) <ni184775761@gmail.comL>
 */

class gobang {

    /**
     * @var Class Main server class
     */
    public $server;

    /**
     * @var array All the player
     */
    public $players = array();

    /**
     * @var array All the player who is ready to play
     */
    public $ready = array();

    /**
     * @var array All the player who is gaming
     */
    public $gaming = array();

    /**
     * @var array All the games which is playing
     */
    public $games = array();

    function  __construct($server)
    {
        $this->server = $server;
    }

    /**
     * Find a ready player for the current player
     * 
     * @param player $player the player who is ready to game
     */
    function get_competitor($player)
    {
        $ready_num = count($this->ready);
        if($ready_num >= 2)
        {
            $player_index = array_search($player, $this->ready);
            foreach($this->ready as $competitor)
            {
                $i = array_search($competitor, $this->ready);
                if(isset($competitor) && $i !== $player_index)
                {
                    return $this->ready[$i];
                }
            }
        }
        else return false;
    }

    function add_game($player1, $player2)
    {
        $this->add_gaming($player1);
        $this->add_gaming($player2);
        $this->send_info();
        
        $new_game = new game($player1, $player2, $this);
        $this->games[] = $new_game;
        $this->server->log("[add_game]:an new game is added");
    }
    /**
     * Add a new player to the players array
     * 
     * @param player $player the new player to add
     */
    function add_player($player)
    {
        $this->players[] = $player;
        $this->send_info();
    }

    /**
     * Add a new ready player to the ready array
     *
     * @param player $player the ready player to add
     */
    function add_ready($player)
    {
        $this->ready[] = $player;
        $this->send_info();
        $this->server->log("[add_ready]:player $player->name is added");

        $player2 = $this->get_competitor($player);
        if($player2 !== false)
        {
            $this->server->log("[add_ready]:competitor found");
            $this->add_game($player, $player2);
        }
    }

    function add_gaming($player)
    {
        $this->gaming[] = $player;
        $this->remove_ready($player);
        $player->change_state(2);
    }

    /**
     * remove a new  player from the players array
     * alse check if it is in the ready array, if so remove it
     *
     * @param player $player the  player to be removed
     */
    function remove_player($player)
    {
        $this->remove_ready($player);
        $this->remove_gaming($player);
        $pindex = array_search($player, $this->players);
        unset($this->players[$pindex]);
        $this->send_info();
    }

    /**
     * remove a player from the ready array
     *
     * @param player $player the  player to be removed
     */
    function remove_ready($player)
    {
        $rindex = array_search($player, $this->ready);
        if(count($this->ready) > 0 && $rindex >= 0)
        {
            unset($this->ready[$rindex]);
            $this->send_info();
            $this->server->log("[remove_ready]: remove player $player->name");
        }
    }

    function remove_gaming($player)
    {
        $gindex = array_search($player, $this->gaming);
        if(count($this->gaming) > 0 && $gindex >= 0)
        {
            if($player->get_state() === 2)
            {
                $player->competitor->send_message("7");
            }
            unset($this->gaming[$gindex]);
            $comindex = array_search($player->competitor, $this->gaming);
            if($comindex >= 0)
            {
                $player->competitor->change_state(0);
                $player->competitor->change_state_result();
                unset($this->gaming[$comindex]);
            }
            $this->send_info();
        }
    }

    function remove_game($game)
    {
        $gindex = array_search($game, $this->games);
        unset($this->games[$gindex]);
    }
    /**
     * send current info about the num of players and ready to all the clients
     * this function will be invloved when the players array or
     * ready array changed
     *
     */
    function send_info()
    {
        $type = 0;
        $value = count($this->players) . "," . count($this->ready) . "," . count($this->gaming);
        foreach($this->players as $player)
        {
            $player->send_message($type,$value);
        }
    }

    /**
     * Receive data from player
     *
     * @param string $data
     * @param player the player who send the data
     */
    function receive_msg($date, $player)
    {
        $message = $this->trans_msg($date);
        $type = $message[0];
        $value = $message[1];

        switch ($type)
        {
            // player state change
            case "0":
                $player_state = $player->get_state();
                if($player_state !== 2)
                {
                    if($value === '0' && $player_state !== 0)
                    {
                        $player->change_state($value);
                        if($player_state !== 3) $this->remove_ready($player);
                        else $this->remove_gaming($player);
                        $player->change_state_result();
                    }
                    else
                    {
                        if($player_state === 0)
                        {
                            $player->change_state($value);
                            $player->change_state_result();
                            $this->add_ready($player);
                        }
                    }
                }
                break;
            case "6":
                $player->set_name($value);
                break;
            case "1":
                $player->game->roll_number($player, $value);
                break;
            case "2":
                $player->game->chess($player, $value);
                break;
            case "3":
            case "5":
                $player->game->chat($player, $value);
                break;

        }
    }

    /**
     * Translate the data
     *
     * @param string $data
     * @return array which contains the type and value from data
     */
    function trans_msg($date)
    {
        $message = array();
        $msg_arr = explode("\n\t", $date);

        $message[0] = $msg_arr[0];
        $message[1] = $msg_arr[1];

        return $message;
    }
    
}


?>
