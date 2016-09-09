<?php
class flowModel extends Model
{
	public $modenum;
	public $id;
	public $moders;
	public $modeid;
	public $modename;
	public $sericnum;
	public $rs		= array();
	public $urs		= array();
	public $mwhere;
	public $mtable;
	public $uname;
	public $uid		= 0;
	public $isflow	= 0;
	
	protected function flowinit(){}
	protected function flowchangedata(){}
	protected function flowdeletebill($sm){}
	protected function flowsubmit($na, $sm){}
	protected function flowaddlog($arr){}
	protected function flowdatalog($arr){}
	protected function flowcheckbefore($zt, $sm){}
	protected function flowcheckafter($zt, $sm){}
	protected function flowcheckfinsh($zt){}
	protected function flowgetfields($lx){}
	protected function flowgetoptmenu($opt){}
	protected function flowoptmenu($ors, $crs){}
	protected function flowisreadqx(){return false;}
	protected function flowprintrows($r){return $r;}
	
	protected $flowweixinarr	= array();
	
	public function echomsg($msg)
	{
		if(!isajax())exit($msg);
		showreturn('', $msg, 201);
		exit();
	}
	
	public function initdata($num, $id=null)
	{
		$this->modenum	= $num;
		$this->moders 	= m('flow_set')->getone("`num`='$num'");
		if(!$this->moders)$this->echomsg('not found mode['.$num.']');
		$table 			= $this->moders['table'];
		$this->modeid	= $this->moders['id'];
		$this->modename	= $this->moders['name'];
		$this->isflow	= (int)$this->moders['isflow'];
		$this->settable($table);
		$this->mtable	= $table;
		$this->viewmodel= m('view');
		$this->billmodel= m('flow_bill');
		$this->checksmodel= m('flow_checks');
		$this->flowinit();
		if($id==null)return;
		$this->loaddata($id, true);
	}
	
	public function loaddata($id, $ispd=true)
	{
		$this->id		= (int)$id;
		$this->mwhere	= "`table`='$this->mtable' and `mid`='$id'";
		$this->rs 		= $this->getone($id);
		$this->uname	= '';
		if(!$this->rs)$this->echomsg('not found record');
		$this->rs['base_name'] 		= '';
		$this->rs['base_deptname'] 	= '';
		if(isset($this->rs['uid']))$this->uid = $this->rs['uid'];
		if(!isset($this->rs['applydt']))$this->rs['applydt'] = '';
		if(!isset($this->rs['status']))$this->rs['status']	 = 1;
		if($this->uid==0 && isset($this->rs['optid']))$this->uid = $this->rs['optid'];
		$this->urs 		= $this->db->getone('[Q]admin',$this->uid,'id,name,deptid,deptname,ranking,superid,superpath,superman');
		if($this->isempt($this->rs['applydt'])&&isset($this->rs['optdt']))$this->rs['applydt']=substr($this->rs['optdt'],0,10);
		if($this->urs){
			$this->drs		= $this->db->getone('[Q]dept',"`id`='".$this->urs['deptid']."'");
			$this->uname	= $this->urs['name'];
			$this->rs['base_name']		= $this->uname;
			if($this->drs){
				$this->rs['base_deptname']	= $this->drs['name'];
			}
		}
		$this->sericnum	= '';
		$this->billrs 	= $this->db->getone('[Q]flow_bill', $this->mwhere);
		if($this->billrs){
			$this->sericnum = $this->billrs['sericnum'];
		}else{
			if($this->isflow==1)$this->savebill();
		}
		
		if($ispd)$this->isreadqx();

		$this->rssust	= $this->rs;
		$this->flowchangedata();
		
		$this->rs['base_modename']	= $this->modename;
		$this->rs['base_sericnum']	= $this->sericnum;
		$this->rs['base_summary']	= $this->rock->reparr($this->moders['summary'], $this->rs);
	}
	
	public function isreadqx()
	{
		$bo = false;
		if($this->uid==$this->adminid && $this->adminid>0)$bo=true;
		if(!$bo && $this->isflow==1){
			if($this->billrs){
				$allcheckid = $this->billrs['allcheckid'];
				if(contain(','.$allcheckid.',',','.$this->adminid.','))$bo = true;
			}
			if(!$bo){
				if(contain($this->urs['superpath'],'['.$this->adminid.']'))$bo = true;
			}
		}
		if(!$bo)$bo = $this->flowisreadqx();
		if(!$bo){
			$where 	= $this->viewmodel->viewwhere($this->moders, $this->adminid);
			$tos 	= m($this->mtable)->rows("`id`='$this->id'  $where ");
			if($tos>0)$bo=true;
		}
		if(!$bo)$this->echomsg('无权限查看模块['.$this->modenum.'.'.$this->modename.']'.$this->uname.'的数据');
	}
	
