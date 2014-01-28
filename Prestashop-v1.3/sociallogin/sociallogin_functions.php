<?php
/**
 * @package sociallogin
 * @license GNU GENERAL PUBLIC LICENSE Version 2, June 1991
 *
 * This program is free software; you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation; either version 2 of the License, or
 * (at your option) any later version.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License along
 * with this program; if not, write to the Free Software Foundation, Inc.,
 * 51 Franklin Street, Fifth Floor, Boston, MA 02110-1301 USA.
 */
if (!defined('_PS_VERSION_'))
	exit;
if (Configuration::get('PS_TOKEN_ACTIVATED') == 1 && strcmp(Tools::getToken(), Tools::getValue('token'))) {
	$msg = "<p style ='color:red;'>".$module->allLangMessage('Invalid Token.').'</p>';
	popUpVerify($msg);
	return;
}
function loginRadiusConnect()
{
	global $cookie;
	//create object of soial login class.
	$module = new sociallogin();
	include_once('LoginRadiusSDK.php');
	$secret = trim(Configuration::get('API_SECRET'));
	$lr_obj = new LoginRadius();
	//Get the userprofile of authenticate user.
	$userprofile = $lr_obj->loginRadiusGetData($secret);
	//If user is not logged in and user is authneticated then handle login functionality.
	if ($lr_obj->is_authenticated == true && !$cookie->isLogged())
	{
		$lrdata = loginRadiusMappingProfileData($userprofile);
		//Check Social provider id is already exist.
		$social_id_exist = 'SELECT * FROM '.pSQL(_DB_PREFIX_.'sociallogin').' as sl
		INNER JOIN '.pSQL(_DB_PREFIX_.'customer')." as c WHERE sl.provider_id='".pSQL($lrdata['id'])."'
		and c.id_customer=sl.id_customer  LIMIT 0,1";
		$db_obj = Db::getInstance()->ExecuteS($social_id_exist);
		$td_user = '';
		$user_id_exist = (!empty($db_obj[0]['id_customer']) ? $db_obj[0]['id_customer'] : '');
		if ($user_id_exist >= 1)
		{
			$active_user = (!empty($db_obj['0']['active']) ? $db_obj['0']['active'] : '');
			//Verify user and provide login.
			loginRadiusVerifiedUserLogin($user_id_exist, $lrdata, $td_user);
		} //If Social provider is is not exist in database.
		elseif ($user_id_exist < 1)
		{
			if (!empty($lrdata['email']))
			{
				$user_email_exist = Db::getInstance()->ExecuteS('SELECT * FROM '.pSQL(_DB_PREFIX_.'customer').' as c
				WHERE c.email="'.$lrdata['email'].'" LIMIT 0,1');
				$user_id = (!empty($user_email_exist['0']['id_customer']) ? $user_email_exist['0']['id_customer'] : '');
				$active_user = (!empty($user_email_exist['0']['active']) ? $user_email_exist['0']['active'] : '');
				if ($user_id >= 1)
				{
					$td_user = 'yes';
					if (deletedUser($user_email_exist))
					{
						$msg = "<p style ='color:red;'>".$module->allLangMessage('Authentication failed.').'</p>';
						popUpVerify($msg);
						return;
					}
					if (Configuration::get('ACC_MAP') == 0)
					{
						$tbl = pSQL(_DB_PREFIX_.'sociallogin');
						$query = "INSERT into $tbl (`id_customer`,`provider_id`,`Provider_name`,`verified`,`rand`)
						values ('".$user_id."','".$lrdata['id']."' , '".$lrdata['provider']."','1','') ";
						Db::getInstance()->Execute($query);
					}
					loginRadiusVerifiedUserLogin($user_id, $lrdata, $td_user);
				}
			}
			$lrdata['send_verification_email'] = 'no';
			//new user. user not found in database. set all details
			if (Configuration::get('user_require_field') == '1')
			{
				if (empty($lrdata['email']))
					$lrdata['send_verification_email'] = 'yes';
				if (Configuration::get('EMAIL_REQ') == '1' && empty($lrdata['email']))
				{
					$lrdata['email'] = emailRand($lrdata);
					$lrdata['send_verification_email'] = 'no';
				}
				//If user is not exist and then add all lrdata into cookie.
				storeInCookie($lrdata);
				//Open the popup to get require fields.
				popUpWindow('', $lrdata);
				return;
			}
			if (Configuration::get('EMAIL_REQ') == '0' && empty($lrdata['email']))
			{
				$lrdata['send_verification_email'] = 'yes';
				//If user is not exist and then add all lrdata into cookie.
				storeInCookie($lrdata);
				//Open the popup to get require fields.
				popUpWindow('', $lrdata);
				return;
			}
			elseif (Configuration::get('EMAIL_REQ') == '1' && empty($lrdata['email']))
				$lrdata['email'] = emailRand($lrdata);
			//Store user data into database and provide login functionality.
			storeAndLogin($lrdata);
		} //If user is delete and set action to provide no login to user.
		elseif (deletedUser($db_obj))
		{
			$msg = "<p style ='color:red;'><b>".$module->allLangMessage('Authentication failed.').'</b></p>';
			popUpVerify($msg);
			return;
		}
		//If user is blocked.
		if ($active_user == 0)
		{
			$msg = "<p style ='color:red;'><b>".$module->allLangMessage('User has been disbled or blocked.').'</b></p>';
			popUpVerify($msg);
			return;
		}
	}
}

/*
* Provide Social linking.
*/
function linking($arrdata, $userprofile)
{
	$module = new sociallogin();
	global $cookie;
	$cookie->lrmessage = '';
	//check User is authenticate and user data is not empty.
	if (!empty($userprofile))
	{
		//Check Social ID and  provider is in database.
		$tbl = pSQL(_DB_PREFIX_.'sociallogin');
		$getdata = Db::getInstance()->ExecuteS('SELECT * FROM '.pSQL(_DB_PREFIX_.'customer').' as c WHERE c.email='."'$arrdata->email'".' LIMIT 0,1');
		$num = (!empty($getdata['0']['id_customer']) ? $getdata['0']['id_customer'] : '');
		$sql = "SELECT COUNT(*) as num from $tbl where `id_customer`='".$num."' and `Provider_name`='".$userprofile->Provider."'";
		$row = Db::getInstance()->getRow($sql);
		if ($row['num'] == 0)
		{
			//check only social id is in database.
			$check_user_id = Db::getInstance()->ExecuteS('SELECT c.id_customer FROM '.pSQL(_DB_PREFIX_.'customer').'
			AS c INNER JOIN '.$tbl.' AS sl ON sl.id_customer=c.id_customer WHERE sl.provider_id= "'.$userprofile->ID.'"');
			if (empty($check_user_id['0']['id_customer']))
				Db::getInstance()->Execute('DELETE FROM '.$tbl."  WHERE provider_id='".$userprofile->ID."'");
			$lr_id = Db::getInstance()->ExecuteS('SELECT provider_id FROM '.$tbl."  WHERE provider_id= '".$userprofile->ID."'");
			//Present then show warning message.
			if (!empty($lr_id['0']['provider_id']))
				$cookie->lrmessage = $module->allLangMessage('Account cannot be mapped as it already exists in database');
			else
			{
				$query = "INSERT into $tbl (`id_customer`,`provider_id`,`Provider_name`,`verified`,`rand`)
				values ('$num','".$userprofile->ID."' , '".$userprofile->Provider."','1','') ";
				Db::getInstance()->Execute($query);
				$cookie->lrmessage = $module->allLangMessage('Your account is successfully mapped');
			}
		} //Already linked with socialid and provider.
		//Show Warning message.
		else
			$cookie->lrmessage = $module->allLangMessage('This social ID is already linked with an account.
			Kindly unmap the current ID before linking new Social ID.');
	}
	//After Linking Provide redirection.
	$loc = $arrdata->currentquerystring;
	$cookie->currentquerystring = '';
	Tools::redirectLink(urldecode($loc));
}

/*
* Check user is Verified and Show notification message.
*/
function loginRadiusVerifiedUserLogin($user_id, $lrdata, $td_user = '')
{
	$module = new sociallogin();
	$social_id = $lrdata['id'];
	if (deletedUser($user_id))
	{
		$msg = "<p style ='color:red;'>".$module->allLangMessage('Authentication failed.').'</p>';
		popUpVerify($msg);
		return;
	}
	if (verifiedUser($user_id, $social_id, $td_user))
	{
		if (Configuration::get('update_user_profile') == 0)
			updateUserProfileData($user_id, $lrdata);
		//login User.
		loginRadiusLoginUser($user_id, $social_id);
		return;
	}
	else
	{
		$msg = $module->allLangMessage('Your confirmation link has been sent to your email address.
		Please verify your email by clicking on confirmation link.');
		popUpVerify($msg, $social_id);
		return;
	}
}

/*
* Check user deleted or not.
*/
function deletedUser($user_id)
{
	$db_obj = Db::getInstance()->ExecuteS('SELECT * FROM '.pSQL(_DB_PREFIX_.'customer').' as c
	WHERE c.id_customer='.$user_id.' LIMIT 0,1');
	$deleted = $db_obj['0']['deleted'];
	if ($deleted == 1)
		return true;
	return false;
}

/*
* find user verified or not.
*/
function verifiedUser($num, $pid, $td_user)
{
	$db_obj = Db::getInstance()->ExecuteS('SELECT * FROM '.pSQL(_DB_PREFIX_.'sociallogin').' as c
	WHERE c.id_customer='." '$num'".' AND c.provider_id='." '$pid'".' LIMIT 0,1');
	$verified = $db_obj['0']['verified'];
	$rand = $db_obj['0']['rand'];
	if ($verified == 1 || $td_user == 'yes')
		return true;
	return false;
}

/*
* Update the user profile data.
*/
function updateUserProfileData($user_id, $lrdata)
{
	$date_upd = date('Y-m-d H:i:s', time());
	$str = '';
	if (!empty($lrdata['fname']))
		$str .= "firstname='".$lrdata['fname']."',";
	if (!empty($lrdata['lname']))
		$str .= "lastname='".$lrdata['lname']."',";
	if (!empty($lrdata['gender']))
	{
		$gender = ((!empty($lrdata['gender']) && (strpos($lrdata['gender'], 'f') !== false
		|| (trim($lrdata['gender']) == 'F'))) ? 2 : 1);
		$str .= "id_gender='".$gender."',";
	}
	if (!empty($lrdata['dob']))
	{
		$dob_arr = explode('/', $lrdata['dob']);
		$dob = $dob_arr[2].'-'.$dob_arr[0].'-'.$dob_arr[1];
		$date_of_birth = (!empty($dob) && Validate::isBirthDate($dob) ? $dob : '');
		$str .= "birthday='".$date_of_birth."',";
	}
	Db::getInstance()->Execute('UPDATE '._DB_PREFIX_.'customer SET '.$str." date_upd='$date_upd' WHERE 	id_customer	= $user_id");
}

/*
* Save logged user credentaisl to array.
*/
function loginRadiusLoginUser($user_id, $social_id)
{
	$db_obj = Db::getInstance()->ExecuteS('SELECT * FROM '.pSQL(_DB_PREFIX_.'customer').' as c
	WHERE c.id_customer='." '$user_id' ".' LIMIT 0,1');
	$arr = array();
	$arr['id'] = $db_obj['0']['id_customer'];
	$arr['fname'] = $db_obj['0']['firstname'];
	$arr['lname'] = $db_obj['0']['lastname'];
	$arr['email'] = $db_obj['0']['email'];
	$arr['pass'] = $db_obj['0']['passwd'];
	$arr['loginradius_id'] = $social_id;
	loginRedirect($arr);
}

/*
* Social Login Interface Script Code.
*/
function loginRadiusInterfaceScript()
{
	$loginradius_apikey = trim(Configuration::get('API_KEY'));
	$http = ((isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] != 'Off' && !empty($_SERVER['HTTPS'])) ? 'https://' : 'http://');
	$loc = (isset($_SERVER['REQUEST_URI']) ? urlencode($http.$_SERVER['HTTP_HOST'].$_SERVER['REQUEST_URI'])
	: urlencode($http.$_SERVER['HTTP_HOST'].$_SERVER['PHP_SELF'].'?'.$_SERVER['QUERY_STRING']));
	$interfacebackgroundcolor = Configuration::get('social_login_background_color');
	$interfacebackgroundcolor = (!empty($interfacebackgroundcolor) ? trim($interfacebackgroundcolor) : '');
	$interfacecolumn = Configuration::get('social_login_icon_column');
	$interfacecolumn = (!empty($interfacecolumn) && is_numeric($interfacecolumn) ? trim($interfacecolumn) : 0);
	return $loginradius_interface_script = '<script src="//hub.loginradius.com/include/js/LoginRadius.js" ></script>
	<script type="text/javascript">
	function loginradius_interface() { $ui = LoginRadius_SocialLogin.lr_login_settings;$ui.interfacesize = "small";
	$ui.lrinterfacebackground="'.$interfacebackgroundcolor.'";$ui.noofcolumns='.$interfacecolumn.';
	$ui.apikey = "'.$loginradius_apikey.'";$ui.callback="'.$loc.'";
	$ui.lrinterfacecontainer ="interfacecontainerdiv";
	LoginRadius_SocialLogin.init(options); }var options={}; options.login=true;
	LoginRadius_SocialLogin.util.ready(loginradius_interface); </script>';
}

/*
* Horizontal Social Sharing Widget Script Code.
*/
function loginRadiusHorizontalShareScript()
{
	$share_script = '<script type="text/javascript">var islrsharing = true; var islrsocialcounter = true;
	var hybridsharing = true;</script>
	<script type="text/javascript" src="//share.loginradius.com/Content/js/LoginRadius.js" id="lrsharescript"></script>';
	$horizontal_theme = Configuration::get('chooseshare') ? Configuration::get('chooseshare') : '0';
	if ($horizontal_theme == 8 || $horizontal_theme == 9)
	{
		$counter_list = unserialize(Configuration::get('socialshare_show_counter_list'));
		if (empty($counter_list))
			$counter_list = array('Pinterest Pin it', 'Facebook Like', 'Google+ Share', 'Twitter Tweet', 'Hybridshare');
		$providers = implode('","', $counter_list);
		$interface = 'simple';
		if ($horizontal_theme == '8')
			$type = 'vertical';
		else
			$type = 'horizontal';
		$share_script .= '<script type="text/javascript">LoginRadius.util.ready(function () {
		$SC.Providers.Selected = ["'.$providers.'"]; $S = $SC.Interface.'.$interface.'; $S.isHorizontal = true;
		$S.countertype = \''.$type.'\'; $S.show("lrcounter_simplebox"); });</script>';
	}
	else
	{
		$rearrange_settings = unserialize(Configuration::get('rearrange_settings'));
		if (empty($rearrange_settings))
			$rearrange_settings = array('facebook', 'googleplus', 'twitter', 'linkedin', 'pinterest');
		$providers = implode('","', $rearrange_settings);
		if ($horizontal_theme == 2 || $horizontal_theme == 3)
			$interface = 'simpleimage';
		else
			$interface = 'horizontal';
		if ($horizontal_theme == 1 || $horizontal_theme == 3)
			$size = '16';
		else
			$size = '32';
		$loginradius_apikey = trim(Configuration::get('API_KEY'));
		$sharecounttype = (!empty($loginradius_apikey) ? ('$u.apikey="'.$loginradius_apikey.'";
		$u.sharecounttype='."'url'".';') : '$u.sharecounttype='."'url'".';');
		$share_script .= '<script type="text/javascript">LoginRadius.util.ready(function () {
		$i = $SS.Interface.'.$interface.'; $SS.Providers.Top = ["'.$providers.'"];
		$u = LoginRadius.user_settings; '.$sharecounttype.' $i.size = '.$size.';$i.show("lrsharecontainer"); });</script>';
	}
	return $share_script;
}

/*
* Vertical Social Sharing Widget Script Code.
*/
function loginRadiusVerticalShareScript()
{
	$share_script = '<script type="text/javascript">var islrsharing = true; var islrsocialcounter = true;
	var hybridsharing = true;</script>
	<script type="text/javascript" src="//share.loginradius.com/Content/js/LoginRadius.js" id="lrsharescript"></script>';
	$vertical_theme = Configuration::get('chooseverticalshare') ? Configuration::get('chooseverticalshare') : '6';
	if ($vertical_theme == 6 || $vertical_theme == 7)
	{
		$counter_list = unserialize(Configuration::get('socialshare_counter_list'));
		if (empty($counter_list))
			$counter_list = array('Pinterest Pin it', 'Facebook Like', 'Google+ Share', 'Twitter Tweet', 'Hybridshare');
		$providers = implode('","', $counter_list);
		if ($vertical_theme == 6)
			$type = 'vertical';
		else
			$type = 'horizontal';
		$share_script .= '<script type="text/javascript">LoginRadius.util.ready(function () {
		$SC.Providers.Selected = ["'.$providers.'"]; $S = $SC.Interface.simple; $S.isHorizontal = false;
		$S.countertype = \''.$type.'\';';
		$choosesharepos = Configuration::get('choosesharepos');
		if ($choosesharepos == 0)
		{
			$position1 = 'top';
			$position2 = 'left';
		}
		elseif ($choosesharepos == 1)
		{
			$position1 = 'top';
			$position2 = 'right';
		}
		elseif ($choosesharepos == 2)
		{
			$position1 = 'bottom';
			$position2 = 'left';
		}
		else
		{
			$position1 = 'bottom';
			$position2 = 'right';
		}
		$offset = Configuration::get('verticalsharetopoffset');
		if (isset($offset) && trim($offset) != '' && is_numeric($offset))
			$share_script .= '$S.top = \''.trim($offset).'px\'; $S.'.$position2.' = \'0px\';
			$S.show("lrcounter_verticalsimplebox"); });</script>';
		else
			$share_script .= '$S.'.$position1.' = \'0px\'; $S.'.$position2.' = \'0px\';
			$S.show("lrcounter_verticalsimplebox"); });</script>';
	}
	else
	{
		$vertical_rearrange_settings = unserialize(Configuration::get('vertical_rearrange_settings'));
		if (empty($vertical_rearrange_settings))
			$vertical_rearrange_settings = array('facebook', 'googleplus', 'twitter', 'linkedin', 'pinterest');
		$providers = implode('","', $vertical_rearrange_settings);
		$interface = 'Simplefloat';
		if ($vertical_theme == 4)
			$size = '32';
		else
			$size = '16';
		$loginradius_apikey = trim(Configuration::get('API_KEY'));
		$sharecounttype = (!empty($loginradius_apikey) ? ('$u.apikey="'.$loginradius_apikey.'";
		$u.sharecounttype='."'url'".';') : '$u.sharecounttype='."'url'".';');
		$share_script .= '</script> <script type="text/javascript">LoginRadius.util.ready(function () {
		$i = $SS.Interface.'.$interface.'; $SS.Providers.Top = ["'.$providers.'"];
		$u = LoginRadius.user_settings; '.$sharecounttype.' $i.size = '.$size.';';
		$choosesharepos = Configuration::get('choosesharepos');
		if ($choosesharepos == 0)
		{
			$position1 = 'top';
			$position2 = 'left';
		}
		elseif ($choosesharepos == 1)
		{
			$position1 = 'top';
			$position2 = 'right';
		}
		elseif ($choosesharepos == 2)
		{
			$position1 = 'bottom';
			$position2 = 'left';
		}
		else
		{
			$position1 = 'bottom';
			$position2 = 'right';
		}
		$offset = Configuration::get('verticalsharetopoffset');
		if (isset($offset) && trim($offset) != '' && is_numeric($offset))
			$share_script .= '$i.top = \''.trim($offset).'px\'; $i.'.$position2.' = \'0px\';
			$i.show("lrshareverticalcontainer"); });</script>';
		else
			$share_script .= '$i.'.$position1.' = \'0px\'; $i.'.$position2.' = \'0px\';
			$i.show("lrshareverticalcontainer"); });</script>';
	}
	return $share_script;
}

