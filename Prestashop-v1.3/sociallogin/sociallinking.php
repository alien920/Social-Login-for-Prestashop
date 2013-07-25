<?php

$useSSL = true;

include(dirname(__FILE__).'/../../config/config.inc.php');
include(dirname(__FILE__).'/../../header.php');
include_once(dirname(__FILE__).'/sociallogin.php');
include_once(dirname(__FILE__).'/sociallogin_functions.php');

global $smarty;
//$socialloginlrmessage = array();
//$socialloginlrmessage = '';
if ($cookie->isLogged())
{
	$smarty->assign(array(
	'lr_check' =>  sociallogin::jsinterface()
));	
	//if(isset($cookie->lrmessage) && $cookie->lrmessage != ''){
		$smarty->assign('socialloginlrmessage', $cookie->lrmessage);
		$cookie->lrmessage='';
		//}
		if (file_exists(dirname(__FILE__).'/sociallinking.tpl')){
			//return $this->display(__FILE__, '/sociallinking.tpl');
	$smarty->display(dirname(__FILE__).'/sociallinking.tpl');
		}
else{
	echo Tools::displayError('No template found');
	}
}
else{
		Tools::redirect('authentication.php?back=my-account.php');
	}
include(dirname(__FILE__).'/../../footer.php');