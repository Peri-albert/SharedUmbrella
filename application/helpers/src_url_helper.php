<?php
defined('BASEPATH') OR exit('No direct script access allowed');

function src_url($uri = '')
{
//	get_instance()->load->helper('url');
	return preg_replace('/\/index.php\?\//','/',base_url($uri));
}
?>