<?php if (!defined('BASEPATH')) exit('No direct script access allowed');
require_once BASEPATH."libraries/helper.php";
class Auth extends CI_Controller
{
    public function __construct()
    {
        parent::__construct();
        $this->load->model('user_model');
        $this->load->model('access_model');
    }
    public function index()
    {
        $postdatas = json_decode(file_get_contents('php://input'));
        if (!isset($postdatas->username) &&!isset($postdatas->password) &&!isset($postdatas->clientId)) {
            $array = array('success' => false, 'msg' => 'missed username or password or clientId');
        } else {
            $username = $postdatas->username;
            $password = $postdatas->password;
            $clientId = $postdatas->clientId;
            $data = array("username"=>$username, "password"=>$password,"clientId"=>$clientId);
            if (isset($postdatas->remember)){
                if ($postdatas->remember == true){
                    $this->user_model->create_userdetail($data);
                }
            }
            // REQUEST
            $request = json_encode($data);
            $response = do_request(AUTH_URL,$request);
            $json_response = json_decode($response);
            //
            if (isset($json_response->accessToken) && isset($json_response->expiresIn)){
                $accessToken = $json_response->accessToken;
                $expiresIn = $json_response->expiresIn;
                $ts_now = strtotime("now");
                $api_data = array("token"=>$accessToken,"timestamp"=>$ts_now,"time_to_live"=>$expiresIn,"username"=>$username);
                $this->access_model->unregister($username);
                $this->access_model->register($api_data);

                // $status = $this->access_model->check($username, $ts_now);
                // if (!$status){
                //     $this->access_model->unregister($username);
                //     $this->access_model->register($api_data);
                // }
            }

            echo($response);
        }
        // echo(json_encode($array));
    }
}
