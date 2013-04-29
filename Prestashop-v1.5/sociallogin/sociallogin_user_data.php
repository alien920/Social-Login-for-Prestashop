<?php
if ( !defined( '_PS_VERSION_' ) ){
	exit;
}
  class LrUser{
  function __construct(){
	include_once("LoginRadius.php");
	$secret = trim(Configuration::get('API_SECRET'));
	$lr_obj=new LoginRadius();
	$userprofile=$lr_obj->loginradius_get_data($secret);
	$provider = (!empty($userprofile->Provider)?($userprofile->Provider):'');
	if($provider=="yahoo" or $provider=="facebook" or $provider=="aol"){
	  $dob = $userprofile->BirthDate;
	if(!empty($dob)) {
	  $dobArr = explode("/",$dob);
	  $dob = $dobArr[2]."-".$dobArr[0]."-".$dobArr[1];
	  $userprofile->BirthDate = $dob;
	}
	}else{
	  $currentTime = time();
	  $time = $currentTime - 599184000;
	  $userprofile->BirthDate = date("Y-m-d",$time);
	}
	if(!empty($userprofile->Gender) and (strpos($userprofile->Gender, "f") !== false)){
	  $userprofile->Gender = "2";
	}else{
	  $userprofile->Gender = "1";
	}
	if (!empty($userprofile->FirstName) && !empty( $userprofile->LastName )) {
      $userprofile->username= $userprofile->FirstName. ' ' . $userprofile->LastName ;
	}
	elseif (!empty($userprofile->FullName)) {
	  $userprofile->username= $userprofile->FullName;
	}
	elseif (!empty($userprofile->ProfileName)) {
	  $userprofile->username = $userprofile->ProfileName;
	}
	elseif (!empty($userprofile->Email['0']->Value)) {
	  $user_name = explode('@',  $userprofile->Email['0']->Value);
	  $userprofile->username = $user_name[0];
	} 
	else {
	  $userprofile->username = (!empty($userprofile->ID)?($userprofile->ID):'');
	}
	$userprofile->FirstName =(!empty($userprofile->FirstName)?remove_special($userprofile->FirstName):"");
	$userprofile->LastName = (!empty($userprofile->LastName)?remove_special($userprofile->LastName):"");
	$userprofile->username = (!empty($userprofile->username)?remove_special($userprofile->username):"");
	$userprofile->Email=(!empty($userprofile->Email['0']->Value)?$userprofile->Email['0']->Value:"");
	if(isset($userprofile->ID)){
	  $dbObj=$this->query($userprofile->ID);
	  $pid=(!empty($dbObj['0']['provider_id']) ? $dbObj['0']['provider_id'] : "");
	  $td_user="";
	  $id=(!empty($dbObj[0]['id_customer'])?$dbObj[0]['id_customer']:"");
	  $num=$id;
	if($id>=1){
	  $active_user=(!empty($dbObj['0']['active'])? $dbObj['0']['active'] :"");
	}
	if($id<1){
	  if(!empty($userprofile->Email)){
	    $query3 = Db::getInstance()->ExecuteS('SELECT * FROM '.pSQL(_DB_PREFIX_.'customer').' as c WHERE c.email='." '$userprofile->Email' ".' LIMIT 0,1');
	    $num=(!empty($query3['0']['id_customer'])?$query3['0']['id_customer']:"");
	    $active_user=(!empty($query3['0']['active'])? $query3['0']['active'] :"");
	if($num>=1) {
	  $td_user="yes";
	if($this->deletedUser($query3)){
	  $home = getHome();
	  $msg= "<p style ='color:red;'>Authentication failed.</p>";
	  popup_verify($msg,$home);
	  return;
	}
	if(Configuration::get('ACC_MAP')==0){
	  $tbl=pSQL(_DB_PREFIX_.'sociallogin');
	  $query= "INSERT into $tbl (`id_customer`,`provider_id`,`Provider_name`,`verified`,`rand`) values ('$num','".$userprofile->ID."' , '".$userprofile->Provider."','1','') ";
	  Db::getInstance()->Execute($query);
	}
	 $this->login_verify($num,$pid,$td_user,$userprofile->ID);
	}
	}
	//new user. user not found in database. set all details
	if(Configuration::get('user_require_field')=="1") {
	  storeInCookie($userprofile);
	  popUpWindow('',$userprofile);
	  return;
	}
	if(Configuration::get('EMAIL_REQ')=="0" and empty($userprofile->Email)){
	  storeInCookie($userprofile);
	  popUpWindow('',$userprofile);
	  return;
	}elseif(Configuration::get('EMAIL_REQ')=="1" and empty($userprofile->Email)){
	  email_rand($userprofile);
	  storeAndLogin($userprofile);
	  return;					
	}
	storeAndLogin($userprofile);
	}elseif($this->deletedUser($dbObj)){
	  $home = getHome();
	  $msg= "<p style ='color:red;'><b>Authentication failed.</b></p>";
	  popup_verify($msg,$home);
	  return;
	}
	if($active_user==0){
	  $home = getHome();
	  $msg= "<p style ='color:red;'><b>User has been disbled or blocked.</b></p>";
	  popup_verify($msg,$home);
	  return;
	}
	$this->login_verify($num,$pid,'',$userprofile->ID);
	}
  }
	
  /*
  * Provide Social linking.
  */	
  public static function linking($arrdata,$userprofile) {	
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
	      $cookie->lrmessage= 'Account cannot be mapped as it already exists in database';
	    }else {
	      $query= "INSERT into $tbl (`id_customer`,`provider_id`,`Provider_name`,`verified`,`rand`) values ('$num','".$userprofile->ID."' , '".$userprofile->Provider."','1','') ";
	      Db::getInstance()->Execute($query);
	      $cookie->lrmessage= 'Your account is successfully mapped';
	    }
	  }
	  else {
	  $cookie->lrmessage= 'Account cannot be mapped as it already exists in database';
	  }
	}
	$loc=$arrdata->currentquerystring;
	$cookie->currentquerystring='';
	Tools::redirectLink(urldecode($loc));
	//Tools::redirectLink(urldecode($arrdata->currentquerystring));
  }
	
  function query($id){
	$slTbl=pSQL(_DB_PREFIX_).'sociallogin';
	$cusTbl=pSQL(_DB_PREFIX_).'customer';
	$id=pSQL($id);
	$q="SELECT * FROM `$slTbl` as sl INNER JOIN `$cusTbl` as c WHERE sl.provider_id='$id' and c.id_customer=sl.id_customer  LIMIT 0,1";
	$dbObj=Db::getInstance()->ExecuteS($q);
	return($dbObj);
  }
	
  /*
  * after check verified user then provide login.
  */
  function login_verify($num,$pid,$td_user="",$id){
  if($this->verifiedUser($num,$pid,$td_user)){
    $this->loginUser($num,$pid,$id);
	return;
	}else{
	$home = getHome();
	$msg= "Your Confirmation link Has Been Sent To Your Email Address. Please verify your email by clicking on confirmation link.";
	popup_verify($msg,$home);
	return;
	}
  }
  
 /*
 * Check user deleted or not.
 */
  function deletedUser($dbObj){
	$deleted=$dbObj['0']['deleted'];
	if($deleted==1){
	return true;
	}
	return false;
  }
	
   /*
   * find user verified or not.
   */
  function verifiedUser($num,$pid,$td_user){
	$dbObj = Db::getInstance()->ExecuteS('SELECT * FROM '.pSQL(_DB_PREFIX_.'sociallogin').' as c WHERE c.id_customer='." '$num'".' AND c.provider_id='." '$pid'".' LIMIT 0,1');
	$verified=$dbObj['0']['verified'];
	$rand=$dbObj['0']['rand'];
	if($verified==1 || $td_user=="yes"){
	return true;
	}
	return false;
  }
  /*
  * Save logged user credentaisl to array.
  */
  function loginUser($num,$pid,$id){
	$dbObj = Db::getInstance()->ExecuteS('SELECT * FROM '.pSQL(_DB_PREFIX_.'customer').' as c WHERE c.id_customer='." '$num' ".' LIMIT 0,1');
	$arr=array();
	$arr['id']=$dbObj['0']['id_customer'];
	$arr['fname']=$dbObj['0']['firstname'];
	$arr['lname']=$dbObj['0']['lastname'];
	$arr['email']=$dbObj['0']['email'];
	$arr['pass']=$dbObj['0']['passwd'];  
	$arr['loginradius_id']=$id;      
	loginRedirect($arr);
  }
}
?>