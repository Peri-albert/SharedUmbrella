<?php
/*如果需要演示该文件的运行，按如下步骤：
1. 在远程服务器的终端一 执行 mosquitto -v 观察设备连入和borker（代理服务器）的情况
2. 在自己的主机的终端一 执行 mosquitto_sub -h 120.25.93.215 -t PrepareUmbrella 订阅位于该ip上的该主题的消息（仅用于查看是否发送了消息以便与脚本对比）
3. 在远程服务器的终端二 执行 php 预读取伞id.php 让服务器脚本订阅PrepareUmbrella 主题用于接收消息
4. 在自己的主机的终端二 执行 mosquitto_pub -h 120.25.93.215 -t PrepareUmbrella -m "{4563908423798" 模拟嵌入式设备发送伞id给服务器
5. 对比远程终端二和自己主机终端一的结果，查看接受到的消息是否一致
*/

//需要改动@1中的ip地址（默认为locahost）和@2中的话题名字（本文件中为PrepareUmbrella）以及@3中的判断合法字符的逻辑默认为首字符为{即为合法字符
//本文件的目的是获取欲读取伞的id，该变量存储在变量$umbrellaID中

$client = new Mosquitto\Client("BrokerListener");	//该实例名为borker，即连接到borker时显示名为BorkerListener
$client->onConnect('connect');			
$client->onDisconnect('disconnect');		
$client->onSubscribe('subscribe');		
$client->onMessage('message');			

$client->connect("localhost", 1883, 5);		//@1
$client->subscribe('borrow', 1);	//@2	
while(true){					//开始执行循环
	$client->loop();
}
$client->disconnect();				//如果在上面的循环中有错误跳出，最后程序将会将连接安全的关掉
unset($client);


//功能及回调函数：
function connect($r) {		//该回调函数被onConnect()调用
	echo "I got code {$r}\n";
}
function subscribe() {
	echo "Subscribed to a topic\n";
}
function disconnect() {
	echo "Disconnected cleanly\n";
}
function message($message) { 
//从这里开始书写处理逻辑
	printf("Got a message ID %d on topic %s with payload:\n%s\n", $message->mid, $message->topic, $message->payload);
	//$message中主要变量:$mid：消息id，$topic：消息所属的话题,$payload:消息内容

	$payload=$message->payload;	//合法的字符串定义为：长度9位，首字符为$（在mosquitto中发布消息中$需要加转义，即："/$12345678")
	if($payload[0]=='{'){ //@3 判断是否为合法字符
		echo "Received a valid umbrella id,remember it\n";
		$umbrellaID=$payload;
		require 'script_borrow.php';	//该文件用于处理刚才接收到的伞id
		//require '发布出伞指令.php';	//如果有手机客户端请求借伞，其终端机器和用户id均已知道，可以执行该文件发布出伞命令
	}else {
		echo "invalid umbrella id\n";
	}
//判断逻辑结束
}

