<?php
function do_request($url, $postfields, $method = "POST", $headers = array(  'Content-Type: application/json'))
{
  $curl = curl_init();
  curl_setopt_array($curl, array(
    CURLOPT_URL => $url,
    CURLOPT_RETURNTRANSFER => true,
    CURLOPT_ENCODING => '',
    CURLOPT_MAXREDIRS => 10,
    CURLOPT_TIMEOUT => 0,
    CURLOPT_FOLLOWLOCATION => true,
    CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
    CURLOPT_CUSTOMREQUEST => $method,
    CURLOPT_POSTFIELDS => $postfields,
    CURLOPT_HTTPHEADER => $headers,
  ));
  $response = curl_exec($curl);
  curl_close($curl);
  return $response;
}

function send_mail($ci,$message, $debug = false)
    {
        $response = array();
        $ci->load->library('email');
        $ci->load->library('parser');
        $ci->email->clear();
        $mail_configs = $ci->email_model->get_all();
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
        $ci->email->initialize($config);
        $ci->email->from($sender,$sender_name);
        $ci->email->set_newline("\r\n");
        $list = array($receiver);
        $ci->email->to($list);
        // $htmlMessage = $ci->parser->parse('messages/email', $data, true);
        $ci->email->subject($subject);
        $ci->email->message($message);
        if ($debug){
            if ($ci->email->send()) {
                echo 'Your email was sent';
            } else {
                echo($ci->email->print_debugger());
            }
        }
    }
?>
