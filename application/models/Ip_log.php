<?php
defined('BASEPATH') OR exit('No direct script access allowed');

class Ip_log extends CI_Model
{
	public function __construct()
	{
		parent::__construct();
		$this->load->database();
		date_default_timezone_set("PRC");
	}
	
	public function w($ip,$url)
	{
		$data = array(
			"ip"		=>	$ip,
			"url"	=>	$url,
			"time"	=>	date('Y-m-d H:i:s')
		);
		$this->db->insert('log',$data);
	}
}
?>