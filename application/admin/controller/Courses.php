<?php
namespace app\admin\controller;
use \think\Controller;
use \think\Session;
use \think\Loader;
use \think\Request;
use \think\Db;
class Courses extends Admin
{
    public function index()
	{
		//默认显示
		$m = Loader::model("Major");
		$c = Loader::model("Course"); 
		$get = NULL;//获取分页数据
		$res = NULL;
		$info = NULL;//获得分页数据
		$page = NULL;//显示分页
		
		// $info = $c->join("gw_teacher t","gw_course.co_teacherid=t.te_id")
		// ->join("gw_majors m","gw_course.co_majorid=ma_id")
		// ->field("co_id,te_name,co_name,co_code,co_time,co_startime,co_endtime,co_type,co_label,co_profile,co_pic,ma_name")
		// ->select()
		// ->toArray();
		
		if(input("conf"))
		{
			$get = $_GET;
			if(!empty($get['maj']) && $get['maj'] == 0)
			{
				$this->error("请先选择系部",url("admin/courses/index"));
			}
			if($get['val']!=NULL)
			{
				$map = [
					"co_name"=>$get['val'],
					"co_majorid"=>$get['maj']
				];
				$info = Db::name("course")
				->join("gw_teacher t","gw_course.co_teacherid=t.te_id")
				->join("gw_majors m","gw_course.co_majorid=ma_id")
				->field("co_id,te_name,co_name,co_code,co_time,co_startime,co_endtime,co_type,co_label,co_profile,co_pic,ma_name")
				->where($map)->paginate(1,false,['query'=>request()->param()]);
			}else{
				$map = [
					"co_majorid"=>$get['maj']
				];
				$info = db::name("course")
				->join("gw_teacher t","gw_course.co_teacherid=t.te_id")
				->join("gw_majors m","gw_course.co_majorid=ma_id")
				->field("co_id,te_name,co_name,co_code,co_time,co_startime,co_endtime,co_type,co_label,co_profile,co_pic,ma_name")
				->where($map)->paginate(1,false,['query'=>request()->param()]);
			}
		}else{
			$info = Db::name("course")
			->join("gw_teacher t","gw_course.co_teacherid=t.te_id")
			->join("gw_majors m","gw_course.co_majorid=ma_id")
			->field("co_id,te_name,co_name,co_code,co_time,co_startime,co_endtime,co_type,co_label,co_profile,co_pic,ma_name")
			->paginate(1,false,['query'=>request()->param()]);
		}
		
		$num = count($info);
		if(!empty($info))
		{
			$res = $info;
			$this->assign("page",$info->render());
		}else{
			$res = false;
		}
		
		//获取系部选择
		$dep = $m->where("ma_parentid = 0")->select()->toArray();
		$this->assign("dep",$dep);
		$this->assign("num",$num);
		$this->assign("info",$res);
		return $this->fetch("index");
	}
	public function coursadd()
	{
		$c = Loader::model("Major");
		$res = $c->where("ma_parentid = 0")->select();
		$info = NULL;
		if(!empty($res))
		{
			$info = $res;
		}else
		{
			$info = false;
		}
		$this->assign("info",$info);
		return $this->fetch("coursadd");
	}
	public function coursmod()
	{
		$c = Loader::model("Course");
		$m = Loader::model("Major");
		$res = $m->where("ma_parentid = 0")->select() ? $m->where("ma_parentid = 0")->select() : false;
		$id = input("id");
		$info = NULL;
		if(!empty($id))
		{
			$info = $c->where("co_id = {$id}")->select()->toArray();	
		}
		$maj = $m->query("select ma_id,ma_parentid,ma_name FROM gw_majors where ma_parentid in (select ma_parentid from gw_majors where ma_id ={$info[0]['co_majorid']})");
		$this->assign("majors",$maj);
		$this->assign("dep",$res);
		$this->assign("info",$info);
		return $this->fetch("coursmod");
	}
	/*确认修改*/
	public function getmod()
	{
		$c = Loader::model("Course");
		$id = $_POST['cid'];
		$mod = $c->where("co_id = {$id}")->find();
		$url = $this->upload();//获取上传图片路径
		if($_POST['majs']==NULL || $_POST['tch'] == NULL || $_POST['maname'] == NULL || 
		$_POST['kcdm'] == NULL || $_POST['kcbq'] == NULL || $_POST['cont']==NULL)
		{
			$this->error("课程信息不能为空,请重新填写",url("admin/courses/index"));
		}else{
			//如果修改图片这需要先删除原来的图片
			if(!empty($mod['co_pic']))
			{
				if(file_exists(ROOT_PATH . 'public' . DS . 'upload/image/'.$mod['co_pic']))
				{
					unlink(ROOT_PATH . 'public' . DS . 'upload/image/'.$mod['co_pic']);
				}
			}
			
			$mod->co_name = $_POST['maname'];
			$mod->co_code = $_POST['kcdm'];
			$mod->co_time = date("Y-m-d",time());
			$mod->co_teacherid = $_POST['tch'];
			$mod->co_startime = $this->gettm($_POST['begint']);
			$mod->co_endtime = $this->gettm($_POST['endt']);
			$mod->co_type = $_POST['kclx'];
			$mod->co_label = $_POST['kcbq'];
			$mod->co_profile = ltrim(rtrim($_POST['cont']));
			if($url!=false)
			{
				$mod->co_pic = $url;
			}
			$mod->co_majorid = $_POST['majs'];
			if($mod->save())
			{
				$this->success("修改成功",url("admin/courses/index"));
			}else{
				$this->error("修改失败",url("admin/courses/index"));
			}
		}
		
	}
	public function getinfo()
	{
		$c = Loader::model("Course");
		$url = NULL;
		$url = $this->upload();
		if(!empty($_POST))
		{
			// var_dump($_POST);
			// die;
			if($_POST['majs']==0 || $_POST['tch'] == NULL || $_POST['maname'] == NULL || 
			$_POST['kcdm'] == NULL || $_POST['kcbq'] == NULL || $_POST['cont']==NULL || $url==NULL)
			{
				$this->error("课程信息不能为空,请重新填写",url("admin/courses/coursadd"));
			}else{
				$c->co_name = $_POST['maname'];
				$c->co_code = $_POST['kcdm'];
				$c->co_time = date("Y-m-d",time());
				$c->co_teacherid = $_POST['tch'];
				$c->co_startime = $this->gettm($_POST['begint']);
				$c->co_endtime = $this->gettm($_POST['endt']);
				$c->co_type = $_POST['kclx'];
				$c->co_label = $_POST['kcbq'];
				$c->co_profile = $_POST['cont'];
				if($url!=false)
				{
					$c->co_pic = $url;
				}
				$c->co_majorid = $_POST['majs'];
				if($c->save())
				{
					$this->success("课程信息录入成功",url("admin/courses/index"));
				}
			}
		}
		//var_dump($_POST);
	}
	public function upload()
	{
		$file = request()->file('file');//上传文件name
		if($file)
		{
			//echo ROOT_PATH . 'public' . DS . 'upload/image/';
			//E:\AMP\apache2.4\htdocs\ttoc\public\upload
			//还没有判断上传前文件是否已经存在
			$info = $file->move(ROOT_PATH . 'public' . DS . 'upload/image/');
			if($info)
			{
				// if(file_exists(ROOT_PATH . 'public' . DS . 'upload/image/'.$info->getSaveName())
				// {
					// unlink(ROOT_PATH . 'public' . DS . 'upload/image/'.$info->getSaveName());
				// }
				return $info->getSaveName();
			}else{
				return $this->getError();
			}
		}
		return false;
		
	}
	public function gettm($tm)
	{
		$sjc = NULL;
		if(empty($tm)){
			return 0;
		}else{
			$times = explode(' ',$tm);
			$times = explode("-",$times[0]);
			$sjc = mktime(0,0,0,$times[1],$times[2],$times[0]);
		}
		return $sjc;
	}
	public function getmaj()
	{
		$maj = Loader::model("Major");
		if(!empty($_POST))
		{
			$info = $maj->where("ma_parentid = {$_POST['parent']}")->select()->toArray();
			echo json_encode($info);
			//var_dump($info);
			exit;
		}else{
			//判断返回错误信息
		}
	}
	/*点删*/
	public function courdel()
	{
		$c = Loader::model("Course");
		$id = input("del");
		$arr = NULL;
		if($id!=NULL)
		{
			$info = $c->where("co_id = {$id}")->field("co_pic")->find()->toArray();
			if(!empty($info['co_pic']))
			{
				if(file_exists(ROOT_PATH.'public'.DS.'upload/image/'.$info['co_pic']))
				{
					unlink(ROOT_PATH.'public'.DS.'upload/image/'.$info['co_pic']);
				}
			}
			if($c->where("co_id = {$id}")->delete())
			{
				$arr = array(
					"id" => 1,
					"url" => url("admin/courses/index"),
					"info"=> "删除成功"
				);
				echo json_encode($arr);
				exit;
			}else{
				$arr = array(
					"id" => 0,
					"info"=> "删除失败"
				);
				echo json_encode($arr);
				exit;
			}
		}
	}
	/*多项删除*/
	public function courdels()
	{
		$c = Loader::model("Course");
		$dels = array();
		$arr = NULL;
		if(!empty($_POST['del']))
		{
			$del = explode(",",$_POST['del']);
			for($i=0;$i<count($del);$i++)
			{
				if($del[$i]!=NULL)
				{
					$dels[]=$del[$i];
				}
			}
			$map['co_id'] = array("in",$dels);
			$pics = $c->where($map)->field("co_pic")->select()->toArray();
			//删除图片
			for($i=0;$i<count($pics);$i++)
			{
				
				if(file_exists(ROOT_PATH.'public'.DS.'upload/image/'.$pics[$i]['co_pic']))
				{
					unlink(ROOT_PATH.'public'.DS.'upload/image/'.$pics[$i]['co_pic']);
				}
			}
			//删除数据库文件
			if($c::destroy($dels))
			{
				$arr = array(
					'id' => 1,
					'url' => url("admin/courses/index"),
					'info' => "删除成功"
				);
				echo json_encode($arr);
				exit;
			}else{
				$arr = array(
					'id' => 0,
					'info' => "删除失败"
				);
				echo json_encode($arr);
				exit;
			}
		}else{
			$arr = array(
				'id' => 0,
				'info' => "请选择删除文件"
			);
			echo json_encode($arr);
			exit;
	}
	}
}
