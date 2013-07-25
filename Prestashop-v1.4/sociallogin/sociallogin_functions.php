<?php
if ( !defined( '_PS_VERSION_' ) ){
exit;
}
class LrUser{
	function __construct(){
		global $cookie;
		$module= new sociallogin();
		include_once("LoginRadiusSDK.php");
		$secret = trim(Configuration::get('API_SECRET'));
		$lr_obj=new LoginRadius();
		$userprofile=$lr_obj->loginradius_get_data($secret);
		if($lr_obj->IsAuthenticated == true && !$cookie->isLogged()) {
			$lrdata = loginradius_mapping_profile_data($userprofile);
			$social_id_exist="SELECT * FROM ".pSQL(_DB_PREFIX_.'sociallogin')." as sl INNER JOIN ".pSQL(_DB_PREFIX_.'customer')." as c WHERE sl.provider_id='".pSQL($lrdata['id'])."' and c.id_customer=sl.id_customer  LIMIT 0,1";
			$dbObj=Db::getInstance()->ExecuteS($social_id_exist);
			$td_user="";
			$user_id_exist=(!empty($dbObj[0]['id_customer'])?$dbObj[0]['id_customer']:"");
			if($user_id_exist>=1){
				$active_user=(!empty($dbObj['0']['active'])? $dbObj['0']['active'] :"");
				$this->loginradius_verified_user_login($user_id_exist,$lrdata,$td_user);
			}
			elseif($user_id_exist<1){
				if(!empty($lrdata['email'])){
					$user_email_exist = Db::getInstance()->ExecuteS('SELECT * FROM '.pSQL(_DB_PREFIX_.'customer').' as c WHERE c.email="'.$lrdata['email'].'" LIMIT 0,1');
					$user_id=(!empty($user_email_exist['0']['id_customer'])?$user_email_exist['0']['id_customer']:"");
					$active_user=(!empty($user_email_exist['0']['active'])? $user_email_exist['0']['active'] :"");
					if($user_id>=1) {
						$td_user="yes";
						if($this->deletedUser($user_email_exist)){
							$msg= "<p style ='color:red;'>".$module->all_messages('Authentication failed.')."</p>";
							popup_verify($msg);
							return;
						}
						if(Configuration::get('ACC_MAP')==0){
							$tbl=pSQL(_DB_PREFIX_.'sociallogin');
							$query= "INSERT into $tbl (`id_customer`,`provider_id`,`Provider_name`,`verified`,`rand`) values ('".$user_id."','".$lrdata['id']."' , '".$lrdata['provider']."','1','') ";
							Db::getInstance()->Execute($query);
						}
						$this->loginradius_verified_user_login($user_id,$lrdata,$td_user);
					}
				}
				$lrdata['send_verification_email']='no';
				if(Configuration::get('user_require_field')=="1") {
					if(empty($lrdata['email']))
						$lrdata['send_verification_email']='yes';
					if(Configuration::get('EMAIL_REQ')=="1" and empty($lrdata['email'])){
						$lrdata['email']= email_rand($lrdata);	
						$lrdata['send_verification_email']='no';				
					}
					storeInCookie($lrdata);
					popUpWindow('',$lrdata);
					return;
				}
				if(Configuration::get('EMAIL_REQ')=="0" and empty($lrdata['email'])) {
					$lrdata['send_verification_email']='yes';
					storeInCookie($lrdata);
					popUpWindow('',$lrdata);
					return;
				}
				elseif(Configuration::get('EMAIL_REQ')=="1" and empty($lrdata['email'])){
					$lrdata['email']= email_rand($lrdata);					
				}
				storeAndLogin($lrdata);
			}
			elseif($this->deletedUser($dbObj)){
				$msg= "<p style ='color:red;'><b>".$module->all_messages('Authentication failed.')."</b></p>";
				popup_verify($msg);
				return;
			}
			if($active_user==0){
				$msg= "<p style ='color:red;'><b>".$module->all_messages('User has been disbled or blocked.')."</b></p>";
				popup_verify($msg);
				return;
			}
		}
	}
	public static function linking($arrdata,$userprofile) {	
		$module= new sociallogin();
		global $cookie;
		$cookie->lrmessage ='';
		if(!empty($userprofile)) {
			$tbl=pSQL(_DB_PREFIX_.'sociallogin');
			$getdata = Db::getInstance()->ExecuteS('SELECT * FROM '.pSQL(_DB_PREFIX_.'customer').' as c WHERE c.email='."'$arrdata->email'".' LIMIT 0,1');
			$num=(!empty($getdata['0']['id_customer'])? $getdata['0']['id_customer']:"");
			$sql="SELECT COUNT(*) as num from $tbl where `id_customer`='".$num."' and `Provider_name`='".$userprofile->Provider."'";
			$row = Db::getInstance()->getRow($sql);
			if($row['num']==0) {
				$check_user_id = Db::getInstance()->ExecuteS('SELECT c.id_customer FROM '.pSQL(_DB_PREFIX_.'customer').' AS c INNER JOIN '.$tbl.' AS sl ON sl.id_customer=c.id_customer WHERE sl.provider_id= "'. $userprofile->ID .'"');
				if(empty($check_user_id['0']['id_customer'])) {
					Db::getInstance()->Execute("DELETE FROM ".$tbl."  WHERE provider_id='". $userprofile->ID ."'");
				}
				$lr_id = Db::getInstance()->ExecuteS("SELECT provider_id FROM ".$tbl."  WHERE provider_id= '" . $userprofile->ID . "'");
				if(!empty($lr_id['0']['provider_id'])) {
				$cookie->lrmessage= $module->all_messages('Account cannot be mapped as it already exists in database');
				}else {
				$query= "INSERT into $tbl (`id_customer`,`provider_id`,`Provider_name`,`verified`,`rand`) values ('$num','".$userprofile->ID."' , '".$userprofile->Provider."','1','') ";
				Db::getInstance()->Execute($query);
				$cookie->lrmessage= $module->all_messages('Your account is successfully mapped');
				}
			}
			else {
				$cookie->lrmessage= $module->all_messages('Account cannot be mapped as it already exists in database');
			}
		}
		$loc=$arrdata->currentquerystring;
		$cookie->currentquerystring='';
		Tools::redirectLink(urldecode($loc));
	}
	function loginradius_verified_user_login($user_id,$lrdata,$td_user=""){ 
		$module= new sociallogin();
		$social_id = $lrdata['id'];
		if($this->deletedUser($user_id)){
			$msg= "<p style ='color:red;'>".$module->all_messages('Authentication failed.')."</p>";
			popup_verify($msg);
			return;
		}
		if($this->verifiedUser($user_id,$social_id,$td_user)) {
			if(Configuration::get('update_user_profile') == 0) 
				$this->update_user_profile_data($user_id,$lrdata);
			$this->loginradius_login_user($user_id,$social_id);
			return;
		}
		else {
			$msg= $module->all_messages('Your Confirmation link Has Been Sent To Your Email Address. Please verify your email by clicking on confirmation link.');
			popup_verify($msg, $social_id);
			return;
		}
	}
	function deletedUser($user_id){
		$dbObj = Db::getInstance()->ExecuteS('SELECT * FROM '.pSQL(_DB_PREFIX_.'customer').' as c WHERE c.id_customer='.$user_id.' LIMIT 0,1');
		$deleted=$dbObj['0']['deleted'];
		if($deleted==1){
			return true;
		}
		return false;
	}
	function verifiedUser($num,$pid,$td_user){
		$dbObj = Db::getInstance()->ExecuteS('SELECT * FROM '.pSQL(_DB_PREFIX_.'sociallogin').' as c WHERE c.id_customer='." '$num'".' AND c.provider_id='." '$pid'".' LIMIT 0,1');
		$verified=$dbObj['0']['verified'];
		$rand=$dbObj['0']['rand'];
		if($verified==1 || $td_user=="yes"){
			return true;
		}
			return false;
	}
	function update_user_profile_data($user_id, $lrdata){
		$date_upd=date("Y-m-d H:i:s",time());
		$str="";
		if(!empty($lrdata['fname'])){
			$str.="firstname='".$lrdata['fname']."',";
		}
		if(!empty($lrdata['lname'])) {
			$str.="lastname='".$lrdata['lname']."',";
		}
		if(!empty($lrdata['gender'])){
			$gender=((!empty($lrdata['gender']) and (strpos($lrdata['gender'], "f") !== false || (trim($lrdata['gender']) == "F"))) ? 2 : 1);
			$str.="id_gender='".$gender."',";
		}
		if(!empty($lrdata['dob'])) {
			$dobArr = explode("/",$lrdata['dob']);
			$dob = $dobArr[2]."-".$dobArr[0]."-".$dobArr[1];
			$date_of_birth = (!empty($dob) && Validate::isBirthDate($dob) ? $dob : '');
			$str.="birthday='".$date_of_birth."',";
		} 
		Db::getInstance()->Execute("UPDATE "._DB_PREFIX_."customer SET ".$str." date_upd='$date_upd' WHERE 	id_customer	= $user_id");
		extraFields($lrdata,$user_id,$lrdata['fname'],$lrdata['lname'],'yes');
	}
	function loginradius_login_user($user_id,$social_id){
		$dbObj = Db::getInstance()->ExecuteS('SELECT * FROM '.pSQL(_DB_PREFIX_.'customer').' as c WHERE c.id_customer='." '$user_id' ".' LIMIT 0,1');
		$arr=array();
		$arr['id']=$dbObj['0']['id_customer'];
		$arr['fname']=$dbObj['0']['firstname'];
		$arr['lname']=$dbObj['0']['lastname'];
		$arr['email']=$dbObj['0']['email'];
		$arr['pass']=$dbObj['0']['passwd'];  
		$arr['loginradius_id']=$social_id;      
		loginRedirect($arr);
	}
}
function loginradius_interface_script() {
	$loginradius_apikey = trim(Configuration::get('API_KEY'));
	$http = ((isset($_SERVER['HTTPS']) && $_SERVER['HTTPS']!='Off' && !empty($_SERVER['HTTPS'])) ? "https://" : "http://");
	$loc=(isset($_SERVER['REQUEST_URI']) ? urlencode($http.$_SERVER["HTTP_HOST"].$_SERVER['REQUEST_URI']): urlencode($http.$_SERVER["HTTP_HOST"].$_SERVER['PHP_SELF'].'?'.$_SERVER['QUERY_STRING']));
	$interfacebackgroundcolor=Configuration::get('social_login_background_color');
	$interfacebackgroundcolor = (!empty($interfacebackgroundcolor) ? trim($interfacebackgroundcolor) : "");
	$interfacecolumn = Configuration::get('social_login_icon_column');
	$interfacecolumn = (!empty($interfacecolumn) && is_numeric($interfacecolumn)? trim($interfacecolumn) : 0);
	return $loginradius_interface_script ='<script src="//hub.loginradius.com/include/js/LoginRadius.js" ></script> <script type="text/javascript"> 
	function loginradius_interface() { $ui = LoginRadius_SocialLogin.lr_login_settings;$ui.interfacesize = "small";$ui.lrinterfacebackground="' . $interfacebackgroundcolor . '";$ui.noofcolumns=' . $interfacecolumn . ';$ui.apikey = "'.$loginradius_apikey.'";$ui.callback="'.$loc.'"; $ui.lrinterfacecontainer ="interfacecontainerdiv"; LoginRadius_SocialLogin.init(options); }var options={}; options.login=true; LoginRadius_SocialLogin.util.ready(loginradius_interface); </script>';
}

