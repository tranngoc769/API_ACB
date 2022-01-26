<?php

class Access_model extends CI_Model {
    public function check($username, $currentts) {
        $query = $this->db
            ->limit(1)
            ->get_where(TOKEN_TABLE, array("username" => $username))
            ->row();
        if ($query == null){
            return false;
        }
        $less = ($query->time_to_live - 30) - ( $currentts -$query->timestamp);
        $lasttp =  $less  > 0;
        if ($lasttp){
            return $query->token;
        }
        return false;
    }
    public function register($data)
    {
        return $this->db->insert(TOKEN_TABLE, $data);
    }
    public function unregister($username)
    {
        return $this->db->where("username",$username)->delete(TOKEN_TABLE);
    }

}
