<?php
namespace app\admin\controller;
use \think\Controller;
use \think\Session;
use \think\Loader;
use \think\Db;
use \think\Request;
class Majors extends Admin
{
	public function index()
	{
		$maj = Loader::model("Major");
		//join
		$res = NULL;
		$dep = NULL;
		$get = NULL;//分页查询获取数据
		$info = NULL;//页面内容
		$dep = $maj->where("ma_parentid = 0")->select()->toArray();
		if(input("conf"))
		{
			$get = $_GET;
			if($get['val']!=NULL)
			{
				$map = [
					"ma_parentid"=>$get['xibu'],
					"ma_name"=>$get['val']
				];
				$info = Db::name("majors")->where($map)->paginate(1,false,['query'=>request()->param()]);
			}else{
				$map = [
					"ma_parentid"=>$get['xibu']
				];
				$info = Db::name("majors")->where($map)->paginate(1,false,['query'=>request()->param()]);
			}
		}else{
			$info = Db::name("majors")->where("ma_parentid > 0")->paginate(1);
		}
		
		$num = count($info);
		if(!empty($info))
		{
			$res = $info;
			$this->assign("page",$res->render());//分页
		}else
		{
			$res = false;
		}
		if($dep == NULL)
		{
			$dep = false;
		}
		//传入系部信息
		$this->assign("dep",$dep);
		$this->assign("num",$num);
		$this->assign("info",$res);
		
		return $this->fetch("index");
	}
	public function majoradd()
	{
		$maj = Loader::model('Major');
		$info = $maj->where("ma_parentid = 0")->select();
		$res = NULL;
		if(!empty($info))
		{
			$res = $info;
		}else{
			$res = false;
		}
		$this->assign("info",$res);
		return $this->fetch("majoradd");
	}
	public function majormod()
	{
		$maj = Loader::model('Major');
		$info = $maj->where("ma_parentid = 0")->select();
		$res = NULL;
		$id = input("id");
		if(!empty($info))
		{
			$res = $info;
		}else{
			$res = false;
		}
		$majs =$maj->where("ma_id = {$id}")->find();
		$this->assign("id",$majs['ma_parentid']);
		$this->assign("info",$res);
		$this->assign("majs",$majs);
		return $this->fetch("majormod");
	}
	/*专业添加*/
	public function majaddajax()
	{
		$maj = Loader::model("Major");
		$arr = NULL;
		if(!empty($_POST) && !empty($_POST['majs']))
		{
			$maj->ma_name = $_POST['majs'];
			$maj->ma_parentid = $_POST['xibu'];
			if($maj->save())
			{
				$arr = array(
					"id"=>1,
					"url"=>url("admin/majors/index"),
					'info'=>"添加成功"
				);
				echo json_encode($arr);
				exit;
			}
			$arr = array(
				"id"=>0,
				'info'=>"添加失败"
			);
			echo json_encode($arr);
			exit;
		}
		$arr = array(
				"id"=>2,
				'info'=>"数据不能为空"
			);
		echo json_encode($arr);
		exit;
	}
	/*专业修改*/
	public function majmodajax()
	{
		$maj = Loader::model("Major");
		$arr = NULL;
		if(!empty($_POST))
		{
			$info = $maj->where("ma_id = {$_POST['id']}")->find();
			$info->ma_name = $_POST['majs'];
			$info->ma_parentid = $_POST['xibu'];
			if($info->save())
			{
				$arr = array(
					"id"=>1,
					"url"=>url("admin/majors/index"),
					'info'=>"修改成功"
				);
				echo json_encode($arr);
				exit;
			}
			$arr = array(
					"id"=>0,
					'info'=>"失败成功"
				);
			echo json_encode($arr);
			exit;
		}
		$arr = array(
					"id"=>2,
					'info'=>"数据不能为空"
				);
		echo json_encode($arr);
		exit;
	}
	public function getinfos()
	{
		$info = NULL;
		$m = Loader::model("Major");
		if(!empty($_POST['reqs']))
		{
			$info = $_POST['reqs'];
			$res = $m->where("ma_parentid = {$info}")->select()->toArray();
			if($res){
				echo json_encode($res);
			}else{
				$arr = [
					[
						"ma_id"=>0
					]
				];
				echo json_encode($arr);
			}
			exit;
		}	
	}
}
