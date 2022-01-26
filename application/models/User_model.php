<?php
define("USER","account");
class User_model extends CI_Model {
    public function update_userdetail($username, $data)
    {
        return $this->db->where("account", $username)->update(USER, $data);
    }
    public function create_userdetail($data)
    {
        return $this->db->insert(USER, $data);
    }
    
    public function get_all()
    {
        return $this->db->get(USER)->result_array();
    }
}