	public function iseditqx()
	{
		$bo = 0;
		if($bo==0 && $this->isflow==1){
			if($this->billrs && $this->uid == $this->adminid){
				if($this->billrs['nstatus']==0 || $this->billrs['nstatus']==2){
					$bo = 1;
				}
			}
		}
		if($bo==0){
			$where 	= $this->viewmodel->editwhere($this->moders, $this->adminid);
			$tos 	= m($this->mtable)->rows("`id`='$this->id'  $where ");
			if($tos>0)$bo=1;
		}
		return $bo;
	}
	
	public function isdeleteqx()
	{
		$bo = 0;
		if($bo==0 && $this->isflow==1){
			if($this->billrs && $this->uid == $this->adminid){
				if($this->billrs['nstatus']==0 || $this->billrs['nstatus']==2){
					$bo = 1;
				}
			}
		}
		if($bo==0){
			$where 	= $this->viewmodel->deletewhere($this->moders, $this->adminid);
			$tos 	= m($this->mtable)->rows("`id`='$this->id'  $where ");
			if($tos>0)$bo=1;
		}
		return $bo;
	}
	
	
	public function getfields($lx=0)
	{
		$fields = array();
		$farr 	= $this->db->getrows('[Q]flow_element',"`mid`='$this->modeid' and `iszb`=0 and `iszs`=1",'`fields`,`name`','sort,id');
		foreach($farr as $k=>$rs)$fields[$rs['fields']] = $rs['name'];
		$fters	= $this->flowgetfields($lx);
		if(is_array($fters))$fields = array_merge($fields, $fters);
		return $fields;
	}
	
	/**
	*	读取展示数据
	*	$lx 0pc, 1移动
	*/
	public function getdatalog($lx=0)
	{
		m('log')->addread($this->mtable, $this->id);
		$arr['modename'] = $this->modename;
		$arr['modeid']   = $this->modeid;
		$arr['modenum']  = $this->modenum;
		$arr['mid']  	 = $this->id;
		$contview 	 	 = '';
		$path 			 = ''.P.'/flow/view/vie'.$lx.'_'.$this->modenum.'.html';
		$fstr			 = m('file')->getstr($this->mtable, $this->id,1);
		$issubtabs		 = 0;
		if($fstr != ''){
			$this->rs['file_content'] 	= $fstr;
		}
		if(isset($this->rs['explain']))$this->rs['explain'] = str_replace("\n",'<br>', $this->rs['explain']);
		if(isset($this->rs['content']))$this->rs['content'] = str_replace("\n",'<br>', $this->rs['content']);
		$subd 			= $this->getsubdata(0);
		$issubtabs		= $subd['iscz'];
		
		if(file_exists($path)){
			$contview 	 = file_get_contents($path);
			$contview 	 = $this->rock->reparr($contview, $this->rs);
		}
		if($this->isempt($contview)){
			$_fields		 = array();
			if($this->isflow==1){
				$_fields['base_sericnum'] 	= '单号';
				$_fields['base_name'] 		= '申请人';
				$_fields['base_deptname'] 	= '申请人部门';
			}
			$fields			 = array_merge($_fields, $this->getfields($lx));
			if($lx==0)foreach($fields as $k=>$rs){$this->rs[''.$k.'_style'] = 'width:75%';break;}
			if($fstr!='')$fields['file_content'] 			= '相关文件';
			if($issubtabs == 1)$fields[$subd['fields']]		= $subd['name'];
			if(!isset($fields['optdt']))$fields['optdt']='操作时间';
			$contview 	= c('html')->createtable($fields, $this->rs);
			$contview 	= '<div align="center">'.$contview.'</div>';
		}
		$arr['contview'] = $contview;
		$arr['readarr']	 = m('log')->getreadarr($this->mtable, $this->id);
		$arr['logarr']	 = $this->getlog();
		$arr['isedit'] 	 = $this->iseditqx();
		$arr['isdel'] 	 = $this->isdeleteqx();
		$arr['isflow'] 	 = $this->isflow;
		$arr['flowinfor']= array();
		if($this->isflow==1)$arr['flowinfor']= $this->getflowinfor();

		$_oarr 			 = $this->flowdatalog($arr);
		if(is_array($_oarr))foreach($_oarr as $k=>$v)$arr[$k]=$v;
		return $arr;
	}
	public function getsubdata($xu=0)
	{
		$iscz			= 0;
		$tables 		= $this->moders['tables'];
		$iszb			= $xu+1;
		$fields			= 'subdata'.$xu.'';
		$subrows 		= $this->db->getrows('[Q]flow_element','`mid`='.$this->modeid.' and iszb=1 and iszs=1','`fields`,`name`','`sort`');
		if($this->db->count>0){
			$iscz		= 1;
			$headstr	= '';
			foreach($subrows as $k=>$rs)$headstr.='@'.$rs['fields'].','.$rs['name'].'';
			if(!isset($this->rs[$fields])){
				$rows    	= $this->db->getall('select * from `[Q]'.$tables.'` where mid='.$this->id.' order by sort');
			}else{
				$rows		= $this->rs[$fields];
			}
			$this->rs[$fields] 				= c('html')->createrows($rows, substr($headstr,1), '#cccccc', 'noborder');
			$this->rs[''.$fields.'_style'] 	= 'padding:0';
		}
		return array(
			'iscz' 	=> $iscz,
			'xu'	=> $xu,
			'fields'=> $fields,
			'name'	=> $this->moders['names']
		);
	}
	
