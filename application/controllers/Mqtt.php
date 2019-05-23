<?php
defined('BASEPATH') OR exit('No direct script access allowed');

class Mqtt extends CI_Controller
{
	public function __construct()
	{
		parent::__construct();
		$this->load->helper('msg');
		$this->load->model('api_model');
	}
	
	//6349-bogon
	public function publish()
	{
		$this->load->file(APPPATH.'third_party/'.'phpMQTT.php');
		$mqtt = new phpMQTT("120.25.93.215", 1883, "6712-iZwz9hhbyo"); //Change client name to something unique
		if ($mqtt->connect())
		{
			$mqtt->publish("topic","Hello World! at ".date("r"),1);
			$mqtt->close();
		}
	}
	
	public function subscribe()
	{
		$this->load->file(APPPATH.'third_party/'.'phpMQTT.php');
		$mqtt = new phpMQTT("120.25.93.215", 1883, "6712-iZwz9hhbyo");
		if(!$mqtt->connect())
		{
			exit(1);
		}
		$topics['topic'] = array("qos"=>0, "function"=>"procmsg");
		$mqtt->subscribe($topics,0);
		
		
		while($mqtt->proc(false)){
		
		}
		$mqtt->close();
		function procmsg($topic,$msg)
		{
				echo "Msg Recieved: ".date("r")."\nTopic:{$topic}\n$msg\n";
		}
	}

}

?>