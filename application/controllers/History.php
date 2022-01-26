<?php if (!defined('BASEPATH')) exit('No direct script access allowed');
require_once BASEPATH . "libraries/helper.php";
class ACB_Response
{
    public $status;
    public $response;
}
class History extends CI_Controller
{
    public function __construct()
    {
        parent::__construct();
        $this->load->model('user_model');
        $this->load->model('access_model');
        $this->load->model('email_model');
    }
    private function send_mail($message, $debug = false)
    {
        $response = array();
        $this->load->library('email');
        $this->load->library('parser');
        $this->email->clear();
        $mail_configs = $this->email_model->get_all();
        if (count($mail_configs)==0){
            echo("please config in database");
            return;
        }
        $sender = "";
        $subject = "";
        $sender_name = "";
        $receiver = "";
        for ($i=0; $i < count($mail_configs) ; $i++) { 
            # code...
            $mail = $mail_configs[$i];
            $config['protocol'] = $mail["protocol"];
            $config['smtp_host'] = $mail["smtp_host"];
            $config['smtp_port'] = $mail["smtp_port"]*1;
            $config['smtp_user'] = $mail["smtp_user"];
            $config['smtp_pass'] = $mail["smtp_pass"];
            $config['mailtype'] = $mail["mailtype"];
            $config['charset'] = $mail["charset"];
            $sender = $mail["sender"];
            $sender_name = $mail["sender_name"];
            $receiver = $mail["receiver"];
            $subject = $mail["subject"];
            $config['smtp_crypto'] = 'ssl'; 
        }
        $this->email->initialize($config);
        $this->email->from($sender,$sender_name);
        $this->email->set_newline("\r\n");
        $list = array($receiver);
        $this->email->to($list);
        // $htmlMessage = $this->parser->parse('messages/email', $data, true);
        $this->email->subject($subject);
        $this->email->message($message);
        if ($$debug){
            if ($this->email->send()) {
                echo 'Your email was sent';
            } else {
                echo($this->email->print_debugger());
            }
        }
    }
    public function test_mail(){
        $this->send_mail("TEST", true);
    }
    private function get_history($accessToken, $max_row, $username)
    {
        $acb_res = new ACB_Response();
        $acb_res->status = true;
        $headers = array(
            'Authorization: Bearer ' . $accessToken
        );
        $response = do_request(HISTORY_URL . "?maxRows=" . $max_row . "&account=" . $username, null, "GET", $headers);
        $his_response = json_decode($response);
        $acb_res->response = $response;
        if (isset($his_response->codeStatus) && isset($his_response->messageStatus)) {
            if ($his_response->codeStatus != 200 || $his_response->messageStatus != "success") {
                $acb_res->status = false;
            } else {
                $acb_res->status = true;
            }
        } else {
            $acb_res->status = false;
        }
        return $acb_res;
    }
    public function index()
    {
        $get_success = false;
        $error_array = array();
        $max_row = 20;
        if (isset($_GET['row'])) {
            if ($_GET['row'] * 1 != 0) {
                $max_row = $_GET['row'];
            }
        }
        if ($max_row > 5){
            $max_row = 5;
        }
        $max_row = 5;
        $ts_now = strtotime("now");
        $users = $this->user_model->get_all();
        for ($i = 0; $i < count($users); $i++) {
            # code...
            $user = $users[$i];
            $username = $user["username"];
            $password = $user["password"];
            $clientId = $user["clientId"];
            $status = $this->access_model->check($username, $ts_now);
            if ($status) {
                $accessToken = $status;
                $acb_res = $this->get_history($accessToken, $max_row, $username);
                if ($acb_res->status == false) {
                    $error_array[$username] = $acb_res->response;
                } else {
                    echo ($acb_res->response);
                    return;
                }
            } else {
                $data = array("username" => $username, "password" => $password, "clientId" => $clientId);
                $request = json_encode($data);
                $response = do_request(AUTH_URL, $request);
                $json_response = json_decode($response);
                if (isset($json_response->accessToken) && isset($json_response->expiresIn)) {
                    $accessToken = $json_response->accessToken;
                    $expiresIn = $json_response->expiresIn;
                    $ts_now = strtotime("now");
                    $api_data = array("token" => $accessToken, "timestamp" => $ts_now, "time_to_live" => $expiresIn, "username" => $username);
                    $this->access_model->unregister($username);
                    $this->access_model->register($api_data);
                    // Get Records
                    $acb_res = $this->get_history($accessToken, $max_row, $username);
                    if ($acb_res->status == false) {
                        $error_array[$username] = $acb_res->response;
                    } else {
                        echo ($acb_res->response);
                        return;
                    }
                } else {
                    $error_array[$username] = $response;
                }
            }
        }
        if ($get_success == false) {
            $api_res = array("status" => "failed", "data" => $error_array);
            $error_msg = json_encode($api_res);
            $this->send_mail($error_msg);
            echo ($error_msg);
            return;
        }
    }
}
