/**
 * @author Neekey(Niyunjian) <ni184775761@gmail.com>
 */

var host = "ws://localhost:8000";
var role = null;
var roll = null;
var cometitor_name = "";
var name = "";

window.onload = init;

function init()
{
    build_chessboard();
    add_listeners();
    creat_socket(host, onopen, onmessage, onclose);
    $("#name_div").hide();
    $("#msg_div").hide();
}

function add_listeners()
{
    $("#ready").bind("click", ready_click);
    $("#send_name").bind("click", send_name);
    $("#role_deceide").bind("click", roll_number);
    $("#chessgrids div").bind("click", do_chess);
    $("#send_msg").bind("click", chat);
}

function build_chessboard()
{
    draw_chessboard();
    add_grids();
}

function onopen()
{
    log("欢迎进入在线五子棋对战平台 <br /> 请输入您的昵称");
    $("#name_div").show();
}
function onmessage(msg)
{
    var message = msg_trans(msg.data);
    var type = message[0];
    var value = message[1];
    switch(type)
    {
        case "0":
            var value_arr = value.split(",");
            update_playerinfo(value_arr[0], value_arr[1], value_arr[2]);
            break;
        case "1":
            state_change_result(value);
            break;
        case "2":
            competitor_arrive(value);
            break;
        case "3":
            var value_arr = value.split(",");
            role = value_arr[0];
            roll_decide(value_arr[1], role);
            break;
        case "4":
            chess_result(value);
            break;
        case "7":
            state_change_result(0);
            notice("您的对手已经断开连接！");
            break;
        case "8":
            chat_rec(value);
            break;
        case "9":
            $("#name_div").hide();
            break;
    }
}

function onclose()
{
    log("连接关闭 - 状态 "+this.readyState + "<br />请刷新页面重试！");
    $("#name_div,#welcome,#game").hide();
}

function game_start()
{
    set_color();
    $("#role_deceide").delay(1000).hide("slow");
    $("#chesscover").hide("slow");
    // White first
    turn_change(0);
}

function do_chess()
{
    var position = this.id;
    $(this).unbind("click");
    grid_blocked(this);

    $("#chesscover2").show();
    send(2, position);
}

function chess_result(value)
{
    var valarr = value.split(",");
    $("#turn_flag").hide();

    if(valarr[0] == "2")
    {
        if(valarr[2] == role) comp_grid_blocked(valarr[1]);
        state_change_result(3);
        log("You win!");
        notice("You win!");
    }
    else if(valarr[0] == "3")
    {
        if(valarr[2] == role) comp_grid_blocked(valarr[1]);
        state_change_result(3);
        log("You lose!");
        notice("You lose!");
    }
    else
    {
        if(valarr[0] == role) comp_grid_blocked(valarr[1]);
        turn_change(valarr[0]);
    }
}

function send_name()
{
    var n = $("#name")[0].value;
    if(n != "")
    {
        name = n;
        send("6", $("#name")[0].value);
        $(this).unbind("click");
        $("#game").fadeIn("slow");
        log("欢迎进入在线五子棋对战平台");
        welcome_text("Hello! " + name);
        $("#welcome").show("slow");
    }
    else alert("昵称不能为空！");
}

function chat()
{
    var msg = $("#msg")[0].value;
    if(msg == '') alert("发送消息不能为空");
    else
    {
        $("#msg")[0].value = "";
        send(5, msg);
    }
}

function chat_rec(msg)
{
    msg = cometitor_name + "对您说：" + msg;
    $("#chat").empty();
    $("#chat").append(msg);
    $("#chat").animate({
        width: "200px",
        opacity: "1",
        top: "150"}, "fase").delay(1500).animate({
        width: "200px",
        opacity: "0",
        top: "300"}, "fase");
}

function notice(msg)
{
    $("#chat").empty();
    $("#chat").append(msg);
    $("#chat").animate({
        width: "200px",
        opacity: "1",
        top: "150"}, "fase").delay(1500).animate({
        width: "200px",
        opacity: "0",
        top: "300"}, "fase");
}

function state_change_result(result)
{
    $("#ready").removeClass();
    $("#ready").unbind("click");
    if(result == "0")
    {
        log("欢迎进入在线五子棋对战平台")
        reset_board();
        $("#ready").bind("click", ready_click);
        $("#ready").toggleClass("ready");
        $("#turn_flag").hide();
        ready_text("Ready");
        $("#msg_div").hide();
        $("#role_deceide").hide();
    }
    else if(result == "1")
    {
        $("#ready").bind("click", ready_click);
        $("#ready").toggleClass("notready");
        ready_text("Wait");
        $("#msg_div").hide();
        $("#role_deceide").hide();
    }
    else if(result == "2")
    {
        $("#ready").toggleClass("gaming");
        ready_text("Gaming");
        $("#msg_div").show();
    }
    else
    {
        $("#ready").bind("click", clear_board);
        $("#ready").toggleClass("clear");
        $("#turn_flag").hide();
        ready_text("Clear");
        $("#msg_div").hide();
    }
}

