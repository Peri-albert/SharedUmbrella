<?php
defined('BASEPATH') OR exit('No direct script access allowed');

class Api_model extends CI_Model
{
    public function __construct()
    {
        parent::__construct();
        $this->load->database();
    }

    public function log_session($openid, $sess, $sess_k)
    {
        $this->db->select('uid');
        $this->db->where('openid', $openid);
        $res = $this->db->get('user')->row_array();
        if (empty($res)) {
            $uid = $this->create_account($openid);
            $this->save_session($sess, $sess_k, $openid);
            exit(msg(200, $sess));
        }
    }

    public function get_session($openid)
    {
        $this->db->select('sess,time');
        $this->db->where('openid', $openid);
        $res = $this->db->get('session')->row_array();
        if (empty($res)) {
            exit(msg(401, 'no info'));
        }
        return $res;
    }

    public function save_session($sess, $sess_k, $openid)
    {
        $data = array('openid' => $openid, 'session_key' => $sess_k, 'sess' => $sess, 'time' => time());
        $this->db->where('openid', $openid);
        $res = $this->db->get('session')->row_array();
        if (empty($res)) {
            $this->db->insert('session', $data);
        } else {
            $this->db->where('openid', $openid);
            $this->db->update('session', $data);
        }
    }

    private function create_account($openid)
    {
        $data = array('openid' => $openid, 'msgpush' => 0, 'ustatus' => 0, 'deposit' => 0, 'ucreatetime' => time());
        $this->db->insert("user", $data);
        return $this->db->insert_id();
    }

    public function is_borrow($uid)
    {
        $this->db->where('uid', $uid);
        $this->db->where('bstatus', 0);
        $res = $this->db->get('borrow')->row_array();
        if (empty($res)) {
            return TRUE;
        }
        return FALSE;
    }

    public function save_borrow_info($data = array())
    {
        $this->db->insert('borrow', $data);
        $bid = $this->db->insert_id();
        return $this->save_payrecode($bid);
    }

    public function save_borrow_umbrella_id($id, $tid)
    {
        $this->db->where('bstatus', 0);
        $this->db->where('tid', $tid);
        $this->db->update('borrow', array('umid' => $id));
    }

    public function save_payrecode($bid)
    {
        $data = array('bid' => $bid, 'pamount' => 0.00, 'pstatus' => 1, 'ptime' => time());
        return $this->db->insert('payrecode', $data);
    }

    public function return_umbrella($umid, $tid)
    {
        $this->db->select('bid,btime');
        $this->db->where('umid', $umid);
        $this->db->where('bstatus', 0);
        $res = $this->db->get('borrow')->row_array();
        $bid = $res['bid'];
        $start = $res['btime'];
        $end = time();

        $this->db->where('umid', $umid);
        $this->db->where('bstatus', 0);
        $this->db->update('borrow', array('bstatus' => 1));

        $pamount = should_pay_moeny($start, $end);
        $update = array('pstatus' => 0, 'pamount' => $pamount);
        $this->db->where('bid', $bid);
        $this->db->update('payrecode', $update);

        $return_data = array('bid' => $bid, 'tid' => $tid, 'returnstatus' => 0, 'returntime' => $end,);
        $this->db->insert('return', $return_data);
        exit('success');
    }

    public function detail_for_wrong($id)
    {
        $this->db->where('bid', $id);
        $detail = $this->db->get('borrow')->row_array();
        if (empty($detail)) {
            exit(msg(404, "找不到订单记录"));
        }
        return $detail;
    }

    public function new_wrong($data = array())
    {
        if (empty($data)) {
            return FALSE;
        }
        return $this->db->insert("fault", $data);
    }

    public function time_left($uid = '', $where = '')
    {
        if ($uid == '' || $where == '') {
            return 0;
        }
        $this->db->select('bstatus,btime');
        $this->db->where($where);
        $row = $this->db->get('borrow')->row_array();
        if (isset($row['bstatus'])) {
            if ($row['bstatus'] != 0) {
                return 0;
            }
        }
        if (isset($row['btime'])) {
            return $row['btime'];
        }
        return -1;
    }

    public function amount($select = '', $where = '')
    {
        if ($select == '' || $where == '') {
            exit(msg(500, "服务器错误"));
        }
        $this->db->select($select);
        $this->db->where($where);
        $res = $this->db->get('user')->row_array();
        if (!empty($res)) {
            return $res['deposit'];
        }
        return -1;
    }

    public function get_msgpush_status($uid = '')
    {
        $this->db->select('msgpush');
        $this->db->where('uid', $uid);
        $res = $this->db->get('user')->row_array();
        if (!empty($res)) {
            return $res['msgpush'];
        }
        return -1;
    }

    public function msg_set($uid, $code)
    {
        $this->db->where("uid", $uid);
        return $this->db->update("user", array("msgpush" => $code));
    }

    public function pay($uid, $time)
    {
        $data = array('uid' => $uid, 'pamount' => 0, 'pstatus' => 1, 'pdescription' => '', 'ptime' => $time);
        $this->db->insert('recharge', $data);
        return $this->db->insert_id();
    }

    public function terminal_position($where)
    {
        if ($where != '') {
            $this->db->where($where);
        }
        $res = $this->db->get('terminal')->result_array();
        return $res;
    }

    public function get_history_list_info($where = '')
    {
        if ($where != '') {
            $this->db->where($where);
        }
        $this->db->join('payrecode', 'payrecode.bid=borrow.bid');
        $this->db->order_by('btime', 'DESC');
        $res = $this->db->get('borrow')->result_array();
        return $res;
    }

    public function get_detail($where)
    {
        $this->db->select('bid,umid,btime,bstatus');
        $this->db->where($where);
        $res = $this->db->get('borrow')->row_array();
        return $res;
    }

    public function pay_info($id)
    {
        $this->db->select('pamount');
        $this->db->where('bid', $id);
        $info = $this->db->get('payrecode')->row_array();
        return $info['pamount'];
    }

    public function return_time($id)
    {
        $this->db->select('returntime');
        $this->db->where('bid', $id);
        $info = $this->db->get('return')->row_array();
        return $info['returntime'];
    }

    public function back_door($uid)
    {
        $tid = 12;
        $this->db->select('bid,btime');
        $this->db->where('uid', $uid);
        $this->db->where('bstatus', 0);
        $res = $this->db->get('borrow')->row_array();
        $bid = $res['bid'];
        $start = $res['btime'];
        $end = time();

        $this->db->where('uid', $uid);
        $this->db->where('bstatus', 0);
        $this->db->update('borrow', array('bstatus' => 1));

        $pamount = should_pay_moeny($start, $end);
        $update = array('pstatus' => 0, 'pamount' => $pamount);
        $this->db->where('bid', $bid);
        $this->db->update('payrecode', $update);

        $return_data = array('bid' => $bid, 'tid' => $tid, 'returnstatus' => 0, 'returntime' => $end,);
        $this->db->insert('return', $return_data);
    }
}

?>