	/**
	*	读取编辑数据
	*/
	public function getdataedit()
	{
		$arr['data'] 	= $this->rssust;
		$arr['table'] 	= $this->mtable;
		$arr['tables'] 	= $this->moders['tables'];
		$arr['modeid'] 	= $this->modeid;
		$arr['isedit'] 	= $this->iseditqx();
		$arr['isflow'] 	= $this->isflow;
		$arr['user'] 	= $this->urs;
		$arr['status'] 	= $this->rs['status'];
		$arr['filers'] 	= m('file')->getfile($this->mtable,$this->id);
		return $arr;
	}
	
	/*
	*	读取流程信息
	*/
	public function getflowinfor()
	{
		$ischeck = 0;
		$ischange= 0;
		$str	 = '';
		$arr 	 = $this->getflow();
		$nowcheckid = ','.$arr['nowcheckid'].',';
		if(contain($nowcheckid, ','.$this->adminid.',')){
			$ischeck = 1;
		}
		$logarr = $this->getlog();
		$nowcur = $this->nowcourse;
		if($this->rock->arrvalue($this->nextcourse,'checktype')=='change')$ischange = 1;
		$sarr['ischeck'] 		= $ischeck;
		$sarr['ischange'] 		= $ischange;
		$sarr['nowcourse'] 		= $nowcur;
		$sarr['nextcourse'] 	= $this->nextcourse;
		$sarr['nstatustext'] 	= $arr['nstatustext'];
		
		if($this->rs['status']==2)$sarr['nstatustext'].=',<font color="#AB47F7">待提交人处理</font>';
		$loglen 				= count($logarr);
		foreach($logarr as $k=>$rs){
			$rs = $logarr[$loglen-$k-1];
			if($rs['courseid']>0){
				$sty = '';
				$col = $rs['color'];
				if($str!='')$str.=' → ';
				$str.='<span style="'.$sty.'">'.$rs['actname'].'('.$rs['name'].'<font color="'.$col.'">'.$rs['statusname'].'</font>)</span>';
			}
		}
		foreach($this->flowarr as $k=>$rs){
			if($rs['ischeck']==0){
				$sty = 'color:#888888';
				if($rs['isnow']==1)$sty='font-weight:bold;color:#800000';
				if($str!='')$str.=' <font color=#888888>→</font> ';
				$str.='<span style="'.$sty.'">'.$rs['name'].'';
				if(!isempt($rs['nowcheckname']))$str.='('.$rs['nowcheckname'].')';
				$str.='</span>';
			}
		}
		$sarr['flowcoursestr'] 	= $str;
		
		$actstr	= ',通过|green,不通过|red';
		if(isset($nowcur['courseact']) ){
			$actstrt = $nowcur['courseact'];
			if(!isempt($actstrt))$actstr = ','.$actstrt;
		}
		$act 	= c('array')->strtoarray($actstr);
		foreach($act as $k=>$as1)if($k>0 && $as1[0]==$as1[1])$act[$k][1]='';
		$sarr['courseact'] 		= $act;
		$nowstatus				= $this->rs['status'];
		$sarr['nowstatus']		= $nowstatus;
		return $sarr;
	}
	
