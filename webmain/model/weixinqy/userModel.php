<?php
class weixinqy_userClassModel extends weixinqyModel { public function initWeixin() { $this->settable('wxqy_user'); } public function getuserlist() { $pid = $this->deptrootid; $token = $this->gettoken(); $url = ''.$this->gettourl('URL_userlist').'?access_token='.$token.'&department_id='.$pid.'&fetch_child=1'; $barr = $this->backarr; $result = c('curl')->getcurl($url); if(!$this->isempt($result)){ $arr = json_decode($result); $barr = $this->setbackarr($arr->errcode, $arr->errmsg); if($arr->errcode==0){ $list = $arr->userlist; $idss = "'0'"; foreach($list as $k=>$rs){ $idss .= ",'".$rs->userid."'"; $this->saveuserinfo($rs); } $this->delete("`userid` not in($idss)"); } } return $barr; } private function saveuserinfo($rs) { $userid = $rs->userid; $where = "`userid`='$userid'"; $reos = $this->getone($where,'userid'); if(!$reos)$where=''; $sarr = array( 'userid' => $userid, 'name' => $rs->name, 'department'=> json_encode($rs->department), 'position' => isset($rs->position) ? $rs->position : '', 'telephone' => isset($rs->telephone) ? $rs->telephone : '', 'mobile' => isset($rs->mobile) ? $rs->mobile : '', 'gender' => $rs->gender, 'status' => $rs->status, 'enable' => $rs->enable, 'isleader' => $rs->isleader, 'email' => isset($rs->email) ? $rs->email : '', 'avatar' => isset($rs->avatar) ? $rs->avatar : '', 'optdt' => $this->rock->now ); $this->record($sarr, $where); } public function anaytosys() { $barr = $this->backarr; $rows = $this->getall('id>0'); if(!$rows){ $barr['msg']='请先获取列表在同步'; return $barr; } if(m('wxqy_dept')->rows('id>0')==0){ $barr['msg']='请先获取企业微信组织结构列表后在同步'; return $barr; } $idss = '0'; $db = m('admin'); $pyobj = c('pingyin'); $pass = md5('123456'); foreach($rows as $k=>$rs){ $name = $rs['name']; $user = $rs['userid']; $where = "`user`='$user'"; $ids = (int)$db->getmou('id', $where); $uarr = array( 'name' => $name, 'user' => $user, 'ranking' => $rs['position'], 'mobile' => $rs['mobile'], 'email' => $rs['email'], 'sex' => ($rs['gender']==1) ? '男' : '女', ); if($ids==0){ $where = ''; $uarr['adddt'] = $this->rock->now; $uarr['workdate'] = $this->rock->date; $uarr['pass'] = $pass; $uarr['pingyin'] = $pyobj->get($name,1); } if(!isempt($rs['telephone']))$uarr['tel'] = $rs['telephone']; if(!isempt($rs['department'])){ $depta = explode(',', str_replace(array('[',']'), array('',''), $rs['department'])); $deptid= (int)$depta[0]; if($deptid==$this->deptrootid)$deptid = 1; $uarr['deptid'] = $deptid; } $db->record($uarr, $where); } m('admin')->updateinfo(); $barr['errcode'] = 0; $barr['msg'] = '同步成功'; return $barr; } public function getuserinfo($userid) { $token = $this->gettoken(); $url = ''.$this->gettourl('URL_userget').'?access_token='.$token.'&userid='.$userid.''; $barr = $this->backarr; $result = c('curl')->getcurl($url); if(!$this->isempt($result)){ $arr = json_decode($result); $barr = $this->setbackarr($arr->errcode, $arr->errmsg); if($arr->errcode==0){ $this->saveuserinfo($arr); } } return $barr; } public function anayface($userid, $hubo=false) { if($hubo){ $barr = $this->getuserinfo($userid); if($barr['errcode'] != 0)return $barr; } $avatar = $this->getmou('avatar', "`userid`='$userid'"); $barr = $this->backarr; if(isempt($avatar)){ $barr['msg'] = '获取失败或可能未激活企业微信'; return $barr; } $cont = c('curl')->getcurl($avatar); if(isempt($cont))return $this->backerror('curl读取不到头像文件'); $dobj = c('down'); $farr = $dobj->createimage($cont,'jpg','微信头像'); $barr['face'] = ''; $barr['msg'] = '无法获取,原因很多'; if(isset($farr['id'])){ $dbs = m('admin'); $urs = $dbs->getone("`user`='$userid'",'`id`'); if(!$urs){$barr['msg'] =''.$userid.'不存在';return $barr;} $face = $dbs->changeface($urs['id'], $farr['id']); if($face!=''){ $barr['errcode'] = 0; $barr['face'] = $face; $barr['msg'] = 'ok'; } }else{ $barr['msg'] = $dobj->gettishi($barr['msg']); } return $barr; } public function createuser($userid,$arr=array()) { $str = ''; foreach($arr as $k=>$v){ if($k=='department' || $k=='order'){ $str.=',"'.$k.'":'.$v.''; }else{ $str.=',"'.$k.'":"'.$v.'"'; } } $body = '{"userid": "'.$userid.'"'.$str.'}'; $token = $this->gettoken(); $url = ''.$this->gettourl('URL_usercreate').'?access_token='.$token.''; $barr = $this->backarr; $result = c('curl')->postcurl($url, $body); if($result!=''){ $arra = json_decode($result); $barr = $this->setbackarr($arra->errcode, $arra->errmsg); if($arra->errcode==0){ unset($arr['order']); $arr['status'] = 4; $arr['userid'] = $userid; $this->record($arr); } } return $barr; } public function updateuser($userid,$arr=array(),$ostr='', $status='1') { $str = ''; foreach($arr as $k=>$v){ if($k=='department' || $k=='order'){ $str.=',"'.$k.'":'.$v.''; }else{ $str.=',"'.$k.'":"'.$v.'"'; } } $body = '{"userid": "'.$userid.'"'.$str.''.$ostr.'}'; $token = $this->gettoken(); $url = ''.$this->gettourl('URL_userupdate').'?access_token='.$token.''; $barr = $this->backarr; $result = c('curl')->postcurl($url, $body); if($result!=''){ $arra = json_decode($result); $barr = $this->setbackarr($arra->errcode, $arra->errmsg); if($arra->errcode==0){ unset($arr['order']); $arr['userid'] = $userid; if($status=='0')$arr['status'] = '2'; $this->record($arr, "`userid`='$userid'"); } } return $barr; } public function deleteuser($userid) { $token = $this->gettoken(); $url = ''.$this->gettourl('URL_userdelete').'?access_token='.$token.'&userid='.$userid.''; $barr = $this->backarr; $result = c('curl')->getcurl($url); if(!$this->isempt($result)){ $arr = json_decode($result); $barr = $this->setbackarr($arr->errcode, $arr->errmsg); if($arr->errcode==0){ $this->delete("`userid`='$userid'"); } } return $barr; } public function deletepluser($arr=array()) { $s1 = ''; foreach($arr as $ss)$s1 .= ',"'.$ss.'"'; if($s1=='')return $this->backerror('没有可删除用户'); return $this->_deluserpl(substr($s1, 1)); } private function _deluserpl($userids) { $token = $this->gettoken(); $url = ''.$this->gettourl('URL_batchdelete').'?access_token='.$token.''; $body = '{"useridlist":['.$userids.']}'; $result = c('curl')->postcurl($url, $body); $barr = $this->backarr; if(!$this->isempt($result)){ $arr = json_decode($result); $barr = $this->setbackarr($arr->errcode, $arr->errmsg); if($arr->errcode==0 || $arr->errcode==40031){ $barr['errcode'] = 0; $this->delete('`userid` in('.$userids.')'); } } return $barr; } public function notinadmin($ids='') { $sql = "select a.`userid` from [Q]wxqy_user a left join `[Q]admin` b on a.`userid`=b.`user` where"; if($ids!=''){ $sql.= " b.id in($ids)"; }else{ $sql.= ' b.id is null'; } $rows= $this->db->getall($sql); $arr = array(); foreach($rows as $k=>$rs){ $arr[] = $rs['userid']; } return $arr; } public function delusernoinstr($ids='') { $arr = $this->notinadmin($ids); if(!$arr){ $barr = $this->backarr; $barr['msg'] = '没可删除的用户'; return $barr; } return $this->deletepluser($arr); } public function subscribe($user, $zt) { if(isempt($user))return; $where = "`userid`='$user'"; if($this->rows($where)==0)$where=''; $this->record(array( 'userid' => $user, 'status' => $zt, 'optdt' => $this->rock->now ), $where); if($zt == 1){ $urs = m('admin')->getone("`user`='$user'",'`face`,`id`'); if($urs)if(isempt($urs['face']))$this->anayface($user, true); } } private function checkEmail($inAddress) { return filter_var($inAddress, FILTER_VALIDATE_EMAIL); } public function optuserwx($id) { $rs = m('admin')->getone("`id`='$id'"); $userid = $rs['user']; $rs1 = $this->getone("`userid`='$userid'"); $arr = array( 'name' => $rs['name'], 'mobile' => $rs['mobile'], 'email' => $rs['email'], 'position' => $rs['ranking'], 'telephone' => $rs['tel'], 'gender' => ($rs['sex']=='女')?2:1 ); $check = c('check'); $maxxu = (int)$this->db->getmou('[Q]admin','max(sort)','id>0'); $order = $maxxu - (int)$rs['sort']; $sort = $order; $deptid = $rs['deptid']; if(isempt($deptid) || $deptid=='0')return $this->backerror('人员没有设置部门'); if($deptid=='1')$deptid = $this->deptrootid; $deptids = arrvalue($rs,'deptids'); if(!isempt($deptids)){ $deptid.=','.$deptids.''; $deptidsa = explode(',', $deptids); foreach($deptidsa as $_seid)$order.=','.$sort.''; } $arr['department']='['.$deptid.']'; $arr['order']='['.$order.']'; $barr['errcode']=-1; $msg = ''; if(isempt($rs['mobile']) && isempt($rs['email'])){ $msg = '手机号和邮箱不能同时为空'; } if($msg=='' && !isempt($rs['mobile']))if(!$check->iscnmobile($rs['mobile'])){ $msg ='手机号码格式不对'; } if($msg=='' && !isempt($rs['email']))if(!$check->isemail($rs['email'])){ $msg ='邮箱格式不对'; } if($msg==''){ if(!$rs1){ if($rs['status']=='0')return $this->backerror('该用户是停用不允许创建'); $barr = $this->createuser($userid, $arr); }else{ $barr = $this->updateuser($userid, $arr, ',"enable":'.$rs['status'].'', $rs['status']); } }else{ $barr['errcode']=-1; $barr['msg']=$msg; } return $barr; } public function isgeng($urs, $rs) { if(!$urs || !$rs)return 1; $gender = ($rs['sex']=='男') ? '1' : '2'; $isgc = 0; $deptid = $rs['deptid']; $deptids = arrvalue($rs,'deptids'); if(!isempt($deptids))$deptid.=','.$deptids.''; $gxls = explode(',',',职位,姓名,部门,邮箱,性别,状态,电话'); if($urs['position']!=$rs['ranking'])$isgc=1; if($urs['name']!=$rs['name'])$isgc=2; if($urs['department']!='['.$deptid.']')$isgc=3; if($urs['email']!=$rs['email'])$isgc=4; if($urs['gender']!=$gender)$isgc=5; if($urs['enable']!=$rs['status'])$isgc=6; if($urs['telephone']!=$rs['tel'])$isgc=7; return array($isgc, $gxls[$isgc]); } public function sendanayface() { $sql = "select a.`userid`,b.`face` from [Q]wxqy_user a left join `[Q]admin` b on a.`userid`=b.`user` where b.id is not null and a.`avatar` is not null"; $rows = $this->db->getall($sql); $barr = $this->backarr; $xu = 0; foreach($rows as $k=>$rs){ if(isempt($rs['face'])){ $ybbo = m('reim')->asynurl('asynrun','wxqyface', array( 'userid' => $rs['userid'] )); if(!$ybbo){ $barr['msg'] = '未开启服务器无法使用'; return $barr; } $xu++; } } $barr['errcode'] = 0; $barr['msg'] = '已发送异步处理数'.$xu.'条'; return $barr; } }