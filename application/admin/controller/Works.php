<?php
namespace app\admin\controller;
use \think\Controller;
use \think\Session;
use \think\Db;
use \think\Request;
use \think\Loader;
class Works extends Admin
{
    public function index()
    {
		$m = Loader::model("Major");
		$get = NULL;
		$data = NULL;
		$info = NULL;
		$table = "problem_gdwl";//默认显示表内容
		$kcbq = NULL;//默认课程
		$dep = $m->where("ma_parentid=0")->select()->toArray();
		if(input("conf"))
		{
			$get = $_GET;
			if(!empty($get['cour']))
			{
				$this->error("请先选择课程",url("admin/works/index"));
			}
			$code = explode("-",$get['cour']);
			$table = "problem_".$code[1];
			$kcbq = $code[1];
			if($get['val']!=NULL)
			{
				$data=[
					"pr_content"=>$get['val']
				];
				$info = \think\Db::name($table)->where($data)->paginate(10,false,["query"=>request()->param()]);
			}else{
				$info = \think\Db::name($table)->paginate(10,false,["query"=>request()->param()]);
			}
		}else
		{
			$info = \think\Db::name($table)->paginate(10,false,["query"=>request()->param()]);
			$kcbq = "gdwl";
		}
		if(!empty($info))
		{
			$this->assign("page",$info->render());
			$this->assign("info",$info);
			$num = count($info);
			$this->assign("num",$num);
		}else{
			$this->assign("page",false);
			$this->assign("info",false);
			$this->assign("num",0);
		}
		$this->assign("dep",$dep);
		$this->assign("kcbq",$kcbq);
		return $this->fetch("index");
	}
	public function proadd()
	{
		$m = Loader::model("Major");
		$info = $m->where("ma_parentid=0")->select()->toArray();
		$this->assign("info",$info);
		return $this->fetch("proadd");
	}	
	public function getmaj()
	{
		$m = Loader::model("Major");
		$post = NULL;
		$res = NULL;
		if(!empty($_POST['val']))
		{
			$post = $_POST['val'];
			$res = $m->where("ma_parentid = {$post}")->select()->toArray();
			if(!empty($res))
			{
				echo json_encode($res);
			}else
			{
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
	public function getcours()
	{
		$c = Loader::model("Course");
		$post = NULL;
		$res = NULL;
		if(!empty($_POST['val']))
		{
			$post = $_POST['val'];
			$res = $c->where("co_majorid = {$post}")->select()->toArray();
			if(!empty($res))
			{
				echo json_encode($res);
			}else
			{
				$arr = [
					[
						"co_id"=>0
					]
				];
				echo json_encode($arr);
			}
			exit;
		}
	}
	public function setinfos()
	{
		$post = NULL;
		$url = NULL;
		$table = NULL;
		$res = NULL;
		if(!empty($_POST))
		{
			$url = $this->upload();
			$post = $_POST;
			$code = explode("-",$post['cour']);
			$table = "gw_problem_".$code[1];
			$res = \think\Db::query("show tables like '{$table}'");
			if(!$res)
			{
				$sql = "create table ".$table."(
						pr_id int auto_increment primary key comment '题库id',
						pr_content varchar(256) not NULL default 0 comment '标题',
						pr_letter varchar(20) not NULL default 0 comment '选项',
						pr_problem text not NULL default 0 comment '选项内容',
						pr_answer varchar(10) not NULL default 0 comment '答案',
						pr_type tinyint not NULL default 0 comment '多选2/单选1'
					);";
					\think\Db::query($sql);
			}
			$this->savexcel($url,$table);
		}
	}
	//处理上传excel文件
	public function savexcel($file,$table)
	{
		vendor("PHPExcel.PHPExcel");//引入文件
		$objReader = NULL;//获取excel对象
		$objPHPExcel = NULL;//加载文件内容,编码utf-8 
		$file_name = NULL;//获取文件路径
		if(file_exists(ROOT_PATH . 'public' . DS . 'upload/task/'.$file))
		{
			$extension = explode(".",$file);//获取后缀名
			$file_name = ROOT_PATH . 'public' . DS . 'upload/task/'.$file;
			if ($extension =='xlsx') //判断后缀
			{
				$objReader = new \PHPExcel_Reader_Excel2007();
				$objPHPExcel = $objReader->load($file_name,$encode='utf-8');
			} else if($extension =='xls') {
				$objReader = new \PHPExcel_Reader_Excel5();
				$objPHPExcel = $objReader->load($file_name,$encode='utf-8');
			} 
			//$excel_array = $objPHPExcel->getSheet(0)->toArray();
			$excels = $objPHPExcel->getSheet(0);
			$highestRow = $sheet->getHighestRow(); // 取得总行数
            $highestColumn = $sheet->getHighestColumn(); // 取得总列数
			$j=0;
			$data = [];
			for($i=2;$i<$highestRow;$i++)
			{
				$data['pr_content'] = $objPHPExcel->getActiveSheet()->getCell("A".$i)->getValue();
				$data['pr_letter'] = $objPHPExcel->getActiveSheet()->getCell("B".$i)->getValue();
				$data['pr_problem'] = $objPHPExcel->getActiveSheet()->getCell("C".$i)->getValue();
				$data['pr_answer'] = $objPHPExcel->getActiveSheet()->getCell("D".$i)->getValue();
				$data['pr_type'] = $objPHPExcel->getActiveSheet()->getCell("E".$i)->getValue();
				//不判断题目的重复性
				// if(!(\think\Db::table($table)->-where(" pr_content = '{$data['pr_content']}'")>select()))
				// {
					//如果数据库不存在该数据时执行插入
					
				// }
			}
			if(\think\Db::table($table)->insertAll($data))
			{
				$this->success("导入成功",url("admin/works/index"));
				//导入成功后删除文件
				unlink($file_name);
			}else{
				$this->error("导入失败",url("admin/works/index"));
			}
			$this->error("找不到导入文件",url("admin/works/index"));
		}
	}
	public function upload()
	{
		$file = request()->file('file');
		if(!empty($file))
		{
			$info = $file->validate(['size'=>1000000,'ext'=>'xls,xlsx,docx'])->move(ROOT_PATH . 'public' . DS . 'upload/task');
			if($info)
			{
				return $info->getSaveName();
			}else{
				return $file->getError();
			}
		}
		return false;
	}
	public function proadds()
	{
		$m = Loader::model("Major");
		$info = $m->where("ma_parentid=0")->select()->toArray();
		$this->assign("info",$info);
		return $this->fetch("proadds");
	}
	public function addinfo()
	{
		$post = NULL;
		$table = NULL;
		$code = NULL;
		$arr = NULL;
		$res = NULL;
		if(!empty($_POST))
		{
			$post = $_POST;
			$code = explode('-',$post['cour']);
			$table = "gw_problem_".$code[1];
			$res = \think\Db::query("show tables like '{$table}'");
			if(empty($res))
			{
				$sql = "create table ".$table."(
						pr_id int auto_increment primary key comment '题库id',
						pr_content varchar(256) not NULL default 0 comment '标题',
						pr_letter varchar(20) not NULL default 0 comment '选项',
						pr_problem text not NULL comment '选项内容',
						pr_answer varchar(10) not NULL default 0 comment '答案',
						pr_type tinyint not NULL default 0 comment '多选2/单选1'
					);";
				\think\Db::query($sql);
			}
			$data = [
				"pr_content"=>$post['tit'],
				"pr_letter"=>$post['cho'],
				"pr_problem"=>$post['stem'],
				"pr_answer"=>$post['ans'],
				"pr_type"=>$post['types']
			];
			if(\think\Db::table($table)->insert($data))
			{
				$arr = [
					'id'=>1,
					'info'=>"添加成功",
					'url'=>url("admin/works/index")
				];
				echo json_encode($arr);
			}else{
				$arr = [
					'id'=>1,
					'info'=>"添加失败"
				];
				echo json_encode($arr);
			}
			exit;
		}
	}
	public function promod()
	{
		$m = Loader::model("Major");
		$c = Loader::model("Course");
		$table = NULL;
		$info = NULL;
		//获取系部信息
		$dep = NULL;
		//获取专业信息
		$maj = NULL;
		//获取课程的信息
		$cour = NULL;
		$id = input("id");
		$kcbq = input("kcbq");
		if(!empty($id) && !empty($kcbq))
		{
			$table = "problem_".$kcbq;//获取表
			//获取修改数据
			$info = \think\Db::name($table)->where("pr_id = {$id}")->select();
			$cour = $c->where("co_code = '{$kcbq}'")->select()->toArray(); 
			$maj = $m->where("ma_id = {$cour[0]['co_majorid']}")->select()->toArray(); 
			$dep = $m->where("ma_id = {$maj[0]['ma_parentid']}")->select()->toArray(); 
		}
		$this->assign("info",$info);
		$this->assign("cour",$cour);
		$this->assign("maj",$maj);
		$this->assign("dep",$dep);
		return $this->fetch("promod");
	}
	//修改数据
	public function prominfo()
	{
		$post = NULL;
		$table = NULL;
		$data = NULL;
		if(!empty($_POST))
		{
			$post = $_POST;
			$code = explode("-",$post['cour']);
			$table = "problem_".$code[1];
			$data = [
				"pr_content"=>$post['tit'],
				"pr_letter"=>$post['cho'],
				"pr_problem"=>rtrim(ltrim($post['stem'])),
				"pr_answer"=>$post['ans'],
				"pr_type"=>$post['types']
			];
			$res = \think\Db::name($table)->where("pr_id = {$post['pid']}")->update($data);
			if($res)
			{
				$arr = [
					"id"=>1,
					"info"=>"修改成功",
					"url"=>url("admin/works/index")
				];
				echo json_encode($arr);
			}else{
				$arr = [
					"id"=>0,
					"info"=>"修改失败",
				];
				echo json_encode($arr);
			}
			exit;
		}
	}
	//单个删除
	public function prodel()
	{
		$post = NULL;
		if(!empty($_POST['del']))
		{
			$post = $_POST['del'];
			$table = "problem_".$post[1];
			if(\think\Db::name($table)->delete($post[0]))
			{
				$arr = [
					'id'=>1,
					"info"=>"删除成功",
					"url"=>url("admin/works/index")
				];
				echo json_encode($arr);
			}else{
				$arr = [
					'id'=>0,
					"info"=>"删除失败"
				];
				echo json_encode($arr);
			}
			exit;
		}
	}
	//多个删除
	public function prodels()
	{
		$post = NULL;
		$dels = [];
		$table = NULL;
		if(!empty($_POST['del']))
		{
			$post = $_POST['del'];
			$ids = explode(",",$post[0]);
			for($i=0;$i<count($ids);$i++)
			{
				if($ids[$i]!=NULL)
				{
					$dels[] = $ids[$i];
				}
			}
			$table = "problem_".$post[1];
			if(\think\Db::name($table)->delete($dels))
			{
				$arr = [
					'id'=>1,
					"info"=>"删除成功",
					"url"=>url("admin/works/index")
				];
				echo json_encode($arr);
			}else{
				$arr = [
					'id'=>0,
					"info"=>"删除失败"
				];
				echo json_encode($arr);
			}
			exit;
		}
	}
}