function loginradius_horizontal_share_script() {
	$share_script='<script type="text/javascript">var islrsharing = true; var islrsocialcounter = true;var hybridsharing = true;</script><script type="text/javascript" src="//share.loginradius.com/Content/js/LoginRadius.js" id="lrsharescript"></script>';
	$horizontal_theme = Configuration::get('chooseshare')? Configuration::get('chooseshare'):"0";
	if($horizontal_theme == 8 || $horizontal_theme == 9 ) {
		$counter_list = unserialize(Configuration::get('socialshare_show_counter_list'));
		if(empty($counter_list)) {
			$counter_list = array('Pinterest Pin it','Facebook Like','Google+ Share','Twitter Tweet','Hybridshare');
		}
		$providers = implode('","', $counter_list);
		$interface='simple';
		if($horizontal_theme == '8') {
			$type ='vertical';
		}
		else {
			$type ='horizontal';
		}
		$share_script .= '<script type="text/javascript">LoginRadius.util.ready(function () { $SC.Providers.Selected = ["' . $providers . '"]; $S = $SC.Interface.' . $interface . '; $S.isHorizontal = true; $S.countertype = \'' . $type . '\'; $S.show("lrcounter_simplebox"); });</script>';	
	}
	else {
		$rearrange_settings = unserialize(Configuration::get('rearrange_settings'));
		if(empty($rearrange_settings)) {
			$rearrange_settings = array('facebook', 'googleplus', 'twitter', 'linkedin', 'pinterest');
		}
		$providers = implode('","', $rearrange_settings);
		if($horizontal_theme == 2 || $horizontal_theme == 3) 
			$interface='simpleimage';
		else 
			$interface='horizontal';
		if($horizontal_theme == 1 || $horizontal_theme == 3) 
			$size='16';
		else
			$size='32';
		$loginradius_apikey = trim(Configuration::get('API_KEY'));
		$sharecounttype = (!empty($loginradius_apikey) ? ('$u.apikey="'.$loginradius_apikey.'";$u.sharecounttype='."'url'".';') : '$u.sharecounttype='."'url'".';'); 
		$share_script .= '<script type="text/javascript">LoginRadius.util.ready(function () { $i = $SS.Interface.' . $interface . '; $SS.Providers.Top = ["' . $providers . '"]; $u = LoginRadius.user_settings; ' . $sharecounttype . ' $i.size = ' . $size . ';$i.show("lrsharecontainer"); });</script>'; 
	}
	return $share_script;
}

