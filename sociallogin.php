<?php

if ( !defined( '_PS_VERSION_' ) ){
	exit;
}
/**
 *Created by loginradius.com ,email, date and other description will goes here....
**/
define("SL_NAME","sociallogin");

define("SL_VERSION","1.2");

define("SL_AUTHOR","LoginRadius");

define("SL_DESCRIPTION","Let your users log in and comment via their accounts with popular ID providers such as Facebook, Google, Twitter, Yahoo, Vkontakte and over 15 more!.");

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

    public function hookLeftColumn( $params,$str="" ){

		include_once(dirname(__FILE__)."/sociallogin_functions.php");

		global $smarty;

		global $cookie;

		if ($cookie->isLogged()){

			return;

		}

		$API_KEY = trim(Configuration::get('API_KEY'));

		$API_SECRET = trim(Configuration::get('API_SECRET'));

		$cookie->lr_login="false";

		$margin_style="";

		if($str=="margin"){

			$margin_style='style="margin-left:8px;margin-bottom:8px;"';

		}

		$Title=Configuration::get('TITLE');
		if(empty($API_SECRET) || !preg_match('/^\{?[A-Z0-9]{8}-[A-Z0-9]{4}-[A-Z0-9]{4}-[A-Z0-9]{4}-[A-Z0-9]{12}\}?$/i', $API_SECRET)) {
		
	    $iframe = "<p style='color:red'>Your LoginRadius API secret is not valid, please correct it or contact LoginRadius support at <a href='http://www.LoginRadius.com' target='_blank'>www.loginradius.com</a></p>";
		  
	    }
	    else {
		  $jsfiles='<script>$(function(){
    loginradius_interface();					 
    });</script>';

		$iframe=$Title.'<br/>'. $jsfiles.'<div class="interfacecontainerdiv"></div>';
			
		}
        if($str=="right" ||$str==""){

			$right='True';
			
        }
		else {
		
        	$right=false;

        }
        $smarty->assign('right',$right);
				
        $smarty->assign('added',FALSE);
		
		$smarty->assign( 'margin_style', $margin_style );       

		$smarty->assign( 'iframe', $iframe );

		return $this->display( __FILE__, 'loginradius.tpl' );

	}

	public function hookRightColumn( $params ){

		return $this->hookLeftColumn( $params,"right" );

	}
	
    public function hookHeader( $params ){
    
	global $smarty;
	$API_KEY = trim(Configuration::get('API_KEY'));
		if(isset($_SERVER['HTTPS']) && !empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] != 'off'){
$http = "https://";
}else{
$http = "http://";
}

		$loc=(isset($_SERVER['REQUEST_URI']) ? urlencode($http.$_SERVER["HTTP_HOST"].$_SERVER['REQUEST_URI']): urlencode($http.$_SERVER["HTTP_HOST"].$_SERVER['PHP_SELF'].'?'.$_SERVER['QUERY_STRING']));
    $js_files='<script src="//hub.loginradius.com/include/js/LoginRadius.js" ></script> <script type="text/javascript"> 
	function loginradius_interface() { $ui = LoginRadius_SocialLogin.lr_login_settings;$ui.interfacesize = "";$ui.apikey = "'.$API_KEY.'";$ui.callback="'.$loc.'"; $ui.lrinterfacecontainer ="interfacecontainerdiv"; LoginRadius_SocialLogin.init(options); }
	var options={}; options.login=true; LoginRadius_SocialLogin.util.ready(loginradius_interface); </script>';
	
    $smarty->assign('js_files',$js_files);
	$smarty->assign('added',TRUE);
    return $this->display( __FILE__, 'loginradius.tpl' );
	
    }
	
	public function hookCreateAccountTop( $params ){

		return $this->hookLeftColumn( $params,"margin" );

	}

	public function hookTop(){

		global $cookie;

		if ($cookie->isLogged()){

			return;

		}

		include_once(dirname(__FILE__)."/sociallogin_functions.php");

		if(isset($_REQUEST['token'])){

			include_once("sociallogin_user_data.php");

			$obj=new LrUser();

		}elseif(isset($_REQUEST['SL_VERIFY_EMAIL'])){

			verifyEmail();

		}elseif(isset($_REQUEST['hidden_val'])){

			global $cookie;       

			if(isset($_POST['SL_EMAIL']) and ($_REQUEST['hidden_val']==$cookie->SL_hidden) ){

				if(isset($_POST['SL_EMAIL'])){

					SL_email_save($_POST['SL_EMAIL']);

				}

			}else{

				echo "Cookie problem. Are cookies disabled?";

			}

		}

	}



	public function install(){

		if(!parent::install()

		|| !$this->registerHook( 'leftColumn' )

		|| !$this->registerHook( 'createAccountTop' )

		|| !$this->registerHook( 'rightColumn' )

		|| !$this->registerHook( 'top' )
		
		||  !$this->registerHook('header')  

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

		Db::getInstance()->ExecuteS($CREATE_TABLE);

	}



	public function getContent(){

		$html = '';
		
		if(Tools::isSubmit('submitKeys'))  {
		
			if(Tools::getValue('API_SECRET')){
			
				$val = trim(Tools::getValue('LoginRadius_redirect'));
				
				if($val=="url"){
				
					$val = trim(Tools::getValue('redirecturl'));//redirecturl
					
				}
				Configuration::updateValue('LoginRadius_redirect', Tools::getValue('LoginRadius_redirect'));
				
				Configuration::updateValue('redirecturl',Tools::getValue('redirecturl'));	
						
				Configuration::updateValue('API_KEY', trim(Tools::getValue('API_KEY')));
				
				Configuration::updateValue('API_SECRET', trim(Tools::getValue('API_SECRET')));
				
				Configuration::updateValue('TITLE', Tools::getValue('TITLE'),"Please Login with");
				
				Configuration::updateValue('EMAIL_REQ',(int)( Tools::getValue('EMAIL_REQ')));
				
				Configuration::updateValue('SEND_REQ',(int)( Tools::getValue('SEND_REQ')));
				
				Configuration::updateValue('CURL_REQ',(int)( Tools::getValue('CURL_REQ')));	
				
				Configuration::updateValue('ACC_MAP',(int)( Tools::getValue('ACC_MAP')));
						
				$html .= $this->displayConfirmation($this->l('Settings updated.'));		
				
			}else{
			
				$html .= $this->displayError($this->l('Keys are empty.'));
				
			}		
		}

		$API_KEY = trim(Configuration::get('API_KEY'));		

		$API_SECRET = trim(Configuration::get('API_SECRET'));

		$Title = Configuration::get('TITLE');
	
		$EMAIL_REQ = Configuration::get('EMAIL_REQ');
		
		$SEND_REQ = Configuration::get('SEND_REQ');
		
		$CURL_REQ = Configuration::get('CURL_REQ');
		
		$ACC_MAP = Configuration::get('ACC_MAP');
		
		$LoginRadius_redirect=Configuration::get('LoginRadius_redirect');
		
		$redirecturl=Configuration::get('redirecturl');

		$checked[0]="";		

		$checked[1]="";		

		$checked[2]="";		

		$redirect="";		

		$jsVal=1;		

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
		
		$html.='<h2>Social Login</h2><img src="../modules/sociallogin/sociallogin.png" style="float:left; margin-right:15px;"><b>'.$this->l('Thank you for installing the Social Login module!.').'</b><br /><br /><p>You can customize the settings for your module on this page, though you will have to choose your desired ID providers and get your unique <b>LoginRadius API Key & Secret</b> from <a href="http://www.loginradius.com" target="_blank">www.LoginRadius.com</a>. In order to make the login process secure, we require you to manage it from your LoginRadius account.</p>		

		<p>LoginRadius is a North America based technology company that offers social login through popular hosts such as Facebook, Twitter, Google and over 15 more! For tech support or if you have any questions, please contact us at <b>hello@loginradius.com.</b></p>';		

		$html .= '<h2>'.$this->l('Module settings').'</h2>		

		<script language="javascript">		

		function hidetextbox(hide){		

			if(hide==1){

				document.getElementById("redirectid").style.visibility ="hidden";		

			}else{

				document.getElementById("redirectid").style.visibility ="visible";		

			}		

		}		

		window.onload = function (){		

			hidetextbox('.$jsVal.');		

		}		

		</script>		

		<form action="'.$_SERVER['REQUEST_URI'].'" method="post">		

		<fieldset>		

		<legend>'.$this->l('Settings').'</legend>		

		<label>'.$this->l('Login radius API key').'</label>

		<div class="margin-form">

		<input type="text" size="50" name="API_KEY" value="'.$API_KEY.'" />	

		</div>		

		<label>		

		'.$this->l('Login radius secret Key').'</label>

		<div class="margin-form">

		<input type="text" name="API_SECRET"  size="50" value="'.$API_SECRET.'" />		

		</div>

		<label>		

		'.$this->l('Login radius Title').'</label>		

		<div class="margin-form"><input type="text" name="TITLE"  size="50" value="'.$Title.'" />		

		</div>

		<label>'.$this->l('Receive Email').'</label> <div class="margin-form"> <input type="radio" name="SEND_REQ" value="1" '.(Tools::getValue('SEND_REQ', Configuration::get('SEND_REQ')) ? 'checked="checked" ' : '').'/><b>Yes</b>		

		<input type="radio" name="SEND_REQ" value="0"  '.(!Tools::getValue('SEND_REQ', Configuration::get('SEND_REQ')) ? 'checked="checked" ' : '').'/>	<b>No</b>	

		<p>'.$this->l('Select YES if you would like receive an email when new user register to your site.').'</p>	

		</div>

		<label>'.$this->l('Email Required').'</label>   <div class="margin-form"><input type="radio" name="EMAIL_REQ" value="0" '.(!Tools::getValue('EMAIL_REQ', Configuration::get('EMAIL_REQ')) ? 'checked="checked" ' : '').' /><b>Yes</b>		

		<input type="radio" name="EMAIL_REQ" value="1" '.(Tools::getValue('EMAIL_REQ', Configuration::get('EMAIL_REQ')) ? 'checked="checked" ' : '').'/><b>No</b>		

		</div>

		<label>'.$this->l('Select API Credentilas').'</label><div class="margin-form"> <input type="radio" name="CURL_REQ" value="0" '.(!Tools::getValue('CURL_REQ', Configuration::get('CURL_REQ')) ? 'checked="checked" ' : '').' /> <b>Use cURL (Require cURL support = enabled in your php.ini settings) </b></br></div>		

		<div class="margin-form"> <input type="radio" name="CURL_REQ" value="1" '.(Tools::getValue('CURL_REQ', Configuration::get('CURL_REQ')) ? 'checked="checked" ' : '').'/><b>Use FSOCKOPEN (Require allow_url_fopen = On and safemode = off in your php.ini settings)</b>		

		</div>		
<label>'.$this->l('Account Mapping').'</label>
 <div class="margin-form"> <input type="radio" name="ACC_MAP" value="0" '.(!Tools::getValue('ACC_MAP', Configuration::get('ACC_MAP')) ? 'checked="checked" ' : '').'/><b>Yes</b>		

		<input type="radio" name="ACC_MAP" value="1"  '.(Tools::getValue('ACC_MAP', Configuration::get('ACC_MAP')) ? 'checked="checked" ' : '').'/>	<b>No</b>	

		<p>'.$this->l('Select YES if you would like to link your existing account with Social Login.').'</p>	

		</div>

		<label>'.$this->l('Setting for Redirect after login').'</label>	

		<div class="margin-form"><input name="LoginRadius_redirect" value="backpage" type="radio" onclick="javascript:hidetextbox(1);" '.$checked[0].' /><b>Redirect to back page  </b></div>		

		<div class="margin-form">	 <input name="LoginRadius_redirect" value="profile" type="radio" onclick="javascript:hidetextbox(1);" '.$checked[1].' /> <b>Redirect to the profile </b> </div>	

		<div class="margin-form"><input name="LoginRadius_redirect" value="url" type="radio" onclick="javascript:hidetextbox(0);" '.$checked[2].' /> <b>Redirect to the following url:</b>

		</div>		

		<div class="margin-form"> <span id="redirectid"><input type="text" name="redirecturl"  size="40" value="'.$redirect.'" /></span></div>	

		<div class="clear center"><p>&nbsp;</p>		

		<input class="button" type="submit" name="submitKeys" value="'.$this->l('   Save   ').'" />		

		</div>		

		</fieldset>		

		</form>';

		

		$html .= '<fieldset class="space">		

		<legend><img src="../img/admin/unknown.gif" alt="" class="middle" />'.$this->l('Module Help Links').'</legend>		

		<ol>		

		<li><a href="http://support.loginradius.com/customer/portal/articles/594031" target="_blank">'.$this->l('Documentation').'</a></li>		

		<li><a href="http://prestashop.loginradius.com/" target="_blank">'.$this->l('Plugin webpage').'</a></li>		

		<li><a href="https://www.loginradius.com/loginradius/aboutus" target="_blank">'.$this->l('About LoginRadius').'</a></li>		

		<li><a href="http://blog.loginradius.com/" target="_blank">'.$this->l('LoginRadius Blog').'</a></li>		

		<li><a href="https://www.loginradius.com/Plugins/" target="_blank">'.$this->l('Other LoginRadius plugins').'</a></li>		

		<li><a href="http://support.loginradius.com/customer/portal/topics/276924-prestashop-module/articles" target="_blank">'.$this->l('Tech Support').'</a></li>		

		</ol>';		

		return $html;

	} 

	

	public function uninstall(){

		if ( !parent::uninstall() ){

			Db::getInstance()->Execute( 'DROP table `' . _DB_PREFIX_ . 'sociallogin`' );

		}

		Db::getInstance()->Execute( 'DROP table `' . _DB_PREFIX_ . 'sociallogin`' );

		parent::uninstall();

	}

}

?>