function ready_click()
{
    var type = this.className;
    type == "ready" ? send("0", "1") : send("0", "0");
    $(this).unbind('click');
    $(this).removeClass();
    $(this).toggleClass("state_wait");
    ready_text("Changing..");
}

function clear_board()
{
    reset_board();
    role = null;
    roll = null;
    cometitor_name = "";
    $(this).unbind("click");
    send("0", "0");
}
function reset_board()
{
    $("#chessgrids div").bind("click", do_chess);
    $("#chessgrids div").removeClass();
    $("#chessgrids div").addClass("chessgrid");
}
function competitor_arrive(cm_name)
{
    log("您的对手为：" + cm_name);

    $("#ready").unbind('click');
    $("#ready").removeClass();
    $("#ready").toggleClass("state_wait");
    ready_text("Changing..");

    roll_text("Roll Number!");
    $("#role_deceide").unbind("click");
    $("#role_deceide").bind("click",roll_number);
    $("#role_deceide").removeClass();
    $("#role_deceide").toggleClass("roll");
    
    $("#role_deceide").show('slow');
    
    cometitor_name = cm_name;
}

function roll_decide(com_num, result)
{
    roll_text("You rolled: " + roll + "<br />" + cometitor_name + " rolled: " + com_num);
    
    var log_msg = "You are";
    if(result == "0")  log_msg += " White";
    else log_msg += " Black";
    log_msg += ("<br /> Competitor: " + cometitor_name);
    log(log_msg);
    game_start();
}
function roll_number()
{
    var random = make_random_num();
    roll = random;
    
    roll_text("You rolled: " + random + "<br /> Wait competitor...");
    $("#role_deceide").unbind("click");
    $("#role_deceide").removeClass();
    $("#role_deceide").toggleClass("wait_roll");
    send(1,roll);
}
function make_random_num()
{
    return Math.floor(Math.random()*10+1);
}

function draw_chessboard()
{
    var canvas = $("#chessboard")[0];
    var c_context = canvas.getContext("2d");

    c_context.fillStyle = "#E49924";
    c_context.fillRect(0, 0, 300, 300);

    c_context.beginPath();
    var i = 0;
    for(i = 0; i < 15; i++)
    {
        c_context.moveTo(i * 20 + 10, 10);
        c_context.lineTo(i * 20 + 10, 290);
        c_context.moveTo(10, i * 20 + 10);
        c_context.lineTo(290, i * 20 + 10);
    }
    c_context.strokeStyle = "#000";
    c_context.stroke();
}
function add_grids()
{
    var board = $("#chessgrids")[0];
    var col;
    var row;

    for(col = 0; col < 15; col++)
    {
        for(row = 0; row < 15; row++)
        {
            $(board).append('<div id="' + row + '#' + col + '" class="chessgrid chessgrid_empty"></div>');
        }
    }
}

function set_color()
{
    var class_add = (role == 1 ? "chessgrid_empty_black" : "chessgrid_empty_white");
    var class_remove = (role == 0 ? "chessgrid_empty_black" : "chessgrid_empty_white");
    $("#chessgrids div").removeClass();
    $("#chessgrids div").addClass(class_add);
    $("#chessgrids div").addClass("chessgrid");
}

function grid_blocked(grid)
{
    var class_add = (role == 1 ? "chessgrid_black" : "chessgrid_white");
    $(grid).removeClass();
    $(grid).addClass("chessgrid");
    $(grid).addClass(class_add);
}

function comp_grid_blocked(gridid)
{
    var grid = document.getElementById(gridid);
    $(grid).unbind("click");
    var class_add = (role == 0 ? "chessgrid_black" : "chessgrid_white");
    $(grid).removeClass();
    $(grid).addClass("chessgrid");
    $(grid).addClass(class_add);
}


function update_playerinfo(online, ready, gaming)
{
    $("#playerinfo").empty();
    var playerinfo = "Online: " + online + "\n\t" + "Ready: " + ready + "\n\t" + "Gaming: " + gaming;
    $("#playerinfo").append(playerinfo);
}

function turn_change(turn)
{
    $("#turn_flag").removeClass();
    $("#turn_flag").empty();
    $("#turn_flag").hide();
    if(turn == role) 
    {
        $("#turn_flag").addClass("your_turn");
        $("#turn_flag").append("Your turn!");
        $("#chesscover2").hide();
        $("#turn_flag").show("fast").delay(2000).hide("slow");
    }
    else
    {
        $("#turn_flag").addClass("comp_turn");
        $("#turn_flag").append("Competitor's turn!");
        $("#chesscover2").show();
        $("#turn_flag").show("fast");
    }
   
}

function roll_text(text)
{
    $("#role_deceide").empty();
    $("#role_deceide").append(text);
}

function ready_text(text)
{
    $("#ready").empty();
    $("#ready").append(text);
}

function welcome_text(text)
{
    $("#welcome").empty();
    $("#welcome").append(text);
}
function log(message)
{
    $("#log").empty();
    $("#log").append(message);
}
