<?php
namespace app\admin\controller;
use \think\Controller;
use \think\Session;
use \think\Loader;
use \think\Request;
use \think\Db;
class Section extends Controller
{
    public function index()
    {
		$info = false;
		//默认展示内容
		$ckcbq = "gdwl";
		$num = 0;
		$get = NULL;
		$data = NULL;
		//组合表
		$table = "gw_unit_".$ckcbq;
		//查询显示内容
		$dep = Loader::model("Major")->where("ma_parentid = 0")->select()->toArray();
		if(\think\Db::query("show tables like '{$table}'"))
		{
			if(input("conf"))
			{
				$get = $_GET;
				if($get['val']!=NULL)
				{
					$data = [
						"un_name"=>$get['val'],
						"un_parentid"=>$get['units']
					];
				}else{
					$data = [
						"un_parentid"=>$get['units']
					];
				}
				//$sql = "select gw2.un_id,gw1.un_name,gw2.un_name as pname from {$table} as gw1 left join {$table} as gw2 on gw1.un_id=gw2.un_parentid where gw2.un_name != ''";
				$info = \think\Db::table($table)->where($data)->paginate(1,false,['query'=>request()->param()]);
			}else{
				$info = \think\Db::table($table)->where("un_parentid>0")->paginate(1,false,['query'=>request()->param()]);
			}
			$num = count($info);		
		}
		
		$this->assign("info",$info);
		$this->assign("ckcbq",$ckcbq);
		$this->assign("num",$num);
		$this->assign("dep",$dep);
		$this->assign("page",$info->render());
		return $this->fetch("index");
	}
	public function secadd()
	{
		$m = Loader::model("Major");
		$dep = $m->where("ma_parentid = 0")->select()->toArray();
		$this->assign("info",$dep);
		return $this->fetch("secadd");
	}
	public function secmod()
	{
		$id = input("id");
		$kcbq = input("kcbq");
		$c = Loader::model("Course");
		$m = Loader::model("Major");
		$dep = $m->where("ma_parentid!=0")->select()->toArray();//获取系部列表
		$sdep = NULL;//获取当前系部
		$smaj = NULL;//获取当前专业
		$scour = NULL;//获取当前课程
		$sunit = NULL;//获取当前单元
		if(!empty($id) && !empty($kcbq))
		{
			//组合表
			$table = "gw_unit_".$kcbq;
			//向上查询
			$sec = \think\Db::table($table)->where("un_id = $id")->select();
			$sunit = \think\Db::table($table)->where("un_id = {$sec[0]['un_parentid']}")->select();
			$scour = $c->where("co_id = {$sec[0]['un_courseid']}")->select()->toArray();
			$smaj = $m->where("ma_id = {$scour[0]['co_majorid']}")->select()->toArray();
			$sdep = $m->where("ma_id = {$smaj[0]['ma_parentid']}")->select()->toArray();
		}
		$this->assign("info",$dep);
		$this->assign("sdep",$sdep[0]['ma_id']);//当前系部
		$this->assign("smaj",$smaj);//当前专业
		$this->assign("scour",$scour);//当前课程
		$this->assign("sunit",$sunit);//当前单元
		$this->assign("sec",$sec);
		return $this->fetch("secmod");
	}
	//获取课程单元
	public function getuns()
	{
		$cour = NULL;
		$info = NULL;
		$c = Loader::model("Course");
		if(!empty($_POST['cours']))
		{
			$cour = explode("-",$_POST['cours']);
			//查找课程代码
			$table = "gw_unit_".$cour[1];
			$res = \think\Db::query("show tables like '{$table}'");
			if($res)
			{
				$info = \think\Db::table($table)->where("un_parentid = 0")->select();
			}else
			{
				$info = [
					[
						'un_id'=>0
					]
				];
			}
			echo json_encode($info);
			exit;
		}
	}
	//添加新数据
	public function setunits()
	{
		
		$arr = NULL;
		if(!empty($_POST['units']) && !empty($_POST['sec']))
		{
			$cour = explode("-",$_POST['cour']);
			//表
			$table = "gw_unit_".$cour[1];
			$data = [
				'un_name'=>$_POST['sec'],
				"un_parentid"=>$_POST['units'],
				"un_courseid"=>$cour[0]
			];
			$res = \think\Db::table($table)->insert($data);
			if($res)
			{
				$arr = [
					"id"=>1,
					'info'=>"添加成功",
					'url'=>url('admin/section/index')
				];
			}else{
				$arr = [
					"id"=>0,
					'info'=>"添加失败",
				];
			}
			echo json_encode($arr);
			exit;
		}
	}
	//修改
	public function setmod()
	{
		$arr = NULL;
		if(!empty($_POST))
		{
			//组合表
			$table = "gw_unit_".$_POST['kcbq'];
			$data = [
				'un_name'=>$_POST['sec']
			];
			$res = \think\Db::table($table)->where("un_id = {$_POST['secid']}")->update($data);
			if($res)
			{
				$arr = [
					'id'=>1,
					'info'=>"修改成功",
					'url'=>url("admin/section/index")
				];
			}else
			{
				$arr = [
					'id'=>0,
					'info'=>"修改失败"
				];
			}
			echo json_encode($arr);
			exit;
		}
	}
	//单个删除
	public function secdel()
	{
		$arr = NULL;
		if(!empty($_POST['del']))
		{
			$del = $_POST['del'];
			$table = "gw_unit_".$del[1];
			if(\think\Db::table($table)->delete($del[0]))
			{
				$arr = [
					'id'=>1,
					'info'=>"删除成功",
					'url'=>url("admin/section/index")
				];
			}else
			{
				$arr = [
					'id'=>0,
					'info'=>"删除失败"
				];
			}
			echo json_encode($arr);
			exit;
		}
	}
	//多删除
	public function secdels()
	{
		$arr = NULL;
		$data = Array();
		if(!empty($_POST['dels']))
		{
			$info = $_POST['dels'];
			$table = "gw_unit_".$info[1];
			$dels = explode(",",$info[0]); 
			for($i=0;$i<count($dels);$i++)
			{
				if($dels[$i]!=NULL)
				{
					$data[]=$dels[$i];
				}
			}
			if(\think\Db::table($table)->delete($data))
			{
				$arr = [
					'id'=>1,
					'info'=>"删除成功",
					'url'=>url("admin/section/index")
				];
			}else{
				$arr = [
					'id'=>0,
					'info'=>"删除失败"
				];
			}
			echo json_encode($arr);
			exit;
		}
		
	}
}