	private $getlogrows = array();
	public function getlog()
	{
		if($this->getlogrows)return $this->getlogrows;
		$rows = $this->db->getrows('[Q]flow_log',$this->mwhere, '`checkname` as `name`,`checkid`,`name` as actname,`optdt`,`explain`,`statusname`,`courseid`,`color`','`id` desc');
		$uids = '';
		$dts  = c('date');
		foreach($rows as $k=>$rs){
			$uids.=','.$rs['checkid'].'';
			$col = $rs['color'];
			if(isempt($col))$col='green';
			if(contain($rs['statusname'],'不'))$col='red';
			$rows[$k]['color'] = $col;
			$rows[$k]['optdt'] = $dts->stringdt($rs['optdt'], 'G(周w) H:i:s');
		}
		if($uids!=''){
			$rows = m('admin')->getadmininfor($rows, substr($uids, 1), 'checkid');
		}
		$this->getlogrows = $rows;
		return $rows;
	}
	
	public function addlog($arr=array(),$fileid='')
	{
		$addarr	= array(
			'table'		=> $this->mtable,
			'mid'		=> $this->id,
			'checkname'	=> $this->adminname, 
			'checkid'	=> $this->adminid, 
			'optdt'		=> $this->rock->now,
			'courseid'	=> '0',
			'status'	=> '1',
			'ip'		=> $this->rock->ip,
			'web'		=> $this->rock->web,
			'modeid'	=> $this->modeid
		);
		foreach($arr as $k=>$v)$addarr[$k]=$v;
		m('flow_log')->insert($addarr);
		$ssid = $this->db->insert_id();
		if($fileid!='')m('file')->addfile($fileid, $this->mtable, $this->id);
		$addarr['id'] = $ssid;
		$this->flowaddlog($addarr);
		return $ssid;
	}
	
	public function submit($na='', $sm='')
	{
		if($na=='')$na='提交';
		$isturn	 = 1;
		if($na=='保存')$isturn	= 0;
		$this->addlog(array(
			'name' 		=> $na,
			'explain' 	=> $sm
		));
		if($this->isflow == 1){
			$marr['isturn'] = $isturn;
			$marr['status'] = 0;
			$this->update($marr, $this->id);
			$farr = $this->getflow();
			$farr['status'] = 0;
			$this->savebill($farr);
			if($isturn == 1){
				$this->nexttodo($farr['nowcheckid'],'submit');
			}
		}
		$this->flowsubmit($na, $sm);
	}
	
	/*
	*	获取流程
	*/
	public function getflow($sbo=false)
	{
		$rows  	= $this->db->getrows('[Q]flow_course', "`setid`='$this->modeid' and `status`=1" ,'*', '`sort`,id asc');
		$this->flowarr 	= array();
		$allcheckid 	= $nowcheckid 	=  $nowcheckname  = $nstatustext = '';
		$allcheckids	= array();
		$nstatus 		= $this->rs['status'];
		$this->nowcourse	= array();
		$this->nextcourse	= array();
		$this->flowisend	= 0;
		
		$curs 	= $this->db->getrows('[Q]flow_log',"$this->mwhere and `courseid`>0",'checkid,checkname,courseid,`valid`,`status`,`statusname`,`name`','id desc');
		$cufss  =  $ztnas  = $chesarr	= array();
		foreach($curs as $k=>$rs){
			$_su  = ''.$rs['courseid'].'';
			$_su1 = ''.$rs['courseid'].'_'.$rs['checkid'].'';
			if($rs['valid']==1 && $rs['status']==1){
				if(!isset($cufss[$_su]))$cufss[$_su]=0;
				$cufss[$_su]++;
				$chesarr[$_su1] = 1;
			}
			if(!in_array($rs['checkid'], $allcheckids))$allcheckids[] = $rs['checkid'];
			if($nstatustext=='' && $rs['courseid']>0){
				$nstatustext = ''.$rs['checkname'].'处理'.$rs['statusname'].'';
				$nstatus	 = $rs['status'];
			}
			$ztnas[$rs['courseid']] = ''.$rs['checkname'].''.$rs['statusname'].'';
		}
		$nowstep = $zongsetp  = -1;
		$isend 	 = 0;
		foreach($rows as $k=>$rs){
			$checkwhere = $rs['where'];
			$checkshu 	= $rs['checkshu'];
			
			if(!$this->isempt($checkwhere)){
				$checkwhere = $this->rock->jm->base64decode($checkwhere);
				$to = $this->rows("`id`='$this->id' and $checkwhere");
				if($to==0)continue;
			}
			
			$zongsetp++;
			$uarr 		= $this->getcheckname($rs);
			$checkid	= $uarr[0];
			$checkname	= $uarr[1];
			$ischeck 	= 0;
			$checkids	= $checknames = '';
			
			$_su  		= ''.$rs['id'].'';
			$nowshu		= 0;
			if(isset($cufss[$_su]))$nowshu = $cufss[$_su];
			
			if(!$this->isempt($checkid)){
				$checkida 	= explode(',', $checkid);
				$checkidna 	= explode(',', $checkname);
				$_chid		= $_chna	= '';
				
				foreach($checkida as $k1=>$chkid){
					$_su1 = ''.$rs['id'].'_'.$chkid.'';
					if(!in_array($chkid, $allcheckids))$allcheckids[] = $chkid;
					if(!isset($chesarr[$_su1])){
						$_chid.=','.$chkid.'';
						$_chna.=','.$checkidna[$k1].'';
					}
				}
				if($_chid!='')$_chid = substr($_chid, 1);
				if($_chna!='')$_chna = substr($_chna, 1);
				
				if($_chid==''){
					$ischeck	= 1;
				}else{
					if($checkshu>0&&$nowshu>=$checkshu)$ischeck	= 1;
				}
				$checkids 	= $_chid;
				$checknames = $_chna;
			}else{
				if($checkshu>0&&$nowshu>=$checkshu)$ischeck	= 1;
			}
			
			$rs['ischeck'] 		= $ischeck;
			$rs['islast'] 		= 0;
			$rs['checkid'] 		= $checkid;
			$rs['checkname'] 	= $checkname;
			$rs['nowcheckid'] 	= $checkids;
			$rs['nowcheckname'] = $checknames;
			$rs['isnow'] 		= 0;
			
			if($ischeck==0 && $nowstep==-1){
				$rs['isnow']= 1;
				$nowstep = $zongsetp;
				$this->nowcourse = $rs;
				$nowcheckid		 = $checkids;
				$nowcheckname	 = $checknames;
			}
			if($zongsetp==$nowstep+1)$this->nextcourse = $rs;
			$this->flowarr[]= $rs;
		}
		if($zongsetp>-1)$this->flowarr[$zongsetp]['islast']=1;
		if($nowstep == -1){
			$isend = 1;
		}else{
			$nstatustext 	= '待'.$nowcheckname.'处理';
		}
		$this->flowisend 	= $isend;
		$allcheckid			= join(',', $allcheckids);
		$arrbill['allcheckid'] 		= $allcheckid;
		$arrbill['nowcheckid'] 		= $nowcheckid;
		$arrbill['nowcheckname']	= $nowcheckname;
		$arrbill['nstatustext']		= $nstatustext;
		$arrbill['nstatus']			= $nstatus;
		$arrbill['status']			= $this->rs['status'];
		if($sbo)$this->getflowsave($arrbill);
		return $arrbill;
	}
	
