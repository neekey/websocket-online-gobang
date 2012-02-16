/**
 * @author Neekey(Niyunjian) <ni184775761@gmail.com>
 */

function creat_socket(host, onopen, onmessage, onclose)
{
    try
    {
        socket = new WebSocket(host);
        log('WebSocket初始化状态 '+socket.readyState);
        socket.onopen    = onopen; 
        socket.onmessage = onmessage; 
        socket.onclose   = onclose; 
    }
    catch(ex){log(ex);}
}
/**
 * Send msg to server
 *
 * @param string value
 * 0: ready/notready 1/0
 * 1: role-deceide num
 * 2: x,y
 * 3: regret/agree/disagree
 * 4: surrender
 * 5: message
 * 6: name
 */
function send(type, value)
{
    if(socket.readyState == 1)
    {
        socket.send(type + "\n\t" + value);
    }
    else alert("disconnected from server!");
}

/**
 * Parse the msg from server
 *
 * @param string msg
 * 0: playerinfo
 * 1: if state be changed 0/1/2
 * 2: find a competitor
 * 3: role result (0/1),(num) => (white/black),(competitor's roll number)
 * 4: chess result
 *      0 => next turn is white  0,x#y
 *      1 => next turn is black  1,x#y
 *      2 => you win             2,x#y
 *      3 => you lose            3,x#y
 * 5: competitor regret
 * 6: competitor surrender
 * 7: competitor disconnected
 * 8: message
 * 9: name confirm
 *
 * @return array field/value as the msg received
 */
function msg_trans(msg)
{
    $.trim(msg);
    message = new Array();
    var msg_arr = msg.split("\n\t");
    
    message[0] = msg_arr[0];
    message[1] = msg_arr[1];

    return message;
}

