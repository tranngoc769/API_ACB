<?php if (!defined('BASEPATH')) exit('No direct script access allowed');
require_once BASEPATH."libraries/helper.php";
class Home extends CI_Controller
{
    public function __construct()
    {
        parent::__construct();
    }
    public function index()
    {
        echo("ACB API");
        // echo(json_encode($array));
    }
}