	public function getflowsave($sarr)
	{
		$this->billmodel->update($sarr, $this->mwhere);
	}
	
	//获取审核人
	private function getcheckname($crs)
	{
		$type	= $crs['checktype'];
		$cuid 	= $name = '';
		$courseid = $crs['id'];
		if(!$this->isempt($crs['num'])){
			$uarr	= $this->flowcheckname($crs['num']);
			if(is_array($uarr)){
				if(!$this->isempt($uarr[0]))return $uarr;
			}
		}
		
		$cheorws= $this->checksmodel->getall($this->mwhere.' and courseid='.$courseid.' and `status`=0','checkid,checkname');
		if($cheorws){
			foreach($cheorws as $k=>$rs){
				$cuid.=','.$rs['checkid'].'';
				$name.=','.$rs['checkname'].'';
			}
			if($cuid != ''){
				$cuid = substr($cuid, 1);
				$name = substr($name, 1);
				return array($cuid, $name);
			}
		}
		
		if($type=='super'){
			$cuid = $this->urs['superid'];
			$name = $this->urs['superman'];
		}
		if($type=='dept' || $type=='super'){
			if($this->isempt($cuid)){
				$cuid = $this->drs['headid'];
				$name = $this->drs['headman'];
			}
		}
		if($type=='apply'){
			$cuid = $this->urs['id'];
			$name = $this->urs['name'];
		}
		if($type=='opt'){
			$cuid = $this->rs['optid'];
			$name = $this->rs['optname'];
		}
		if($type=='user'){
			$cuid = $crs['checktypeid'];
			$name = $crs['checktypename'];
		}
		if($type=='rank'){
			$rank = $crs['checktypename'];
			if(!$this->isempt($rank)){
				$rnurs	= $this->db->getrows('[Q]admin',"`status`=1 and `ranking`='$rank'",'id,name','sort');
				foreach($rnurs as $k=>$rns){
					$cuid.=','.$rns['id'].'';
					$name.=','.$rns['name'].'';
				}
				if($cuid != ''){
					$cuid = substr($cuid, 1);
					$name = substr($name, 1);
				}
			}
		}
		$cuid	= $this->rock->repempt($cuid);
		$name	= $this->rock->repempt($name);
		return array($cuid, $name);
	}
	
