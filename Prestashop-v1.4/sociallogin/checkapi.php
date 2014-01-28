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
require_once '../../config/config.inc.php';
require_once '../../init.php';
$errors = array();
if (Configuration::get('PS_TOKEN_ACTIVATED') == 1 && strcmp(Tools::getToken(), Tools::getValue('token')))
	$errors[] = Tools::displayError('Invalid token');
if (Tools::getValue('apikey') && !count($errors))
{
	$apikey = trim(Tools::getValue('apikey'));
	$apisecret = trim(Tools::getValue('apisecret'));
	$apicred = Tools::getValue('api_request');
	if (!isValidApiSettings($apikey))
		echo '<div id="Error">Please enter a valid API Key.</div>';
	elseif (!isValidApiSettings($apisecret))
		echo '<div id="Error">Please enter a valid API Secret.</div>';
	elseif ($apikey == $apisecret)
		echo '<div id="Error">Please enter a valid API Key and  Secret</div>';
	elseif (checkApiSettings($apikey, $apisecret, $apicred))
		echo checkApiSettings($apikey, $apisecret, $apicred);
}
/**
 * Check apikey and secret is valid.
 */
function isValidApiSettings($apikey)
{
	return !empty($apikey) && preg_match('/^\{?[A-Z0-9]{8}-[A-Z0-9]{4}-[A-Z0-9]{4}-[A-Z0-9]{4}-[A-Z0-9]{12}\}?$/i', $apikey);
}

/**
 * Check api credential settings.
 */
function checkApiSettings($apikey, $apisecret, $apicred)
{
	$json_response = '';
	if (isset($apikey))
	{
		$validate_url = "https://hub.loginradius.com/ping/$apikey/$apisecret";
		if ($apicred == 'curl')
		{
			if (in_array('curl', get_loaded_extensions()) && function_exists('curl_exec'))
			{
				$curl_handle = curl_init();
				curl_setopt($curl_handle, CURLOPT_URL, $validate_url);
				curl_setopt($curl_handle, CURLOPT_CONNECTTIMEOUT, 5);
				curl_setopt($curl_handle, CURLOPT_TIMEOUT, 15);
				curl_setopt($curl_handle, CURLOPT_SSL_VERIFYPEER, false);
				if (ini_get('open_basedir') == '' && (ini_get('safe_mode') == 'Off' || !ini_get('safe_mode')))
					curl_setopt($curl_handle, CURLOPT_FOLLOWLOCATION, 1);
				else
				{
					curl_setopt($curl_handle, CURLOPT_HEADER, 1);
					$url = curl_getinfo($curl_handle, CURLINFO_EFFECTIVE_URL);
					curl_close($curl_handle);
					$curl_handle = curl_init();
					$url = str_replace('?', '/?', $url);
					curl_setopt($curl_handle, CURLOPT_URL, $url);
				}
				curl_setopt($curl_handle, CURLOPT_RETURNTRANSFER, true);
				$json_response = curl_exec($curl_handle);
				$http_code = curl_getinfo($curl_handle, CURLINFO_HTTP_CODE);
				if (in_array($http_code, array(400, 401, 403, 404, 500, 503, 0)) && $http_code != 200)
					return '<div id="Error">Uh oh, looks like something went wrong. Try again in a sec!</div>';
				else if (curl_errno($curl_handle) == 28)
					return '<div id="Error">Uh oh, looks like something went wrong. Try again in a sec!</div>';
			}
			else
				return '<div id="Error">Please check your CURL setting in php.ini file.</div>';
			$user_profile = Tools::jsonDecode($json_response);
			if (isset($user_profile->ok))
				return '<div id="Success">Your API Connection settings are working correctly. Please Save your current Settings.</div>';
			else
				return '<div id="Error">Please enter correct API Key and Secret from your loginRadius Account.</div>';
			curl_close($curl_handle);
		}
		else
		{
			$json_response = Tools::file_get_contents($validate_url);
			if (isset($http_response_header) && !empty($http_response_header[0]))
			{
				if (strpos($http_response_header[0], '400') !== false
				|| strpos($http_response_header[0], '401') !== false
				|| strpos($http_response_header[0], '403') !== false
				|| strpos($http_response_header[0], '404') !== false
				|| strpos($http_response_header[0], '500') !== false
				|| strpos($http_response_header[0], '503') !== false)
					return '<div id="Error">Uh oh, looks like something went wrong. Try again in a sec!</div>';
			}
			$user_profile = Tools::jsonDecode($json_response);
			if (empty($json_response))
				return '<div id="Error">Please check your FSOCKOPEN setting in php.ini file.</div>';
			if (isset($user_profile->ok))
				return '<div id="Success">Your API Connection settings are working correctly. Please Save your current Settings.</div>';
			else
				return '<div id="Error">Please enter correct API Key and Secret from your loginRadius Account.</div>';
		}
	}
}
