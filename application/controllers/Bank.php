<?php if (!defined('BASEPATH')) exit('No direct script access allowed');
require_once BASEPATH . "libraries/helper.php";
class ACB_Response
{
    public $status;
    public $response;
}
class Bank extends CI_Controller
{
    public function __construct()
    {
        parent::__construct();
        $this->load->model('user_model');
        $this->load->model('access_model');
        $this->load->model('email_model');
    }
    
    public function test_mail(){
        send_mail($this,"TEST", true);
    }
    private function checkName($accessToken, $account,  $bankCode, $username)
    {
        $acb_res = new ACB_Response();
        $acb_res->status = true;
        $headers = array(
            'Authorization: Bearer ' . $accessToken
        );
        $response = do_request(BANKSERVICE . $account."?bankCode=".$bankCode."&accountNumber=". $username, null, "GET", $headers);
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
        $bankCode = "";
        $account = "";
        
        if (isset($_GET['bankCode']) && isset($_GET['account'])) {
            $account = $_GET['account'];
            $bankCode = $_GET['bankCode'];
        }else{
            $api_res = array("status" => "failed", "data" => "missed bankCode or account");
            $error_msg = json_encode($api_res);
            send_mail($this,$error_msg);
            echo ($error_msg);
            return;
        }
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
                $acb_res = $this->checkName($accessToken, $account, $bankCode, $username);
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
                    $acb_res = $this->checkName($accessToken, $account, $bankCode, $username);
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
            send_mail($this,$error_msg);
            echo ($error_msg);
            return;
        }
    }
}
