<?php
if ( !defined( '_PS_VERSION_' ) ){
exit;
}
/**
*Created by loginradius.com ,email, date and other description will goes here....
**/
define("SL_NAME","sociallogin");
define("SL_VERSION","2.0");
define("SL_AUTHOR","LoginRadius");
define("SL_DESCRIPTION","Let your users log in and comment via their accounts with popular ID providers such as Facebook, Google, Twitter, Yahoo, Vkontakte and over 21 more!.");
define("SL_DISPLAY_NAME","Social Login");

class sociallogin extends Module{
  public function __construct(){
	$this->name = SL_NAME;
	$this->version = SL_VERSION;
	$this->author = SL_AUTHOR;
	$this->need_instance = 1;
	$this->module_key="3afa66f922e9df102449d92b308b4532";//don't change given by sir
	parent::__construct();
	$this->displayName = $this->l( SL_DISPLAY_NAME );
	$this->description = $this->l( SL_DESCRIPTION );
	}
	
  /*
  *  Left column hook that show social login interface left side.
  */
  public function hookLeftColumn( $params,$str="" ){
	global $smarty ,$cookie;
	if (Context:: getContext()->customer->isLogged()){
	  return;
	}
	include_once(dirname(__FILE__)."/sociallogin_functions.php");
	include_once(dirname(__FILE__)."/LoginRadius.php");
	$API_KEY = trim(Configuration::get('API_KEY'));
	$API_SECRET = trim(Configuration::get('API_SECRET'));
	$cookie->lr_login=false;
	$margin_style="";
	if($str=="margin"){
	  $margin_style='style="margin-left:8px;margin-top:5px;"';
	}
	$Title=Configuration::get('TITLE');
	if(empty($API_KEY) || empty($API_SECRET) ){
	  $iframe="<p style ='color:red;'>
	LoginRadius Social Login Plugin is not configured!<p>
	To activate your plugin, navigate to modules > Social Login in your Prestashop admin panel and hit the configure and insert LoginRadius API Key & Secret under LoginRadius API Settings section. Follow <a href ='http://support.loginradius.com/customer/portal/articles/677100-how-to-get-loginradius-api-key-and-secret' target = '_blank'>this</a> document to learn how to get API Key & Secret.";
	  if($str=="right" ||$str==""){ $right=true;} 
	  else { $right=false; }
	  $smarty->assign('right',$right);	
	  $smarty->assign( 'iframe', $iframe );
	  $smarty->assign( 'margin_style', '' );   
	  return $this->display( __FILE__, 'loginradius.tpl' );
	} else{
	  $jsfiles='<script>$(function(){loginradius_interface();});</script>';
	  $iframe=$Title.'<br/>'.$jsfiles.'<div id="interfacecontainerdiv" class="interfacecontainerdiv"> </div>';	
	}
	if($str=="right" ||$str==""){
	  $right=true;
	} else {
	  $right=false;
	}
	$smarty->assign('right',$right);		
	$smarty->assign( 'margin_style', $margin_style );     
	$smarty->assign( 'iframe', $iframe );
	return $this->display( __FILE__, 'loginradius.tpl' );
  }
  
   /*
  *  Right column hook that show social login interface right side.
  */
  public function hookRightColumn( $params ){
	return $this->hookLeftColumn( $params,"right" );
  }
  
  /*
  *  Account top hook that show social login interface at create an account (register ) .
  */
  public function hookCreateAccountTop( $params ){
	return $this->hookLeftColumn( $params,"margin" );
  }
  /*
  *  Header hook that add script [Social share script, Social counter script, Social Interface script] at head .
  */
  public function  hookHeader( $params) {
	global $cookie;
	include_once(dirname(__FILE__)."/sociallogin_functions.php");
	$API_KEY = trim(Configuration::get('API_KEY'));
	$API_SECRET = trim(Configuration::get('API_SECRET'));
	if(isset($_SERVER['HTTPS']) && $_SERVER['HTTPS']!='Off' && !empty($_SERVER['HTTPS'])) {
	  $http='https://';
	}
	else {
	  $http='http://';
	}
	$loc=(isset($_SERVER['REQUEST_URI']) ? urlencode($http.$_SERVER["HTTP_HOST"].$_SERVER['REQUEST_URI']): urlencode($http.$_SERVER["HTTP_HOST"].$_SERVER['PHP_SELF'].'?'.$_SERVER['QUERY_STRING']));
	$share = Configuration::get('chooseshare')? Configuration::get('chooseshare'):"0";
	$verticalsharetopoffset = is_numeric(Configuration::get('verticalsharetopoffset'))? Configuration::get('verticalsharetopoffset').'px':"0px";
	$verticalcountertopoffset = is_numeric(Configuration::get('verticalcountertopoffset'))? Configuration::get('verticalcountertopoffset').'px':"0px";
	$social_share_pretext=Configuration::get('social_share_pretext');
	$choosesharepos = Configuration::get('choosesharepos');
	$choosecounterpos = Configuration::get('choosecounterpos');
	if ($share == '0') {
	  $sharesize = '32';
	  $shareinterface = 'horizontal';
	  $sharetitle='<div style="margin:0;"><b>'.$social_share_pretext.'</b></div>';
	}
	else if ($share == '1') {
	  $sharesize = '16';
	  $shareinterface = 'horizontal';
	  $sharetitle='<div style="margin:0;"><b>'.$social_share_pretext.'</b></div>';
	}
	else if ($share == '2') {
	  $sharesize = '32';
	  $shareinterface = 'simpleimage';
	  $sharetitle='<div style="margin:0;"><b>'.$social_share_pretext.'</b></div>';
	}
	else if ($share == '3') {
	  $sharesize = '16';
	  $shareinterface = 'simpleimage';
	  $sharetitle='<div style="margin:0;"><b>'.$social_share_pretext.'</b></div>';
	}
	else if ($share == '4') {
	  $sharesize = '32';
	  $shareinterface = 'Simplefloat';
	  $sharetitle='';
	}
	else if ($share == '5') {
	  $sharesize = '16';
	  $shareinterface = 'Simplefloat';
	  $sharetitle='';
	}
	if ($choosesharepos == '0') {
	  $vershretop = $verticalsharetopoffset;
	  $vershreright = '';
	  $vershrebottom = '';
	  $vershreleft = '0px';
	}
	else if ($choosesharepos == '1') {
	  $vershretop = $verticalsharetopoffset;
	  $vershreright = '0px';
	  $vershrebottom = '';
	  $vershreleft = '';
	}
	else if ($choosesharepos == '2') {
	  $vershretop = $verticalsharetopoffset;
	  $vershreright = '';
	  $vershrebottom = is_numeric(Configuration::get('verticalsharetopoffset'))? '':"0px";
	  $vershreleft = '0px';
	}
	else if ($choosesharepos == '3') {
	  $vershretop = $verticalsharetopoffset;
	  $vershreright = '0px';
	  $vershrebottom = is_numeric(Configuration::get('verticalsharetopoffset'))? '':"0px";
	  $vershreleft = '';
	}
	else {
	  $vershretop = $verticalsharetopoffset;
	  $vershreright = '';
	  $vershrebottom = '';
	  $vershreleft = '';
	}
	$sharescript = '<script type="text/javascript">var islrsharing = true; var islrsocialcounter = true;</script> <script type="text/javascript" src="//share.loginradius.com/Content/js/LoginRadius.js" id="lrsharescript"></script> <script type="text/javascript"> LoginRadius.util.ready(function () { $i = $SS.Interface.'.$shareinterface.'; $SS.Providers.Top = [';
	$rearrange_settings = unserialize(Configuration::get('rearrange_settings'));
	$rearrnage_provider_list=$rearrange_settings;
	if(empty($rearrnage_provider_list)) {
	  $rearrange_settings[] = 'facebook';
	  $rearrange_settings[] = 'googleplus';
	  $rearrange_settings[] = 'twitter';
	  $rearrange_settings[] = 'linkedin';
	  $rearrange_settings[] = 'pinterest';
	}
	foreach ($rearrange_settings as $key=>$value) { 
	  $sharescript .= '"' .$value .'",';
	}
	$sharescript .=']; $u = LoginRadius.user_settings; $u.apikey= "'.$API_KEY.'"; $i.size = '.$sharesize.';$i.left = "'.$vershreleft.'"; $i.top = "'.$vershretop.'";$i.right = "'.$vershreright.'";$i.bottom = "'.$vershrebottom.'"; $i.show("lrsharecontainer"); }); </script>';
	$counter = Configuration::get('choosecounter')? Configuration::get('choosecounter'):"0";
	if ($counter == '0') {
	  $ishorizontal = 'true';
	  $counter_type = 'horizontal';
	}
	else if ($counter == '1') {
	  $ishorizontal = 'true';
	  $counter_type = 'vertical';
	}
	else if ($counter == '2') {
	  $ishorizontal = 'false';
	  $counter_type = 'horizontal';
	}
	else if ($counter == '3') {
	  $ishorizontal = 'false';
	  $counter_type = 'vertical';
	}
	if ($choosecounterpos == '0') {
	  $vercnttop = $verticalcountertopoffset;
	  $vercntright = '';
	  $vercntbottom = '';
	  $vercntleft = '0px';
	}
	else if ($choosecounterpos == '1') {
	  $vercnttop = $verticalcountertopoffset;
	  $vercntright = '0px';
	  $vercntbottom = '';
	  $vercntleft = '';
	}
	else if ($choosecounterpos == '2') {
	  $vercnttop = $verticalcountertopoffset;
	  $vercntright = '';
	  $vercntbottom = is_numeric(Configuration::get('verticalsharetopoffset'))? '' : '0px';
	  $vercntleft = '0px';
	}
	else if ($choosecounterpos == '3') {
	  $vercnttop = $verticalcountertopoffset;
	  $vercntright = '0px';
	  $vercntbottom = is_numeric(Configuration::get('verticalsharetopoffset'))? '' : '0px';
	  $vercntleft = '';
	}
	else {
	  $vercnttop = $verticalcountertopoffset;
	  $vercntright = '';
	  $vercntbottom = '';
	  $vercntleft = '';
	}
	$counterscript= '<script type="text/javascript">var islrsharing = true; var islrsocialcounter = true;</script> <script type="text/javascript" src="//share.loginradius.com/Content/js/LoginRadius.js"></script> <script type="text/javascript"> LoginRadius.util.ready(function () { $SC.Providers.Selected = [';
	$countericons = unserialize(Configuration::get('countericons'));
	$counter_provider=$countericons;
	if(empty($counter_provider)) {
	  $countericons[] = 'Pinterest Pin it';
	  $countericons[] = 'Facebook Like';
	  $countericons[] = 'Google+ Share';
	  $countericons[] = 'Twitter Tweet';
	  $countericons[] = 'Hybridshare';
	}
	foreach ($countericons as $key=>$value) { 
	  $counterscript .= '"' .$value .'",';
	}
	$counterscript .=']; $S = $SC.Interface.simple; $S.isHorizontal = '.$ishorizontal.'; $S.countertype = "'.$counter_type.'"; $S.left = "'.$vercntleft.'"; $S.top = "'.$vercnttop.'";$S.right = "'.$vercntright.'";$S.bottom = "'.$vercntbottom.'";$S.show("lrcounter_simplebox"); }); </script>';
	return $script= '<script src="//hub.loginradius.com/include/js/LoginRadius.js" ></script> <script type="text/javascript"> 
	function loginradius_interface() { $ui = LoginRadius_SocialLogin.lr_login_settings;$ui.interfacesize = "";$ui.apikey = "'.$API_KEY.'";$ui.callback="'.$loc.'"; $ui.lrinterfacecontainer ="interfacecontainerdiv"; LoginRadius_SocialLogin.init(options); }
	var options={}; options.login=true; LoginRadius_SocialLogin.util.ready(loginradius_interface); </script>'.$sharescript.''.$counterscript;
  }
  
