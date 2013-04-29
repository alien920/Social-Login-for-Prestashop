<?php
/*
* 2007-2012 PrestaShop
*
* NOTICE OF LICENSE
*
* This source file is subject to the Academic Free License (AFL 3.0)
* that is bundled with this package in the file LICENSE.txt.
* It is also available through the world-wide-web at this URL:
* http://opensource.org/licenses/afl-3.0.php
* If you did not receive a copy of the license and are unable to
* obtain it through the world-wide-web, please send an email
* to license@prestashop.com so we can send you a copy immediately.
*
* DISCLAIMER
*
* Do not edit or add to this file if you wish to upgrade PrestaShop to newer
* versions in the future. If you wish to customize PrestaShop for your
* needs please refer to http://www.prestashop.com for more information.
*
*  @author PrestaShop SA <contact@prestashop.com>
*  @copyright  2007-2012 PrestaShop SA
*  @version  Release: $Revision: 16855 $
*  @license    http://opensource.org/licenses/afl-3.0.php  Academic Free License (AFL 3.0)
*  International Registered Trademark & Property of PrestaShop SA
*/

/* SSL Management */
$useSSL = true;

include(dirname(__FILE__).'/../../config/config.inc.php');
include(dirname(__FILE__).'/../../header.php');
include_once(dirname(__FILE__).'/sociallogin.php');
include_once(dirname(__FILE__).'/sociallogin_user_data.php');

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