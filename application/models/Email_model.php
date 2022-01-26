<?php
define("USER","account");
class Email_model extends CI_Model {
    public function get_all()
    {
        return $this->db->get("config_mail")->result_array();
    }
}
