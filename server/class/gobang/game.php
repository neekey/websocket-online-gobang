<?php
/**
 * game class
 *
 * @author Neekey(NiYunjian) <ni184775761@gmail.comL>
 */
class game {

    /**
     * @var mixed Record the history of chessing
     */
    private $history = array();

    /**
     * @var mixed Used to deceide the role
     * $roles[3] => the state of role-deceide is not made
     *              0 => role-deceide is not made
     *              1 => only one player has made his radom num
     *              2 => both player has made his radom num
     * $roles[0] => white player default null
     * $roles[1] => black player default null
     * $roles[4] => temp for roll numbers
     *
     * #notice that the larger will be white
     */
    private $roles = array();

    private $players = array();

    /**
     * @var int Describe the game process
     * 0: initial
     * 1: deceiding roles
     * 2: gaming
     * 3: game over
     */
    private $state = 0;

    /**
     * @var int 0: white, 1: black
     */
    private $cur_turn = 0;

    private $gobang;

    /**
     * @var array Record the chessboard
     * -1 => empty
     * 0  => white
     * 1  => black
     */
    private $chessboard = array();

    function  __construct($player1, $player2, $gobang)
    {
        $this->init_chessboard();
        $this->gobang = $gobang;

        $this->players[] = $player1;
        $this->players[] = $player2;
        $player1->set_game($this, $player2);
        $player2->set_game($this, $player1);

        $this->roles[3] = 0;
        $this->roles[0] = null;
        $this->roles[1] = null;
        $this->roles[4] = 0;

        $this->gobang->server->log("[game]:game initialing");
        $this->run();
    }

    /**
     * Game begain
     *
     */
    function run()
    {
        foreach($this->players as $player)
        {
            $player->send_message(2, $player->competitor->name);
        }
        $this->state = 1;
    }

    /**
     * If player rolled an number this fn will be involved
     *
     * @param player $player
     * @param int $num the rolled number
     */
    function roll_number($player, $num)
    {
        $this->gobang->server->log("[roll_number]: cur_state > " . $this->roles[3] . " name > " . $player->name);
        if($this->roles[3] === 0)
        {
            $this->roles[0] = $player;
            $this->roles[4] = $num;
            $this->roles[3]++;
        }
        else
        {
            if($this->roles[4] < $num)
            {
                $this->roles[0]->send_message(3, "1,$num");
                $this->roles[1] = $roles[0];
                $this->roles[0] = $player;
                $this->roles[3]++;
                $player->send_message(3, "0," . $this->roles[4]);
            }
            else
            {
                $this->roles[1] = $player;
                $this->roles[3]++;
                $player->send_message(3, "1," . $this->roles[4]);
                $this->roles[0]->send_message(3, "0,$num");
            }
            $this->state = 2;
            $this->send_both(1, 2);
        }
    }

    /**
     * When a player add an new piece this fn will be involved
     *
     * @param player $player
     * @param string $position x#y
     */
    function chess($player, $position)
    {
        $role = array_search($player, $this->roles);
        if($this->check_pos_valid($position))
        {
            $this->gobang->server->log("[chess]: chess valid");
            $this->add_position($position, $role);
            if($this->check_if_win($position, $role))
            {
                $this->gobang->server->log("[chess]: win");
                $this->win($player, $position);
            }
            else
            {
                $this->gobang->server->log("[chess]: not win");
                $this->send_both(4, $this->cur_turn . ",$position");
            }
        }
    }

    /**
     * When a player wins this fn will be involved
     *
     * @param player $player who is win
     */
    function win($player, $position)
    {
        $this->state = 3;
        foreach($this->players as $p) $p->change_state(3);
        
        $player->send_message(4, 2 . ",$position,$this->cur_turn");
        $player->competitor->send_message(4, 3 . ",$position,$this->cur_turn");
    }
    
    function surrender($player)
    {

    }

    /**
     * Check the new position if there already a piece there
     *
     * @param string $positon
     */
    function check_pos_valid($positon)
    {
        if(count($this->history) > 0)
        {
            if(array_search($positon, $this->history) !== false) return false;
            else return true;
        }
        return true;
    }

