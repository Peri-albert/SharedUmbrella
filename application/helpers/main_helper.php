<?php
defined('BASEPATH') OR exit('No direct script access allowed');

function should_pay_moeny($start, $end) {
	$use_time = $end - $start;
	$use_time < 0 ? -$use_time : $use_time;
	if ($use_time <= 60 * 60 * 12) {
		return round(0, 2);
	} elseif ($use_time <= 60 * 60 * 24) {
		return round(1, 2);
	} else {
		$out_time = ($use_time - 60 * 60 * 24) / 3600;
		$money = round($out_time, 0) * 0.05 + 1;
		if ($money >= 20) {
			return round(20, 2);
		}
		return round($money, 2);
	}
	return round(0, 2);
}

function out_time($start, $end, $formate = 'i') {
	$use_time = $end - $start;
	$use_time < 0 ? -$use_time : $use_time;
	if ($formate === FALSE) {
		return 60 * 60 * 12 - $use_time;
	} elseif ($formate === 'i') {
		$t = $use_time - 60 * 60 * 12;
		return intval($t/60);
	}
}
?>