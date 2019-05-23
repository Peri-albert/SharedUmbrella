<?php
defined('BASEPATH') OR exit('No direct script access allowed');

class Data_model extends CI_Model
{
	public function __construct()
	{
		parent::__construct();
		$this->load->database();
	}
	
	public function terminal_position($lon='', $lat='')
	{
		if($lon==''||$lat=='')
		{
			return array();
		}
		$lon1 = $lon + 1;
		$lon2 = $lon + 1;
		$lat1 = $lat + 1;
		$lat2 = $lat - 1;
		$where = 
	}
}
?>