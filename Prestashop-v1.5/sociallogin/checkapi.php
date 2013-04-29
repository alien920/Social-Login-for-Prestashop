<?php 
if (isset($_GET['apikey'])){
  $apikey = trim($_GET['apikey']);
  $apisecret = trim($_GET['apisecret']);
  $apicred = $_GET['api_request'];
  if (!isValidApiSettings($apikey)) {
    echo '<div id="Error">Please enter a valid API Key.</div>';
  }
  elseif(!isValidApiSettings($apisecret)) {
    echo '<div id="Error">Please enter a valid API Secret.</div>';
  }
  elseif($apikey == $apisecret) {
  echo '<div id="Error">Both API key and Secret are same .Please enter the correct API key and Secret.</div>';
  }
  elseif (check_api_settings($apikey, $apisecret, $apicred)) {
    echo check_api_settings($apikey, $apisecret, $apicred);
  }
}
/**
 * Check apikey and secret is valid.
 */
  function isValidApiSettings($apikey) {
    return !empty($apikey) && preg_match('/^\{?[A-Z0-9]{8}-[A-Z0-9]{4}-[A-Z0-9]{4}-[A-Z0-9]{4}-[A-Z0-9]{12}\}?$/i', $apikey);
  }

/**
 * Check api credential settings.
 */
  function check_api_settings($apikey, $apisecret, $apicred) {
  	$JsonResponse='';
    if (isset($apikey)){
      $ValidateUrl = "https://hub.loginradius.com/ping/$apikey/$apisecret";
      if ($apicred == 'curl') {
	    if (in_array ('curl', get_loaded_extensions ()) AND function_exists('curl_exec')) {
          $curl_handle = curl_init();
          curl_setopt($curl_handle, CURLOPT_URL, $ValidateUrl);
          curl_setopt($curl_handle, CURLOPT_CONNECTTIMEOUT, 5);
		  curl_setopt($curl_handle, CURLOPT_TIMEOUT, 5);
          curl_setopt($curl_handle, CURLOPT_SSL_VERIFYPEER, false);
          if (ini_get('open_basedir') == '' && (ini_get('safe_mode') == 'Off' or !ini_get('safe_mode'))){
            curl_setopt($curl_handle, CURLOPT_FOLLOWLOCATION, 1);
          }
          else {
            curl_setopt($curl_handle,CURLOPT_HEADER, 1);
            $url = curl_getinfo($curl_handle,CURLINFO_EFFECTIVE_URL);
            curl_close($curl_handle);
            $curl_handle = curl_init();
            $url = str_replace('?','/?',$url);
            curl_setopt($curl_handle, CURLOPT_URL, $url);
         }
		 curl_setopt($curl_handle, CURLOPT_RETURNTRANSFER, true);
		 $JsonResponse = curl_exec($curl_handle);
		 $httpCode = curl_getinfo($curl_handle, CURLINFO_HTTP_CODE);
			 if(in_array($httpCode, array(400, 401, 403, 404, 500, 503, 0)) && $httpCode != 200){
				return '<div id="Error">Uh oh, looks like something went wrong. Try again in a sec!</div>';
			 }
			 else{
				if(curl_errno($curl_handle) == 28){
					return '<div id="Error">Uh oh, looks like something went wrong. Try again in a sec!</div>';
				}
			 }
		}
		else{
			return '<div id="Error">Please check your CURL setting in php.ini file.</div>';
		}
		$UserProfile = json_decode($JsonResponse);
		if (isset( $UserProfile->ok)) {
			return '<div id="Success">Your API Connection settings are working correctly. Please Save your current Settings.</div>';
		}
		else{
			return '<div id="Error">Please enter correct API Key and Secret from your loginRadius Account.</div>';
		}
		curl_close($curl_handle);
      }
      else {
        $JsonResponse = @file_get_contents($ValidateUrl);
        if(strpos(@$http_response_header[0], "400") !== false || strpos(@$http_response_header[0], "401") !== false || strpos(@$http_response_header[0], "403") !== false || strpos(@$http_response_header[0], "404") !== false || strpos(@$http_response_header[0], "500") !== false || strpos(@$http_response_header[0], "503") !== false){
				return '<div id="Error">Uh oh, looks like something went wrong. Try again in a sec!</div>';
		 }
        $UserProfile = json_decode($JsonResponse);
		if(empty($JsonResponse)){
			return '<div id="Error">Please check your FSOCKOPEN setting in php.ini file.</div>';
		}
		if (isset( $UserProfile->ok)) {
			return '<div id="Success">Your API Connection settings are working correctly. Please Save your current Settings.</div>';
		}
		else{
			return '<div id="Error">Please enter correct API Key and Secret from your loginRadius Account.</div>';
		}
       }
    }
  }
