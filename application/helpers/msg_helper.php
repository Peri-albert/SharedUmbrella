<?php
defined('BASEPATH') OR exit('No direct script access allowed');

function msg($code=200, $msg="api_default_info")
{
	$msg = array(
		"code"	=>	$code,
		"msg"	=>	$msg,
	);
	return json_encode($msg, JSON_UNESCAPED_UNICODE);
}

function is_session($key)
{
	$CI = &get_instance();
	$CI->load->database();
	$CI->db->where('sess', $key);
	$res = $CI->db->get('session')->row_array();
	if(!empty($res))
	{
		$openid = $res['openid'];
		$CI->db->where('openid', $openid);
		$uid = $CI->db->get('user')->row_array();
		return $uid['uid'];
	}
	exit(msg(503, "用户未登录，请重试"));
}
?>