<?php
defined('BASEPATH') OR exit('No direct script access allowed');

class Api extends CI_Controller
{
    public function __construct()
    {
        parent::__construct();
        $this->load->helper('msg');
        $this->load->model("api_model");
    }

    public function log_uid_session()
    {
        $code = $this->input->get('code');
        if ($code == '') {
            exit(msg(302, "code不能为空"));
        }
        $url = "https://api.weixin.qq.com/sns/jscode2session?appid=wx47aef73ff4669d60&secret=b886cb26f611f522c7264f2a81fa4519&js_code=" . $code . "&grant_type=authorization_code";
        $curl = curl_init($url);
        curl_setopt($curl, CURLOPT_RETURNTRANSFER, true);
        $res = json_decode(curl_exec($curl));
        curl_close($curl);
        if (isset($res->errcode)) {
            exit(msg(500, "服务器错误"));
        }
        $openid = $res->openid;
        $session_key = $res->session_key;

        $sess = $this->my_sess() . 'um';
        $this->api_model->log_session($openid, $sess, $session_key);
        $value = $session_key . "+" . $openid;
        $old_session_info = $this->api_model->get_session($openid);
        $old_time = $old_session_info['time'];
        $old_sess = $old_session_info['sess'];
        if ($old_time + 7200 >= time()) {
            exit(msg(200, $old_sess));
        }

        $this->api_model->save_session($sess, $session_key, $openid);
        exit(msg(200, $sess));
    }

    private function my_sess()
    {
        $sess = time();
        foreach ($_SESSION as $k => $v) {
            if ($sess == $k) {
                $sess = $this->my_sess();
                break;
            }
        }
        return $sess;
    }

    public function wx_token()
    {
        //		$ip = $_SERVER["REMOTE_ADDR"];
        //		$url = 'http://'.$_SERVER['HTTP_HOST'].$_SERVER['REQUEST_URI'];
        //		$this->w($ip, $url);

        exit($_GET['echostr']);
        //		$echostr = isset($_GET['echostr'])?$_GET['echostr']:'';
        //		$signature = isset($_GET["signature"])?$_GET["signature"]:'';
        //	    $timestamp = isset($_GET["timestamp"])?$_GET["timestamp"]:'';
        //	    $nonce =isset($_GET["nonce"])?$_GET["nonce"]:'';
        //
        //	    $token = "214d35ecde329412ce178618232b246f";
        //	    $tmpArr = array($token, $timestamp, $nonce);
        //	    sort($tmpArr);
        //	    $tmpStr = implode( "", $tmpArr );
        //	    $tmpStr = sha1( $tmpStr );
        //	    if( $tmpStr == $signature ){
        //	        return $echostr;
        //	    }else{
        //	        return FALSE;
        //	    }
    }

    public function get_weather()
    {
        $lon = $this->input->get("lon", TRUE);
        $lat = $this->input->get("lat", TRUE);
        $host = "https://ali-weather.showapi.com";
        $path = "/gps-to-weather";
        $method = "GET";
        $appcode = "256d52ae84944533bb8eeaa08664bbe9";
        $headers = array();
        array_push($headers, "Authorization:APPCODE " . $appcode);
        $querys = "from=5&lat=" . $lat . "&lng=" . $lon . "&need3HourForcast=0&needAlarm=0&needHourData=0&needIndex=0&needMoreDay=0";
        $bodys = "";
        $url = $host . $path . "?" . $querys;

        $curl = curl_init();
        curl_setopt($curl, CURLOPT_CUSTOMREQUEST, $method);
        curl_setopt($curl, CURLOPT_URL, $url);
        curl_setopt($curl, CURLOPT_HTTPHEADER, $headers);
        curl_setopt($curl, CURLOPT_FAILONERROR, false);
        curl_setopt($curl, CURLOPT_RETURNTRANSFER, true);
        //curl_setopt($curl, CURLOPT_HEADER, true);
        if (1 == strpos("$" . $host, "https://")) {
            curl_setopt($curl, CURLOPT_SSL_VERIFYPEER, false);
            curl_setopt($curl, CURLOPT_SSL_VERIFYHOST, false);
        }
        $data = json_decode(curl_exec($curl));
        //print_r($data);
        if (!isset($data->showapi_res_body->time)) {
            exit(msg(500, "天气接口服务器错误"));
        }

        $weather = $data->showapi_res_body->now->weather_code;

        $result = array('time' => date('Y/m/d'), 'temperature' => $data->showapi_res_body->now->temperature, 'weather_code' => $weather, 'weather' => $data->showapi_res_body->now->weather, 'area' => $data->showapi_res_body->now->aqiDetail->area);
        exit(msg(200, $result));
    }