function loginradius_vertical_share_script() {
	$share_script='<script type="text/javascript">var islrsharing = true; var islrsocialcounter = true;var hybridsharing = true;</script><script type="text/javascript" src="//share.loginradius.com/Content/js/LoginRadius.js" id="lrsharescript"></script>';
	$vertical_theme = Configuration::get('chooseverticalshare')? Configuration::get('chooseverticalshare'):"6";
	if($vertical_theme == 6 || $vertical_theme == 7) {
		$counter_list = unserialize(Configuration::get('socialshare_counter_list'));
		if(empty($counter_list)) {
			$counter_list = array('Pinterest Pin it','Facebook Like','Google+ Share','Twitter Tweet','Hybridshare');
		}
		$providers = implode('","', $counter_list);
		if($vertical_theme == 6 ) 
			$type = 'vertical';
		else 
			$type = 'horizontal';
		$share_script .= '<script type="text/javascript">LoginRadius.util.ready(function () { $SC.Providers.Selected = ["' . $providers . '"]; $S = $SC.Interface.simple; $S.isHorizontal = false; $S.countertype = \'' . $type . '\';';
		$choosesharepos = Configuration::get('choosesharepos');
		if($choosesharepos == 0 ) {
			$position1 = 'top';
			$position2 = 'left';
		}
		else if($choosesharepos == 1 ) {
			$position1 = 'top';
			$position2 = 'right';
		}
		else if($choosesharepos == 2 ) {
			$position1 = 'bottom';
			$position2 = 'left';
		}
		else {
			$position1 = 'bottom';
			$position2 = 'right';
		}
		$offset=Configuration::get('verticalsharetopoffset');
		if (isset($offset) && trim($offset) != "" && is_numeric($offset)) {
			$share_script .= '$S.top = \'' . trim($offset) . 'px\'; $S.' . $position2 . ' = \'0px\';$S.show("lrcounter_verticalsimplebox"); });</script>';
		}
		else {
			$share_script .='$S.' . $position1 . ' = \'0px\'; $S.' . $position2 . ' = \'0px\';$S.show("lrcounter_verticalsimplebox"); });</script>';
		}
	}
	else {
		$vertical_rearrange_settings = unserialize(Configuration::get('vertical_rearrange_settings'));
		if(empty($vertical_rearrange_settings)) {
			$vertical_rearrange_settings = array('facebook', 'googleplus', 'twitter', 'linkedin', 'pinterest');
		}
		$providers = implode('","', $vertical_rearrange_settings);
		$interface='Simplefloat';
		if($vertical_theme == 4 ) 
			$size='32';
		else
			$size='16';
		$loginradius_apikey = trim(Configuration::get('API_KEY'));
		$sharecounttype = (!empty($loginradius_apikey) ? ('$u.apikey="'.$loginradius_apikey.'";$u.sharecounttype='."'url'".';') : '$u.sharecounttype='."'url'".';'); 	
		$share_script .= '</script> <script type="text/javascript">LoginRadius.util.ready(function () { $i = $SS.Interface.' . $interface . '; $SS.Providers.Top = ["' . $providers . '"]; $u = LoginRadius.user_settings; ' . $sharecounttype . ' $i.size = ' . $size . ';';
		$choosesharepos = Configuration::get('choosesharepos');
		if($choosesharepos == 0 ) {
			$position1 = 'top';
			$position2 = 'left';
		}
		else if($choosesharepos == 1 ) {
			$position1 = 'top';
			$position2 = 'right';
		}
		else if($choosesharepos == 2 ) {
			$position1 = 'bottom';
			$position2 = 'left';
		}
		else {
			$position1 = 'bottom';
			$position2 = 'right';
		}
		$offset=Configuration::get('verticalsharetopoffset');
		if (isset($offset) && trim($offset) != "" && is_numeric($offset)) {
			$share_script .= '$i.top = \'' . trim($offset) . 'px\'; $i.' . $position2 . ' = \'0px\';$i.show("lrshareverticalcontainer"); });</script>';
		}
		else {
			$share_script .= '$i.' . $position1 . ' = \'0px\'; $i.' . $position2 . ' = \'0px\';$i.show("lrshareverticalcontainer"); });</script>';
		}	
	}
	return $share_script;
}
/*
* Redirection after login.
*/
function redirectURL(){
	$redirect='';
	$loc=Configuration::get('LoginRadius_redirect');
	if($loc=="profile"){
		$redirect="my-account.php";
	}
	elseif($loc=="url"){
		$custom_url=Configuration::get('redirecturl');
		$redirect = !empty($custom_url)? $custom_url : "my-account.php";
	}
	else {
		$fullurl=$_SERVER['HTTP_REFERER'];
		if(strpos($fullurl,"&back=")){
			$urldata=explode("&back=",$fullurl);
			$url= urldecode($urldata[1]);
			$redirect= $url; 
		}
		elseif(strpos($fullurl,"callback=")){
			$urldata=explode("callback=",$fullurl);
			$url= urldecode($urldata[1]);
			$redirect= $url;
		}
		if(empty($redirect)) {
			$http = ((isset($_SERVER['HTTPS']) && $_SERVER['HTTPS']!='Off' && !empty($_SERVER['HTTPS'])) ? "https://" : "http://");
			$redirect= urldecode($http.$_SERVER["HTTP_HOST"] . $_SERVER["REQUEST_URI"]); 
		}
	}
	return $redirect;
}

function login_radius_resend_email_verification($social_id){
	$module= new sociallogin();
	$getdata = Db::getInstance()->ExecuteS("SELECT * from "._DB_PREFIX_."customer AS c INNER JOIN "._DB_PREFIX_."sociallogin AS sl ON sl.id_customer=c.id_customer  WHERE sl.provider_id='$social_id'");
	if($getdata['0']['verified'] == 1) {
		$msg= $module->all_messages('Email has been already verified. Now you can login using Social Login.');
		popup_verify($msg);
	}
	else {
		$to=$getdata['0']['email'];
		$rand=SL_randomchar();
		Db::getInstance()->Execute("UPDATE "._DB_PREFIX_."sociallogin SET rand=".$rand." WHERE provider_id='$social_id'");
		$sub=$module->all_messages('Verify your email id.');
		$protocol_content = (Configuration::get('PS_SSL_ENABLED')) ? 'https://' : 'http://';
		$link=$protocol_content.$_SERVER['HTTP_HOST'].__PS_BASE_URI__."?SL_VERIFY_EMAIL=$rand&SL_PID=".$social_id."";
		$msgg=$module->all_messages('Please click on the following link or paste it in browser to verify your email: '). $link;
		SL_email($to,$sub,$msgg,$getdata['0']['firstname'],$getdata['0']['lastname'],$social_id);
	}
}

/*
* Save the logged in user credentails in cookie.
*/
function loginRedirect($arr){
	global $cookie;
	$cookie->id_compare = $arr['id'];
	$cookie->id_customer = $arr['id'];
	$cookie->customer_lastname = $arr['lname'];
	$cookie->customer_firstname = $arr['fname'];
	$cookie->logged = 1;
	$cookie->passwd = $arr['pass'];
	$cookie->email = $arr['email'];
	$cookie->loginradius_id= $arr['loginradius_id'];
	$cookie->lr_login="true";
	if ((empty($cookie->id_cart) || Cart::getNbProducts($cookie->id_cart) == 0))
		$cookie->id_cart = (int)Cart::lastNoneOrderedCart($cookie->id_customer);
	Module::hookExec('authentication');
	$redirect=redirectURL();
	Tools::redirectLink($redirect);
}

/*
* When user have Email address then check login functionaity
*/
function storeAndLogin($user_profile_data, $rand=''){
	$module= new sociallogin();
	$email=pSQL($user_profile_data['email']);
	$random_value='';
	$verified =1;
	if(!empty($rand) &&  $user_profile_data['send_verification_email'] == 'yes') {
		$random_value= $rand;
		$verified=0;
	}
	if(!empty($user_profile_data['fname']) && !empty($user_profile_data['name'])){
		$username = $user_profile_data['fname'] . ' ' . $user_profile_data['lname'];
	}elseif(!empty($user_profile_data['fullname'])){
		$username = $user_profile_data['fullname'];
	}
	elseif(!empty($user_profile_data['profilename'])){
		$username = $user_profile_data['profilename'];
	}
	elseif(!empty($user_profile_data['nickname'])){
		$username = $user_profile_data['nickname'];
	}elseif(!empty($email)){
		$user_name = explode('@', $email);
		$username = $user_name[0];
	}else{
		$username = $user_profile_data['id'];
		$firstName = $user_profile_data['id'];
	}
	if($user_profile_data['dob']) {
		$dobArr = explode("/",$user_profile_data['dob']);
		$dob = $dobArr[2]."-".$dobArr[0]."-".$dobArr[1];
	}
	$date_of_birth = (!empty($dob) && Validate::isBirthDate($dob) ? $dob : '');
	$password = Tools::passwdGen();
	$pass=Tools::encrypt($password);
	$date_added=date("Y-m-d H:i:s",time());
	$date_updated=$date_added;
	$last_pass_gen=$date_added;
	$s_key = md5(uniqid(rand(), true));
	$fname=(!empty($user_profile_data['fname']) ? pSQL($user_profile_data['fname']) : pSQL($username));
	$fname=remove_special($fname);
	$lname=(!empty($user_profile_data['lname']) ? pSQL($user_profile_data['lname']) : pSQL($username));
	$lname=remove_special($lname);
	$newsletter='0';
	$optin='0';
	$gender=((!empty($user_profile_data['gender']) and (strpos($userprofile->Gender, "f") !== false || (trim($user_profile_data['gender']) == "F"))) ? 2 : 1);
	$required_field_check = Db::getInstance()->ExecuteS("SELECT field_name FROM  ".pSQL(_DB_PREFIX_)."required_field");
	foreach ($required_field_check AS $item){
		if($item['field_name']=='newsletter')
			$newsletter='1';
		if($item['field_name']=='optin')
			$optin='1';
	}
	$query= "INSERT into "._DB_PREFIX_."customer (`id_gender`,`id_default_group`,`firstname`,`lastname`,`email`,`passwd`,`last_passwd_gen`,`birthday`,`newsletter`,`optin`,`active`,`date_add`,`date_upd`,`secure_key` ) values ('$gender','1','$fname','$lname','$email','$pass','$last_pass_gen','$date_of_birth','$newsletter','$optin','1','$date_added','$date_updated','$s_key') ";
	Db::getInstance()->Execute($query);
	$insert_id=(int)Db::getInstance()->Insert_ID();
	$tbl=pSQL(_DB_PREFIX_.'sociallogin');
	Db::getInstance()->Execute("DELETE FROM $tbl WHERE provider_id='".$user_profile_data['id']."'");
	$query= "INSERT into $tbl (`id_customer`,`provider_id`,`Provider_name`,`verified`,`rand`) values ('$insert_id','".$user_profile_data['id']."','".$user_profile_data['provider']."','$verified','$random_value') ";
	Db::getInstance()->Execute($query);
	//extra data from here later to complete
	$tbl=pSQL(_DB_PREFIX_.'customer_group');
	Db::getInstance()->Execute("DELETE FROM $tbl WHERE id_customer='$insert_id'");
	$query= "INSERT into $tbl (`id_customer`,`id_group`) values ('$insert_id','1') ";
	Db::getInstance()->Execute($query);
	extraFields($user_profile_data,$insert_id,$fname,$lname);
	if(!empty($rand) && $user_profile_data['send_verification_email'] == 'yes'){
		$to=$email;
		$sub=$module->all_messages('Verify your email id. ');
		$protocol_content = (Configuration::get('PS_SSL_ENABLED')) ? 'https://' : 'http://';
		$link=$protocol_content.$_SERVER['HTTP_HOST'].__PS_BASE_URI__."?SL_VERIFY_EMAIL=$rand&SL_PID=".$user_profile_data['id']."";
		$msg=$module->all_messages('Please click on the following link or paste it in browser to verify your email: ').$link;
		SL_email($to,$sub,$msg,$fname,$lname,$user_profile_data['id']);
	}
	else {
		$arr=array();
		$arr['id']=(string)$insert_id;
		$arr['lname']=$lname;
		$arr['fname']=$fname;
		$arr['pass']=$pass;
		$arr['email']=$email;
		$arr['loginradius_id']= $user_profile_data['id'];
		$user['pass']=$password;
		$user['fname']=$arr['fname'];
		$user['lname']=$arr['lname'];
		if(Configuration::get('SEND_REQ')=="1")
			Admin_email($arr['email'],$arr['fname'],$arr['lname']);
		if(Configuration::get('user_notification')=="0")
			user_notification_email($arr['email'],$user);
		loginRedirect($arr);	
	}
}

