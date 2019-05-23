<?php
defined('BASEPATH') OR exit('No direct script access allowed');

class Data extends CI_Controller
{
	public function __construct()
	{
		parent::__construct();
	}
	
	public function get_test_data()
	{
		$arr = array('a'=>123, 'b'=>'name');
		$t = time();
		$n = 'aaa';
		print_r(array(md5('15962746606'),
			sha1(implode(array('15962746606',$t,$n))),
			$t,
			$n
			));
		echo json_encode($arr);
	}
	
	
	
	public function terminal_position()
	{
		$lon = $_GET['lon'];
		$lat = $_GET['lat'];
//		$ter_position = $this->
	}
}
?>