	/**
		创建单号
	*/
	public function createnum()
	{
		$num = $this->moders['sericnum'];
		if($num=='无'||$this->isempt($num))$num='TM-Ymd-';
		$apdt 	= str_replace('-','',$this->rs['applydt']);
		$num	= str_replace('Ymd',$apdt,$num);
		return $this->db->sericnum($num,'[Q]flow_bill');
	}
	public function savebill($oarr=array())
	{
		$dbs = $this->billmodel;
		$whes= $this->mwhere;
		$biid= (int)$dbs->getmou('id', $whes);
		$arr = array(
			'table' => $this->mtable,
			'mid' 	=> $this->id,
			'optdt' => $this->rock->now,
			'optname' 	=> $this->adminname,
			'optid' 	=> $this->adminid,
			'modeid'  	=> $this->modeid,
			'isdel'		=> '0',
			'nstatus'	=> $this->rs['status'],
			'applydt'	=> $this->rs['applydt'],
			'modename'  => $this->modename
		);
		foreach($oarr as $k=>$v)$arr[$k]=$v;
		if($biid==0){
			$arr['uid'] 	= $this->uid;
			$arr['sericnum']= $this->createnum();
			$whes			= '';
			$this->sericnum	= $arr['sericnum'];
		}
		$dbs->record($arr, $whes);
		return $arr;
	}
	
	public function nexttodo($nuid, $type, $sm='', $act='')
	{
		$cont	= '';
		$gname	= '流程待办';
		if($type=='submit' || $type=='next'){
			$cont = '您有['.$this->adminname.']的['.$this->modename.',单号:'.$this->sericnum.']需要处理';
		}
		//退回
		if($type == 'nothrough'){
			$cont = '您提交['.$this->modename.',单号:'.$this->sericnum.']'.$this->adminname.'处理['.$act.']，原因:['.$sm.']';
			$gname= '流程申请';
		}
		if($type == 'finish'){
			$cont = '您提交的['.$this->modename.',单号:'.$this->sericnum.']已全部处理完成';
		}
		if($cont!='')$this->push($nuid, $gname, $cont);
	}
	
	private function addcheckname($courseid, $uid, $uname)
	{
		$zyarr = array(
			'table' 	=> $this->mtable,
			'mid' 		=> $this->id,
			'modeid' 	=> $this->modeid,
			'courseid' 	=> $courseid,
			'optid' 	=> $this->adminid,
			'optname' 	=> $this->adminname,
			'optdt' 	=> $this->rock->now,
			'status' 	=> 0
		);
		$this->checksmodel->delete($this->mwhere.' and `checkid`='.$uid.' and `courseid`='.$courseid.'');
		$zyarr['checkid'] 	= $uid;
		$zyarr['checkname'] = $uname;
		$this->checksmodel->insert($zyarr);
	}
	
