<?php
class adminClassAction extends Action
{
	public function loadadminAjax()
	{
		$id = (int)$this->get('id',0);
		$data = m('admin')->getone($id);
		if($data){
			$data['pass']='';
		}
		$arr['data'] = $data;
		
		$this->returnjson($arr);
	}

	public function beforeshow($table)
	{
		$fields = 'id,name,`user`,deptname,`type`,status,tel,workdate,ranking,superman,loginci,sex,sort,face';
		$s 		= '';
		$key 	= $this->post('key');
		if($key!=''){
			$s = " and (`name` like '%$key%' or `user` like '%$key%' or `ranking` like '%$key%' or `deptname` like '%$key%') ";
		}
		return array(
			'fields'=> $fields,
			'where'	=> $s
		);
	}
	
	private function checkEmail($inAddress)
	{   
		return filter_var($inAddress, FILTER_VALIDATE_EMAIL);
	}
	
	
	public function publicbeforesave($table, $cans, $id)
	{
		$user = strtolower(str_replace(' ','',$cans['user']));
		$name = str_replace(' ','',$cans['name']);
		$email= str_replace(' ','',$cans['email']);
		$mobile 	= $cans['mobile'];
		$weixinid 	= $cans['weixinid'];
		$msg  = '';	
		if(is_numeric($user))return '用户名不能是数字';
		if(preg_match("/[\x7f-\xff]/", $user))return '用户名不能有中文';
		if(!$this->checkEmail($email))return '邮箱格式有误';
		if(!isempt($mobile)){
			if(!is_numeric($mobile) || strlen($mobile)!=11)return '手机格式有误';
		}
		if(!isempt($weixinid)){
			if(!is_numeric($weixinid))return '微信号格式有误';
		}
		$db  = m($table);
		if($msg=='')if($db->rows("`user`='$user' and `id`<>'$id'")>0)$msg ='用户名['.$user.']已存在';
		if($msg=='')if($db->rows("`name`='$name' and `id`<>'$id'")>0)$msg ='姓名['.$name.']已存在';
		$rows = array();
		if($msg == ''){
			$did  = $cans['deptid'];
			$sup  = $cans['superid'];
			$rows = $db->getpath($did, $sup);
		}
		$rows['user'] = $user;
		$rows['name'] = $name;
		$rows['email'] = $email;
		$arr = array('msg'=>$msg, 'rows'=>$rows);
		return $arr;
	}
	
	public function publicaftersave($table, $cans, $id)
	{
		m($table)->record(array('superman'=>$cans['name']), "`superid`='$id'");
		if(getconfig('systype')=='demo'){
			m('weixin:user')->optuserwx($id);
		}
	}
	
	public function updatedataAjax()
	{
		$a = $this->updatess();
		echo '总'.$a[0].'条记录,更新了'.$a[1].'条';
	}
	
	public function updatess()
	{
		return m('admin')->updateinfo();
	}
	
	
	//批量导入
	public function saveadminplAjax()
	{
		$val = $this->post('val');
		if(isempt($val))backmsg('error');
		
		$arrs 	= explode("\n", $val);
		$oi 	= 0;
		$db 	= m('admin');
		$sort 	= (int)$db->getmou('max(`sort`)', '`id`>0');
		$dbs	= m('dept');
		foreach($arrs as $valss){
			$user = '';
			$name = '';
			if(!isempt($valss)){
				$a 		= $this->adtewe(explode('	', $valss),10);
				$user 	= $a[0];
				$name 	= $a[1];
			}
			if(!isempt($user) && !isempt($name)){
				if($db->rows("`user`='$user'")>0)continue;
				if($db->rows("`name`='$name'")>0)continue;
				
				$oi++;
				$arr['user'] = $user;
				$arr['name'] = $name;
				$arr['sex']  = $a[2];
				$arr['ranking']  	= $a[3];
				$arr['deptname']  	= $a[4];
				$arr['mobile']  	= $a[5];
				$arr['email']  		= $a[6];
				$arr['tel']  		= $a[7];
				$arr['pass']  		= md5('123456');
				$arr['sort']  		= $sort+$oi;
				$arr['workdate']  	= $this->date;
				$arr['adddt']  		= $this->now;
				
				$deptid 	= (int)$dbs->getmou('id', "`name`='".$arr['deptname']."'");
				if($deptid==0)$arr['deptname'] = '';
				$arr['deptid'] = $deptid;
				
				$db->insert($arr);
			}
		}
		if($oi>0)$this->updatess();
		backmsg('','成功导入'.$oi.'个用户');
	}
	
	private function adtewe($a, $len){
		for($i=0;$i<$len;$i++){
			if(!isset($a[$i]))$a[$i] = '';
		}
		return $a;
	}
}