<?php
namespace app\admin\controller;
use \think\Controller;
use \think\Session;

class Index extends Controller
{
    public function index()
    {
		// echo Session::get("user.name");
		// echo Session::get("user.ident");
		// die;
		// $included_files = get_included_files();
		// foreach ($included_files as $filename) {
		  // echo "$filename\n";
		// }
		// die;
		return $this->fetch("index");
	}
	public function welcome()
	{
		return $this->fetch("welcome");
	}
}