/*
* Redirection after login.
*/
function redirectURL()
{
	$redirect = '';
	$loc = Configuration::get('LoginRadius_redirect');
	if ($loc == 'profile')
		$redirect = 'my-account.php';
	elseif ($loc == 'url')
	{
		$custom_url = Configuration::get('redirecturl');
		$redirect = !empty($custom_url) ? $custom_url : 'my-account.php';
	} else
	{
		if (Tools::getValue('back'))
			$redirect = Tools::getValue('back');
		else {
			$http = ((isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] != 'Off' && !empty($_SERVER['HTTPS'])) ? 'https://' : 'http://');
			$redirect = urldecode($http.$_SERVER['HTTP_HOST'].$_SERVER['REQUEST_URI']);
		}
	}
	return $redirect;
}

/*
* Email verification link when user click on resend email buttuon.
*/
function loginRadiusResendEmailVerification($social_id)
{
	$module = new sociallogin();
	$getdata = Db::getInstance()->ExecuteS('SELECT * from '._DB_PREFIX_.'customer
	AS c INNER JOIN '._DB_PREFIX_."sociallogin AS sl ON sl.id_customer=c.id_customer  WHERE sl.provider_id='$social_id'");
	if ($getdata['0']['verified'] == 1)
	{
		$msg = $module->allLangMessage('Email has been already verified. Now you can login using Social Login.');
		popUpVerify($msg);
	}
	else
	{
		$to = $getdata['0']['email'];
		$rand = slRandomChar();
		Db::getInstance()->Execute('UPDATE '._DB_PREFIX_.'sociallogin SET rand='.$rand." WHERE provider_id='$social_id'");
		$sub = $module->allLangMessage('Verify your email id.');
		$protocol_content = (Configuration::get('PS_SSL_ENABLED')) ? 'https://' : 'http://';
		$link = $protocol_content.$_SERVER['HTTP_HOST'].__PS_BASE_URI__."?SL_VERIFY_EMAIL=$rand&SL_PID=".$social_id.'';
		$msgg = $module->allLangMessage('Please click on the following link or paste it in browser to verify your email: ').$link;
		slEmail($to, $sub, $msgg, $social_id);
	}
}

/*
* Save the logged in user credentails in cookie.
*/
function loginRedirect($arr)
{
	global $cookie;
	$cookie->id_compare = $arr['id'];
	$cookie->id_customer = $arr['id'];
	$cookie->customer_lastname = $arr['lname'];
	$cookie->customer_firstname = $arr['fname'];
	$cookie->logged = 1;
	$cookie->passwd = $arr['pass'];
	$cookie->email = $arr['email'];
	$cookie->loginradius_id = $arr['loginradius_id'];
	$cookie->lr_login = 'true';
	if ((empty($cookie->id_cart) || Cart::getNbProducts($cookie->id_cart) == 0))
		$cookie->id_cart = (int)Cart::lastNoneOrderedCart($cookie->id_customer);
	Module::hookExec('authentication');
	$redirect = redirectURL();
	Tools::redirectLink($redirect);
}

/*
* When user have Email address then check login functionaity
*/
function storeAndLogin($user_profile_data, $rand = '')
{
	$module = new sociallogin();
	$email = pSQL($user_profile_data['email']);
	$random_value = '';
	$verified = 1;
	if (!empty($rand) && $user_profile_data['send_verification_email'] == 'yes')
	{
		$random_value = $rand;
		$verified = 0;
	}
	if (!empty($user_profile_data['fname']) && !empty($user_profile_data['name']))
		$username = $user_profile_data['fname'].' '.$user_profile_data['lname'];
	elseif (!empty($user_profile_data['fullname']))
		$username = $user_profile_data['fullname'];
	elseif (!empty($user_profile_data['profilename']))
		$username = $user_profile_data['profilename'];
	elseif (!empty($user_profile_data['nickname']))
		$username = $user_profile_data['nickname'];
	elseif (!empty($email))
	{
		$user_name = explode('@', $email);
		$username = $user_name[0];
	}
	else
	{
		$username = $user_profile_data['id'];
		$first_name = $user_profile_data['id'];
	}
	if ($user_profile_data['dob'])
	{
		$dob_arr = explode('/', $user_profile_data['dob']);
		$dob = $dob_arr[2].'-'.$dob_arr[0].'-'.$dob_arr[1];
	}
	$date_of_birth = (!empty($dob) && Validate::isBirthDate($dob) ? $dob : '');
	$password = Tools::passwdGen();
	$pass = Tools::encrypt($password);
	$date_added = date('Y-m-d H:i:s', time());
	$date_updated = $date_added;
	$last_pass_gen = $date_added;
	$s_key = md5(uniqid(rand(), true));
	$fname = (!empty($user_profile_data['fname']) ? pSQL($user_profile_data['fname']) : pSQL($username));
	$fname = removeSpecial($fname);
	$lname = (!empty($user_profile_data['lname']) ? pSQL($user_profile_data['lname']) : pSQL($username));
	$lname = removeSpecial($lname);
	$gender = ((!empty($user_profile_data['gender']) && (strpos($user_profile_data['gender'], 'f') !== false
	|| (trim($user_profile_data['gender']) == 'F'))) ? 2 : 1);
	$query = 'INSERT into '._DB_PREFIX_."customer (`id_gender`,`id_default_group`,`firstname`,`lastname`,
	`email`,`passwd`,`last_passwd_gen`,`birthday`,`active`,`date_add`,`date_upd`,`secure_key` )
	values ('$gender','1','$fname','$lname','$email','$pass','$last_pass_gen','$date_of_birth',
	'1','$date_added','$date_updated','$s_key') ";
	Db::getInstance()->Execute($query);
	$insert_id = (int)Db::getInstance()->Insert_ID();
	$tbl = pSQL(_DB_PREFIX_.'sociallogin');
	Db::getInstance()->Execute("DELETE FROM $tbl WHERE provider_id='".$user_profile_data['id']."'");
	$query = "INSERT into $tbl (`id_customer`,`provider_id`,`Provider_name`,`verified`,`rand`)
	values ('$insert_id','".$user_profile_data['id']."','".$user_profile_data['provider']."','$verified','$random_value') ";
	Db::getInstance()->Execute($query);
	//extra data from here later to complete
	$tbl = pSQL(_DB_PREFIX_.'customer_group');
	Db::getInstance()->Execute("DELETE FROM $tbl WHERE id_customer='$insert_id'");
	$query = "INSERT into $tbl (`id_customer`,`id_group`) values ('$insert_id','1') ";
	Db::getInstance()->Execute($query);
	if (Configuration::get('user_require_field') == '1')
		extraFields($user_profile_data, $insert_id, $fname, $lname);
	if (!empty($rand) && $user_profile_data['send_verification_email'] == 'yes')
	{
		$to = $email;
		$sub = $module->allLangMessage('Verify your email id. ');
		$protocol_content = (Configuration::get('PS_SSL_ENABLED')) ? 'https://' : 'http://';
		$link = $protocol_content.$_SERVER['HTTP_HOST'].__PS_BASE_URI__."?SL_VERIFY_EMAIL=$rand&SL_PID=".$user_profile_data['id'].'';
		$msg = $module->allLangMessage('Please click on the following link or paste it in browser to verify your email: ').$link;
		slEmail($to, $sub, $msg, $user_profile_data['id']);
	}
	else
	{
		$arr = array();
		$arr['id'] = (string)$insert_id;
		$arr['lname'] = $lname;
		$arr['fname'] = $fname;
		$arr['pass'] = $pass;
		$arr['email'] = $email;
		$arr['loginradius_id'] = $user_profile_data['id'];
		$user['pass'] = $password;
		$user['fname'] = $arr['fname'];
		$user['lname'] = $arr['lname'];
		if (Configuration::get('SEND_REQ') == '1')
			adminEmail($arr['email']);
		if (Configuration::get('user_notification') == '0')
			userNotificationEmail($arr['email'], $user);
		loginRedirect($arr);
	}
}

