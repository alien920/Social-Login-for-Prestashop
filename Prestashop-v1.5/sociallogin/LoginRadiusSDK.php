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

/**
 * LoginRadius SDK class
 */
class LoginRadius
{
	public $is_authenticated, $json_response, $user_profile;

	/**
	 * Get data from LoginRadius API endpoints.
	 * @param $api_secrete
	 * @return mixed
	 */
	public function loginRadiusGetData($api_secrete)
	{
		$is_authenticated = false;
		if (Tools::getValue('token'))
		{
			$token = Tools::getValue('token');
			$validate_url = 'https://hub.loginradius.com/UserProfile/'.$api_secrete.'/'.$token;
			$json_response = $this->loginRadiusCallApi($validate_url);
			$user_profile = Tools::jsonDecode($json_response);
			if (isset($user_profile->ID) && $user_profile->ID != '')
			{
				$this->is_authenticated = true;
				return $user_profile;
			}
		}
	}

	/**
	 * Call LoginRadius API to get data.
	 */
	public function loginRadiusCallApi($validate_url)
	{
		$useapi = Configuration::get('CURL_REQ');
		if ($useapi == 'curl')
		{
			$curl_handle = curl_init();
			curl_setopt($curl_handle, CURLOPT_URL, $validate_url);
			curl_setopt($curl_handle, CURLOPT_CONNECTTIMEOUT, 5);
			curl_setopt($curl_handle, CURLOPT_TIMEOUT, 15);
			curl_setopt($curl_handle, CURLOPT_SSL_VERIFYPEER, false);
			if (ini_get('open_basedir') == '' && (ini_get('safe_mode') == 'Off' || !ini_get('safe_mode')))
			{
				curl_setopt($curl_handle, CURLOPT_FOLLOWLOCATION, 1);
				curl_setopt($curl_handle, CURLOPT_RETURNTRANSFER, true);
			}
			else
			{
				curl_setopt($curl_handle, CURLOPT_HEADER, 1);
				$url = curl_getinfo($curl_handle, CURLINFO_EFFECTIVE_URL);
				curl_close($curl_handle);
				$curl_handle = curl_init();
				$url = str_replace('?', '/?', $url);
				curl_setopt($curl_handle, CURLOPT_URL, $url);
				curl_setopt($curl_handle, CURLOPT_RETURNTRANSFER, true);
				curl_setopt($curl_handle, CURLOPT_SSL_VERIFYPEER, false);
			}
			$json_response = curl_exec($curl_handle);
			$http_code = curl_getinfo($curl_handle, CURLINFO_HTTP_CODE);
			if (in_array($http_code, array(400, 401, 403, 404, 500, 503)) && $http_code != 200)
				return '<div id="Error">Uh oh, looks like something went wrong. Try again in a sec!</div>';
			else if (curl_errno($curl_handle) == 28)
				return '<div id="Error">Uh oh, looks like something went wrong. Try again in a sec!</div>';
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
		}
		return $json_response;
	}
}

?>