  /*
  *  home hook that showing share and counter widget at home page. 
  */
  public function hookHome($params) {
	global $smarty;
	$sharingpretext=(Configuration::get('chooseshare')==4 || Configuration::get('chooseshare')==5) ? '' : Configuration::get('social_share_pretext');
	$counterpretext=(Configuration::get('choosecounter')==2 || Configuration::get('choosecounter')==3) ? '' : Configuration::get('social_counter_pretext');
	if( Configuration::get ('social_share_home')=='1') {
	  if( Configuration::get('enable_social_sharing')=='1') {
	    $sharing='<b>'.$sharingpretext.'</b><br/><div class="lrsharecontainer"></div>';
	    $smarty->assign( 'sharing', $sharing ); 
	  }
	}
	if( Configuration::get ('social_counter_home')=='1') {
	  if( Configuration::get('enable_social_counter')=='1') {
	    $counter='<b>'.$counterpretext.'</b><br/><div class="lrcounter_simplebox"></div>';
	    $smarty->assign( 'counter', $counter ); 
	  }
	}
	if( Configuration::get('enable_social_sharing')=='1' || Configuration::get('enable_social_counter')=='1') {
	  return $this->display( __FILE__, 'sharing.tpl' );
	}
  }
  
  /*
  *  Invoice hook that showing share and counter widget at Invoice page. 
  */
  public function hookInvoice($params){
	global $smarty;
	$sharingpretext=(Configuration::get('chooseshare')==4 || Configuration::get('chooseshare')==5) ? '' : Configuration::get('social_share_pretext');
	$counterpretext=(Configuration::get('choosecounter')==2 || Configuration::get('choosecounter')==3) ? '' : Configuration::get('social_counter_pretext');
	if( Configuration::get('enable_social_sharing')=='1') {
	  $sharing='<b>'.$sharingpretext.'</b><br/><div class="lrsharecontainer"></div>';
	  $smarty->assign( 'sharing', $sharing ); 
	}
	if( Configuration::get('enable_social_counter')=='1') {
	  $counter='<b>'.$counterpretext.'</b><div class="lrcounter_simplebox"></div>';
	  $smarty->assign( 'counter', $counter ); 
	}
	if( Configuration::get('enable_social_sharing')=='1' || Configuration::get('enable_social_counter')=='1') {
	  return $this->display( __FILE__, 'sharing.tpl' );
	}
  }
  
  /*
  *  Cart hook that showing share and counter widget at Cart page. 
  */
  public function hookShoppingCart($params){
	global $smarty;
	$sharingpretext=(Configuration::get('chooseshare')==4 || Configuration::get('chooseshare')==5) ? '' : Configuration::get('social_share_pretext');
	$counterpretext=(Configuration::get('choosecounter')==2 || Configuration::get('choosecounter')==3) ? '' : Configuration::get('social_counter_pretext');
	if( Configuration::get ('social_share_cart')=='1') {
	  if( Configuration::get('enable_social_sharing')=='1') {
	    $sharing='<b>'.$sharingpretext.'</b><br/><div class="lrsharecontainer"></div>';
	    $smarty->assign( 'sharing', $sharing ); 
	  }
	}
	if( Configuration::get ('social_counter_cart')=='1') {
	  if( Configuration::get('enable_social_counter')=='1') {
	    $counter='<b>'.$counterpretext.'</b><br/><div class="lrcounter_simplebox"></div>';
	    $smarty->assign( 'counter', $counter ); 
	  }
	}
	if( Configuration::get('enable_social_sharing')=='1' || Configuration::get('enable_social_counter')=='1') {
	  return $this->display( __FILE__, 'sharing.tpl' );
	}
  }
	 /*
  *  Product footer hook that showing share and counter widget at product footer page. 
  */
  public function hookProductFooter() {
	global $cookie, $link, $smarty;
	/* Product informations */
	$product = new Product((int)Tools::getValue('id_product'), false, (int)$cookie->id_lang);
	$this->currentproduct = $product;
	$productLink = $link->getProductLink($product);
	$sharingpretext=(Configuration::get('chooseshare')==4 || Configuration::get('chooseshare')==5) ? '' : Configuration::get('social_share_pretext');
	$counterpretext=(Configuration::get('choosecounter')==2 || Configuration::get('choosecounter')==3) ? '' : Configuration::get('social_counter_pretext');
	$language = strtolower(Language::getIsoById($cookie->id_lang));
	if( Configuration::get ('social_share_product')=='1') {
	  if( Configuration::get('enable_social_sharing')=='1') {
 	    $sharing='<b>'.$sharingpretext.'</b><br/><div class="lrsharecontainer"></div>';
	    $smarty->assign( 'sharing', $sharing ); 
	  }
	}
	if( Configuration::get ('social_counter_product')=='1') {
	  if( Configuration::get('enable_social_counter')=='1') {
	    $counter='<b>'.$counterpretext.'</b><br/><div class="lrcounter_simplebox"></div>';
	    $smarty->assign( 'counter', $counter );
	  }
	}
	if( Configuration::get('enable_social_sharing')=='1' || Configuration::get('enable_social_counter')=='1') {
	  return $this->display( __FILE__, 'sharing.tpl' );
	}
  }
  