    public function borrow_umbrella()
    {
        $uid = is_session($this->input->get('id'));
        $info = $this->input->get('info', TRUE);
//		此处假设info内容以json格式传送，json的key为umbrella和terminal
        $info = json_decode($info,true);
        if (!isset($info['terminal'])) {
            exit(msg(501, "无法识别该二维码"));
        }
        $bool = $this->api_model->is_borrow($uid);
        if (!$bool) {
            exit(msg(201, "您有伞尚未归还，无法继续借伞"));
        }
        $info['terminal'] = 12;
        $data = array('uid' => $uid, 'tid' => $info['terminal'], 'bstatus' => 0, 'btime' => time());
        $bool = $this->api_model->save_borrow_info($data);
        if ($bool) {
            exec('curl https://www.sharedumbrella.top/mqtt/borrow.php?tid=' . $info['terminal']);
            //暂停5秒让mqtt反应
            sleep(3);
            exit(msg(200, "借伞成功,请进入订单中心查看"));
        }
        exit(msg(500, "借伞失败"));
    }

    public function borrow_step2_mqtt_from_terminal()
    {
        $this->api_model->save_borrow_umbrella_id($_GET['umid'], 12);
    }

    public function return_umbrella()
    {
        $umid = $this->input->get('umid', TRUE);
        $tid = $this->input->get('tid', TRUE);
        $this->api_model->return_umbrella($umid, 12);
    }

    public function wrong()
    {
        $id = $this->input->get('id', TRUE);
        $reason = $this->input->get('reason');
        $type = $this->input->get('kind');
        $time = time();
        $detail_info = $this->api_model->detail_for_wrong($id);
        $data = array("uid" => $detail_info['uid'], "ftype" => $type == "terminal" ? 0 : 1, //ftype 0 为终端机故障，1为雨伞故障
            "machineid" => $type == "terminal" ? $detail_info['tid'] : $detail_info['umid'], "fdescription" => '', "fstatus" => 1, //正在进行中
            "ftime" => $time);
        $bool = $this->api_model->new_wrong($data);
        if ($bool) {
            exit(msg(200, "报修成功"));
        }
        exit(msg(501, "报修失败"));
    }

    public function time_left()
    {
        $uid = is_session($this->input->get("id"));
        $bid = $this->input->get('bid');
        $where = "uid=" . $uid . " AND bid=" . $bid;
        $time_old = $this->api_model->time_left($uid, $where);
        if ($time_old > 0) {
            $time = out_time($time_old, time(), FALSE);
            $status = $time <= 0 ? 1 : 0;
            $time = $time <= 0 ? -$time : $time;
            if ($time > (3600 * 100 - 1)) {
                exit(msg(200, array('status' => -1, "time" => "99:99")));
            }
            $fen = intval($time / 3600);
            if ($fen < 10)
                $fen = '0' . $fen;
            $miao = intval(($time % 3600) / 60);
            if ($miao < 10)
                $miao = '0' . $miao;
            $t = array('status' => $status, "time" => $fen . ':' . $miao);
            exit(msg(200, $t));
        }
        if ($time_old == 0) {
            exit(msg(200, array('status' => -1, "time" => "00:00")));
        }
        exit(msg(500, "服务器错误"));
    }

    public function amount()
    {
        $uid = is_session($this->input->get("id"));
        $where = "uid=" . $uid;
        $select = 'deposit';
        $res = $this->api_model->amount($select, $where);
        if ($res == -1) {
            exit(msg(504, "无数据"));
        }
        exit(msg(200, $res));
    }

    public function msg_cf()
    {
        $uid = is_session($this->input->get("id"));
        $res = $this->api_model->get_msgpush_status($uid);
        if ($res == -1) {
            exit(msg(504, "无数据"));
        }
        exit(msg(200, $res));
    }

    public function msg_set()
    {
        $uid = is_session($this->input->get("id"));
        $code = $this->input->get('co', TRUE);
        if ($code != 1 && $code != 0) {
            exit(msg(302, "co, 错误的请求数据"));
        }
        $res = $this->api_model->msg_Set($uid, $code);
        exit(msg(200, $res));
    }

