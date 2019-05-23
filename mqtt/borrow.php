<?php

/*如果需要演示该文件的运行，按如下步骤：
1. 在远程服务器的终端一 执行 mosquitto -v 观察设备连入和borker（代理服务器）的情况
2. 在自己的主机的终端一 执行 mosquitto_sub -h 120.25.93.215 -t PrepareUmbrella 订阅位于该ip上的该主题的消息（模拟终端设备订阅该主题）
3. 在远程服务器的终端二 执行 php 发布出伞指令.php 让服务器脚本发布消息（模拟服务器向终端设备发出指令）
4. 查看自己主机终端一是否已经接受到服务器发送的消息
*/

$tid = isset($_GET['tid'])?$_GET['tid']:'12';
if($tid==''){
    return;
}


//发布执行出伞动作的指令，相关主题设置为ControlCommand.终端设备订阅该主题，服务器发布该主题
$client = new Mosquitto\Client("BrokerCommander");	//实例化一个Mosquitto实例，名为"BrokerCommander"，专门用于处理命令发布
$client->onConnect('connect');			
$client->onDisconnect('disconnect');		
//开始连接
$client->connect("localhost", 1883, 5);	
//进行发布
//发布一条消息，以 { 开头加上 终端ID ，各个终端接收到此消息后和自己的id比对，成功则开闸放伞
$mid = $client->publish('inTopic', "{1", 1, true);  //发布函数会有返回值，返回发布的id
// echo "Sent message ID: {$mid}\n";

$client->disconnect();				//程序将连接安全的关掉
unset($client);

function connect($r) {		//该回调函数被onConnect()调用
	// echo "I got code {$r}"."<br/>"; 
}

function disconnect() {
	// echo "Disconnected cleanly\n"."<br/>";
}

