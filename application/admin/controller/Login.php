<?php
namespace app\admin\controller;
use \think\Session;
use \think\Loader;
class Login extends Admin
{
    public function index()
    {
		return $this->fetch("login");
	}
	
	public function check()
	{
		$stu = Loader::model("Student");
		if(!empty($_POST['username']) && !empty($_POST['password']) && !empty($_POST['captcha']))
		{
			if(captcha_check($_POST['captcha']))
			{
				$pass =  sha1($_POST['password']);
				$info = $stu->where("st_user = '{$_POST['username']}' and st_pass = '{$pass}'")->find();
				if($info==NULL)
				{
					$tea = Loader::model("Admin");
					$info = $tea->where("ad_user = '{$_POST['username']}' and ad_pass = '{$pass}'")->find();
					
					if($info!=NULL)
					{
						session::set('user.name',$_POST['username']);//记录名称
						session::set('user.ident',1);//设置身份1为教师2为学生
						$arr = array(
							"id"=>1,
							"url"=>url("admin/index/index"),
							"info"=>"登陆成功"
						);
						echo json_encode($arr);
						//$this->success("登陆成功",url("admin/index/index"));
						exit;
					}
					$arr = array(
							"id"=>0,
							"info"=>"登陆信息错误"
						);
					echo json_encode($arr);
					exit;
				}else{
					session::set('user.name',$_POST['username']);//记录名称
					session::set('user.ident',2);//设置身份1为教师2为学生
					//$this->success("登陆成功",url("admin/index/index"));
					$arr = array(
							"id"=>1,
							"url"=>url("admin/index/index"),
							"info"=>"登陆成功"
						);
					echo json_encode($arr);
					exit;
				}
			}else{
				$arr = array(
							"id"=>2,
							"info"=>"验证码错误"
						);
				echo json_encode($arr);
				exit;
			}
		}else
		{
			$arr = array(
						"id"=>3,
						"info"=>"数据不能为空！"
					);
			echo json_encode($arr);
			exit;
		}
	}
	public function logout()
	{
		if(session::has("user"))
		{
			session::delete("user");
		}
		$this->success("用户已退出",url("admin/login/index"));
		exit;
	}
	
}
