<?php
namespace app\admin\controller;
use \think\Controller;
use \think\Session;
use \think\Loader;
use \think\Request;
use \think\Db;
class Scontent extends Admin
{
    public function index()
    {
		$num = 0;
		$info = false;
		$ckabq = "gdwl";//默认显示课程小节内容
		$ctable = "gw_content_".$ckabq;//默认显示表内容
		$utable = "gw_unit_".$ckabq;//默认显示表内容
		//这是默认小节名称测试用，正式还需判断课程获取当前课程的表
		$get = NULL;
		$data = NULL;
		$dep = Loader::model("Major")->where("ma_parentid=0")->select()->toArray();
		
		if(input("conf"))
		{
			$get = $_GET;
			$cid = explode("-",$get['cour']);
			$c = Loader::model("Course")->where("co_id = {$cid[0]}")->field("co_code")->select()->toArray();
			//组合表
			if(!empty($c[0]))
			{
				$ctable = "gw_content_".$c[0]['co_code'];
				$utable = "gw_unit_".$c[0]['co_code'];
			}
			if(\think\Db::query("show tables like '{$ctable}'"))
			{
				if($get['val']!=NULL)
				{
					$data = [
						"ct_title"=>$get['val'],
						"ct_unitid"=>$get['scont']
					];
				}else{
					$data = [
						"ct_unitid"=>$get['scont']
					];
				}
				$info = \think\Db::table($ctable)->alias(["{$ctable}"=>'co'])
				->join("{$utable}","co.ct_unitid = $utable.un_id")
				->field("ct_id,un_name,ct_title,ct_content,ct_files,ct_problemid")
				->where($data)
				->paginate(1,false,['query'=>request()->param()]);
			}
		}else{
			$info = \think\Db::table($ctable)->alias(["{$ctable}"=>'co'])
			->join("{$utable}","co.ct_unitid = $utable.un_id")
			->field("ct_id,un_name,ct_title,ct_content,ct_files,ct_problemid")
			->paginate(1,false,['query'=>request()->param()]);
		}
		$num = count($info);
		
		$this->assign("num",$num);
		$this->assign("info",$info);
		$this->assign("ckcbq",$ckabq);
		$this->assign("dep",$dep);
		$this->assign("page",$info->render());
		return $this->fetch("index");
	}
	public function scontadd()
	{
		$m = Loader::model("Major");
		$info = $m->where("ma_parentid = 0")->select()->toArray();
		$this->assign("info",$info);
		return $this->fetch("scontadd");
	}
	public function scontmod()
	{
		$m = Loader::model("Major");
		$c = Loader::model("Course");
		$info = $m->where("ma_parentid = 0")->select()->toArray();
		$id = input("id");//获取修改ID
		$kcbq = input("kcbq");//获取课程标签
		$url = NULL;//获取上传文件
		$sdep = NULL;//获取当前系部id
		$smaj = NULL;//获取当前专业id
		$scour = NULL;//获取当前课程id
		$sdy = NULL;//获取当前单元id
		$ssec = NULL;//获取当前小节id
		$sda = NULL;//获取当前小节内容
		$pro = NULL;//获取当前题
		if(!empty($id) && !empty($kcbq))
		{
			//获取表名
			$ctable = "gw_content_".$kcbq;
			$utable = "gw_unit_".$kcbq;
			$ptable = "gw_problem_".$kcbq;//课程题库表
			$sda = \think\Db::table($ctable)->where("ct_id = $id")->select();
			//获取上传数据
			// $url = $this->upload();
			//查询内容
			$ssec = \think\Db::table($utable)->where("un_id = {$sda[0]['ct_unitid']}")->select();
			$sdy = \think\Db::table($utable)->where("un_id = {$ssec[0]['un_parentid']}")->select();
			$scour = $c->where("co_id = {$sdy[0]['un_courseid']}")->select()->toArray();
			$smaj = $m->where("ma_id = {$scour[0]['co_majorid']}")->select()->toArray();
			$sdep = $m->where("ma_id = {$smaj[0]['ma_parentid']}")->select()->toArray();
			$pro = \think\Db::table($ptable)->select();//获取当钱题库所有题
			$pid = \think\Db::table($ptable)->where("pr_id = {$sda[0]['ct_problemid']}")->field("pr_id")->select();//获取当前题id
			
			$this->assign("pid",$pid);
			$this->assign("pro",$pro);
			$this->assign("ssec",$ssec);
			$this->assign("sdy",$sdy);
			$this->assign("scour",$scour);
			$this->assign("smaj",$smaj);
			$this->assign("sdep",$sdep);
			$this->assign("scont",$sda);
			$this->assign("kcbq",$kcbq);//传递表名
		}
		//输出到视图
		$this->assign("info",$info);
		return $this->fetch("scontmod");
	}
	//获取小节列表
	public function getsecs()
	{
		$info = NULL;
		if(!empty($_POST['units']))
		{
			//课程单元id-课程id-课程代码
			$info = explode("-",$_POST['units']);
			$table = "gw_unit_".$info[2];
			
			$res = \think\Db::table($table)->where("un_parentid = {$info[0]}")->select();
			if($res!=NULL)
			{
				echo json_encode($res);
			}else
			{
				$arr = [
					[
						"un_id"=>0
					]
				];
				echo json_encode($arr);
			}
			exit;
		}
	}
	//上传文件
	public function upload()
	{
		$file = request()->file("file");
		if($file)
		{
			$info = $file->validate(['size'=>1000000,'ext'=>'jpg,gif,png,jpeg,mp4,xls,xlsx,doc,docx,txt,pdf'])->move(ROOT_PATH . 'public' . DS . 'upload/files');
			if($info)
			{
				return $info->getSaveName();
			}else{
				return $file->getError();
			}
		}
		return NULL;
	}
	//获取上传数据
	public function setcont()
	{
		$url = NULL;
		$info = NULL;
		if(!empty($_POST))
		{
			$info = $_POST;
			$url = $this->upload();
			if($info['maj']==0 || $info['cour']==0 || $info['units']==0 || $info['sec']==0)
			{
				$this->error("带星号内容必填",url("admin/scontent/index"));
			}
			//组合表
			$tab = explode("-",$info['cour']);
			$table = "gw_content_".$tab[1];
			//判断表是否存在
			$res = \think\Db::query("show tables like '{$table}'");
			if(empty($res))
			{
				$sql = "create table ".$table."(
					ct_id int auto_increment primary key comment '内容id',
					ct_unitid int not NULL default 0 comment '单元小节id',
					ct_title varchar(128) not NULL default 0 comment '标题',
					ct_content text comment '小节内容',
					ct_files varchar(128) default 0 comment '上传文件',
					ct_problemid varchar(256) default 0 comment '题目id'
					);
				";
				\think\Db::query($sql);
			}
			$data = [
				'ct_unitid'=>$info['sec'],
				'ct_title'=>$info['tit'],
				'ct_content'=>$info['editorValue'],
				'ct_files'=>$url ? $url : 0,
				'ct_problemid'=>$info['pro']
			];
			//添加数据
			$pres = \think\Db::table($table)->insert($data);
			if($pres)
			{
				$this->success("添加成功",url("admin/scontent/index"));
			}else{
				$this->error("添加失败",url("admin/scontent/index"));
			}
		 }
	}
	public function getmods()
	{
		$url = NULL;
		$data = [
			'ct_title'=>0,
			'ct_problemid'=>0
		];
		if(!empty($_POST))
		{
			$id = $_POST['id'];
			//组合表名
			$table = "gw_content_".$_POST['kcbq'];
			$url = $this->upload();
			if(!empty($url))
			{
				//判断上传文件是否为空不为空删除以前的文件
				if(!empty($url)){
					$del = \think\Db::table($table)->where("ct_id = $id")->select();
					if(file_exists(ROOT_PATH . 'public' . DS . 'upload/files/'.$del[0]['ct_files']))
					{
						unlink(ROOT_PATH . 'public' . DS . 'upload/files/'.$del[0]['ct_files']);
					}
				}
				$data['ct_files'] = $url;
			}
			if(!empty($_POST['editorValue']))
			{
				$data['ct_content'] = $_POST['editorValue'];
			}
			$data['ct_title']=$_POST['tit'];
			$data['ct_problemid']=$_POST['pro'];
			
			if(\think\Db::table($table)->where("ct_id = {$id}")->update($data))
			{
				$this->success("修改成功",url("admin/scontent/index"));
			}
			$this->error("修改失败",url("admin/scontent/index"));
		}
	}
	//单个删除
	public function scontdel()
	{
		$del = NULL;
		$arr = NULL;
		if(!empty($_POST['del']))
		{
			$del = $_POST['del'];
			$table = "gw_content_".$del[1];
			$pic = \think\Db::table($table)->where("ct_id = {$del[0]}")->field("ct_files")->select();
			//判断文件是否存在
			if(file_exists(ROOT_PATH . 'public' . DS . 'upload/files/'.$pic[0]['ct_files']))
			{
				unlink(ROOT_PATH . 'public' . DS . 'upload/files/'.$pic[0]['ct_files']);
			}
			if(\think\Db::table($table)->delete($del[0]))
			{
				$arr = [
					"id"=>1,
					"info"=>"删除成功",
					"url"=>url("admin/scontent/index")
				];
				echo json_decode($arr);
			}else{
				$arr = [
					"id"=>0,
					"info"=>"删除失败"
				];
				echo json_decode($arr);
			}
			exit;
		}
	}
	//多个删除
	public function scontdels()
	{
		$arr = NULL;
		$dels = NULL;
		//$pic = Array();//获取删除图片路径
		$data = Array();//获取删除id
		if(!empty($_POST['dels']))
		{
			$table = "gw_content_".$_POST['dels'][1];
			$dels = explode(",",$_POST['dels'][0]);
			//得到id
			for($i=0;$i<count($dels);$i++)
			{
				if($dels[$i]!=NULL)
				{
					$data[]=$dels[$i];
				}
			}
			//获取图片
			$map['ct_id'] = ['in',$data];
			$pic = \think\Db::table($table)->where($map)->field("ct_files")->select();
			//删除图片
			for($i=0;$i<count($pic);$i++)
			{
				if(file_exists(ROOT_PATH . 'public' . DS . 'upload/files/'.$pic[$i]['ct_files']))
				{
					unlink(ROOT_PATH . 'public' . DS . 'upload/files/'.$pic[$i]['ct_files']);
				}
			}
			//删除数据
			if(\think\Db::table($table)->delete($data))
			{
				$arr = [
					"id"=>1,
					"info"=>"删除成功",
					"url"=>url("admin/scontent/index")
				];
				echo json_decode($arr);
			}else{
				$arr = [
					"id"=>0,
					"info"=>"删除失败"
				];
				echo json_decode($arr);
			}
			exit;
		}
	}
 	public function getpro()
	{
		$post = NULL;
		$table = NULL;
		if(!empty($_POST['pros']))
		{
			$post = $_POST['pros'];
			$code = explode("-",$post);
			$table = "gw_problem_".$code[1];
			if(\think\Db::query("show tables like '{$table}'"))
			{
				$res = \think\Db::table($table)->select();
				echo json_encode($res);
			}else{
				$arr = [
					[
						"pr_id"=>0
					]
				];
				echo json_encode($arr);
			}
			exit;
		}
		
	}
	
}