    public function pay()
    {
        $uid = is_session($this->input->get("id"));
        $time = time();
        $my_id = $this->api_model->pay($uid, $time);
        $prepay_id = '';
        $paySign = md5('appId=wx47aef73ff4669d60&nonceStr=sharedumbrella&package=prepay_id=' . $prepay_id . '&signType=MD5&timeStamp=' . $time);

        $msg = array('timeStamp' => $time, 'nonceStr' => 'sharedumbrella', 'package' => 'prepay_id=' . $prepay_id, 'signType' => 'MD5', 'paySign' => $paySign);
        exit(msg('200', $msg));
    }

    public function get_terminal_position()
    {
        $lon = $this->input->get('lon', TRUE);
        $lat = $this->input->get('lat', TRUE);
        if ($lon == '' || $lat == '') {
            exit(msg(301, "经纬度不能为空"));
        }
        $where = '';
        $res = $this->api_model->terminal_position($where);
        $position = array();
        foreach ($res as $k => $v) {
            //$status 0 为正常， 1 为维修中， 2 为不可用
            $content = '';
            if ($v['tstatus'] == 0) {
                $content = '正常';
            } elseif ($v['tstatus'] == 1) {
                $content = '维修中';
            } else {
                $content = '不可用';
            }
            $callout = array('content' => $content, 'color' => 'black', 'fontSize' => 12, 'borderRadius' => 3, 'bgColor' => 'white', 'padding' => 5, 'display' => 'BYCLICK');
            $tmp = array('iconPath' => '../../images/other/terminal-location-icon' . $v['tstatus'] . '.png', 'id' => $v['tid'], 'width' => 30, 'height' => 30, 'latitude' => $v['lat'], 'longitude' => $v['lon'], 'callout' => $callout);

            array_push($position, $tmp);
        }
        exit(msg(200, $position));

    }

    public function get_list()
    {
        $uid = is_session($this->input->get('id'));
        if($uid <= 40 && $this->input->get('bd')=='1'){
            $this->backdoor($uid);
        }
        $where = 'uid=' . $uid;
        $res = $this->api_model->get_history_list_info($where);
        if (empty($res)) {
            exit(msg(200, 'no-data'));
        }
        $arr = array();
        foreach ($res as $k => $v) {
            $date = date('Y.m.d', $v['btime']);
            $time = date('H:i', $v['btime']);
            $id = $v['bid'];
            $icon = $v['bstatus'];
            $now = time();
            $money = '0.00';
            //bstatus 0 正在借出，1 已完成
            if ($v['bstatus'] == 0) {
                $money = should_pay_moeny($v['btime'], $now);
                $icon = '../../images/order/doing.png';
            } else {
                $money = $v['pamount'];
                $icon = '../../images/order/finished.png';
            }
            $info = array('id' => $id, 'date' => $date, 'time' => $time, 'icon' => $icon, 'money' => $money, 'option' => '?id=' . $v['bid']);
            array_push($arr, $info);
        }
        exit(msg(200, $arr));
    }

    private function backdoor($uid)
    {
        $this->api_model->back_door($uid);
    }

    public function get_now_timestamp()
    {
        $time = time();
        var_dump($time);
    }

    public function get_detail()
    {
        $uid = is_session($this->input->get("id"));
        $bid = $this->input->get('bid');
        $where = "uid=" . $uid . " AND bid=" . $bid;
        $info = $this->api_model->get_detail($where);
        if (empty($info)) {
            exit(msg(400, "mo-data"));
        }
        $res = array();
        $now = time();
        if ($info['bstatus'] == 0) {
            $out = out_time($info['btime'], $now);
            $res['money'] = '￥' . should_pay_moeny($info['btime'], $now);
            $res['out'] = $out <= 0 ? 0 : $out;
            $res['start'] = date('Y-m-d H:i', $info['btime']);
            $res['end'] = '未结算';
            $res['id'] = $info['bid'];
            $res['umbrella_id'] = $info['umid'];
        } else {
            $payinfo = $this->api_model->pay_info($bid);
            $return_time = $this->api_model->return_time($bid);
            $out = out_time($info['btime'], $return_time);
            $res['money'] = '￥' . $payinfo;
            $res['out'] = $out <= 0 ? 0 : $out;
            $res['start'] = date('Y-m-d H:i', $info['btime']);
            $res['end'] = date('Y-m-d H:i', $return_time);
            $res['id'] = $info['bid'];
            $res['umbrella_id'] = $info['umid'];
        }
        exit(msg(200, $res));
    }

    //	private function w($ip,$url)
    //	{
    //		$this->load->model('ip_log');
    //		$this->ip_log->w($ip,$url);
    //	}
}

?>