	/**
	*	处理
	*/
	public function check($zt, $sm='')
	{
		if($this->rs['status']==1)$this->echomsg('流程已处理完成,无需操作');
		$arr 	 	= $this->getflow();
		$flowinfor 	= $this->getflowinfor();
		if($flowinfor['ischeck']==0){
			$this->echomsg('当前是['.$arr['nowcheckname'].']处理');
		}
		$nowcourse	= $this->nowcourse;
		$nextcourse	= $this->nextcourse;
		$zynameid	= $this->rock->post('zynameid');
		$zyname		= $this->rock->post('zyname');
		$nextname	= $this->rock->post('nextname');
		$nextnameid	= $this->rock->post('nextnameid');
		$iszhuanyi	= $ischangenext = 0;
		if($zt==1 && $this->rock->arrvalue($nextcourse,'checktype')=='change'){
			if($nextnameid=='')$this->echomsg('请选择下一步处理人');
			$ischangenext = 1;
		}
		if($zynameid!='' && $zt==1){
			if($zynameid==$this->adminid)$this->echomsg('不能转给自己');
			if($sm!='')$sm.=',';
			$sm.='转给：'.$zyname.'';
			$iszhuanyi 		 = 1;
		}
		$barr 		= $this->flowcheckbefore($zt, $sm);
		$msg 		= '';
		if(isset($barr['msg']))$msg = $barr['msg'];
		if(is_string($barr))$msg = $barr;
		if(!isempt($msg))$this->echomsg($msg);
		
		$courseact 	= $flowinfor['courseact'];
		$act 		= $courseact[$zt];
		$courseid	= $nowcourse['id'];
		
		$this->checksmodel->delete($this->mwhere.' and `checkid`='.$this->adminid.' and `courseid`='.$courseid.'');
		if($iszhuanyi == 1){
			$this->addcheckname($courseid, $zynameid, $zyname);
			$nowcourse['id'] = 0;
		}
		if($ischangenext==1){
			$_nesta = explode(',', $nextnameid);
			$_nestb = explode(',', $nextname);
			foreach($_nesta as $_i=>$_nes)$this->addcheckname($nextcourse['id'], $_nesta[$_i], $_nestb[$_i]);
		}
		$this->addlog(array(
			'courseid' 	=> $nowcourse['id'],
			'name' 		=> $nowcourse['name'],
			'status'	=> $zt,
			'statusname'=> $act[0],
			'color'		=> $act[1],
			'explain'	=> $sm
		));
		
		$uparr		= array();
		$bsarr 	 	= $this->getflow();
		if($zt==1){
			$nextcheckid = $bsarr['nowcheckid'];
			$uparr['status'] 	= 0;
			$bsarr['status'] 	= 0;
			$this->nexttodo($nextcheckid, 'next', $sm, $act[0]);
		}else if($zt==2){
			$bsarr['status'] 	= $zt;
			$uparr['status'] 	= $zt;
			$this->nexttodo($this->uid, 'nothrough', $sm, $act[0]);
		}
		$this->flowcheckafter($zt, $sm);
		
		$bsarr['nstatus'] = $zt;
		$bsarr['checksm'] = $sm;
		
		if(!$this->nowcourse){//没有当前步骤就是结束完成了
			$uparr['status'] = $zt;
			$bsarr['status'] = $zt;
			$this->nexttodo($this->uid, 'finish', $sm);
			$this->flowcheckfinsh($zt);
		}
		
		if($uparr){
			$this->update($uparr, $this->id);
			foreach($uparr as $k=>$v)$this->rs[$k]=$v;
		}
		$this->getflowsave($bsarr);
		return '处理成功';
	}
	
	public function push($receid, $gname='', $cont, $title='', $wkal=0)
	{
		if($this->isempt($receid) && $wkal==1)$receid='all';
		if($this->isempt($receid))return false;
		if($gname=='')$gname = $this->modename;
		$reim	= m('reim');
		$url 	= ''.URL.'task.php?a=p&num='.$this->modenum.'&mid='.$this->id.'';
		$wxurl 	= ''.URL.'task.php?a=x&num='.$this->modenum.'&mid='.$this->id.'';
		$slx	= 0;
		$pctx	= $this->moders['pctx'];
		$mctx	= $this->moders['mctx'];
		$wxtx	= $this->moders['wxtx'];
		if($pctx==0 && $mctx==1)$slx=2;
		if($pctx==1 && $mctx==0)$slx=1;
		if($pctx==0 && $mctx==0)$slx=3;
		$cont	= $this->rock->reparr($cont, $this->rs);
		if(contain($receid,'u') || contain($receid, 'd'))$receid = m('admin')->gjoin($receid);
		$reim->pushagent($receid, $gname, $cont, $title, $url, $slx);
		if($wxtx==1){
			if($title=='')$title = $this->modename;
			$wxarra  = $this->flowweixinarr;
			$wxarr	 = array(
				'title' 		=> $title,
				'description' 	=> $cont,
				'url' 			=> $wxurl
			);
			foreach($wxarra as $k=>$v)$wxarr[$k]=$v;
			m('weixin:index')->sendnews($receid, ''.$gname.',0', $wxarr);
			$this->flowweixinarr=array();
		}
	}
	
