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

/* SSL Management */
$useSSL = true;
include(dirname(__FILE__).'/../../config/config.inc.php');
include(dirname(__FILE__).'/../../header.php');
include_once(dirname(__FILE__).'/sociallogin.php');
include_once(dirname(__FILE__).'/sociallogin_functions.php');

global $smarty;
$errors = array();
if ($cookie->isLogged())
{
	if (Configuration::get('PS_TOKEN_ACTIVATED') == 1
	&& strcmp(Tools::getToken(), Tools::getValue('token')))
		$errors[] = Tools::displayError('Invalid token');
	if (!count($errors))
	{
		$sociallogin = new sociallogin();
		$smarty->assign(array(
			'lr_check' => $sociallogin->jsinterface()
		));
		$smarty->assign('socialloginlrmessage', $cookie->lrmessage);
		$cookie->lrmessage = '';
		if (file_exists(dirname(__FILE__).'/sociallinking.tpl'))
			$smarty->display(dirname(__FILE__).'/sociallinking.tpl');
		else
			echo Tools::displayError('No template found');
	}
} else
	Tools::redirect('authentication.php?back=my-account.php');
include(dirname(__FILE__).'/../../footer.php');