/*
* save the user data in cookie.
*/
function storeInCookie($user_profile_data){
	global $cookie;
	$cookie->login_radius_data =serialize($user_profile_data);
}

/*
* Show poup window for Email and Required fields.
*/
function popUpWindow($msg='',$data=array()){
	$module= new sociallogin();
	$style='style="padding:10px 11px 10px 30px;overflow-y:auto;height:auto;"';
	$profilefield=unserialize(Configuration::get('profilefield'));
	if(empty($profilefield)) {
		$profilefield[] = '3';
	}
	if(Configuration::get('user_require_field')=="1") {
		$height =100;
		for($i=1;$i <sizeof($profilefield)-1;$i++) {
			$height +=50;
		}
		$style='style="padding:10px 11px 10px 30px;overflow-y:auto;height:'.$height.'px;"';
	}
	$profilefield = implode(';', $profilefield);
	Tools::addCSS(__PS_BASE_URI__.'modules/sociallogin/sociallogin_style.css');
	?>
	<!-- <link rel="stylesheet" type="text/css" href="/modules/sociallogin/sociallogin_style.css" />-->
	<script src="//ajax.googleapis.com/ajax/libs/jquery/1.8.0/jquery.min.js"></script>
	<script language="javascript">
	jQuery(document).ready(function($){
	/*The country onchange starts here*/
	var orig_html;
	var orig_value;
	var state_value;
	var us_states = {AL: 'Alabama', AK: 'Alaska', AZ: 'Arizona', AR: 'Arkansas', CA: 'California', CO: 'Colorado', CT: 'Connecticut', DE: 'Delaware', DC: 'District of Columbia', FL: 'Florida', GA: 'Georgia', HI: 'Hawaii', ID: 'Idaho', IL: 'Illinois', IN: 'Indiana', IA: 'Iowa', KS: 'Kansas', KY: 'Kentucky', LA: 'Louisiana', ME: 'Maine', MD: 'Maryland', MA: 'Massachusetts', MI: 'Michigan', MN: 'Minnesota', MS: 'Mississippi', MO: 'Missouri', MT: 'Montana', NE: 'Nebraska', NV: 'Nevada', NH: 'New Hampshire', NJ: 'New Jersey', NM: 'New Mexico', NY: 'New York', NC: 'North Carolina', ND: 'North Dakota', OH: 'Ohio', OK: 'Oklahoma', OR: 'Oregon', PA: 'Pennsylvania', RI: 'Rhode Island', SC: 'South Carolina', SD: 'South Dakota', TN: 'Tennessee', TX: 'Texas', UT: 'Utah', VT: 'Vermont', VA: 'Virginia', WA: 'Washington', WV: 'West Virginia', WI: 'Wisconsin', WY: 'Wyoming'};
	var ca_states = {BC:"British Columbia",ON:"Ontario",NF:"Newfoundland",NS:"Nova Scotia",PE:"Prince Edward Island",NB:"New Brunswick",QC:"Quebec",MB:"Manitoba",SK:"Saskatchewan",AB:"Alberta",NT:"Northwest Territories",YT:"Yukon Territory"};
	var mx_states = {AGS:"Aguascalientes",BCN: "Baja California",BCS: "Baja California Sur", CAM:"Campeche",CHP: "Chiapas", CHH:"Chihuahua",COA: "Coahuila", COL:"Colima", DIF:"Distrito Federal", DUR:"Durango",GUA: "Guanajuato",GRO: "Guerrero", HID:"Hidalgo", JAL:"Jalisco", MEX:"Estado de Mexico",MIC: "Michoacan de Ocampo", MOR:"Morelos", NAY:"Nayarit", NLE:"Nuevo Leon", OAX:"Oaxaca", PUE:"Puebla", QUE:"Queretaro de Arteaga",ROO: "Quintana Roo", SLP:"San Luis Potosi",SIN: "Sinaloa", SON:"Sonora", TAB:"Tabasco", TAM:"Tamaulipas", TLA:"Tlaxcala", VER:"Veracruz-Llave", YUC:"Yucatan", ZAC:"Zacatecas"};
	var ar_states = {B:"Buenos Aires",K:"Catamarca", H:"Chaco", U:"Chubut", C:"Ciudad de Buenos Aires", X:"Córdoba", W: "Corrientes" , E:"Entre Rios", P:"Formosa", Y:"Jujuy",L: "La Pampa", F:"La Rioja", M:"Mendoza", N:"Misiones", Q:"Neuquen", R:"Rio Negro",A:"Salta", J:"San Juan",D: "San Luis", Z:"Santa Cruz",S: "Santa Fe", G:"Santiago del Estero", V:"Tierra del Fuego", T:"Tucuman"};
	var it_states = {AG:"Agrigento" ,AL:"Alessandria" ,AN: "Ancona", AO:"Aosta" ,AR:"Arezzo",AP: "Ascoli Piceno", AT: "Asti", AV: "Avellino" ,BA: "Bari" ,BT:"Barletta-Andria-Trani" ,BL:"Belluno" ,BN:"Benevento" ,BG:"Bergamo" ,BI:"Biella" ,BO:"Bologna" ,BZ:"Bolzano" ,BS: "Brescia" ,BR :"Brindisi" ,CA :"Cagliari", CL: "Caltanissetta" ,CB :"Campobasso" ,CI:"Carbonia-Iglesias" ,CE:"Caserta" ,CT:"Catania" ,CZ:"Catanzaro", CH: "Chieti" ,CO: "Como" ,CS: "Cosenza" ,CR: "Cremona" ,KR: "Crotone" ,CN: "Cuneo" ,EN: "Enna" ,FM: "Fermo" ,FE: "Ferrara" ,FI: "Firenze" ,FG: "Foggia", FC:"Forlì-Cesena" ,FR:"Frosinone" ,GE:"Genova" ,GO:"Gorizia" ,GR:"Grosseto" ,IM:"Imperia" ,IS:"Isernia" ,AQ:"L'Aquila" ,SP:"La Spezia" ,LT:"Latina" ,LE:"Lecce" ,LC: "Lecco" ,LI: 	"Livorno" ,LO: "Lodi" ,LU:"Lucca" ,MC: "Macerata" ,MN:"Mantova" ,MS: "Massa" ,MT: "Matera" ,VS: 	"Medio Campidano" ,ME:	"Messina" ,MT:"Milano" ,MO:"Modena" ,MB:"Monza e della Brianza" ,NA:"Napoli" ,NO:	"Novara" ,NU:"Nuoro" ,OG:"Ogliastra",OT:	"Olbia-Tempio",OR:"Oristano" ,PD:"Padova" ,PA: "Palermo" ,PR: "Parma" ,PV: "Pavia" ,PG:"Perugia" ,PU: "Pesaro-Urbino",PE:"Pescara" ,PC: "Piacenza" ,PI: "Pisa" ,PT: "Pistoia" ,PN:"Pordenone" ,PZ:"Potenza" ,PO:"Prato" ,RG:"Ragusa" ,RA:"Ravenna" ,RC: "Reggio Calabria" ,RE: "Reggio Emilia",RI:"Rieti" ,RN: "Rimini",RM: "Roma" ,RO:"Rovigo",SA: "Salerno" ,SS: "Sassari" ,SV: "Savona" ,SI: "Siena" ,SR:"Siracusa" ,SO: "Sondrio" ,TA:"Taranto" ,TE: "Teramo" ,TR:"Terni" ,TO: "Torino" ,TP: "Trapani" ,TN: "Trento" ,TV:"Treviso" ,TS:"Trieste" ,UD: "Udine" ,VA: "Varese",VE: "Venezia" ,VB:"Verbano-Cusio-Ossola" ,VC:"Vercelli" ,VR:"Verona",VV:"Vibo Valentia" ,VI: "Vicenza" ,VT:"Viterbo"};
	var id_states= {	AC:"Aceh",BA: "Bali", BB:"Bangka",BT: "Banten",BE:"Bengkulu", JT:"Central Java",KT:"Central Kalimantan",ST:"Central Sulawesi",JI:"Coat of arms of East Java",KI:"East kalimantan",NT:"East Nusa Tenggara",GO:"Lambang propinsi",JK:"Jakarta",JA:"Jambi",LA:"Lampung",MA:"Maluku",MU:"North Maluku",SA:"North Sulawesi",SU:"North Sumatra",PA:"Papua",RI:"Riau",KR:"Lambang Riau",SG:"Southeast Sulawesi",KS:"South Kalimantan",SN:"South Sulawesi",SS:"South Sumatra",JB:"West Java",KB:"West Kalimantan",NB:"West Nusa Tenggara",PB:"Lambang Provinsi Papua Barat",SR:"West Sulawesi",SB:"West Sumatra",YO:"Yogyakarta"}; 
	var jp_states={  01 : "Aichi",  02 : "Akita",03 : "Aomori",04 : "Chiba",05 : "Ehime",06 : "Fukui", 07 : "Fukuoka",08 : "Fukushima",09 : "Gifu", 10 : "Gumma",11 : "Hiroshima",12 : "Hokkaido", 13 : "Hyogo",14 : "Ibaraki",15 : "Ishikawa", 16 : "Iwate",17 : "Kagawa",18 : "Kagoshima",19 : "Kanagawa",20 : "Kochi",21 : "Kumamoto",22 : "Kyoto",23 : "Mie",24 : "Miyagi",25 : "Miyazaki",26 : "Nagano",27 : "Nagasaki",28 : "Nara",29 : "Niigata",30 : "Oita",31 : "Okayama",32 : "Osaka",33 : "Saga",34 : "Saitama",35 : "Shiga",36 : "Shimane",37 : "Shizuoka",38 : "Tochigi",39 : "Tokushima",40 : "Tokyo",41 : "Tottori",42 : "Toyama",43 : "Wakayama",44 : "Yamagata",45 : "Yamaguchi",46 : "Yamanashi",47 : "Okinawa"};
	var $el = $("#location-country");
	$el.data('oldval', $el.val());
	$el.change(function(){
	var $this = $(this);
	if(this.value=="US"){
		document.getElementById('location-state-div').style.display='block';
		var str = '<span class="spantxt">State:</span><select class="inputtxt" name="location-state" id="location-state">';
		orig_html = $("#location-state-div").html();
		orig_value = $("#location-state").val();
		for(var st in us_states){
		if(st == state_value)
			str += '<option value="'+st+'" selected="selected">'+us_states[st]+'</option>';
		else
			str += '<option value="'+st+'">'+us_states[st]+'</option>';
		}
		str += "</select>";
		$("#location-state-div").html(str);
		$this.data('oldval', $this.val());
	}
	else if(this.value=="CA"){
		document.getElementById('location-state-div').style.display='block';
		var str = '<span class="spantxt">State:</span><select class="inputtxt" name="location-state" id="location-state">';
		orig_html = $("#location-state-div").html();
		orig_value = $("#location-state").val();
		for(var st in ca_states){
		if(st == state_value)
			str += '<option value="'+st+'" selected="selected">'+ca_states[st]+'</option>';
		else
			str += '<option value="'+st+'">'+ca_states[st]+'</option>'; 
		}
		str += "</select>";
		$("#location-state-div").html(str);
		$this.data('oldval', $this.val());
	}
	else if(this.value=="MX"){
		document.getElementById('location-state-div').style.display='block';
		var str = '<span class="spantxt">State:</span><select class="inputtxt" name="location-state" id="location-state">';
		orig_html = $("#location-state-div").html();
		orig_value = $("#location-state").val();
		for(var st in mx_states){
			if(st == state_value)
			str += '<option value="'+st+'" selected="selected">'+mx_states[st]+'</option>';
			else
			str += '<option value="'+st+'">'+mx_states[st]+'</option>';
		}
		str += "</select>";
		$("#location-state-div").html(str);
		$this.data('oldval', $this.val());
	}
	else if(this.value=="AR"){
		document.getElementById('location-state-div').style.display='block';
		var str = '<span class="spantxt">State:</span><select class="inputtxt" name="location-state" id="location-state">';
		orig_html = $("#location-state-div").html();
		orig_value = $("#location-state").val();
		for(var st in ar_states){
			if(st == state_value)
			str += '<option value="'+st+'" selected="selected">'+ar_states[st]+'</option>';
			else
			str += '<option value="'+st+'">'+ar_states[st]+'</option>';
		}
		str += "</select>";
		$("#location-state-div").html(str);
		$this.data('oldval', $this.val());
	}
	else if(this.value=="JP"){
		document.getElementById('location-state-div').style.display='block';
		var str = '<span class="spantxt">State:</span><select class="inputtxt" name="location-state" id="location-state">';
		orig_html = $("#location-state-div").html();
		orig_value = $("#location-state").val();
		for(var st in jp_states){
			if(st == state_value)
				str += '<option value="'+st+'" selected="selected">'+jp_states[st]+'</option>';
			else
				str += '<option value="'+st+'">'+jp_states[st]+'</option>';
		}
		str += "</select>";
		$("#location-state-div").html(str);
		$this.data('oldval', $this.val());
	}
	else if(this.value=="ID"){
		document.getElementById('location-state-div').style.display='block';
		var str = '<span class="spantxt">State:</span><select class="inputtxt" name="location-state" id="location-state">';
		orig_html = $("#location-state-div").html();
		orig_value = $("#location-state").val();
		for(var st in id_states){
		if(st == state_value)
			str += '<option value="'+st+'" selected="selected">'+id_states[st]+'</option>';
		else
			str += '<option value="'+st+'">'+id_states[st]+'</option>';
		}
		str += "</select>";
		$("#location-state-div").html(str);
		$this.data('oldval', $this.val());
	}
	else if(this.value=="IT"){
		document.getElementById('location-state-div').style.display='block';
		var str = '<span class="spantxt">State:</span><select class="inputtxt" name="location-state" id="location-state">';
		orig_html = $("#location-state-div").html();
		orig_value = $("#location-state").val();
		for(var st in it_states){
		if(st == state_value)
			str += '<option value="'+st+'" selected="selected">'+it_states[st]+'</option>';
		else
			str += '<option value="'+st+'">'+it_states[st]+'</option>';
		}
		str += "</select>";
		$("#location-state-div").html(str);
		$this.data('oldval', $this.val());
	}
	else {
		document.getElementById('location-state-div').style.display='none';
	}
	});
	});
	function popupvalidation() {
		var filter = /^([a-zA-Z0-9_\.\-])+\@(([a-zA-Z0-9\-])+\.)+([a-zA-Z0-9]{2,4})+$/;
		var loginRadiusForm=document.getElementById("validfrm");
		for(var i = 0; i < loginRadiusForm.elements.length; i++){
			if(loginRadiusForm.elements[i].id=="location-country") {
			if(loginRadiusForm.elements[i].value == 0){
			document.getElementById('textmatter').style.color="#ff0000";
			loginRadiusForm.elements[i].style.borderColor="#ff0000";
			loginRadiusForm.elements[i].focus();
			return false;
				}
			}
			if(loginRadiusForm.elements[i].value.trim() == ""){
				document.getElementById('textmatter').style.color="#ff0000";
				loginRadiusForm.elements[i].style.borderColor="#ff0000";
				loginRadiusForm.elements[i].focus();
				return false;
			}
			else {
				document.getElementById('textmatter').style.color="#666666";
				loginRadiusForm.elements[i].style.borderColor="#E5E5E5";
			}
			if(loginRadiusForm.elements[i].id=="SL_PHONE") {
				if(isNaN(loginRadiusForm.elements[i].value)==true) {
					document.getElementById('textmatter').style.color="#ff0000";
					loginRadiusForm.elements[i].style.borderColor="#ff0000";
					loginRadiusForm.elements[i].focus();
					return false;
				}
			}
			if(loginRadiusForm.elements[i].id=="SL_EMAIL") {
				var email=loginRadiusForm.elements[i].value;
				var atPosition = email.indexOf("@");
				var dotPosition = email.lastIndexOf(".");
				if(atPosition < 1 || dotPosition < atPosition+2 || dotPosition+2>=email.length) {
				document.getElementById('textmatter').style.color="#ff0000";
				loginRadiusForm.elements[i].style.borderStyle="solid";
				loginRadiusForm.elements[i].style.borderColor="#ff0000";
				loginRadiusForm.elements[i].focus();
				return false;
				}
				else
				{
				document.getElementById('textmatter').style.color="#666666";
				loginRadiusForm.elements[i].style.borderColor="#E5E5E5";
				}
			}
		}
	return true;
}
	</script>
	<?php
	global $cookie;
	$cookie->SL_hidden=microtime();
	?>
	<div id="fade" class="LoginRadius_overlay">
	<div id="popupouter" style="margin-top:-350px;">
	<div id="popupinner" <?php echo $style;?>>
	<div id="textmatter"><strong>
	<?php
	if($msg==''){
	//echo "Please fill the following details to complete the registration";
	$show_msg=Configuration::get('POPUP_TITLE');
	echo $msg= ( !empty($show_msg) ? $show_msg : $module->all_messages('Please fill the following details to complete the registration')) ;
	}
	else
	{
	echo $msg;
	}
	?>
	</strong></div>
	<form method="post" name="validfrm" id="validfrm" action="" onsubmit="return popupvalidation();">
	<?php
	$html="";
	if(Configuration::get('user_require_field')=="1") {
		if(strpos($profilefield,'1') !== false && (empty($data['fname']) || isset($data['firstname']))) {
			$html.='<div>
			<span class="spantxt">'.$module->all_messages('First Name').'</span><input type="text" name="SL_FNAME" id="SL_FNAME" placeholder="FirstName" value= "'.(isset($_POST['SL_FNAME'])?htmlspecialchars($_POST['SL_FNAME']):'').'" class="inputtxt" />
			</div>';
		}
		if(strpos($profilefield,'2') !== false && (empty($data['lname'])||isset($data['lastname']))) {
			$html.='<div>
			<span class="spantxt">'.$module->all_messages('Last Name').'</span><input type="text" name="SL_LNAME" id="SL_LNAME" placeholder="LastName" value= "'.(isset($_POST['SL_LNAME'])?htmlspecialchars($_POST['SL_LNAME']):'').'" class="inputtxt" />
			</div>';
		}
		$html .= '<div id="location-state-div" style="display:none;">
		<input id="location-state" type="text" name="location-state" value="empty" />
		</div>';
		$countries = Db::getInstance()->executeS('
		SELECT *
		FROM '._DB_PREFIX_.'country c WHERE c.active =1');
		if (is_array($countries) AND !empty($countries)) {
			$list = '';
			if(strpos($profilefield,'3') !== false) {
			$html .= '<div id="location-country-div">
			<span class="spantxt">'.$module->all_messages('Country').'</span> <select id="location-country" name="location_country" class="inputtxt"><option value="0">None</option>';
			foreach ($countries AS $country) {
			$country_name = new Country($country['id_country']);
			$html .= '<option value="'.($country['iso_code']).'"'.(isset($_POST['iso_code']) ? ' selected="selected"' : '').'>'.$country_name->name['1'].'</option>'."\n";
			}
			$html.='</select></div>';
			}
		}
	}
	if(empty($data['email']) || $data['send_verification_email'] == 'yes'){
		$html.='<div><span class="spantxt">'.$module->all_messages('Email').'</span>
		<input type="text" name="SL_EMAIL" id="SL_EMAIL" placeholder="Email" value= "'.(isset($_POST['SL_EMAIL'])?htmlspecialchars($_POST['SL_EMAIL']):'').'" class="inputtxt" />
		</div>';
	}
	if(Configuration::get('user_require_field')=="1") {
		if(strpos($profilefield,'4') !== false) {
			$html.='<div>
			<span class="spantxt">'.$module->all_messages('City').'</span><input type="text" name="SL_CITY" id="SL_CITY" placeholder="City" value= "'.(isset($_POST['SL_CITY'])?htmlspecialchars($_POST['SL_CITY']):'').'" class="inputtxt" />
			</div>';
		}
		if(strpos($profilefield,'5') !== false) {
			$html.='<div><span class="spantxt">'.$module->all_messages('Mobile Number').'</span>
			<input type="text" name="SL_PHONE" id="SL_PHONE" placeholder="Mobile Number" value= "'.(isset($_POST['SL_PHONE'])?htmlspecialchars($_POST['SL_PHONE']):'').'" class="inputtxt" />
			</div>';
		}
		if(strpos($profilefield,'6') !== false) {
			$html.='<div><span class="spantxt">'.$module->all_messages('Address').'</span>
			<input type="text" name="SL_ADDRESS" id="SL_ADDRESS" placeholder="Address" value= "'.(isset($_POST['SL_ADDRESS'])?htmlspecialchars($_POST['SL_ADDRESS']):'').'" class="inputtxt" />
			</div>';
		}
		if(strpos($profilefield,'8') !== false) {
			$html.='<div><span class="spantxt">'.$module->all_messages('ZIP code').'</span>
			<input type="text" name="SL_ZIP_CODE" id="SL_ZIP_CODE" placeholder="Zip Code" value= "'.(isset($_POST['SL_ZIP_CODE'])?htmlspecialchars($_POST['SL_ZIP_CODE']):'').'" class="inputtxt" />
			</div>';
		}
		if(strpos($profilefield,'7') !== false) {
			$html.='<div><span class="spantxt">'.$module->all_messages('Address Title').'</span><input type="text" name="SL_ADDRESS_ALIAS" id="SL_ADDRESS_ALIAS" placeholder="Please assign an address title for future reference" value= "'.(isset($_POST['SL_ADDRESS_ALIAS'])?htmlspecialchars($_POST['SL_ADDRESS_ALIAS']):'').'" class="inputtxt" />
			</div>';
		}
	}
	$html.='<div><input type="hidden" name="hidden_val" value="'.$cookie->SL_hidden.'" />
	<input type="submit" id="LoginRadius" name="LoginRadius" value="'.$module->all_messages('Submit').'" class="inputbutton">
	<input type="button" value="'.$module->all_messages('Cancel').'" class="inputbutton" onclick="window.location.href=window.location.href;" />
	</div></div>
	</form>
	</div>
	</div>
	</div>';
	echo $html;
}
// Verify email-address.
function verifyEmail(){
	$module= new sociallogin();
	$tbl=pSQL(_DB_PREFIX_.'sociallogin');
	$pid=pSQL($_REQUEST['SL_PID']);
	$rand=pSQL($_REQUEST['SL_VERIFY_EMAIL']);
	$db = Db::getInstance()->ExecuteS("SELECT * FROM  ".pSQL(_DB_PREFIX_)."sociallogin  WHERE rand='$rand' and provider_id='$pid' and verified='0'");
	$num=(!empty($db['0']['id_customer'])?$db['0']['id_customer']:"");
	$provider_name=(!empty($db['0']['Provider_name'])? pSQL($db['0']['Provider_name']) : "");
	if($num<1){
		return;
	}
	Db::getInstance()->Execute("UPDATE $tbl SET verified='1' , rand='' WHERE rand='$rand' and provider_id='$pid'");
	Db::getInstance()->Execute("UPDATE $tbl SET rand='' WHERE Provider_name='$provider_name' and id_customer='$num'");
	$msg= $module->all_messages('Email is verified. Now you can login using Social Login.');
	popup_verify($msg);
}

// send credenntials to customer.
function user_notification_email($email,$user) {
	$module= new sociallogin();
	$protocol_content = (Configuration::get('PS_SSL_ENABLED')) ? 'https://' : 'http://';
	$link=$protocol_content.$_SERVER['HTTP_HOST'];
	$sub=$module->all_messages('Thank You For Registration');
	$vars = array(
	'{firstname}' => $user['fname'], 
	'{lastname}' => $user['lname'], 
	'{email}' => $email,
	'{passwd}'=> $user['pass']
	);
	$id_lang = (int)Configuration::get('PS_LANG_DEFAULT');
	Mail::Send($id_lang, 'account',$sub, $vars, $email);
}

// Notify admin when new user register.
function Admin_email($email,$firstname,$lastname) {
	$module= new sociallogin();
	$protocol_content = (Configuration::get('PS_SSL_ENABLED')) ? 'https://' : 'http://';
	$link=$protocol_content.$_SERVER['HTTP_HOST'];
	$sub=$module->all_messages('New User Registration');
	$msg=$module->all_messages('New User Registered to your site');
	$vars = array(
	'{email}' => $email,
	'{message}'=> $msg,
	'{id_order}' =>'',
	'{id_product}' =>''
	);
	$db = Db::getInstance()->ExecuteS("SELECT * FROM  ".pSQL(_DB_PREFIX_)."employee  WHERE id_profile=1 ");
	$id_lang = (int)Configuration::get('PS_LANG_DEFAULT');
	foreach ($db as $row) {
		$find_id=$row['id_employee'];
		$find_email=$row['email'];
		Mail::Send($id_lang, 'contact',$sub, $vars, $find_email);
	}
}
// Send mail.
function SL_email($to,$sub,$msg,$firstname,$lastname,$social_id){
	$module= new sociallogin();
	if($_SERVER['HTTP_HOST']=="localhost"){
		echo $module->all_messages('Email will work at online only.');
	}else{
		$msgg=$module->all_messages('Your Confirmation link Has Been Sent To Your Email Address. Please verify your email by clicking on confirmation link.');
		popup_verify($msgg, $social_id);
		$id_lang = (int)Configuration::get('PS_LANG_DEFAULT');
		$protocol_content = (Configuration::get('PS_SSL_ENABLED')) ? 'https://' : 'http://';
		$link=$protocol_content.$_SERVER['HTTP_HOST'].__PS_BASE_URI__;		
		$vars = array(
		'{email}' => $to,
		'{message}' => $msg,
		'{id_order}' =>'',
		'{id_product}' =>''
		);		
		Mail::Send($id_lang, 'contact',$sub, $vars, $to);
	}
}
// Get random character.
function SL_randomchar(){
	$char="";
	for($i=0;$i<20;$i++){
		$char.=rand(0,9);
	}
	return($char);
}
//Save data after POPup call.
function SL_data_save($lrdata=array()){
	$module= new sociallogin();
	global $cookie;
	$provider_id=$lrdata['id'];
	$provider_name=$lrdata['provider'];
	if(!$cookie->isLogged()) {
		 $email= $lrdata['email'];
		 $query="SELECT c.id_customer from "._DB_PREFIX_."customer AS c INNER JOIN "._DB_PREFIX_."sociallogin AS sl ON sl.id_customer=c.id_customer  WHERE c.email='$email'";
		$query = Db::getInstance()->ExecuteS($query);
		if(!empty($query['0']['id_customer'])){
			$error_msg="<p style ='color:red;'>".$module->all_messages('This email id already exist')."</p>";
			$lrdata['email']='';
			popUpWindow($error_msg,$lrdata);
			return;
		}
		else{
		    $cookie->login_radius_data='';
		    $cookie->SL_hidden='';
			$query1="SELECT * FROM "._DB_PREFIX_."customer  WHERE email='$email'";
			$query1 = Db::getInstance()->ExecuteS($query1);
			$num=(!empty($query1['0']['id_customer'])?$query1['0']['id_customer']:"");
			if(!empty($num)){
				$rand=SL_randomchar();
				$fname=(!empty($query1['0']['firstname'])? $query1['0']['firstname'] :"");
				$lname=(!empty($query1['0']['lastname'])? $query1['0']['lastname'] :"");
				$tbl=pSQL(_DB_PREFIX_.'sociallogin');
				$query= "INSERT into $tbl (`id_customer`,`provider_id`,`Provider_name`,`rand`,`verified`) values ('$num','$provider_id','$provider_name','$rand','0') ";
				Db::getInstance()->Execute($query);
				$to=$email;
				$sub=$module->all_messages('Verify your email id. ');
				$protocol_content = (Configuration::get('PS_SSL_ENABLED')) ? 'https://' : 'http://';
				$link=$protocol_content.$_SERVER['HTTP_HOST'].__PS_BASE_URI__."?SL_VERIFY_EMAIL=$rand&SL_PID=$provider_id";
				$msg=$module->all_messages('Please click on the following link or paste it in browser to verify your email: ').$link;
				$sub=$module->all_messages('Verify your email id.');
				SL_email($email,$sub,$msg,$fname,$lname,$provider_id);
				return;
			}
			else{
				$rand=SL_randomchar();
				storeAndLogin($lrdata, $rand);
			}
		}
	}
}

//Show Error Message.
function popup_verify($msg, $social_id='') {
	$module= new sociallogin();
	Tools::addCSS(__PS_BASE_URI__.'modules/sociallogin/sociallogin_style.css');
	$html ='<div id="fade" class="LoginRadius_overlay">
	<div id="popupouter">
	<div id="popupinner">
	<div id="textmatter">'.$msg.'';
	$html.='</div><form method="post" name="validform_verify" action="">';
	$html.='
	<input type="button" value="'.$module->all_messages('Ok').'" onclick="window.location.href=window.location.href;" class="inputbutton" />';
	if(!empty($social_id)) {
		$html.='
		<input type="hidden" value="'.$social_id.'" name="social_id_value" class="inputbutton" />
		<input type="submit" value="'.$module->all_messages('Resend Email Verification').'" name="resend_email_verification" class="inputbutton" />';
	}
	$html.='</form></div></div></div></div>';
	echo $html;
}

//Insert popup optionla fields.
function extraFields($data,$insert_id,$fname,$lname, $update=''){
	$str="";
	if(!empty($data['country'])){
		$country=$data['country'];
		$id=pSQL(getIdByCountryISO($country));
		 $str.="id_country='$id',";
	}
	if(!empty($data['country']) && empty($id)) {
	 $country=$data['country'];
	 $id=pSQL(getIdByCountryName($country));
	 $str="id_country='$id',";
	}
	elseif(empty($id)){
		$id = (int)(Configuration::get('PS_COUNTRY_DEFAULT'));
		$str.="id_country='$id',";
	}
	if(isset($data['state']) && $data['state'] != 'empty' && !empty($data['state'])){
		$state=$data['state'];
		$iso=pSQL(getIsoByState($state));
		$str.="id_state='$iso',";
	}
	if(isset($data['city']) && !empty($data['city'])){
		$city=pSQL($data['city']);
		$str.="city='$city',";
	}
	if(isset($data['zipcode']) && !empty($data['zipcode'])){
		$zip=trim(pSQL($data['zipcode']));
		$str.="postcode='$zip',";
	}
	if(isset($data['address']) && !empty($data['address'])){
		$address=pSQL($data['address']);
		$str.="address1='$address',";
	}
	if(isset($data['phonenumber']) && !empty($data['phonenumber'])){
		$phone=pSQL($data['phonenumber']);
		$str.="phone_mobile='$phone',";
	}
	if(isset($data['addressalias']) && !empty($data['addressalias'])){
		$add_alias=pSQL($data['addressalias']);
		$str.="alias='$add_alias',";
	}
	$tbl=_DB_PREFIX_."address";
	$date=date("y-m-d h:i:s");
	if($update =='yes') {
	if(!empty($fname)){
		 $str.="firstname='$fname',";
	}
	if(!empty($lname)){
		 $str.="lastname='$lname',";
	}
	$q= "UPDATE $tbl SET ".$str." date_upd='$date' WHERE id_customer='$insert_id'";
	$q = Db::getInstance()->Execute($q);
	}
		else {
	$str.="date_add='$date',date_upd='$date',";
	$fname=pSQL($fname);
	$lname=pSQL($lname);
	 $q= "INSERT into $tbl SET ".$str." id_customer='$insert_id', lastname='$lname',firstname='$fname' ";
	$q = Db::getInstance()->Execute($q);
	}
}

//Get country name by Counter ISo=code.
function getIdByCountryISO($ISO){
	$tbl=_DB_PREFIX_."country";
	$field="iso_code";
	$ISO=pSQL(trim($ISO));
	$q="SELECT * from $tbl WHERE $field='$ISO'";
	$q = Db::getInstance()->ExecuteS($q);
	$iso="";
	$iso=(isset($q[0]['id_country']) ? $q[0]['id_country']: '');
	return($iso);
}

// Get Counter name by ID.
function getIdByCountryName($country){
	$tbl=_DB_PREFIX_."country_lang";
	$country=pSQL(trim($country));
	$q="SELECT * from $tbl WHERE name='$country'";
	$q = Db::getInstance()->ExecuteS($q);
	$iso=$q[0]['id_country'];
	return($iso);
}

// Get State. from ISO-code.
function getIsoByState($state){
	$tbl=_DB_PREFIX_."state";
	$q="SELECT * from $tbl WHERE  iso_code ='$state'";
	$q = Db::getInstance()->ExecuteS($q);
	if(!empty($q)) {
		$id=$q[0]['id_state'];
		return($id);
	}
}

// remove special character from name.
function remove_special($field){
	$in_str = str_replace(array('<', '>', '&', '{', '}', '*', '/', '(', '[', ']' , '@', '!', ')', '&', '*', '#', '$', '%', '^', '|','?', '+', '=','"',','), array(''), $field);
	$cur_encoding = mb_detect_encoding($in_str) ;
	if($cur_encoding == "UTF-8" && mb_check_encoding($in_str,"UTF-8"))
		$name= $in_str;
	else
		$name= utf8_encode($in_str);
	if(!Validate::isName($name)) {
		$len = strlen($name);
		$return_val = "";    
		for($i=0;$i<$len;$i++){
			if(ctype_alpha($name[$i])){
				$return_val .= $name[$i];
			}
		}
		$name=  $return_val;
		if(empty($name)){
			$letters = range('a', 'z');
			for($i=0;$i<5;$i++){
				$name .= $letters[rand(0,26)];
			}
		}
	}
	return $name;
}
/*
* Retrieve random Email address.
*/	
function email_rand($lrdata){
	switch ($lrdata['provider']) {
	case 'twitter':
	$email= $lrdata['id'].'@'.$lrdata['provider'].'.com';
	break;
	default:
	$email_id = drupal_substr($lrdata['id'], 7);
	$email_id2 = str_replace("/", "_", $email_id);
	$email = str_replace(".", "_", $email_id2) . '@' . $lrdata['provider'] . '.com';
	break;
	}
	return $email;
}
function loginradius_mapping_profile_data($userprofile) {
	$lrdata['fullname'] = (!empty($userprofile->FullName) ? $userprofile->FullName : '');
	$lrdata['profilename'] = (!empty($userprofile->ProfileName) ? $userprofile->ProfileName : '');
	$lrdata['nickname'] = (!empty($userprofile->NickName) ? $userprofile->NickName : '');
	$lrdata['fname'] = (!empty($userprofile->FirstName) ? $userprofile->FirstName : '');
	$lrdata['lname'] = (!empty($userprofile->LastName) ? $userprofile->LastName : '');
	$lrdata['id'] = (!empty($userprofile->ID) ? $userprofile->ID : '');
	$lrdata['provider'] = (!empty($userprofile->Provider) ? $userprofile->Provider : '');
	$lrdata['nickname'] = (!empty($userprofile->NickName) ? $userprofile->NickName : '');
	$lrdata['email'] = (sizeof($userprofile->Email) > 0 ? $userprofile->Email[0]->Value : '');
	$lrdata['thumbnail'] = (!empty($userprofile->ImageUrl) ? trim($userprofile->ImageUrl) : '');
	if (empty($lrdata['thumbnail']) && $lrdata['provider'] == 'facebook') {
		$lrdata['thumbnail'] = "http://graph.facebook.com/" . $lrdata['id'] . "/picture?type=square";
	}
	$lrdata['dob'] = (!empty($userprofile->BirthDate) ? $userprofile->BirthDate : '');
	$lrdata['gender'] = (!empty($userprofile->Gender) ? $userprofile->Gender : '');
	$lrdata['company'] = (!empty($userprofile->Positions[1]->Company->Name) ? $userprofile->Positions[1]->Company->Name :'');
	if (empty($lrdata['company'])) {
		$lrdata['company'] = (!empty($userprofile->Industry) ? $userprofile->Industry : '');
	}
	$lrdata['hometown'] = (!empty($userprofile->HomeTown) ? $userprofile->HomeTown : '');
	$lrdata['aboutme'] = (!empty($userprofile->About) ? $userprofile->About : '');
	$lrdata['website'] = (!empty($userprofile->ProfileUrl) ? $userprofile->ProfileUrl : '');
	$lrdata['state'] = (!empty($userprofile->State) ? $userprofile->State : '');
	$lrdata['city'] = (!empty($userprofile->City) ? $userprofile->City : '');
	if (empty($lrdata['city']) || $lrdata['city'] == 'unknown') {
		$lrdata['city'] = (!empty($userprofile->LocalCity) &&  $userprofile->LocalCity != 'unknown' ? $userprofile->LocalCity : '');
	}
	$lrdata['country'] = (!empty($userprofile->Country) ? $userprofile->Country : '');
	if (empty($lrdata['country'])) {
		$lrdata['country'] = (!empty($userprofile->LocalCountry) ? $userprofile->LocalCountry : '');
	}
	$lrdata['phonenumber'] = (!empty($userprofile->PhoneNumbers['0']->PhoneNumber) ? $userprofile->PhoneNumbers['0']->PhoneNumber : '' );
	$lrdata['address']=(!empty($userprofile->Addresses['0']->Address1)? $userprofile->Addresses['0']->Address1 : '');
	$lrdata['zipcode']=(!empty($userprofile->Addresses['0']->PostalCode) ? $userprofile->Addresses['0']->PostalCode : '');
	return $lrdata;
}
?>