    /**
     * Add an new position to chessboard and history
     *
     * @param string $position
     * @param int $role 
     */
    function add_position($position, $role)
    {
        $pos = explode("#", $position);
        $x = $pos[0];
        $y = $pos[1];
        $this->chessboard[$x][$y] = $role;
        $this->history[] = $position;
        $this->cur_turn === 0 ? $this->cur_turn = 1 : $this->cur_turn = 0;
    }

    /**
     * Chat
     *
     * @param player $speaker
     * @param string $msg
     */
    function chat($speaker, $msg)
    {
        $speaker->competitor->send_message(8, $msg);
    }

    /**
     * Initialize the chessboard
     *
     */
    function init_chessboard()
    {
        for($i = 0; $i < 15; $i++)
        {
            for($j = 0; $j < 15; $j++)
            {
                $this->chessboard[$i][$j] = -1;
            }
        }
    }
    
    /**
     * Send message to each player
     *
     */
    function send_both($type, $value = "")
    {
        foreach($this->players as $player)
        {
            $player->send_message($type, $value);
        }
    }

    /**
     * Check the chessboard if someone is win
     *
     * @param string $position the new piece position
     * @param int $role
     */
    function check_if_win($positon, $role)
    {
        $pos = explode("#", $positon);
        $x = $pos[0];
        $y = $pos[1];
        $hor = $ver = $slo = $len = 1;

        $temp_x = $x;
        $temp_y = $y;

        // check hor direction
        while (true)
        {
            $temp_x++;
            if($temp_x < 15)
            {
                if($this->chessboard[$temp_x][$y] === $role)
                {
                    $hor++;
                    if($hor >= 5)
                    {
                        return true;
                    }
                }
                else break;
            }
            else break;
        }
        $temp_x = $x;
        while (true)
        {
            $temp_x--;
            if($temp_x >= 0)
            {
                if($this->chessboard[$temp_x][$y] === $role)
                {
                    $hor++;
                    if($hor >= 5) return true;
                }
                else break;
            }
            else break;
        }

        // check ver direction
        while (true)
        {
            $temp_y++;
            if($temp_y < 15)
            {
                if($this->chessboard[$x][$temp_y] === $role)
                {
                    $ver++;
                    if($ver >= 5) return true;
                }
                else break;
            }
            else break;
        }
        $temp_y = $y;
        while (true)
        {
            $temp_y--;
            if($temp_y >= 0)
            {
                if($this->chessboard[$x][$temp_y] === $role)
                {
                    $ver++;
                    if($ver >= 5) return true;
                }
                else break;
            }
            else break;
        }

        // check slo direction
        $temp_x = $x;
        $temp_y = $y;
        while (true)
        {
            $temp_y++;
            $temp_x++;
            if($temp_y < 15 && $temp_x < 15)
            {
                if($this->chessboard[$temp_x][$temp_y] === $role)
                {
                    $slo++;
                    if($slo >= 5) return true;
                }
                else break;
            }
            else break;
        }
        $temp_x = $x;
        $temp_y = $y;
        while (true)
        {
            $temp_y--;
            $temp_x--;
            if($temp_y >= 0 && $temp_x >= 0)
            {
                if($this->chessboard[$temp_x][$temp_y] === $role)
                {
                    $slo++;
                    if($slo >= 5) return true;
                }
                else break;
            }
            else break;
        }

        // check len direction
        $temp_x = $x;
        $temp_y = $y;
        while (true)
        {
            $temp_y--;
            $temp_x++;
            if($temp_y >= 0 && $temp_x < 15)
            {
                if($this->chessboard[$temp_x][$temp_y] === $role)
                {
                    $len++;
                    if($len >= 5) return true;
                }
                else break;
            }
            else break;
        }
        $temp_x = $x;
        $temp_y = $y;
        while (true)
        {
            $temp_y++;
            $temp_x--;
            if($temp_y < 15 && $temp_x >= 0)
            {
                if($this->chessboard[$temp_x][$temp_y] === $role)
                {
                    $len++;
                    if($len >= 5) return true;
                }
                else break;
            }
            else break;
        }
        return false;
    }
}
?>