	public function deletebill($sm='')
	{
		$is = $this->isdeleteqx();
		if($is==0)return '无权删除';
		m('flow_log')->delete($this->mwhere);
		m('reads')->delete($this->mwhere);
		m('file')->delfiles($this->mtable, $this->id);
		$tables 	= $this->moders['tables'];
		if(!isempt($tables)){
			$arrse = explode(',', $tables);
			foreach($arrse as $arrses)m($arrses)->delete('mid='.$this->id.'');
		}
		$this->billmodel->delete($this->mwhere);
		$this->delete($this->id);
		$this->flowdeletebill($sm);
		return 'ok';
	}
	
	
	/*
	*	获取操作菜单
	*/
	public function getoptmenu($flx=0)
	{
		$rows 	= $this->db->getrows('[Q]flow_menu',"`setid`='$this->modeid' and `status`=1",'id,wherestr,name,statuscolor,statusvalue,num,islog,issm,type','`sort`');
		$arr 	= array();
		foreach($rows as $k=>$rs){
			$wherestr 	= $rs['wherestr'];
			$bo 		= false;
			if(isempt($wherestr)){
				$bo = true;
			}else{
				$ewet	= m('where')->getstrwhere($this->rock->jm->base64decode($wherestr));
				$tos 	= $this->rows("`id`='$this->id' and $ewet");
				if($tos>0)$bo = true;
			}
			$rs['lx']	  = $rs['type'];
			$rs['optnum'] = $rs['num'];
			if(!isempt($rs['num'])){
				$glx = $this->flowgetoptmenu($rs['num']);
				if(is_bool($glx))$bo = $glx;
			}
			$rs['optmenuid'] = $rs['id'];
			if(!isempt($rs['statuscolor']))$rs['color']  = $rs['statuscolor'];
			unset($rs['id']);unset($rs['num']);unset($rs['wherestr']);unset($rs['type']);unset($rs['statuscolor']);
			if($bo)$arr[] = $rs;
		}
		
		if($this->isdeleteqx()==1){
			$arr[] = array('name'=>'删除','color'=>'red','optnum'=>'del','issm'=>1,'islog'=>0,'statusvalue'=>9,'lx'=>'9','optmenuid'=>-9);
		}
		
		if($this->isflow==1){
			$chearr = $this->getflowinfor();
			if($chearr['ischeck']==1){
				$arr[] = array('name'=>'<b>去处理单据...</b>','color'=>'#1abc9c','lx'=>996);
				if(1==2)foreach($chearr['courseact'] as $zv=>$dz){
					if($zv>0){
						$assar =  array('name'=>$dz[0],'color'=>$dz[1],'optnum'=>'check','issm'=>1,'islog'=>0,'statusvalue'=>$zv,'lx'=>'10','optmenuid'=>-10);
						if($zv==1)$assar['issm'] = 0;
						$arr[] = $assar;
					}
				}
			}
		}
		return $arr;
	}
	
	/**
	*	操作菜单操作
	*/
	public function optmenu($czid, $zt, $sm='')
	{
		$msg 	 = '';
		$cname 	 = $this->rock->post('changename');
		$cnameid = $this->rock->post('changenameid');
		$cdate   = $this->rock->post('changedate');
		if($czid==-9){
			$msg = $this->deletebill($sm);
		}else if($czid==-10){
			$msg 	 = $this->check($zt, $sm);
			if(contain($msg,'成功'))$msg = 'ok';
		}else{
			$ors 	 = m('flow_menu')->getone("`id`='$czid' and `setid`='$this->modeid' and `status`=1");
			if(!$ors)return '菜单不存在';
			$name	 = str_replace('.', '', $ors['name']);
			$actname = $ors['actname'];if(isempt($actname))$actname=$name;
			if($ors['islog']==1){
				if(!isempt($cname)){
					if(!isempt($sm))$sm.=',';
					$sm.=''.$name.':'.$cname.'';
				}
				$this->addlog(array(
					'explain' 	=> $sm,
					'name'		=> $actname,
					'statusname'=> $ors['statusname'],
					'status'	=> $ors['statusvalue'],
					'color'		=> $ors['statuscolor']
				));
			}
			if($ors['type']==4 && !isempt($ors['fields'])){
				$fielsa = explode(',', $ors['fields']);
				$uarrs  = array();
				foreach($fielsa as $fielsas){
					$fsdiwe = 'fields_'.$fielsas.'';
					if(isset($_REQUEST[$fsdiwe]))$uarrs[$fielsas]=$this->rock->post($fsdiwe);
				}
				if($uarrs)$this->update($uarrs, $this->id);
			}
			$this->flowoptmenu($ors, array(
				'cname' 	=> $cname,
				'sm'    	=> $sm,
				'cnameid' 	=> $cnameid,
				'cdate' 	=> $cdate
			));
		}
		if($msg=='')$msg='ok';
		return $msg;
	}
	
	
	
	
	
	
	
	/**
	*	打印导出
	*/
	public function printexecl($event)
	{
		$arr['moders'] = $this->moders;
		$arr['fields'] = $this->getfields();
		$cell = 1;
		foreach($arr['fields'] as $k=>$v)$cell++;
		$arr['cell']	= $cell;
		
		$where 			= '1=1';
		$str1		 	= $this->moders['where'];
		if(!isempt($str1)){
			$str1 = $this->rock->covexec($str1);
			$where = $str1;
		}
		
		$vwhere 		= $this->viewmodel->viewwhere($this->moders, $this->adminid);
		$rows 			= $this->getrows(''.$where.' '.$vwhere.'', '*', 'id desc', 100);
		$arr['rows']	= $this->flowprintrows($rows);
		$arr['count']	= $this->db->count;
		return $arr;
	}
}