/*
* save the user data in cookie.
*/
function storeInCookie($user_profile_data)
{
	global $cookie;
	$cookie->login_radius_data = serialize($user_profile_data);
}

/*
* Show poup window for Email and Required fields.
*/
function popUpWindow($msg = '', $data = array())
{
	$module = new sociallogin();
	$style = 'style="padding:10px 11px 10px 30px;overflow-y:auto;height:auto;"';
	$top_style = 'style="top:50%"';
	$profilefield = unserialize(Configuration::get('profilefield'));
	if (empty($profilefield))
		$profilefield[] = '3';
	if (Configuration::get('user_require_field') == '1')
	{
		$height = 100;
		$top_style_value = 50;
		$profile_fiele_value = count($profilefield);
		for ($i = 1; $i < $profile_fiele_value - 1; $i++)
		{
			$height += 50;
			$top_style_value -= 5;
		}
		$top_style = 'style=top:'.$top_style_value.'%;"';
		$style = 'style="padding:10px 11px 10px 30px;overflow-y:auto;height:'.$height.'px;"';
	}
	$profilefield = implode(';', $profilefield);
	?>
	<link rel="stylesheet" type="text/css"
	href="<?php echo __PS_BASE_URI__; ?>/modules/sociallogin/css/sociallogin_style.css"/>
	<script src="//ajax.googleapis.com/ajax/libs/jquery/1.8.0/jquery.min.js"></script>
	<script type="text/javascript" src="<?php echo __PS_BASE_URI__;?>modules/sociallogin/js/popupjs.js"></script>
	<?php
	global $cookie;
	$cookie->sl_hidden = microtime();
	?>
	<div id="fade" class="LoginRadius_overlay">
<div id="popupouter" <?php echo $top_style; ?>>
<div id="popupinner" <?php echo $style; ?>>
	<div id="textmatter"><strong>
			<?php
			if ($msg == '')
			{
				$show_msg = Configuration::get('POPUP_TITLE');
				echo $msg = (!empty($show_msg) ? $show_msg : $module->allLangMessage('Please fill the following details to complete
	the registration'));
			}
			else
				echo $msg;
			?>
</strong></div>
<form method="post" name="validfrm" id="validfrm" action='' onsubmit="return popupvalidation();">
	<?php
	$html = '';
	if (Configuration::get('user_require_field') == '1')
	{
		if (strpos($profilefield, '1') !== false && (empty($data['fname']) || isset($data['firstname'])))
		{
			$html .= '<div>
			<span class="spantxt">'.$module->allLangMessage('First Name').'</span>
			<input type="text" name="SL_FNAME" id="SL_FNAME" placeholder="FirstName"
			value= "'.(Tools::getValue('SL_FNAME') ? htmlspecialchars(Tools::getValue('SL_FNAME')) : '').'" class="inputtxt" />
			</div>';
		}
		if (strpos($profilefield, '2') !== false && (empty($data['lname']) || isset($data['lastname'])))
		{
			$html .= '<div>
			<span class="spantxt">'.$module->allLangMessage('Last Name').'</span>
			<input type="text" name="SL_LNAME" id="SL_LNAME" placeholder="LastName" value=
			"'.(Tools::getValue('SL_LNAME') ? htmlspecialchars(Tools::getValue('SL_LNAME')) : '').'" class="inputtxt" />
			</div>';
		}
		$countries = Db::getInstance()->executeS('
		SELECT *
		FROM '._DB_PREFIX_.'country c WHERE c.active =1');
		if (strpos($profilefield, '3') !== false)
		{
			if (is_array($countries) && !empty($countries))
			{
				$list = '';
				$html .= '<div id="location-country-div">
				<span class="spantxt">'.$module->allLangMessage('Country').'</span>
				<select id="location-country" name="location_country" class="inputtxt"><option value="0">None</option>';
				foreach ($countries as $country)
				{
					$country_name = new Country($country['id_country']);
					$html .= '<option value="'.($country['iso_code']).'"
					'.(Tools::getValue('location_country')
					&& (Tools::getValue('location_country') == $country['iso_code']) ? ' selected="selected"' : '').'>
					'.$country_name->name['1'].'</option>'."\n";
				}
				$html .= '</select></div>';
			}
		}
		$value = true;
		if (Tools::getValue('location_country') && strpos($profilefield, '3') !== false)
		{
			$country = new Country(Tools::getValue('location_country'));
			$value = $country->contains_states;
		}
		if ($value)
		{
			$html .= '<div id="location-state-div" style="display:none;">
		<input id="location-state" type="text" name="location-state" value="empty" />
		</div>';
		}
		else
		{
			$country_id = Db::getInstance()->executeS('
		SELECT *
		FROM '._DB_PREFIX_.'country  c WHERE c.iso_code= "'.Tools::getValue('location_country').'"');
			$states = Db::getInstance()->executeS('
		SELECT *
		FROM '._DB_PREFIX_.'state  c WHERE c.id_country= "'.$country_id['0']['id_country'].'"');
			//State::getStatesByIdCountry($country_id['0']['id_country']);
			if (is_array($states))
			{
				$list = '';
				$style = '';
				if (empty($states))
					$style = 'style="display:none;"';
				$html .= '<div id="location-state-div" '.$style.'>
				<span class="spantxt">'.$module->allLangMessage('State').'</span>
				<select id="location-state" name="location-state" class="inputtxt">';
				if (empty($states))
					$html .= '<option value="empty">None</option>';
				foreach ($states as $state)
				{
					$state_name = new State($state['id_state']);
					$html .= '<option value="'.($state['iso_code']).'"
				'.(Tools::getValue('location-state') && (Tools::getValue('location-state') == $state['iso_code']) ?
				' selected="selected"' : '').'>'.$state_name->name.'</option>'."\n";
				}
				$html .= '</select></div>';
			}
		}
	}
	if (empty($data['email']) || $data['send_verification_email'] == 'yes')
	{
		$html .= '<div><span class="spantxt">'.$module->allLangMessage('Email').'</span>
			<input type="text" name="SL_EMAIL" id="SL_EMAIL" placeholder="Email"
			value= "'.(Tools::getValue('SL_EMAIL') ? htmlspecialchars(Tools::getValue('SL_EMAIL')) : '').'" class="inputtxt" />
			</div>';
	}
	if (Configuration::get('user_require_field') == '1')
	{
		if (strpos($profilefield, '4') !== false)
		{
			$html .= '<div>
			<span class="spantxt">'.$module->allLangMessage('City').'</span>
			<input type="text" name="SL_CITY" id="SL_CITY" placeholder="City"
			value= "'.(Tools::getValue('SL_CITY') ? htmlspecialchars(Tools::getValue('SL_CITY')) : '').'" class="inputtxt" />
			</div>';
		}
		if (strpos($profilefield, '5') !== false)
		{
			$html .= '<div><span class="spantxt">'.$module->allLangMessage('Mobile Number').'</span>
			<input type="text" name="SL_PHONE" id="SL_PHONE" placeholder="Mobile Number"
			value= "'.(Tools::getValue('SL_PHONE') ? htmlspecialchars(Tools::getValue('SL_PHONE')) : '').'" class="inputtxt" />
			</div>';
		}
		if (strpos($profilefield, '6') !== false)
		{
			$html .= '<div><span class="spantxt">'.$module->allLangMessage('Address').'</span>
			<input type="text" name="SL_ADDRESS" id="SL_ADDRESS" placeholder="Address"
			value= "'.(Tools::getValue('SL_ADDRESS') ? htmlspecialchars(Tools::getValue('SL_ADDRESS')) : '').'" class="inputtxt" />
			</div>';
		}
		if (strpos($profilefield, '8') !== false)
		{
			$html .= '<div><span class="spantxt">'.$module->allLangMessage('ZIP code').'</span>
			<input type="text" name="SL_ZIP_CODE" id="SL_ZIP_CODE" placeholder="Zip Code"
			value= "'.(Tools::getValue('SL_ZIP_CODE') ? htmlspecialchars(Tools::getValue('SL_ZIP_CODE')) : '').'" class="inputtxt" />
			</div>';
		}

		if (strpos($profilefield, '7') !== false)
		{
			$html .= '<div><span class="spantxt">'.$module->allLangMessage('Address Title').'</span>
			<input type="text" name="SL_ADDRESS_ALIAS" id="SL_ADDRESS_ALIAS"
			placeholder="Please assign an address title for future reference"
			value= "'.(Tools::getValue('SL_ADDRESS_ALIAS') ? htmlspecialchars(Tools::getValue('SL_ADDRESS_ALIAS')) : '').'"
			class="inputtxt" /></div>';
		}
	}
	$html .= '<div><input type="hidden" name="hidden_val" value="'.$cookie->sl_hidden.'" />
	<input type="submit" id="LoginRadius" name="LoginRadius" value="'.$module->allLangMessage('Submit').'" class="inputbutton">
	<input type="button" value="'.$module->allLangMessage('Cancel').'"
	class="inputbutton" onclick="window.location.href=window.location.href;" />
	</div></div>
	</form>
	</div>
	</div>
	</div>';
	echo $html;
}

/*
 * Verify email-address.
 */
function verifyEmail()
{
	$module = new sociallogin();
	$tbl = pSQL(_DB_PREFIX_.'sociallogin');
	$pid = pSQL(Tools::getValue('SL_PID'));
	$rand = pSQL(Tools::getValue('SL_VERIFY_EMAIL'));
	$db = Db::getInstance()->ExecuteS('SELECT * FROM  '.pSQL(_DB_PREFIX_)."sociallogin  WHERE rand='$rand'
	and provider_id='$pid' and verified='0'");
	$num = (!empty($db['0']['id_customer']) ? $db['0']['id_customer'] : '');
	$provider_name = (!empty($db['0']['Provider_name']) ? pSQL($db['0']['Provider_name']) : '');
	if ($num < 1)
		return;
	Db::getInstance()->Execute("UPDATE $tbl SET verified='1' , rand='' WHERE rand='$rand' and provider_id='$pid'");
	Db::getInstance()->Execute("UPDATE $tbl SET rand='' WHERE Provider_name='$provider_name' and id_customer='$num'");
	$msg = $module->allLangMessage('Email is verified. Now you can login using Social Login.');
	popUpVerify($msg);
}

/*
 * send credentials to customer.
 */
function userNotificationEmail($email, $user)
{
	$module = new sociallogin();
	$protocol_content = (Configuration::get('PS_SSL_ENABLED')) ? 'https://' : 'http://';
	$link = $protocol_content.$_SERVER['HTTP_HOST'];
	$sub = $module->allLangMessage('Thank You For Registration');
	$vars = array(
		'{firstname}' => $user['fname'],
		'{lastname}' => $user['lname'],
		'{email}' => $email,
		'{passwd}' => $user['pass']
	);
	$id_lang = (int)Configuration::get('PS_LANG_DEFAULT');
	Mail::Send($id_lang, 'account', $sub, $vars, $email);
}
/*
 * Notify admin when new user register.
 */
function adminEmail($email)
{
	$module = new sociallogin();
	$protocol_content = (Configuration::get('PS_SSL_ENABLED')) ? 'https://' : 'http://';
	$link = $protocol_content.$_SERVER['HTTP_HOST'];
	$sub = $module->allLangMessage('New User Registration');
	$msg = $module->allLangMessage('New User Registered to your site');
	$vars = array(
		'{email}' => $email,
		'{message}' => $msg
	);
	$db = Db::getInstance()->ExecuteS('SELECT * FROM  '.pSQL(_DB_PREFIX_).'employee  WHERE id_profile=1 ');
	$id_lang = (int)Configuration::get('PS_LANG_DEFAULT');
	foreach ($db as $row)
	{
		$find_id = $row['id_employee'];
		$find_email = $row['email'];
		Mail::Send($id_lang, 'contact', $sub, $vars, $find_email);
	}
}
/*
 * Send mail.
 */
function slEmail($to, $sub, $msg, $social_id)
{
	$module = new sociallogin();
	$msgg = $module->allLangMessage('Your confirmation link has been sent to your email address.
	Please verify your email by clicking on confirmation link.');
	popUpVerify($msgg, $social_id);
	$id_lang = (int)Configuration::get('PS_LANG_DEFAULT');
	$protocol_content = (Configuration::get('PS_SSL_ENABLED')) ? 'https://' : 'http://';
	$link = $protocol_content.$_SERVER['HTTP_HOST'].__PS_BASE_URI__;
	$vars = array(
		'{email}' => $to,
		'{message}' => $msg
	);
	Mail::Send($id_lang, 'contact', $sub, $vars, $to);
}
/*
 * Get random character.
 */
function slRandomChar()
{
	$char = '';
	for ($i = 0; $i < 20; $i++)
		$char .= rand(0, 9);
	return ($char);
}
/*
 * Save data after POPup call.
 */
function slDataSave($lrdata = array())
{
	$module = new sociallogin();
	global $cookie;
	$provider_id = $lrdata['id'];
	$provider_name = $lrdata['provider'];
	if (!$cookie->isLogged())
	{
		$email = $lrdata['email'];
		$query = 'SELECT c.id_customer from '._DB_PREFIX_.'customer AS c INNER JOIN '._DB_PREFIX_."sociallogin AS sl
		ON sl.id_customer=c.id_customer  WHERE c.email='$email'";
		$query = Db::getInstance()->ExecuteS($query);
		if (!empty($query['0']['id_customer']))
		{
			$error_message = Configuration::get('ERROR_MESSAGE');
			$error_msg = "<p style ='color:red;'>".$error_message.'</p>';
			$lrdata['email'] = '';
			popUpWindow($error_msg, $lrdata);
			return;
		}
		else
		{
			$cookie->login_radius_data = '';
			$cookie->sl_hidden = '';
			$query1 = 'SELECT * FROM '._DB_PREFIX_."customer  WHERE email='$email'";
			$query1 = Db::getInstance()->ExecuteS($query1);
			$num = (!empty($query1['0']['id_customer']) ? $query1['0']['id_customer'] : '');
			if (!empty($num))
			{
				$rand = slRandomChar();
				$fname = (!empty($query1['0']['firstname']) ? $query1['0']['firstname'] : '');
				$lname = (!empty($query1['0']['lastname']) ? $query1['0']['lastname'] : '');
				$tbl = pSQL(_DB_PREFIX_.'sociallogin');
				$query = "INSERT into $tbl (`id_customer`,`provider_id`,`Provider_name`,`rand`,`verified`)
				values ('$num','$provider_id','$provider_name','$rand','0') ";
				Db::getInstance()->Execute($query);
				$to = $email;
				$sub = $module->allLangMessage('Verify your email id. ');
				$protocol_content = (Configuration::get('PS_SSL_ENABLED')) ? 'https://' : 'http://';
				$link = $protocol_content.$_SERVER['HTTP_HOST'].__PS_BASE_URI__."?SL_VERIFY_EMAIL=$rand&SL_PID=$provider_id";
				$msg = $module->allLangMessage('Please click on the following link or paste it in browser
				to verify your email: ').$link;
				$sub = $module->allLangMessage('Verify your email id.');
				slEmail($email, $sub, $msg, $provider_id);
				return;
			}
			else
			{
				$rand = slRandomChar();
				storeAndLogin($lrdata, $rand);
			}
		}
	}
}
/*
 * Show Error Message.
 */
function popUpVerify($msg, $social_id = '')
{
	$module = new sociallogin();
	$http = (Configuration::get('PS_SSL_ENABLED'));
	if ($http == 0)
		$http = 'http://';
	else
		$http = 'https://';
	$home = $http.$_SERVER['HTTP_HOST'].$_SERVER['PHP_SELF'];
	$html = '
	<link rel="stylesheet" type="text/css" href="'.__PS_BASE_URI__.'/modules/sociallogin/css/sociallogin_style.css" />
	<div id="fade" class="LoginRadius_overlay">
	<div id="popupouter">
	<div id="popupinner">
	<div id="textmatter">'.$msg.'';
	$html .= '</div><form method="post" name="validform_verify" action="">';
	$html .= '
	<input type="button" value="'.$module->allLangMessage('Ok').'" onclick=window.location.assign("'.$home.'") class="inputbutton" />';
	if (!empty($social_id))
	{
		$html .= '
		<input type="hidden" value="'.$social_id.'" name="social_id_value" class="inputbutton" />
		<input type="submit" value="'.$module->allLangMessage('Resend Email Verification').'"
		name="resend_email_verification" class="inputbutton" />';
	}
	$html .= '</form></div></div></div></div>';
	echo $html;
}
/*
 * Insert popup optional fields.
 */
function extraFields($data, $insert_id, $fname, $lname, $update = '')
{
	$str = '';
	if (!empty($data['country']))
	{
		$country = $data['country'];
		$id = pSQL(getIdByCountryISO($country));
		if (!empty($id))
			$str .= "id_country='$id',";
	}
	if (!empty($data['country']) && empty($id))
	{
		$country = $data['country'];
		$id = pSQL(getIdByCountryName($country));
		if (!empty($id))
			$str = "id_country='$id',";
	}
	elseif (empty($id))
	{
		$id = (int)Configuration::get('PS_COUNTRY_DEFAULT');
		if (!empty($id))
			$str .= "id_country='$id',";
	}
	if (isset($data['state']) && $data['state'] != 'empty' && !empty($data['state']))
	{
		$state = $data['state'];
		$iso = pSQL(getIsoByState($state));
		if (!empty($iso))
			$str .= "id_state='$iso',";
	}
	if (isset($data['city']) && !empty($data['city']))
	{
		$city = pSQL($data['city']);
		$str .= "city='$city',";
	}
	if (isset($data['zipcode']) && !empty($data['zipcode']))
	{
		$zip = trim(pSQL($data['zipcode']));
		$str .= "postcode='$zip',";
	}
	if (isset($data['address']) && !empty($data['address']))
	{
		$address = pSQL($data['address']);
		$str .= "address1='$address',";
	}
	if (isset($data['phonenumber']) && !empty($data['phonenumber']))
	{
		$phone = pSQL($data['phonenumber']);
		$str .= "phone_mobile='$phone',";
	}
	if (isset($data['addressalias']) && !empty($data['addressalias']))
	{
		$add_alias = pSQL($data['addressalias']);
		$str .= "alias='$add_alias',";
	}
	$tbl = _DB_PREFIX_.'address';
	$date = date('y-m-d h:i:s');
	if ($update == 'yes')
	{
		if (!empty($fname))
			$str .= "firstname='$fname',";
		if (!empty($lname))
			$str .= "lastname='$lname',";
		$q = "UPDATE $tbl SET ".$str." date_upd='$date' WHERE id_customer='$insert_id'";
		$q = Db::getInstance()->Execute($q);
	}
	else
	{
		$str .= "date_add='$date',date_upd='$date',";
		$fname = pSQL($fname);
		$lname = pSQL($lname);
		$q = "INSERT into $tbl SET ".$str." id_customer='$insert_id', lastname='$lname',firstname='$fname' ";
		$q = Db::getInstance()->Execute($q);
	}
}
/*
 * Get country name by Counter ISo=code.
 */
function getIdByCountryISO($iso_value)
{
	if (!empty($iso_value))
	{
		$tbl = _DB_PREFIX_.'country';
		$field = 'iso_code';
		if (isset($iso_value->Code))
			$iso_value = $iso_value->Code;
		if (isset($iso_value) && !is_array($iso_value))
		{
			$iso_value = pSQL(trim($iso_value));
			$q = "SELECT * from $tbl WHERE $field='$iso_value'";
			$q = Db::getInstance()->ExecuteS($q);
			$iso_value = '';
			$iso_value = (isset($q[0]['id_country']) ? $q[0]['id_country'] : '');
			return ($iso_value);
		}
		return '';
	}
}
/*
 * Get Counter name by ID.
 */
function getIdByCountryName($country)
{
	if (!empty($country))
	{
		if (isset($country->Name))
			$country = $country->Name;
		if (is_string($country))
		{
			$iso = '';
			$tbl = _DB_PREFIX_.'country_lang';
			$country = pSQL(trim($country));
			$q = "SELECT * from $tbl WHERE name='$country'";
			$q = Db::getInstance()->ExecuteS($q);
			if (!empty($q))
				$iso = $q[0]['id_country'];
			return $iso;
		}
	}
	return '';
}
/*
 * Get State. from ISO-code.
 */
function getIsoByState($state)
{
	if (!empty($state) && is_string($state))
	{
		$tbl = _DB_PREFIX_.'state';
		$q = "SELECT * from $tbl WHERE  iso_code ='$state'";
		$q = Db::getInstance()->ExecuteS($q);
		if (!empty($q))
		{
			$id = $q[0]['id_state'];
			return ($id);
		}
	}
	return '';
}
/*
 * remove special character from name.
 */
function removeSpecial($field)
{
	$in_str = str_replace(array('<', '>', '&', '{', '}', '*', '/', '(', '[', ']', '@', '!', ')', '&', '*',
	'#', '$', '%', '^', '|', '?', '+', '=', '"', ','), array(''), $field);
	$cur_encoding = mb_detect_encoding($in_str);
	if ($cur_encoding == 'UTF-8' && mb_check_encoding($in_str, 'UTF-8'))
		$name = $in_str;
	else
		$name = utf8_encode($in_str);
	if (!Validate::isName($name))
	{
		$len = strlen($name);
		$return_val = '';
		for ($i = 0; $i < $len; $i++)
		{
			if (ctype_alpha($name[$i]))
				$return_val .= $name[$i];
		}
		$name = $return_val;
		if (empty($name))
		{
			$letters = range('a', 'z');
			for ($i = 0; $i < 5; $i++)
				$name .= $letters[rand(0, 26)];
		}
	}
	return $name;
}

/*
* Retrieve random Email address.
*/
function emailRand($lrdata)
{
	switch ($lrdata['provider'])
	{
		case 'twitter':
			$email = $lrdata['id'].'@'.$lrdata['provider'].'.com';
			break;
		default:
			$email_id = substr($lrdata['id'], 7);
			$email_id2 = str_replace('/', '_', $email_id);
			$email = str_replace('.', '_', $email_id2).'@'.$lrdata['provider'].'.com';
			break;
	}
	return $email;
}

function loginRadiusMappingProfileData($userprofile)
{
	$lrdata['fullname'] = (!empty($userprofile->FullName) ? $userprofile->FullName : '');
	$lrdata['profilename'] = (!empty($userprofile->ProfileName) ? $userprofile->ProfileName : '');
	$lrdata['nickname'] = (!empty($userprofile->NickName) ? $userprofile->NickName : '');
	$lrdata['fname'] = (!empty($userprofile->FirstName) ? $userprofile->FirstName : '');
	$lrdata['lname'] = (!empty($userprofile->LastName) ? $userprofile->LastName : '');
	$lrdata['id'] = (!empty($userprofile->ID) ? $userprofile->ID : '');
	$lrdata['provider'] = (!empty($userprofile->Provider) ? $userprofile->Provider : '');
	$lrdata['nickname'] = (!empty($userprofile->NickName) ? $userprofile->NickName : '');
	$lrdata['email'] = (count($userprofile->Email) > 0 ? $userprofile->Email[0]->Value : '');
	$lrdata['thumbnail'] = (!empty($userprofile->ImageUrl) ? trim($userprofile->ImageUrl) : '');
	if (empty($lrdata['thumbnail']) && $lrdata['provider'] == 'facebook')
		$lrdata['thumbnail'] = 'http://graph.facebook.com/'.$lrdata['id'].'/picture?type=square';
	$lrdata['dob'] = (!empty($userprofile->BirthDate) ? $userprofile->BirthDate : '');
	$lrdata['gender'] = (!empty($userprofile->Gender) ? $userprofile->Gender : '');
	$lrdata['company'] = (!empty($userprofile->Positions[1]->Company->Name) ? $userprofile->Positions[1]->Company->Name : '');
	if (empty($lrdata['company']))
		$lrdata['company'] = (!empty($userprofile->Industry) ? $userprofile->Industry : '');
	$lrdata['hometown'] = (!empty($userprofile->HomeTown) ? $userprofile->HomeTown : '');
	$lrdata['aboutme'] = (!empty($userprofile->About) ? $userprofile->About : '');
	$lrdata['website'] = (!empty($userprofile->ProfileUrl) ? $userprofile->ProfileUrl : '');
	$lrdata['state'] = (!empty($userprofile->State) ? $userprofile->State : '');
	$lrdata['city'] = (!empty($userprofile->City) ? $userprofile->City : '');
	if (empty($lrdata['city']) || $lrdata['city'] == 'unknown')
		$lrdata['city'] = (!empty($userprofile->LocalCity) && $userprofile->LocalCity != 'unknown' ? $userprofile->LocalCity : '');
	$lrdata['country'] = (!empty($userprofile->Country) ? $userprofile->Country : '');
	if (empty($lrdata['country']))
		$lrdata['country'] = (!empty($userprofile->LocalCountry) ? $userprofile->LocalCountry : '');
	$lrdata['phonenumber'] = (!empty($userprofile->PhoneNumbers['0']->PhoneNumber) ? $userprofile->PhoneNumbers['0']->PhoneNumber : '');
	$lrdata['address'] = (!empty($userprofile->Addresses['0']->Address1) ? $userprofile->Addresses['0']->Address1 : '');
	$lrdata['zipcode'] = (!empty($userprofile->Addresses['0']->PostalCode) ? $userprofile->Addresses['0']->PostalCode : '');
	return $lrdata;
}

?>