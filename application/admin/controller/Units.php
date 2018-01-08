<?php
namespace app\admin\controller;
use \think\Controller;
use \think\Session;
use \think\Loader;
class Units extends Controller
{
    public function index()
    {
		//取最新课程
		$c = Loader::model("Course");
		//排序后没有判断取表问题(表是否已经创建)
		//$kcbq = $c->where("co_id>=1")->order("co_id desc")->field("co_code")->limit(1,1)->select()->toArray();
		// $info = \think\Db::table($tname)->alias(["{$tname}"=>'u'])
		// ->join("gw_course","u.un_courseid = gw_course.co_id")
		// ->field("gw_course.co_name,gw_course.co_code,u.un_name,u.un_id")
		// ->select();
		$tname = "gw_unit_gdwl";//默认表名
		$info = false;
		$num = 0;
		$get = NULL;
		$data = NULL;
		$dep = Loader::model("Major")->where("ma_parentid = 0")->select()->toArray();
		if(\think\Db::query("show tables like '{$tname}'"))
		{
			if(input("conf"))
			{
				$get = $_GET;
				if(empty($get['cour']))
				{
					$this->error("请先选择课程",url("admin/units/index"));
				}
				if($get['val']!=NULL)
				{
					$data = [
						"un_courseid"=>$get['cour'],
						"un_name"=>$get['val'],
						"un_parentid"=>0
					];
				}else{
					$data = [
						"un_courseid"=>$get['cour'],
						"un_parentid"=>0
					];
				}
				$info = \think\Db::table($tname)->alias(["{$tname}"=>'u'])
				->join("gw_course","u.un_courseid = gw_course.co_id")
				->field("gw_course.co_name,gw_course.co_code,u.un_name,u.un_id")
				->where($data)
				->paginate(2,false,["query"=>request()->param()]);
			}else{
				$info = \think\Db::table($tname)->alias(["{$tname}"=>'u'])
				->join("gw_course","u.un_courseid = gw_course.co_id")
				->field("gw_course.co_name,gw_course.co_code,u.un_name,u.un_id")
				->where("un_parentid=0")
				->paginate(2,false,["query"=>request()->param()]);
			}
			$num = count($info);
		}
		$this->assign("ckcbq","gdwl");//默认表名
		$this->assign("info",$info);
		$this->assign("num",$num);
		$this->assign("dep",$dep);
		$this->assign("page",$info->render());
		return $this->fetch("index");
	}
	public function unitadd()
	{
		$m = Loader::model("Major");
		$maj = $m->where("ma_parentid=0")->select()->toArray();
		$this->assign("info",$maj);
		return $this->fetch("unitadd");
	}
	public function unitajax()
	{
		$m = Loader::model("Major");
		$arr = NULL;
		if(!empty($_POST))
		{
			$maj = $m->where("ma_parentid={$_POST['mid']}")->select()->toArray();
			if($maj!=NULL)
			{
				echo json_encode($maj);
				
			}else{
				$arr = [
					[
						"ma_id"=>0
					],
				];
				echo json_encode($arr);
			}
			exit;
		}
	}
	public function unitcours()
	{
		$c = Loader::model("Course");
		if(!empty($_POST))
		{
			$info = $c->where("co_majorid = {$_POST['zid']}")->field("co_id,co_name,co_code")->select()->toArray();
			if(!empty($info))
			{
				echo json_encode($info);
			}else{
				$arr = [
					[
						"co_id"=>0
					],
				];
				echo json_encode($arr);
			}	
			exit;
		}
	}
	//添加数据
	public function getunits()
	{
		$c = Loader::model("Course");
		$arr = NULL;
		$unit = NULL;
		if(!empty($_POST))
		{
			$kcjj = explode("-",$_POST['cour']);
			//组合数据表名称
			$tname = "gw_unit_".$kcjj[1];
			//判断表是否存在
			$unit = \think\Db::query("show tables like '{$tname}'");
			if(empty($unit))
			{//不存在创建表
				$tsql = "create table ".$tname."(
						un_id int auto_increment primary key comment '单元/小节id',
						un_name varchar(32) not NULL default 0 comment '单元/小节名称',
						un_parentid int default 0 comment '父ID',
						un_courseid int not NULL default 0 comment '课程id' 
					);";
				$rest = \think\Db::query($tsql);
			}
			//存在插入数据
			$data = [
				'un_name'=>$_POST['unit'],
				'un_parentid'=>0,
				'un_courseid'=>$kcjj[0]
			];
			
			$res = \think\Db::table($tname)->insert($data);
			
			if($res)
			{
				$arr = [
					'id'=>1,
					'info'=>'添加成功',
					'url'=>url("admin/units/index")
				];
				echo json_encode($arr);
			}else
			{
				$arr = [
					'id'=>1,
					'info'=>'添加失败'
				];
				echo json_encode($arr);
			}
			exit;
		}
	}
	public function unitmod()
	{
		$m = Loader::model("Major");
		$c = Loader::model("Course");
		$id = input("id");//unid;
		$kcbq = input("kcbq");
		$uname = NULL;//获取单元名
		if(!empty($id) && !empty($kcbq))
		{
			$tname = "gw_unit_".$kcbq;
			$uname = \think\Db::table($tname)->where("un_id = $id")->field("un_id,un_name")->select();
			$cinfo = $c->where("co_id = {$id}")->select()->toArray();//可优化
			$cmaj = $m->where("ma_id = {$cinfo[0]['co_majorid']}")->select()->toArray();
		}
		$maj = $m->where("ma_parentid=0")->select()->toArray();
		$this->assign("info",$maj);//系部
		$this->assign("cdep",$cmaj[0]['ma_parentid']);//系部id
		$this->assign("cmaj",$cmaj);//专业
		$this->assign("cours",$cinfo);//课程
		$this->assign("kcbq",$kcbq);//课程标签
		$this->assign("uname",$uname);//单元
		return $this->fetch("unitmod");
	}
	//确定修改数据
	public function unitcge()
	{
		$arr = NULL;
		if(!empty($_POST['kcbq']) && !empty($_POST['unit']) && !empty($_POST['uid']))
		{
			$table = "gw_unit_".$_POST['kcbq'];
			$res = \think\Db::table($table)->where("un_id = {$_POST['uid']}")->update(['un_name'=>$_POST['unit']]);
			if($res)
			{
				$arr = [
					'id'=>1,
					'info'=>"修改成功",
					'url'=>url("admin/units/index")
				];
				echo json_encode($arr);
			}else{
				$arr = [
					'id'=>0,
					'info'=>"修改失败"
				];
				echo json_encode($arr);
			}
			exit;
		}
	}
	public function unitdel()
	{
		$info = NULL;
		$arr = NULL;
		if(!empty($_POST['del']))
		{
			$info = $_POST['del'];
			$table = "gw_unit_".$info[1];
			$res = \think\Db::table($table)->delete($info[0]);
			if($res)
			{
				$arr = [
					'id'=>1,
					'info'=>"删除成功",
					'url'=>url("admin/units/index")
				];
				echo json_encode($arr);
			}else{
				$arr = [
					'id'=>0,
					'info'=>"删除失败"
				];
				echo json_encode($arr);
			}
			exit;
		}
	}
	public function unitdels()
	{
		$info = NULL;
		$darr = array();
		$arr = NULL;
		if(!empty($_POST))
		{
			$info = $_POST['dels'];
			$dels = explode(",",$info[0]);
			for($i=0;$i<count($dels);$i++)
			{
				if($dels[$i]!=NULL)
				{
					$darr[]=$dels[$i];
				}
			}
			$table = "gw_unit_".$info[1];
			if(\think\Db::table($table)->delete($darr))
			{
				$arr = [
					'id'=>1,
					'info'=>"删除成功",
					'url'=>url("admin/units/index")
				];
				echo json_encode($arr);
			}else
			{
				$arr = [
					'id'=>0,
					'info'=>"删除失败"
				];
				echo json_encode($arr);
			}
			exit;
		}
	}
}
