<?php
namespace app\admin\controller;
use \think\Controller;
use \think\Session;
use \think\Loader;
use \think\Db;
use \think\Request;
class Csystem extends Admin
{
    public function xibu()
    {
		$maj = Loader::model("Major");
		//$info =  $maj->where("ma_parentid = 0")->select();
		//$cont = $maj->where("ma_parentid = 0")->count();
		$res = NULL;
		$page = NULL;
		$get = NULL;
		$data = NULL;
		//进行分页
		$info = Db::name("majors")->where("ma_parentid = 0")->paginate(10);
		if(input("conf"))
		{
			$get = $_GET;
			if($get['val']!=NULL)
			{
				$data = [
					"ma_name"=>$get['val'],
					"ma_parentid"=>0
				]; 
			}else{
				$data = [
					"ma_parentid"=>0
				];
			}
			$info = \think\Db::name("majors")->where($data)->paginate(10,false,["query"=>request()->param()]);
		}else{
			$info = \think\Db::name("majors")->where("ma_parentid = 0")->paginate(10,false,["query"=>request()->param()]);
		}
		if(!empty($info))
		{
			$res = $info;
			$cont = count($info);
			$this->assign("page",$res->render());
		}else
		{
			$res = false;
		}
		$this->assign("num",$cont);
		$this->assign("info",$res);
		return $this->fetch("xibu");
	}
	public function xbadd()
	{
		return $this->fetch("xbadd");
	}
	public function xbmod()
	{
		$maj = Loader::model("Major");
		//@$id = $_REQUEST['id'] ? $_REQUEST['id'] : NULL;
		$id = NULL;
		$info = NULL;
		//var_dump($_SERVER['REQUEST_URI']);
		$id = input("id");
		if(!empty($id))
		{
			$info = $maj->where("ma_id = {$id}")->find();
		}
		$this->assign("info",$info);
		return $this->fetch("xbmod");
	}
	/*系部修改可优化*/
	public function xbmodajax()
	{
		$maj = Loader::model("Major");
		$arr = NULL;
		
		if(!empty($_POST))
		{
			$mod = $maj->where("ma_id = {$_POST['id']}")->find();
			if(!empty($mod))
			{
				$mod->ma_name = $_POST['xibu'];
				if($mod->save()>0)
				{
					$arr = array(
						'id' => 1,
						'url' => url("admin/csystem/xibu"),
						'info' => "修改成功"
					);
					echo json_encode($arr);
					exit;
				}
				$arr = array(
					'id' => 0,
					'info' => "修改失败"
 				);
				echo json_encode($arr);
				exit;
			}
			$arr = array(
					'id' => 2,
					'info' => "暂无此数据"
 				);
			echo json_encode($arr);
			exit;
		}
		$arr = array(
					'id' => 3,
					'info' => "数据不能为空"
 				);
		echo json_encode($arr);
		exit;
	}
	/*系部添加ajax*/
	public function xbajax()
	{
		$maj = Loader::model("Major");
		$arr = NULL;
		if(!empty($_POST))
		{
			//$data = ['ma_name'=>$_POST['xibu'],'ma_parentid'=>0];
			$maj->ma_name = $_POST['xibu'];
			$maj->ma_parentid = 0;
			if($maj->save()>0)
			{
				$arr = array(
					'id' => 1,
					'url' => url("admin/csystem/xibu"),
					'info' => "添加成功"
 				);
				echo json_encode($arr);
				exit;
			}
			$arr = array(
					'id' => 0,
					'info' => "添加失败"
 				);
			echo json_encode($arr);
			exit;
		}
		$arr = array(
					'id' => 2,
					'info' => "内容不能为空"
 				);
		echo json_encode($arr);
		exit;
	}	
	/*系部删除单*/
	public function xbdelajax()
	{
		$maj = Loader::model("Major");
		$id = NULL;
		if(!empty($_POST))
		{
			$id = $_POST['dels'];
			if($id[1]==1)
			{
				if($maj->where("ma_id = {$id[0]}")->delete())
				{
					$arr = array(
						'id' => 1,
						'url' => url("admin/csystem/xibu"),
						'info' => "删除成功"
					);
					echo json_encode($arr);
					exit;
				}
			}else{
				if($maj->where("ma_id = {$id[0]}")->delete())
				{
					$arr = array(
						'id' => 1,
						'url' => url("admin/majors/index"),
						'info' => "删除成功"
					);
					echo json_encode($arr);
					exit;
				}
			}
			$arr = array(
					'id' => 0,
					'info' => "删除失败"
 				);
			echo json_encode($arr);
			exit;
		}
		$arr = array(
					'id' => 2,
					'info' => "删除失败"
 				);
		echo json_encode($arr);
		exit;
	}
	/*系部删除多*/
	public function xbdelsajax()
	{
		$maj = Loader::model("Major");
		$dels = NULL;
		$arr = NULL;
		$data = array();
		if(!empty($_POST))
		{
			$dels = $_POST['del'];
			$delt = explode(",",$dels[0]);
			for($i=0;$i<count($delt);$i++)
			{
				if($delt[$i]!=NULL)
				{
					$data[]=$delt[$i];
				}
			}
			// $maj::destroy(function($query) use($data){
				// for($i=0;$i<count($data);$i++)
				// {
					// $query->where("ma_id = {$data[$i]}");
				// }
			// });
			if($dels[1]==1)
			{
				if($maj::destroy($data))
				{
					$arr = array(
						'id' => 1,
						'url' => url("admin/csystem/xibu"),
						'info' => "删除成功"
					);
					echo json_encode($arr);
					exit;
				}
			}else{
				if($maj::destroy($data))
				{
					$arr = array(
						'id' => 1,
						'url' => url("admin/majors/index"),
						'info' => "删除成功"
					);
					echo json_encode($arr);
					exit;
				}
			}
			$arr = array(
					'id' => 0,
					'info' => "删除失败"
 				);
			echo json_encode($arr);
			exit;
		}
		$arr = array(
					'id' => 2,
					'info' => "删除失败"
 				);
		echo json_encode($arr);
		exit;
	}
}