   /*
  *  Top hook that Handle login functionality.
  */
  public function hookTop(){
	global $cookie;
	if (Context:: getContext()->customer->isLogged()){
	  include_once("LoginRadius.php");
	  $secret = trim(Configuration::get('API_SECRET'));
	  $lr_obj=new LoginRadius();
	  $userprofile=$lr_obj->loginradius_get_data($secret);
	  if(isset($_REQUEST['token']) && !empty($userprofile)){
	    include_once("sociallogin_user_data.php");
	    LrUser::linking($cookie,$userprofile);
	  }
	  if(isset($_REQUEST['id_provider'])) {
	    $getdata = Db::getInstance()->ExecuteS('SELECT * FROM '.pSQL(_DB_PREFIX_.'customer').' as c WHERE c.email='." '$cookie->email' ".' LIMIT 0,1');
	    $num=(!empty($getdata['0']['id_customer'])? $getdata['0']['id_customer']:"");
	    $deletequery="delete from ".pSQL(_DB_PREFIX_.'sociallogin')." where provider_id ='".$_REQUEST['id_provider']."'";
	    Db::getInstance()->Execute($deletequery);
		$cookie->lrmessage = 'Your Social identity has been removed successfully';
	    Tools::redirect($_SERVER['HTTP_REFERER']);
	  }
	}
	include_once(dirname(__FILE__)."/sociallogin_functions.php");
	if(isset($_REQUEST['token'])){
	  include_once("sociallogin_user_data.php");
	  $obj=new LrUser();
	}elseif(isset($_REQUEST['SL_VERIFY_EMAIL'])){
	  verifyEmail();
	}elseif(isset($_REQUEST['hidden_val'])){
	  global $cookie;
	if(isset($_POST['LoginRadius']) && $_POST['LoginRadius']=="Submit" && ($_REQUEST['hidden_val']==$cookie->SL_hidden) ){
	  $data=new stdClass;
	if(isset($_POST['LoginRadius'])) {
	  if(isset($_POST['SL_EMAIL'])){ $data->Email=$_POST['SL_EMAIL'];}
	  if(isset($_POST['SL_CITY'])){ $data->CITY=$_POST['SL_CITY'];}
	  if(isset($_POST['location-state'])){ $data->STATE=$_POST['location-state'];}
	  if(isset($_POST['SL_PHONE'])){ $data->PhoneNumbers=$_POST['SL_PHONE'];}
	  if(isset($_POST['SL_ADDRESS'])){ $data->ADDRESS=$_POST['SL_ADDRESS'];}
 	  if(isset($_POST['SL_ZIP_CODE'])){ $data->ZIPCODE=$_POST['SL_ZIP_CODE'];}
	  if(isset($_POST['SL_ADDRESS_ALIAS'])){ $data->ADDRESS_ALIAS=$_POST['SL_ADDRESS_ALIAS'];}
	  if(isset($_POST['location_country'])){ $data->Country=$_POST['location_country'];}
	}
	$ERROR_MESSAGE=Configuration::get('ERROR_MESSAGE');
	if(Configuration::get('user_require_field')=="1") {		
	  if(empty($data->CITY) || empty($data->STATE) || empty($data->PhoneNumbers) || empty($data->ADDRESS) || empty($data->ZIPCODE)|| empty($data->Country) || empty($data->ADDRESS_ALIAS)) {
	  popUpWindow('<p style="color:red; padding:0px;">'.$ERROR_MESSAGE.'</p>');
	  return;
	  }
	}
	if(isset($data->Email) && !empty($data->Email)){
	  $email=$data->Email;
	} else {
	  $email= $cookie->Email;
	}
	if(empty($email) && Configuration::get('EMAIL_REQ')=="0" ) {
	  popUpWindow('<p style="color:red; padding:0px;">'.$ERROR_MESSAGE.'</p>');
	  return;
	}
	else if (!Validate::isEmail($email) && Configuration::get('EMAIL_REQ')=="0"){
	 $check=new stdClass;
	  $check->Email= '';
	popUpWindow('<p style="color:red; padding:0px;">'.$ERROR_MESSAGE.'</p>',$check);
	  return;
	}
	if(Configuration::get('user_require_field')=="1") {	
	 $check=new stdClass;
	  if(!empty($data->Country) && !empty($data->ZIPCODE)) {
	    if(isset($data->Email) && !empty($data->Email)){
	    $check->Email= '';
	    }
	    else 
	      $check->Email= $email;
	    $postcode=trim($data->ZIPCODE);
	    $zip_code = Db::getInstance()->executeS('SELECT * FROM '._DB_PREFIX_.'country c WHERE c.iso_code = "'.$data->Country.'"');
   	    $zip_code_format=$zip_code['0']['zip_code_format'];
	    if(!empty($zip_code_format)) {
	      $zip_regexp = '/^'.$zip_code_format.'$/ui';
	      $zip_regexp = str_replace(' ', '( |)', $zip_regexp);
	      $zip_regexp = str_replace('-', '(-|)', $zip_regexp);
	      $zip_regexp = str_replace('N', '[0-9]', $zip_regexp);
	      $zip_regexp = str_replace('L', '[a-zA-Z]', $zip_regexp);
	      $zip_regexp = str_replace('C', $data->Country, $zip_regexp);
	      if (!preg_match($zip_regexp, $postcode)) {
	        popUpWindow('<p style="color:red; padding:0px;margin-bottom: 3px;">'.$ERROR_MESSAGE.'</p><p 
	style="color: red;margin-bottom: -20px; font-size: 10px;">Your zip/postal code is incorrect.'.'<br />'.'Must be typed as follows:'.' '.str_replace('C', $data->Country, str_replace('N', '0', str_replace('L', 'A', $zip_code_format))).'</p>',$check);
	        return;
	      }
	    }
	  }
	}
	SL_data_save($data);
	}else{
	  $home = getHome();
	  $msgg="Cookie has been deleted, please try again.";
	  popup_verify($msgg,$home);
	  }
	}
  }
  
  /*
  *  customer account hook that show tpl for Social linking.
  */
  public function hookDisplayCustomerAccount($params) {
    $this->smarty->assign('in_footer', false);
	return $this->display(__FILE__, 'my-account.tpl');
  }
  
  /*
  *  my account hook that show tpl for Social linking.
  */
  public function hookMyAccountBlock($params) {
	$this->smarty->assign('in_footer', true);
	return $this->display(__FILE__, 'my-account.tpl');
  }
  
   /*
  * Install hook that  register hook which used by social Login.
  */
  public function install(){
	if(!parent::install()
	  || !$this->registerHook( 'leftColumn' )
	  || !$this->registerHook( 'createAccountTop' )
	  || !$this->registerHook( 'rightColumn' )
	  || !$this->registerHook( 'top' )
	  || !$this->registerHook('Header')
	  || !$this->registerHook('Home')
	  || !$this->registerHook('Invoice')
	  || !$this->registerHook('ShoppingCart')
	  || !$this->registerHook('productfooter')
	  || !$this->registerHook('customerAccount')
	  || !$this->registerHook('myAccountBlock')
	)
	return false;
	$this->db_tbl();
	return true;
  }
    public function db_tbl(){
	$tbl=pSQL(_DB_PREFIX_.'sociallogin');
	$CREATE_TABLE=<<<SQLQUERY
	CREATE TABLE IF NOT EXISTS `$tbl` (
	`id_customer` int(10) unsigned NOT NULL COMMENT 'foreign key of customers.',
	`provider_id` varchar(100) NOT NULL,
	`Provider_name` varchar(100),
	`rand` varchar(20),
	`verified` tinyint(1) NOT NULL
	)
SQLQUERY;
	Db::getInstance()->Execute($CREATE_TABLE);
	}

/*
  *  Login Radius Admin UI.
  */    
  public function getContent(){
	$html = '';
	if(Tools::isSubmit('submitKeys'))  {
	  $API_SECRET=trim(Tools::getValue('API_SECRET'));
	  $API_KEY=trim(Tools::getValue('API_KEY'));
	  if($API_KEY==''){
		$html .= $this->displayError($this->l('Please enter a valid API Key'));
	  }
		elseif($API_SECRET==''){
		$html .= $this->displayError($this->l('Please enter a valid API Secret'));
	   }
		elseif($API_SECRET==$API_KEY){
		$html .= $this->displayError($this->l('Please enter a valid API Key and  Secret'));
	  }
	  elseif($API_SECRET!='' && $API_KEY!='' && preg_match('/^\{?[A-Z0-9]{8}-[A-Z0-9]{4}-[A-Z0-9]{4}-[A-Z0-9]{4}-[A-Z0-9]{12}\}?$/i', $API_KEY) && preg_match('/^\{?[A-Z0-9]{8}-[A-Z0-9]{4}-[A-Z0-9]{4}-[A-Z0-9]{4}-[A-Z0-9]{12}\}?$/i', $API_SECRET)){
	  $val = trim(Tools::getValue('LoginRadius_redirect'));
	  if($val=="url"){
	  $val = trim(Tools::getValue('redirecturl'));//redirecturl
	  }
	  Configuration::updateValue('LoginRadius_redirect', Tools::getValue('LoginRadius_redirect'));
	  Configuration::updateValue('redirecturl',Tools::getValue('redirecturl'));	
	  Configuration::updateValue('SOCIAL_SHARE_CODE',Tools::getValue('SOCIAL_SHARE_CODE'));	
	  Configuration::updateValue('API_KEY', trim(Tools::getValue('API_KEY')));
	  Configuration::updateValue('API_SECRET', trim(Tools::getValue('API_SECRET')));
	  Configuration::updateValue('TITLE', Tools::getValue('TITLE',"Please login with"));
	  Configuration::updateValue('EMAIL_REQ',(int)( Tools::getValue('EMAIL_REQ')));
	  Configuration::updateValue('SEND_REQ',(int)( Tools::getValue('SEND_REQ')));
	  Configuration::updateValue('CURL_REQ',(int)( Tools::getValue('CURL_REQ')));	
	  Configuration::updateValue('ACC_MAP',(int)( Tools::getValue('ACC_MAP')));
	  Configuration::updateValue('SOCIAL_SHARE_CODE', Tools::getValue('SOCIAL_SHARE_CODE'));
	  Configuration::updateValue('SOCIAL_COUNTER_CODE',  Tools::getValue('SOCIAL_COUNTER_CODE'));
	  Configuration::updateValue('ERROR_MESSAGE',  Tools::getValue('ERROR_MESSAGE'));
	  Configuration::updateValue('POPUP_TITLE', Tools::getValue('POPUP_TITLE'));
	  Configuration::updateValue('enable_social_sharing',(int)( Tools::getValue('enable_social_sharing')));
	  Configuration::updateValue('social_share_home',(int)( Tools::getValue('social_share_home')));
	  Configuration::updateValue('social_share_cart',(int)( Tools::getValue('social_share_cart')));
	  Configuration::updateValue('social_share_product',(int)( Tools::getValue('social_share_product')));
	  Configuration::updateValue('social_counter_home',(int)( Tools::getValue('social_counter_home')));
	  Configuration::updateValue('social_counter_cart',(int)( Tools::getValue('social_counter_cart')));
	  Configuration::updateValue('social_counter_product',(int)( Tools::getValue('social_counter_product')));
	  Configuration::updateValue('enable_social_counter',(int)( Tools::getValue('enable_social_counter')));
	  Configuration::updateValue('user_notification',Tools::getValue('user_notification'));
	  Configuration::updateValue('user_require_field',Tools::getValue('user_require_field'));
	  Configuration::updateValue('social_share_pretext',  Tools::getValue('social_share_pretext'),"Share it now!");
	  Configuration::updateValue('social_counter_pretext',  Tools::getValue('social_counter_pretext'));
	  Configuration::updateValue('chooseshare',  Tools::getValue('chooseshare'));
	  Configuration::updateValue('choosecounter',  Tools::getValue('choosecounter'));
	  Configuration::updateValue('choosesharepos',  Tools::getValue('choosesharepos'));
	  Configuration::updateValue('choosecounterpos',  Tools::getValue('choosecounterpos'));
	  Configuration::updateValue('rearrange_settings',  sizeof(Tools::getValue('rearrange_settings'))>0 ? serialize(Tools::getValue('rearrange_settings')) : "");
	  Configuration::updateValue('countericons',  sizeof(Tools::getValue('countericons'))>0 ? serialize(Tools::getValue('countericons')) : "");
	  Configuration::updateValue('verticalsharetopoffset',  Tools::getValue('verticalsharetopoffset'));
	  Configuration::updateValue('verticalcountertopoffset',  Tools::getValue('verticalcountertopoffset'));
	  $html .= $this->displayConfirmation($this->l('Settings updated.'));		
	  }else{
	  $html .= $this->displayError($this->l('API Key & API Secret not valid'));
	  }		
	}
	$API_KEY = trim(Configuration::get('API_KEY'));		
	$API_SECRET = trim(Configuration::get('API_SECRET'));
	$Title = Configuration::get('TITLE');
	$ERROR_MESSAGE=Configuration::get('ERROR_MESSAGE');
	$POPUP_TITLE=Configuration::get('POPUP_TITLE');
	$social_share_pretext=Configuration::get('social_share_pretext');
	$chooseshare= Configuration::get('chooseshare')? Configuration::get('chooseshare'):"0";
	$social_counter_pretext= Configuration::get('social_counter_pretext');
	$choosecounter= Configuration::get('choosecounter') ? Configuration::get('choosecounter') : "0";
	$LoginRadius_redirect=Configuration::get('LoginRadius_redirect');
	$redirecturl=Configuration::get('redirecturl');
	$rearrange_settings=Configuration::get('rearrange_settings');
	$countericons=Configuration::get('countericons');
	$choosesharepos=Configuration::get('choosesharepos');
	$verticalsharetopoffset=Configuration::get('verticalsharetopoffset');
	$choosecounterpos=Configuration::get('choosecounterpos');
	$verticalcountertopoffset=Configuration::get('verticalcountertopoffset');
	$selected="";			
	$redirect="";		
	$jsVal=1;
	$hori32 = "";
	$hori16 = "";
	$horithemelarge = "";
	$horithemesmall = "";
	$vertibox32 = "";
	$vertibox16 = "";
	$checked[0]="";		
	$checked[1]="";		
	$checked[2]="";		
	if ($chooseshare == '0' ) $hori32 = "checked='checked'";
	else if ($chooseshare == '1' ) $hori16 = "checked='checked'";
	else if ($chooseshare == '2' ) $horithemelarge = "checked='checked'";
	else if ($chooseshare == '3' ) $horithemesmall = "checked='checked'";
	else if ($chooseshare == '4' ) $vertibox32 = "checked='checked'";
	else if ($chooseshare == '5' ) $vertibox16 = "checked='checked'";
	else $hori32 = "checked='checked'";	
	$chori32 = "";
	$chori16 = "";
	$cvertibox32 = "";
	$cvertibox16 = "";
	if ($choosecounter == '0' ) $chori16 = "checked='checked'";
	else if ($choosecounter == '1' ) $chori32 = "checked='checked'";
	else if ($choosecounter == '2' ) $cvertibox32 = "checked='checked'";
	else if ($choosecounter == '3' ) $cvertibox16 = "checked='checked'";
	else $chori16 = "checked='checked'";
	if($LoginRadius_redirect=="profile"){
	$checked[1]='checked="checked"';
	}elseif ($LoginRadius_redirect=="url") {
	$checked[2]='checked="checked"';
	$redirect=$redirecturl;
	$jsVal=0;
	}
	else {
	$checked[0]='checked="checked"';
	}
	$autoshare=true;
	$autocounter=true;
	$enablefb = "";
	$enabletwitter = "";
	$enableprint = "";
	$enableemail = "";
	$enablegoogle = "";
	$enabledigg = "";
	$enablereddit = "";
	$enablevk = "";
	$enablegplus = "";
	$enabletumbler = "";
	$enablelinkedin = "";
	$enablemyspace = "";
	$enabledeli = "";
	$enableyahoo = "";
	$enablelive = "";
	$enablehyves = "";
	$enablednnkicks = "";
	$enablepin = "";
	$rearrange_settings = unserialize(Configuration::get('rearrange_settings'));
	$rearrnage_provider_list=$rearrange_settings;
	if(empty($rearrnage_provider_list)) {
	  $rearrnage_provider_list[] = 'facebook';
	  $rearrnage_provider_list[] = 'googleplus';
	  $rearrnage_provider_list[] = 'twitter';
	  $rearrnage_provider_list[] = 'linkedin';
	  $rearrnage_provider_list[] = 'pinterest';
	}
	foreach($rearrnage_provider_list as $provider){
	if($provider=='facebook'){ $enablefb="checked=checked";$autoshare=false; }
	if($provider=='twitter'){ $enabletwitter="checked=checked";$autoshare=false; }
	if($provider=='print'){ $enableprint="checked=checked";$autoshare=false; }
	if($provider=='email'){ $enableemail="checked=checked";$autoshare=false; }
	if($provider=='google'){ $enablegoogle="checked=checked";$autoshare=false; }
	if($provider=='digg'){ $enabledigg="checked=checked";$autoshare=false; }
	if($provider=='reddit'){ $enablereddit="checked=checked";$autoshare=false; }
	if($provider=='vkontakte'){ $enablevk="checked=checked";$autoshare=false; }
	if($provider=='googleplus'){ $enablegplus="checked=checked";$autoshare=false; }
	if($provider=='tumblr'){ $enabletumbler="checked=checked";$autoshare=false; }
	if($provider=='linkedin'){ $enablelinkedin="checked=checked";$autoshare=false; }
	if($provider=='myspace'){ $enablemyspace="checked=checked";$autoshare=false; }
	if($provider=='delicious'){ $enabledeli="checked=checked";$autoshare=false; }
	if($provider=='yahoo'){ $enableyahoo="checked=checked";$autoshare=false; }
	if($provider=='live'){ $enablelive="checked=checked";$autoshare=false; }
	if($provider=='hyves'){ $enablehyves="checked=checked";$autoshare=false; }
	if($provider=='dotnetkicks'){ $enablednnkicks="checked=checked";$autoshare=false; }
	if($provider=='pinterest'){ $enablepin="checked=checked";$autoshare=false; }
	}
/*	if($autoshare==true) {
	$enablefb="checked=checked";
	$enabletwitter="checked=checked";
	$enablepin="checked=checked";
	$enablegplus="checked=checked";
	$enablelinkedin="checked=checked";
	}*/
	$enablefblike = "";
	$enablefbrecommend = "";
	$enablefbsend = "";
	$enablegplusone = "";
	$enablegshare = "";
	$enablelinkedinshare = "";
	$enabletweet = "";
	$enablestbadge = "";
	$enableredditshare = "";
	$enablepinterestshare = "";
	$enablehybridshare = "";
	$countericons = unserialize(Configuration::get('countericons'));
	$counter_provider_list=$countericons;
	if(empty($counter_provider_list)) {
	  $counter_provider_list[] = 'Pinterest Pin it';
	  $counter_provider_list[] = 'Facebook Like';
	  $counter_provider_list[] = 'Google+ Share';
	  $counter_provider_list[] = 'Twitter Tweet';
	  $counter_provider_list[] = 'Hybridshare';
	}
	foreach($counter_provider_list as $provider_counter){
	  if($provider_counter=='Facebook Like'){ $enablefblike="checked=checked";$autocounter=false; }
	  if($provider_counter=='Facebook Recommend'){ $enablefbrecommend="checked=checked";$autocounter=false; }
	  if($provider_counter=='Facebook Send'){ $enablefbsend="checked=checked";$autocounter=false; }
	  if($provider_counter=='Google+ +1'){ $enablegplusone="checked=checked";$autocounter=false; }
	  if($provider_counter=='Google+ Share'){ $enablegshare="checked=checked";$autocounter=false; }
	  if($provider_counter=='LinkedIn Share'){ $enablelinkedinshare="checked=checked";$autocounter=false; }
	  if($provider_counter=='Twitter Tweet'){ $enabletweet="checked=checked";$autocounter=false; }
	  if($provider_counter=='StumbleUpon Badge'){ $enablestbadge="checked=checked";$autocounter=false; }
	  if($provider_counter=='Reddit'){ $enableredditshare="checked=checked";$autocounter=false; }
	  if($provider_counter=='Pinterest Pin it'){ $enablepinterestshare="checked=checked";$autocounter=false; }
	  if($provider_counter=='Hybridshare'){ $enablehybridshare="checked=checked";$autocounter=false; }
	}
/*	if($autocounter==true) {
	  $enablefblike="checked=checked";
	  $enabletweet="checked=checked";
	  $enablegshare="checked=checked";
	  $enablepinterestshare="checked=checked";
	  $enablehybridshare="checked=checked";
	}*/
	// LoginRadius admin UI.
	$html.='
	<link href="'.__PS_BASE_URI__.'modules/sociallogin/socialloginandsocialshare.css" rel="stylesheet" type="text/css" media="all" />
	<script type="text/javascript" src="'.__PS_BASE_URI__.'modules/sociallogin/checkapi.js"></script>
	<script type="text/javascript" src="'.__PS_BASE_URI__.'modules/sociallogin/jquery.ui.sortable.min.js"></script>
	<script type="text/javascript">
	function panelshow(id) {
	if(id=="first") {
	document.getElementById(id).style.display="block";
	document.getElementById("second").style.display="none";
	document.getElementById("third").style.display="none";
	document.getElementById("panel1").className="panel1 open";
	document.getElementById("panel2").className="panel1 closed";
	document.getElementById("panel3").className="panel3 closed";
	}
	if(id=="second") {
	document.getElementById("first").style.display="none";
	document.getElementById(id).style.display="block";
	document.getElementById("third").style.display="none";
	document.getElementById("panel1").className="panel1 closed";
	document.getElementById("panel2").className="panel1 open";
	document.getElementById("panel3").className="panel3 closed";
	}
	if(id=="third") {
	document.getElementById("first").style.display="none";
	document.getElementById("second").style.display="none";
	document.getElementById(id).style.display="block";
	document.getElementById("panel1").className="panel1 closed";
	document.getElementById("panel2").className="panel1 closed";
	document.getElementById("panel3").className="panel3 open";
	}
	}
	$(document).ready(function() {
	function m(n, d){
	P = Math.pow;
	R = Math.round
	d = P(10, d);
	i = 7;
	while(i) {
	(s = P(10, i-- * 3)) <= n && (n = R(n * d / s) / d + "KMGTPE"[i])
	}
	return n;
	}
	$.ajax({
	url: "http://api.twitter.com/1/users/show.json",
	data: {
	screen_name: "LoginRadius"
	},
	dataType: "jsonp",
	success: function(data) {
	count = data.followers_count;
	$("#followers").html(m(count, 1));
	}
	});
	});
	$(document).ready(function() {
	$("div.productTabs").find("a").each(function() {
	$(this).attr("href", "javascript:void(0)");
	});
	$("div.productTabs a").click(function() {
	var id = $(this).attr("id");
	$(".nav-profile").removeClass("selected");
	$(this).addClass("selected");
	$(".tab-profile").hide()
	$("."+id).show();
	});
	$(function() {
	$( "#sortable" ).sortable();
	$( "#sortable" ).disableSelection();
	});
	});
	function hidetextbox(hide){		
	if(hide==1){
	$("#redirecturl").hide();		
	}else{
	$("#redirecturl").show();	
	}		
	}		
	window.onload = function (){		
	hidetextbox('.$jsVal.');		
	}	
	</script>
	<div>
	<div style="float:left; width:70%;">
	<div>
	<fieldset class="sociallogin_form sociallogin_form_main" style="background:#EAF7FF; border: 1px solid #B3E2FF;">
	<div class="row row_title" style="color: #000000; font-weight:normal; background:none;">
	<strong>Thank you for installing the LoginRadius Prestashop Extension!</strong>
	</div>
	<div class="row" style="color: #000000;width:90%; line-height:160%; background:none;">
	To activate the extension, please configure it and manage the social networks from you LoginRadius account. If you do not have an account, click<a href="http://www.loginradius.com" target="_blank"> here </a>and create one for FREE!
	</div>
	<div class="row" style="color: #000000; width:90%; line-height:160%; background:none;">
	We also have Social Plugin for <a href="https://www.loginradius.com/loginradius-cms-social-plugins/joomla-extension" target="_blank">Joomla</a>,<a href="https://www.loginradius.com/loginradius-cms-social-plugins/wordpress-plugin" target="_blank">WordPress</a>, <a href="https://www.loginradius.com/loginradius-cms-social-plugins/drupal-module" target="_blank">Drupal</a>, <a href="https://www.loginradius.com/loginradius-cms-social-plugins/vbulletin-forum-add-on" target="_blank">vBulletin</a>, <a href="https://www.loginradius.com/loginradius-cms-social-plugins/vanilla-forum-add-on" target="_blank">VanillaForum</a>, <a href="https://www.loginradius.com/loginradius-cms-social-plugins/magento-extension" target="_blank">Magento</a>, <a href="https://www.loginradius.com/loginradius-cms-social-plugins/oscommerce-addon" target="_blank">osCommerce</a>, <a href="https://www.loginradius.com/loginradius-cms-social-plugins/x-cart-module" target="_blank">X-Cart</a>, <a href="https://www.loginradius.com/loginradius-cms-social-plugins/zencart-plugin" target="_blank">Zen-Cart</a>, <a href="https://www.loginradius.com/loginradius-cms-social-plugins/dotnetnuke-module" target="_blank">DotNetNuke</a> and <a href="https://www.loginradius.com/loginradius-cms-social-plugins/blogengine-extension" target="_blank">BlogEngine</a>!
	</div>
	<div class="row row_button" style="background:none; border:none; background:none;">
	<div class="button2-left">
	<div class="blank" style="margin:0 0 10px 0;">
	<div class="button" style="float:left; cursor:pointer;">  <a class="modal" href="http://www.loginradius.com/" target="_blank">Set up my FREE account!</a></div>
	</div>
	</div>
	</div>
	</fieldset>
	</div>
	<form action="'.$_SERVER['REQUEST_URI'].'" method="post" enctype="multipart/form-data">
	<dl id="pane" class="tabs">
	<dt class="panel1 open" id="panel1"  style="cursor:pointer;" onclick=javascript:panelshow("first") ><span>Social Login</span></dt>
	<dt class="panel2 closed" id="panel2" style="cursor:pointer;" onclick=javascript:panelshow("second") ><span>Social Share</span></dt>
	<dt class="panel3 closed" id="panel3" style="cursor:pointer;" onclick=javascript:panelshow("third") ><span>Social Counter</span></dt>
	</dl>
	<div class="current">
	<dd><div style="display:block;" id="first">	
	<table class="form-table sociallogin_table">
	<tr>
	<th class="head" colspan="2">LoginRadius API Settings</small></th>
	</tr>
	<tr >
	<input id="connection_url" type="hidden" value="'.__PS_BASE_URI__.'" />
	<td colspan="2" ><span class="subhead"> To activate the plugin, insert LoginRadius API Key & Secret<a href="http://support.loginradius.com/customer/portal/articles/677100-how-to-get-loginradius-api-key-and-secret" target="_blank"> (How to get it?) </a></span>
	<br/><br />
	API Key &nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;<input type="text" size="50" name="API_KEY" id="API_KEY" value="'.$API_KEY.'" />
	<br /><br />
	API Secret	&nbsp;&nbsp;<input type="text" name="API_SECRET" id="API_SECRET"  size="50" value="'.$API_SECRET.'" />
	</td>
	</tr>
	<tr class="row_white">
	<td colspan="2" ><span class="subhead">What API Connection Method do you prefer to enable API communication?</span>
	<br /><br />
	<input type="radio" name="CURL_REQ" id="CURL_REQ" value="0" '.(!Tools::getValue('CURL_REQ', Configuration::get('CURL_REQ')) ? 'checked="checked" ' : '').' />Use cURL (Recommended API connection method but sometimes this is disabled at hosting server.) 
	<br /><br />
	<input type="radio" name="CURL_REQ" id="FSOCKOPEN_REQ" value="1" '.(Tools::getValue('CURL_REQ', Configuration::get('CURL_REQ')) ? 'checked="checked" ' : '').'/>Use FSOCKOPEN (Choose this option, if cURL is disabled at your hosting server.) 
	</td>
	</tr>
	<tr class="row_white">
	<td>
	<div class="row row_button" style="background:none;">
	<div class="button2-left">
	<div class="blank">
	<input type="button" class="button" name="verify_api_setting"  size="50" value="Verify API Settings" onclick="MakeRequest()" style="cursor:pointer;"/>
	</a>
	</div>
	</div>
	</div>
	</td>
	<td><div id="ajaxDiv" style="font-weight:bold;"></div></td>
	</tr>
	</table>
	<table class="form-table sociallogin_table">
	<tr>
	<th class="head" colspan="2">LoginRadius Basic Settings</small></th>
	</tr>
	
	<tr>
	<td colspan="2" ><span class="subhead">Where do you want to redirect your users after successfully logging in?</span><br /><br />
	<input name="LoginRadius_redirect" value="backpage" type="radio" onclick="javascript:hidetextbox(1);" '.$checked[0].' /> Redirect to Same page (Same as traditional login) <br/>
	<input name="LoginRadius_redirect" value="profile" type="radio" onclick="javascript:hidetextbox(1);" '.$checked[1].' /> Redirect to the profile <br/>
	<input name="LoginRadius_redirect" value="url" type="radio" onclick="javascript:hidetextbox(0);" '.$checked[2].' /> Redirect to the following url:<br/>
	<input style ="display:none;" type="text" name="redirecturl" id="redirecturl"  size="40" value="'.$redirect.'" />
	</td>
	</tr>	
	<tr> <td colspan="2" >Please enter the Title to be shown on Social Login interface<br/><input type="text" name="TITLE"  size="50" value="'.$Title.'" /></td></tr>
	<tr class="row_white">
	<td colspan="2" ><span class="subhead">Do you want your existing user to automatically link to their social accounts in case their Prestashop account email address matches with their social account email ID?</span><br /><br />
	
	<input type="radio" name="ACC_MAP" value="0" '.(!Tools::getValue('ACC_MAP', Configuration::get('ACC_MAP')) ? 'checked="checked" ' : '').'/>YES, link social accounts to Prestashop account<br /><br />
	<input type="radio" name="ACC_MAP" value="1"  '.(Tools::getValue('ACC_MAP', Configuration::get('ACC_MAP')) ? 'checked="checked" ' : '').'/> NO, I want my existing users to continue using native Prestashop login
	</td>
	</tr>
	<tr>
	<td colspan="2" ><span class="subhead">Do you want to send emails to admin after new User registrations at your website?</span><br /><br />
	
	<input type="radio" name="SEND_REQ" value="1" '.(Tools::getValue('SEND_REQ', Configuration::get('SEND_REQ')) ? 'checked="checked" ' : '').'/> Yes&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;
	<input type="radio" name="SEND_REQ" value="0"  '.(!Tools::getValue('SEND_REQ', Configuration::get('SEND_REQ')) ? 'checked="checked" ' : '').'/> No
	</td>
	</tr>
	<tr class="row_white">
	<td colspan="2" ><span class="subhead">Do you want to send emails to customer after new User registrations at your website?</span><br /><br />
	
	<input type="radio" name="user_notification" value="0" '.(!Tools::getValue('user_notification', Configuration::get('user_notification')) ? 'checked="checked" ' : '').'/> Yes&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;
	<input type="radio" name="user_notification" value="1"  '.(Tools::getValue('user_notification', Configuration::get('user_notification')) ? 'checked="checked" ' : '').'/> No
	</td>
	</tr>
	<tr >
	<td colspan="2" ><span class="subhead">Do you want users to provide required Prestashop profile fields before completing registration process? (A pop-up will open asking user to provide details of the fields not coming from Social ID provider)</span><br /><br />
	
	<input type="radio" name="user_require_field" value="1" '.(Tools::getValue('user_require_field', Configuration::get('user_require_field')) ? 'checked="checked" ' : '').'/> Yes&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;
	<input type="radio" name="user_require_field" value="0"  '.(!Tools::getValue('user_require_field', Configuration::get('user_require_field')) ? 'checked="checked" ' : '').'/> No
	</td>
	</tr>
	
	<tr class="row_white">
	<td colspan="2" ><span class="subhead">A few ID Providers do not supply users e-mail IDs as part of user profile data. Do you want users to provide their email IDs before completing registration process?</span><br /><br />
	
	<input type="radio" name="EMAIL_REQ" value="0" '.(!Tools::getValue('EMAIL_REQ', Configuration::get('EMAIL_REQ')) ? 'checked="checked" ' : '').' />YES, get real email IDs from the users (Ask users to enter their email IDs in a pop-up)<br /><br />
	<input type="radio" name="EMAIL_REQ" value="1" '.(Tools::getValue('EMAIL_REQ', Configuration::get('EMAIL_REQ')) ? 'checked="checked" ' : '').'/>NO, just auto-generate random email IDs for the users
	</td>
	</tr>
	<tr >
	<input id="connection_url" type="hidden" value="" />
	</tr><tr><td>
	<span class="subhead">Please enter the message to be displayed to the user in the pop-up</span><br /><br /><input type="text" name="POPUP_TITLE"  size="50" value="'.(empty($POPUP_TITLE)?'Please fill the following details to complete the registration':$POPUP_TITLE).'" />
	<br /></td></tr>
	<tr class="row_white">
	<td>
	<span class="subhead">Please enter the message to be shown to the user in case of an invalid entry in popup</span><br /><br /><input type="text" name="ERROR_MESSAGE"  size="50" value="'.(empty($ERROR_MESSAGE)?"Please enter the following fields":$ERROR_MESSAGE).'" />	
	</td>
	</tr>
	</table>
	</div></dd>
	<!-- social share -->
	<dd><div style="display:none;" id="second">
	<table class="form-table sociallogin_table">
	<tr>
	<th class="head" colspan="2">LoginRadius Social Share Settings</small></th>
	</tr>
	<tr><td colspan="2" ><span class="subhead">Do you want to enable Social Sharing for your website?</span><br /><br />
	<input type="radio" name="enable_social_sharing" value="1" '.(Tools::getValue('enable_social_sharing', Configuration::get('enable_social_sharing'))==1 ? 'checked="checked"' : '').' />Yes&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;
	<input type="radio" name="enable_social_sharing" value="0" '.(Tools::getValue('enable_social_sharing', Configuration::get('enable_social_sharing'))==0 ? 'checked="checked"' : '').' />No
	</td>
	</tr>
	<tr class="row_white">
	<td colspan="2" ><span class="subhead">Enter the text that you wish to be displayed above the Social Sharing Interface. Leave the field blank if you dont want any text to be displayed.</span>
	<br/><input type="text" name="social_share_pretext" value="'.$social_share_pretext.'" />
	</td>
	</tr>
	<tr>
	<td colspan="2" ><span class="subhead">What Social Sharing widget theme do you want to use across your website?</span><br /><br />';
	$chooseshare =Configuration::get('chooseshare');
	if($chooseshare == '' || $chooseshare == '0' || $chooseshare == '1' || $chooseshare == '2' || $chooseshare == '3'){
	  $style_visible= 'style="position:absolute;border-bottom:8px solid #ffffff; border-right:8px solid transparent; border-left:8px solid transparent; margin:-18px 0 0 2px"';}
   else{ $style_visible='style="position:absolute;border-bottom:8px solid #ffffff;border-right:8px solid transparent; border-left:8px solid transparent; margin:-18px 0 0 70px;"';}
	$html.='<a id="mymodal" href="javascript:void(0);" onclick="Makehorivisible();"><b>Horizontal</b></a> &nbsp;|&nbsp; 
	<a class="mymodal" href="javascript:void(0);" onclick="Makevertivisible();"><b>Vertical</b></a>
	<div style="border:#dddddd 1px solid; padding:10px; background:#FFFFFF; margin:10px 0 0 0;">
	<span id = "arrow" '.$style_visible.'></span>';
	$chooseshare =Configuration::get('chooseshare');
	if($chooseshare == '' || $chooseshare == '0' || $chooseshare == '1' || $chooseshare == '2' || $chooseshare == '3'){$show_block='style=display:block';}
	else {$show_block='style=display:none';}
	$html.='<div id="sharehorizontal" '.$show_block.'>
	<input name="chooseshare" id = "hori32" '.$hori32.' type="radio"  value="0" style="margin: 2px 10px 0 0; display: block; float: left !important;" /> <img src = "../modules/sociallogin/img/horizonSharing32.png"/><br /><br />
	<input name="chooseshare" id = "hori16" '.$hori16.' type="radio" value="1" style="margin: 2px 10px 0 0; display: block; float: left !important;" /> <img src = "../modules/sociallogin/img/horizonSharing16.png" /><br /><br />
	<input name="chooseshare" id = "horithemelarge" '.$horithemelarge.' type="radio" value="2" style="margin: 2px 10px 0 0; display: block; float: left !important;" /> <img src = "../modules/sociallogin/img/single-image-theme-large.png" /><br /><br />
	<input name="chooseshare" id = "horithemesmall" '.$horithemesmall.' type="radio" value="3" style="margin: 2px 10px 0 0; display: block; float: left !important;" /> <img src = "../modules/sociallogin/img/single-image-theme-small.png" />
	</div>';
	$chooseshare =Configuration::get('chooseshare');
	if($chooseshare== '4' || $chooseshare== '5'){$show_blockvertical='style=display:block';} 
	else {$show_blockvertical='style=display:none';}
	$html.='<div id="sharevertical" '.$show_blockvertical.'>
	<input name="chooseshare" id = "vertibox32" '.$vertibox32.' type="radio" value="4" style="vertical-align:top"/> <img src =  "../modules/sociallogin/img/32VerticlewithBox.png" />
	<input name="chooseshare" id = "vertibox16" '.$vertibox16.' type="radio" value="5" style="vertical-align:top"/> <img src = "../modules/sociallogin/img/16VerticlewithBox.png" style="vertical-align:top"/><br /><br />
	<div style="overflow:auto; background:#EBEBEB; padding:10px;">
	<p style="margin:0 0 6px 0; padding:0px;color:#000000;"><strong>Select the position of Social Sharing widget</strong></p>';
	$stopleft = "";
	$stopright = "";
	$sbottomleft = "";
	$sbottomright = "";
	$choosesharepos = (isset($choosesharepos) ? $choosesharepos : "0");
	$verticalsharetopoffset=(isset($verticalsharetopoffset) ? $verticalsharetopoffset : "");
	if ($choosesharepos == '0' ) $stopleft = "checked=checked";
	else if ($choosesharepos == '1' ) $stopright = "checked=checked";
	else if ($choosesharepos == '2' ) $sbottomleft = "checked=checked";
	else if ($choosesharepos == '3' ) $sbottomright = "checked=checked";
	else $stopleft = "checked=checked";
	$html.='<input name="choosesharepos" id = "topleft" type="radio" '.$stopleft.' value="0" />Top Left<br /> 
	<input name="choosesharepos" id = "topright" type="radio" '.$stopright.' value="1" />Top Right<br />
	<input name="choosesharepos" id = "bottomleft" type="radio" '.$sbottomleft.' value="2" />Bottom Left<br /> 
	<input name="choosesharepos" id = "bottomright" type="radio" '.$sbottomright.' value="3" />Bottom Right<br /><br />
	<p style="margin:0 0 6px 0; padding:0px;color:#000000;"><strong> <strong>Specify distance of vertical sharing interface from top. (Leave empty for default behaviour)</strong><a title="Enter a number (For example - 200). It will set the \'top\' CSS attribute of the interface to the value specified. Increase in the number pushes interface towards bottom." href="javascript:void(0)" style="text-decoration:none"> (<span style="color:#3CF;">?</span>)</a></strong></p><input type="text" id="topoffset" name="verticalsharetopoffset" value="'.$verticalsharetopoffset.'" >
	</div></div></div>
	</td>
	</tr>
	<tr>
	<td colspan="2" ><span class="subhead">What Sharing Networks do you want to show in the sharing widget? (All other sharing networks will be shown as part of LoginRadius sharing icon)</span><br /><div id="loginRadiusSharingLimit" style="color: red; display: none; margin-bottom: 5px;">You can select only 9 providers.</div>
	</td>
	</tr>
	<tr>
	<td>	
	<table class="form-table sociallogin_table" id="shareprovider">
	<tr class="row_white">
	<td style="width:33%;">
	<input name="shareicons" onchange="loginRadiusSharingLimit(this);loginRadiusRearrangeProviderList(this);" type="checkbox"  '.$enablefb.' value="facebook"  /> '.$this->l('Facebook').'<br />
	<input name="shareicons" onchange="loginRadiusSharingLimit(this);loginRadiusRearrangeProviderList(this);" type="checkbox"  '.$enabletwitter.' value="twitter"  /> '.$this->l('Twitter').'<br />
	<input name="shareicons" onchange="loginRadiusSharingLimit(this);loginRadiusRearrangeProviderList(this);" type="checkbox"  '.$enableprint.' value="print"  /> '.$this->l('Print').'<br />
	<input name="shareicons" onchange="loginRadiusSharingLimit(this);loginRadiusRearrangeProviderList(this);" type="checkbox"  '.$enableemail.' value="email"  /> '.$this->l('Email').'<br />
	<input name="shareicons" onchange="loginRadiusSharingLimit(this);loginRadiusRearrangeProviderList(this);" type="checkbox"  '.$enablegoogle.' value="google"  /> '.$this->l('Google').'<br />
	<input name="shareicons" onchange="loginRadiusSharingLimit(this);loginRadiusRearrangeProviderList(this);" type="checkbox"  '.$enablepin.' value="pinterest"  /> '.$this->l('Pinterest').'</td>
	</td>
	<td style="width:33%;">		
	<input name="shareicons" onchange="loginRadiusSharingLimit(this);loginRadiusRearrangeProviderList(this);" type="checkbox"  '.$enabledigg.' value="digg"  /> '.$this->l('Digg').'<br />
	<input name="shareicons" onchange="loginRadiusSharingLimit(this);loginRadiusRearrangeProviderList(this);" type="checkbox"  '.$enablereddit.' value="reddit"  /> '.$this->l('Reddit').'<br />
	<input name="shareicons" onchange="loginRadiusSharingLimit(this);loginRadiusRearrangeProviderList(this);" type="checkbox"  '.$enablevk.' value="vkontakte"  /> '.$this->l('Vkontakte').'<br />
	<input name="shareicons" onchange="loginRadiusSharingLimit(this);loginRadiusRearrangeProviderList(this);" type="checkbox"  '.$enablegplus.' value="googleplus"  /> '.$this->l('GooglePlus').'<br />
	<input name="shareicons" onchange="loginRadiusSharingLimit(this);loginRadiusRearrangeProviderList(this);" type="checkbox"  '.$enabletumbler.' value="tumblr"  /> '.$this->l('Tumblr').'<br/>
	<input name="shareicons" onchange="loginRadiusSharingLimit(this);loginRadiusRearrangeProviderList(this);" type="checkbox"  '.$enablelinkedin.' value="linkedin"  /> '.$this->l('LinkedIn').'<br />
	</td>
	<td style="width:33%;">		
	<input name="shareicons" onchange="loginRadiusSharingLimit(this);loginRadiusRearrangeProviderList(this);" type="checkbox"  '.$enablemyspace.' value="myspace"  /> '.$this->l('MySpace').'<br />
	<input name="shareicons" onchange="loginRadiusSharingLimit(this);loginRadiusRearrangeProviderList(this);" type="checkbox"  '.$enabledeli.' value="delicious"  /> '.$this->l('Delicious').'<br />
	<input name="shareicons" onchange="loginRadiusSharingLimit(this);loginRadiusRearrangeProviderList(this);" type="checkbox"  '.$enableyahoo.' value="yahoo"  /> '.$this->l('Yahoo').'<br />
	<input name="shareicons" onchange="loginRadiusSharingLimit(this);loginRadiusRearrangeProviderList(this);" type="checkbox"  '.$enablelive.' value="live"  /> '.$this->l('Live').'<br />
	<input name="shareicons" onchange="loginRadiusSharingLimit(this);loginRadiusRearrangeProviderList(this);" type="checkbox"  '.$enablehyves.' value="hyves"  /> '.$this->l('Hyves').'<br />
	<input name="shareicons" onchange="loginRadiusSharingLimit(this);loginRadiusRearrangeProviderList(this);" type="checkbox"  '.$enablednnkicks.' value="dotnetkicks"  />'.$this->l('DotNetKicks') .'
	</td>
	</tr>
	</table>
	</td>
	</tr>
	<tr>
	<td colspan="2" >
	<span class="subhead">What sharing network order do you prefer for your sharing widget?</span><br />
	<ul id="sortable" style="height:35px;">';
	$li='';
	$rearrange_settings = unserialize(Configuration::get('rearrange_settings'));
	$rearrnage_provider_list=$rearrange_settings;
	if(empty($rearrnage_provider_list))
	{
	$rearrange_settings[] = 'facebook';
	$rearrange_settings[] = 'googleplus';
	$rearrange_settings[] = 'twitter';
	$rearrange_settings[] = 'linkedin';
	$rearrange_settings[] = 'pinterest';
	}
	foreach($rearrange_settings  as $provider){
	$li.='<li title="'.$provider.'" id="loginRadiusLI'.$provider.'" class="lrshare_iconsprite32 lrshare_'.$provider.'">
	<input type="hidden" name="rearrange_settings[]" value="'.$provider.'" />
	</li>';
	}
	$html.=$li.'</ul>
	</td>
	</tr>
	<tr>
	<td>
	<span class="subhead">'.$this->l('Social Share Location').'</span>
	<table class="form-table sociallogin_table">
	<tr class="row_white">
	<td>';
	if(Tools::getValue('social_share_home')==0 && Tools::getValue('social_share_cart')==0 && Tools::getValue('social_share_product')==0) {
	Configuration::updateValue('social_share_home', 1);
	}
	$html.='<input type="checkbox" name="social_share_home" value="1" '.(Tools::getValue('social_share_home', Configuration::get('social_share_home')) ? 'checked="checked"' : '').' />Show on Home page<br/>
	<input type="checkbox" name="social_share_cart" value="1" '.(Tools::getValue('social_share_cart', Configuration::get('social_share_cart')) ? 'checked="checked"' : '').' />Show on Cart page<br/>
	<input type="checkbox" name="social_share_product" value="1" '.(Tools::getValue('social_share_product', Configuration::get('social_share_product')) ? 'checked="checked"' : '').' />Show on Product Page<br/>
	</td>
	</tr>
	</table>
	</td>
	</tr>
	</table>
	</div></dd>	
	<dd>
	<div style="display:none;" id="third">
	<table class="form-table sociallogin_table">
	<tr>
	<th class="head" colspan="2">LoginRadius Social Counter Settings</small></th>
	</tr>
	<tr>
	<td colspan="2" ><span class="subhead">Do you want to enable Social Counter for your website?</span><br /><br />
	
	<input type="radio" name="enable_social_counter" value="1" '.(Tools::getValue('enable_social_counter', Configuration::get('enable_social_counter'))==1 ? 'checked="checked"' : '').' />Yes&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;
	<input type="radio" name="enable_social_counter" value="0" '.(Tools::getValue('enable_social_counter', Configuration::get('enable_social_counter'))==0 ? 'checked="checked"' : '').' />No
	
	</td>
	</tr>
	<tr class="row_white">
	<td colspan="2" ><span class="subhead">Enter the text that you wish to be displayed above the Social Counter Interface. Leave the field blank if you dont want any text to be displayed</span>
	<br/><input type="text" name="social_counter_pretext" value="'.(Tools::getValue('social_counter_pretext', Configuration::get('social_counter_pretext')) ? $social_counter_pretext : '').'" />
	</td>
	</tr>
	<tr>
	<td colspan="2" ><span class="subhead">What Social Counter widget theme do you want to use across your website?</span><br /><br />';
	
	$choosecounter =Configuration::get('choosecounter');
	if($choosecounter == '' || $choosecounter == '0' || $choosecounter == '1'){
	  $style_visible= 'style="position:absolute;border-bottom:8px solid #ffffff; border-right:8px solid transparent; border-left:8px solid transparent; margin:-18px 0 0 2px"';} 
	else{$style_visible='style="position:absolute;border-bottom:8px solid #ffffff;border-right:8px solid transparent; border-left:8px solid transparent; margin:-18px 0 0 70px;"';}
	$html.='<a class="mymodal" href="javascript:void(0);" onclick="Makechorivisible();" id = "Makechorivisible"><b>Horizontal</b></a> &nbsp;|&nbsp; 
	<a class="mymodal" href="javascript:void(0);" onclick="Makecvertivisible();"><b>Vertical</b></a>
	<div style="border:#dddddd 1px solid; padding:10px; background:#FFFFFF; margin:10px 0 0 0;">
	<span id = "carrow" '.$style_visible.'></span>';
	$choosecounter =Configuration::get('choosecounter');
	if($choosecounter='' || $choosecounter== '0' || $choosecounter== '1'){$show_block='style=display:block';}
	else {$show_block='style=display:none';}
	$html.='<div id="counterhorizontal" '.$show_block.'>
	<input name="choosecounter" id ="chori16" '.$chori16.' type="radio" value="0" style="margin: 2px 10px 0 0; display: block; float: left !important;" /> <img src = "../modules/sociallogin/img/hybrid-horizontal.png" /><br /><br />
	<input name="choosecounter" id = "chori32" '.$chori32.' type="radio" value="1" style="margin: 2px 10px 0 0; display: block; float: left !important;" /> <img src ="../modules/sociallogin/img/hybrid-horizontal-vertical.png" />
	</div>';
	$choosecounter =Configuration::get('choosecounter');
	if($choosecounter== '2' || $choosecounter== '3'){$show_blockvertical='style=display:block';} 
	else {$show_blockvertical='style=display:none';}
	$html.='<div id="countervertical" '.$show_blockvertical.'>
	<input name="choosecounter" id = "cvertibox32" '.$cvertibox32.' type="radio"  value="2" style="vertical-align:top"/> <img src = "../modules/sociallogin/img/hybrid-verticle-horizontal.png" style="vertical-align:top"/>
	<input name="choosecounter" id = "cvertibox16" '.$cvertibox16.' type="radio" value="3" style="vertical-align:top"/> <img src = "../modules/sociallogin/img/hybrid-verticle-vertical.png" />
	<div style="overflow:auto; background:#EBEBEB; padding:10px;">
	<p style="margin:0 0 6px 0; padding:0px;color:#000000;"><strong>Select the position of Social Counter widget</strong></p>';
	$ctopleft = "";
	$ctopright = "";
	$cbottomleft = "";
	$cbottomright = "";
	$choosecounterpos = (isset($choosecounterpos) ? $choosecounterpos : "0");
	$verticalcountertopoffset=(isset($verticalcountertopoffset) ? $verticalcountertopoffset : "");
	if ($choosecounterpos == '0' ) $ctopleft = "checked=checked";
	else if ($choosecounterpos == '1' ) $ctopright = "checked=checked";
	else if ($choosecounterpos == '2' ) $cbottomleft = "checked=checked";
	else if ($choosecounterpos == '3' ) $cbottomright = "checked=checked";
	else $ctopleft = "checked=checked";
	$html.='<input name="choosecounterpos" id = "topleft" type="radio" '.$ctopleft.' value="0" /> Top Left<br /> 
	<input name="choosecounterpos" id = "topright" type="radio" '.$ctopright.' value="1" /> Top Right<br />
	<input name="choosecounterpos" id = "bottomleft" type="radio" '.$cbottomleft.' value="2" /> Bottom Left<br /> 
	<input name="choosecounterpos" id = "bottomright" type="radio" '.$cbottomright.' value="3" /> Bottom Right <br /><br />
	<p style="margin:0 0 6px 0; padding:0px;color:#000000;"><strong><strong>Specify distance of vertical counter interface from top. (Leave empty for default behaviour)</strong><a title="Enter a number (For example - 200). It will set the \'top\' CSS attribute of the interface to the value specified. Increase in the number pushes interface towards bottom." href="javascript:void(0)" style="text-decoration:none"> (<span style="color:#3CF;">?</span>)</a></strong></p>
	<input type="text" id="topoffset" name="verticalcountertopoffset" value="'.$verticalcountertopoffset.'" >
	</div>
	</div></div>
	</td>
	</tr>
	<tr>
	<td colspan="2" ><span class="subhead">What Counter Networks do you want to show in the counter widget?</span><br /><br />
	</td>
	</tr>	
	<tr >
	<td>
	<table class="form-table sociallogin_table" id="shareprovider">
	<tr class="row_white">
	<td>
	<input type="checkbox" name="countericons[]" value="'.$this->l('Facebook Like').'" '.$enablefblike.' /> Facebook Like<br />
	<input type="checkbox" name="countericons[]" value="'.$this->l('Facebook Recommend').'" '.$enablefbrecommend.' /> Facebook Recommend<br />
	<input type="checkbox" name="countericons[]" value="'.$this->l('Facebook Send').'" '.$enablefbsend.' /> Facebook Send<br />
	<input type="checkbox" name="countericons[]" value="'.$this->l('Google+ +1').'" '.$enablegplusone.' /> Google+ +1<br />
	<input type="checkbox" name="countericons[]" value="'.$this->l('Google+ Share').'" '.$enablegshare.' /> Google+ Share<br />
	<input type="checkbox" name="countericons[]" value="'.$this->l('Pinterest Pin it').'" '.$enablepinterestshare.' /> Pinterest Pin it<br />
	</td>
	<td>
	<input type="checkbox" name="countericons[]" value="'.$this->l('LinkedIn Share').'" '.$enablelinkedinshare.' /> LinkedIn Share<br />
	<input type="checkbox" name="countericons[]" value="'.$this->l('Twitter Tweet').'" '.$enabletweet.'  /> Twitter Tweet<br />
	<input type="checkbox" name="countericons[]" value="'.$this->l('StumbleUpon Badge').'" '.$enablestbadge.' /> StumbleUpon Badge<br />
	<input type="checkbox" name="countericons[]" value="'.$this->l('Reddit').'" '.$enableredditshare.'  /> Reddit<br/>
	<input type="checkbox" name="countericons[]" value="'.$this->l('Hybridshare').'" '.$enablehybridshare.'  /> Hybridshare<br/>
	</td>
	</tr>
	</table>
	</td>
	<tr>
	<td>
	<span class="subhead">'.$this->l('Social Counter Location').'</span>
	<table class="form-table sociallogin_table">
	<tr class="row_white">
	<td>';
	if(Tools::getValue('social_counter_home')==0 && Tools::getValue('social_counter_cart')==0 && Tools::getValue('social_counter_product')==0) {
	Configuration::updateValue('social_counter_home', 1);
	}
	$html.='<input type="checkbox" name="social_counter_home" value="1" '.(Tools::getValue('social_counter_home', Configuration::get('social_counter_home')) ? 'checked="checked"' : '').' />Show on Home page<br/>
	<input type="checkbox" name="social_counter_cart" value="1" '.(Tools::getValue('social_counter_cart', Configuration::get('social_counter_cart')) ? 'checked="checked"' : '').' />Show on Cart page<br/>
	<input type="checkbox" name="social_counter_product" value="1" '.(Tools::getValue('social_counter_product', Configuration::get('social_counter_product')) ? 'checked="checked"' : '').' />Show on Product Page<br/>
	</td>
	</tr>
	</table>
	</td>
	</tr>
	</tr>
	</table>
	</div></dd>
	<input class="button" type="submit" name="submitKeys" value="'.$this->l('   Save Configuration ').'" style="cursor:pointer;"/>	
	</div>
	</form>
	</div>
	<div style="float:right; width:29%;">
	<!-- Help Box --> 
	<div style="background:#EAF7FF; border: 1px solid #B3E2FF; overflow:auto; margin:0 0 10px 0;">
	<h3 style="border-bottom:#000000 1px solid; margin:0px; padding:0 0 6px 0; border-bottom: 1px solid #B3E2FF; color: #000000; margin:10px;">Help & Documentations</h3>
	<ul class="help_ul">
	<li><a href="http://support.loginradius.com/customer/portal/articles/1107175-implementation-of-social-login-on-prestashop-v1-5-website" target="_blank">Plugin Installation, Configuration and Troubleshooting</a></li>
	<li><a href="http://support.loginradius.com/customer/portal/articles/677100-how-to-get-loginradius-api-key-and-secret" target="_blank">How to get LoginRadius API Key & Secret</a></li>
	<li><a href="http://support.loginradius.com/customer/portal/articles/594031" target="_blank">Support Documentations</a></li>
	<li><a href="http://community.loginradius.com/" target="_blank">Discussion Forum</a></li>
	<li><a href="https://www.loginradius.com/Loginradius/About" target="_blank">About LoginRadius</a></li>
	<li><a href="https://www.loginradius.com/product/product-overview" target="_blank">LoginRadius Products</a></li>
	<li><a href="https://www.loginradius.com/addons" target="_blank">Other LoginRadius Addons</a></li>
	<li><a href="https://www.loginradius.com/addons" target="_blank">Social Plugins</a></li>
	<li><a href="https://www.loginradius.com/sdks/loginradiussdk" target="_blank">Social SDKs</a></li>
	<li><a href="https://www.loginradius.com/loginradius/Testimonials" target="_blank">Testimonials</a></li>
	</ul>
	</div>
	<div style="clear:both;"></div>
	<div style="background:#EAF7FF; border: 1px solid #B3E2FF;  margin:0 0 10px 0; overflow:auto;">
	<h3 style="border-bottom:#000000 1px solid; margin:0px; padding:0 0 6px 0; border-bottom: 1px solid #B3E2FF; color: #000000; margin:10px;">Stay Update!</h3>
	<p align="justify" style="line-height: 19px;font-size:12px !important;margin:10px !important;color: #000000;">
	To receive updates on new features, releases, etc, please connect to one of our social media pages.</p>
	<ul class="stay_ul">
	<li class="first">
	<iframe rel="tooltip" scrolling="no" frameborder="0" allowtransparency="true" style="border: none; overflow: hidden; width: 46px;
	height: 63px;" src="//www.facebook.com/plugins/like.php?app_id=194112853990900&amp;href=http%3A%2F%2Fwww.facebook.com%2Fpages%2FLoginRadius%2F119745918110130&amp;send=false&amp;layout=box_count&amp;width=90&amp;show_faces=false&amp;action=like&amp;colorscheme=light&amp;font=arial&amp;height=9" data-original-title="Like us on Facebook"></iframe>
	</li>
	</ul>
	<div>
	<div class="twitter_box"><span id="followers"></span></div>
	<a href="https://twitter.com/LoginRadius" class="twitter-follow-button" data-show-count="false" data-show-screen-name="false"></a>
	<script>!function(d,s,id){var js,fjs=d.getElementsByTagName(s)[0];if(!d.getElementById(id)){js=d.createElement(s);js.id=id;js.src="//platform.twitter.com/widgets.js";fjs.parentNode.insertBefore(js,fjs);}}(document,"script","twitter-wjs");</script>
	</div>
	</div>
	<div style="clear:both;"></div>
	<!-- Upgrade Box -->
	<div style="background:#EAF7FF; border: 1px solid #B3E2FF; overflow:auto; margin:0 0 10px 0;">
	<h3 style="border-bottom:#000000 1px solid; margin:0px; padding:0 0 6px 0; border-bottom: 1px solid #B3E2FF; color: #000000; margin:10px;">Support Us</h3>
	<p align="justify" style="line-height: 19px; font-size:12px !important; margin:10px !important;color: #000000;">
	If you liked our FREE open-source plugin, please send your feedback/testimonial to <a style="color:#0000ff;" href="mailto:feedback@loginradius.com">feedback@loginradius.com</a>!</p>
	</div>
	</div>
	</div>';
	return $html;
  } 
  
  /*
  * Get JS scipt to shoe Social linking Interface for account linking.
  */
  public static function jsinterface() {
	global $cookie;
	$li='';
	$API_KEY = trim(Configuration::get('API_KEY'));
	$API_SECRET = trim(Configuration::get('API_SECRET'));
	if(isset($_SERVER['HTTPS']) && $_SERVER['HTTPS']!='Off' && !empty($_SERVER['HTTPS'])){$http='https://';}
	else{$http='http://';}
	$loc=(isset($_SERVER['REQUEST_URI']) ? urlencode($http.$_SERVER["HTTP_HOST"].$_SERVER['REQUEST_URI']): urlencode($http.$_SERVER["HTTP_HOST"].$_SERVER['PHP_SELF'].'?'.$_SERVER['QUERY_STRING']));
	if (Context:: getContext()->customer->isLogged()){
	  if(strpos($loc, 'sociallogin') !== false) {
	    $cookie->currentquerystring = $loc;
	    $loc=urlencode($http.$_SERVER["HTTP_HOST"].$_SERVER['PHP_SELF']);
	  }
	}
	$getdata = Db::getInstance()->ExecuteS('SELECT * FROM '.pSQL(_DB_PREFIX_.'customer').' as c WHERE c.email='." '$cookie->email' ".' LIMIT 0,1');
	$num=(!empty($getdata['0']['id_customer'])? $getdata['0']['id_customer']:"");
	$linkedprovider=Db::getInstance()->ExecuteS("SELECT * from ".pSQL(_DB_PREFIX_.'sociallogin')." where `id_customer`='".$num."'");
	$script= '<script src="//hub.loginradius.com/include/js/LoginRadius.js"></script><script type="text/javascript"> var options={}; options.login=true; LoginRadius_SocialLogin.util.ready(function () { $ui = LoginRadius_SocialLogin.lr_login_settings;$ui.interfacesize = "";$ui.apikey ="'.$API_KEY.'";$ui.callback="'.$loc.'"; $ui.lrinterfacecontainer ="interfacecontainerdiv"; LoginRadius_SocialLogin.init(options); });</script><div id="interfacecontainerdiv" class="interfacecontainerdiv"> </div><ul style="list-style:none">';
	for($i=0; $i<count($linkedprovider); $i++){
	  $message= '<label> Connected with </label>';
	  if(empty($cookie->lr_login)) {
	    $cookie->loginradius_id='';
	  }
	  if($linkedprovider[$i]['provider_id'] == $cookie->loginradius_id) {
	  $message= '<label style="color:green;"> Currently connected with </label>';
	  }
	  $li.="<li style='width:280px;float:left;'><img src='".__PS_BASE_URI__."modules/sociallogin/img/".$linkedprovider[$i]['Provider_name'].".png'>".$message.$linkedprovider[$i]['Provider_name']." <a href='index.php?id_provider=".$linkedprovider[$i]['provider_id']."'><input name='submit' type='button' value='remove' style='background:#666666; color:#FFF; text-decoration:none;cursor: pointer; float:right;'></a></li>";
	  }
	return $script.$li.'</ul></form>';
  }
    /*
	*  delete social login table form database.
	*/
  public function uninstall() {
	if (!parent::uninstall())
	Db::getInstance()->Execute('DELETE FROM `'._DB_PREFIX_.'sociallogin`');
	parent::uninstall();
	return true;
  }
}
?>