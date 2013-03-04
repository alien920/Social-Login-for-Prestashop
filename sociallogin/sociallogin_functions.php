<?php

if ( !defined( '_PS_VERSION_' ) ){

	exit;

}



function redirectURL(){

	$loc=Configuration::get('LoginRadius_redirect');
	
	if($loc=="backpage"){
	
		$loc=Tools::getValue('back');
		
	}elseif($loc=="profile"){
	
		$loc="my-account.php";
		
	}
	elseif($loc=="url"){
	
		$loc=Configuration::get('redirecturl');
		
	}
	
	return $loc;
}



function loginRedirect($arr){

	global $cookie;

	$cookie->id_compare = $arr['id'];

	$cookie->id_customer = $arr['id'];

	$cookie->customer_lastname = $arr['lname'];

	$cookie->customer_firstname = $arr['fname'];

	$cookie->logged = 1;

	$cookie->passwd = $arr['pass'];

	$cookie->email = $arr['email'];

	$cookie->lr_login="true";
	
	if ((empty($cookie->id_cart) || Cart::getNbProducts($cookie->id_cart) == 0))
	$cookie->id_cart = (int)Cart::lastNoneOrderedCart($cookie->id_customer);

	Module::hookExec('authentication');

	$redirect=redirectURL();

	Tools::redirectLink($redirect);

}



function storeAndLogin($obj){



	$email=pSQL($obj->Email);

	$provider_id=$obj->ID;
	
	$provider_name=pSQL($obj->Provider);

	$query3 = Db::getInstance()->ExecuteS('SELECT * FROM '.pSQL(_DB_PREFIX_.'customer').' as c WHERE c.email='." '$email' ".' LIMIT 0,1');

	$num=(!empty($query3['0']['id_customer'])?$query3['0']['id_customer']:"");

	//user email already exists in customer table

	if($num>=1){

				$insert_id=$num;

		//user id in social login too?

		$query2 = Db::getInstance()->ExecuteS('SELECT * FROM '.pSQL(_DB_PREFIX_.'sociallogin').' as sl WHERE sl.id_customer='." '$num' ".' LIMIT 0,1');

		$num=(!empty($query2['0']['id_customer'])?$query2['0']['id_customer']:"");

		if($num<1){		
		
			$tbl=pSQL(_DB_PREFIX_.'sociallogin');

			$query= "INSERT into $tbl (`id_customer`,`provider_id`,`Provider_name`,`verified`,`rand`) values ('$insert_id','$provider_id','$provider_name','1','') ";

			Db::getInstance()->Execute($query);

		}

		//login user

		$arr['id']=$num;

		$arr['lname']=$query3['0']['lastname'];

		$arr['fname']=$query3['0']['firstname'];

		$arr['pass']=$query3['0']['passwd'];

		$arr['email']=$query3['0']['email'];

		loginRedirect($arr);

		return;

	}

	//insert into customer and sociallogin table.

	$password = Tools::passwdGen();

	$pass=Tools::encrypt($password);

	$date_added=date("Y-m-d H:i:s",time());

	$date_updated=$date_added;

	$last_pass_gen=$date_added;

	$s_key = md5(uniqid(rand(), true));

	$fname=(!empty($obj->FirstName) ? pSQL($obj->FirstName) : pSQL($obj->username));

	$fname=remove_special($fname);

	$lname=(!empty($obj->LastName) ? pSQL($obj->LastName) : pSQL($obj->FirstName));

	$lname=remove_special($lname);
	
	$newsletter='0';
	
	$optin='0';

	$gender=pSQL($obj->Gender);

	$bday=pSQL($obj->BirthDate);
	
	$required_field_check = Db::getInstance()->ExecuteS("SELECT field_name FROM  ".pSQL(_DB_PREFIX_)."required_field");
	
    foreach ($required_field_check AS $item){
	  if($item['field_name']=='newsletter')
	  
	  $newsletter='1';
	  
	  if($item['field_name']=='optin')
	  
	  $optin='1';
	  
	  }

	$query= "INSERT into "._DB_PREFIX_."customer (`id_gender`,`id_default_group`,`firstname`,`lastname`,`email`,`passwd`,`last_passwd_gen`,`birthday`,`newsletter`,`optin`,`active`,`date_add`,`date_upd`,`secure_key` ) values ('$gender','1','$fname','$lname','$email','$pass','$last_pass_gen','$bday','$newsletter','$optin','1','$date_added','$date_updated','$s_key') ";

	Db::getInstance()->Execute($query);

	$insert_id=(int)Db::getInstance()->Insert_ID();

	$tbl=pSQL(_DB_PREFIX_.'sociallogin');

	Db::getInstance()->Execute("DELETE FROM $tbl WHERE provider_id='$provider_id'");

	$query= "INSERT into $tbl (`id_customer`,`provider_id`,`Provider_name`,`verified`,`rand`) values ('$insert_id','$provider_id','$provider_name','1','') ";

	Db::getInstance()->Execute($query);

	//extra data from here later to complete

	$tbl=pSQL(_DB_PREFIX_.'customer_group');

	Db::getInstance()->Execute("DELETE FROM $tbl WHERE id_customer='$insert_id'");

	$query= "INSERT into $tbl (`id_customer`,`id_group`) values ('$insert_id','1') ";

	Db::getInstance()->Execute($query);

	extraFields($obj,$insert_id,$fname,$lname);

	$arr=array();

	$arr['id']=(string)$insert_id;

	$arr['lname']=$lname;

	$arr['fname']=$fname;

	$arr['pass']=$pass;

	$arr['email']=$email;

	if(Configuration::get('SEND_REQ')=="1")

	Admin_email($arr['email'],$arr['fname'],$arr['lname']);

	loginRedirect($arr);	

}



function storeInCookie($arr){

	//save details in cookie

	global $cookie;

	$cookie->ID=$arr->ID;

	$arr->username=remove_special($arr->username);

	$cookie->FirstName=$arr->username;

	$cookie->LastName=$arr->username;

	$cookie->Gender=$arr->Gender;
	
    $cookie->Provider=$arr->Provider;
	
	$cookie->BirthDate=$arr->BirthDate;

	if(!empty($arr->Country->Code)){

		$cookie->SL_CCode=$arr->Country->Code;

	}elseif(isset($arr->Country->Name)){

		$cookie->SL_CName=$arr->Country->Name;

	}

	if(isset($arr->State)){

		$cookie->SL_State=$arr->State;

	}

	if(isset($arr->City)){

		$cookie->SL_City=$arr->City;

	}

	if(isset($arr->Addresses['0']->PostalCode)){

		$cookie->SL_PCode=$arr->Addresses['0']->PostalCode;

	}

	if(isset($arr->Addresses['0']->Address1)){

		$cookie->SL_Address=$arr->Addresses['0']->Address1;

	}

	if(isset($arr->PhoneNumbers['0']->PhoneNumber)){

		$cookie->SL_Phone=$arr->PhoneNumbers['0']->PhoneNumber;

	}

}



function getHome(){

	$http =(Configuration::get('PS_SSL_ENABLED'));

	if($http==0){

		$http ='http://';

	}else{

		$http ='https://';

	}

	 $home=$http.$_SERVER['HTTP_HOST'].$_SERVER['REQUEST_URI'];

	return($home);

}



function geturl(){

	$http =(Configuration::get('PS_SSL_ENABLED'));

	if($http==0){

		$http ='http://';

	}else{

		$http ='https://';

	}

    $url=$http.$_SERVER['HTTP_HOST'].__PS_BASE_URI__;

	return($url);

}




function popUpWindow($msg=""){

	$home = getHome();

	$url= geturl();

	if($msg==""){

		$msg="Please enter your email to proceed";

	}

	?>

	<link rel="stylesheet" type="text/css" href="modules/sociallogin/sociallogin_style.css" />

	<script language="javascript">

	function showHome(){

		document.location="<?php echo $home; ?>";

	}

	</script>

	<div id="fade" class="LoginRadius_overlay">

	<div id="popupouter">

	<div id="popupinner">

	<div id="textmatter"><?php echo $msg; ?></div>

	<form method="post" action="">

	<div><input type="text" name="SL_EMAIL" id="SL_EMAIL" class="inputtxt" /></div>

	<div>

	<?php

	global $cookie;

	 $cookie->SL_hidden=microtime();

	?>

	<input type="hidden" name="hidden_val" value="<?php echo $cookie->SL_hidden; ?>" />

	<input type="submit" id="LoginRadiusRedSliderClick" name="LoginRadiusRedSliderClick" value="Submit" class="inputbutton">

	<input type="button" value="Cancel" class="inputbutton" onClick="showHome()" />

	</div>

	</form>

	</div>

	</div>

	</div>

	<?php

}



function verifyEmail(){

	$tbl=pSQL(_DB_PREFIX_.'sociallogin');

	$pid=pSQL($_REQUEST['SL_PID']);

	$rand=pSQL($_REQUEST['SL_VERIFY_EMAIL']);

	$db = Db::getInstance()->ExecuteS("SELECT * FROM  ".pSQL(_DB_PREFIX_)."sociallogin  WHERE rand='$rand' and provider_id='$pid' and verified='0'");

	$num=(!empty($db['0']['id_customer'])?$db['0']['id_customer']:"");
	
    $provider_name=(!empty($db['0']['Provider_name'])? pSQL($db['0']['Provider_name']) : "");
	
	$home = getHome();

	$url= geturl();

	if($num<1){

		//$msg= "Email not found.";

	   // popup_verify($msg,$url);

		return;

	}

	 Db::getInstance()->Execute("UPDATE $tbl SET verified='1' , rand='' WHERE rand='$rand' and provider_id='$pid'");
	 
	 Db::getInstance()->Execute("UPDATE $tbl SET rand='' WHERE Provider_name='$provider_name' and id_customer='$num'");


	$msg= "Email is verified. Now you can login using Social Login.";

	 popup_verify($msg,$url);

}



function Admin_email($email,$firstname,$lastname) {

	$protocol_content = (Configuration::get('PS_SSL_ENABLED')) ? 'https://' : 'http://';

	$link=$protocol_content.$_SERVER['HTTP_HOST'];

	$sub="New User Registration";

	$msg="New User Registered to your site:";

	$msg.= "<br/>UserName=  ".$firstname." ".$lastname."";

	$vars = array(

			'{email}' => $email,

			'{message}'=> $msg

	);

	$db = Db::getInstance()->ExecuteS("SELECT * FROM  ".pSQL(_DB_PREFIX_)."employee  WHERE id_profile=1 ");

	$id_lang = (int)Configuration::get('PS_LANG_DEFAULT');

	foreach ($db as $row)

	{

		$find_id=$row['id_employee'];

		$find_email=$row['email'];

		Mail::Send($id_lang, 'contact',$sub, $vars, $find_email);

	}

}



function SL_email($to,$sub,$msg,$firstname,$lastname){

	if($_SERVER['HTTP_HOST']=="localhost"){

		echo "Email will work at online only.";

	}else{

		$home = getHome();

		$msgg="Email sent. Please check your email id to verify your account.";

		popup_verify($msgg,$home);

		$id_lang = (int)Configuration::get('PS_LANG_DEFAULT');

		$protocol_content = (Configuration::get('PS_SSL_ENABLED')) ? 'https://' : 'http://';

		$link=$protocol_content.$_SERVER['HTTP_HOST'].__PS_BASE_URI__;		

		$vars = array(

				'{email}' => $to,

				'{message}' => $msg

				);		

		Mail::Send($id_lang, 'contact',$sub, $vars, $to);

	}

}



function SL_randomchar(){

	$char="";

	for($i=0;$i<20;$i++){

		$char.=rand(0,9);

	}

	return($char);

}



function SL_email_save($email){

	//if email id is validate?
	$arr=array();

	global $cookie;
	
	$provider_name=pSQL($cookie->Provider);
	
	if (!Validate::isEmail($email)){

		popUpWindow("Your email-id is already registered or invalid");

		return;

	}
    //$arr['id']=$db['0']['id_customer'];

	elseif($_POST['hidden_val']!=$cookie->SL_hidden){

		echo "Cookie has been deleted, please try again.";

		return;	

	}
	
	else{
	
		$query="SELECT c.id_customer from "._DB_PREFIX_."customer AS c INNER JOIN "._DB_PREFIX_."sociallogin AS sl ON sl.id_customer=c.id_customer  WHERE c.email='$email' AND sl.Provider_name='$provider_name' AND verified='1'";

		$query = Db::getInstance()->ExecuteS($query);
	
		if(!empty($query['0']['id_customer'])){
	
			popUpWindow("this email already exist enter new email");
	
		return;
   		}

		else{
		
		$query1="SELECT * FROM "._DB_PREFIX_."customer  WHERE email='$email'";
		
		$query1 = Db::getInstance()->ExecuteS($query1);
		
		$num=(!empty($query1['0']['id_customer'])?$query1['0']['id_customer']:"");
		
		if(!empty($num)){
			
			$rand=SL_randomchar();

	    	$id=$cookie->ID;
		
			$provider_name=pSQL($cookie->Provider);
		
			$provider_id=$id;
			
			$fname=(!empty($query1['0']['firstname'])? $query1['0']['firstname'] :"");

            $lname=(!empty($query1['0']['lastname'])? $query1['0']['lastname'] :"");

			$tbl=pSQL(_DB_PREFIX_.'sociallogin');
			
			$query= "INSERT into $tbl (`id_customer`,`provider_id`,`Provider_name`,`rand`,`verified`) values ('$num','$provider_id','$provider_name','$rand','0') ";
	
			Db::getInstance()->Execute($query);
			
		 	$to=$email;

			$sub="Verify your email id. ";

		    $protocol_content = (Configuration::get('PS_SSL_ENABLED')) ? 'https://' : 'http://';

			$link=$protocol_content.$_SERVER['HTTP_HOST'].__PS_BASE_URI__."?SL_VERIFY_EMAIL=$rand&SL_PID=$provider_id";

			$msg="Please paste or click here to verify your email id. Click $link";

		    $sub="Verify your email id. ";

			SL_email($email,$sub,$msg,$fname,$lname);

            return;
		}
		else{

			$arr['fname']=pSQL($cookie->FirstName);

			$fname=$arr['fname'];

			$arr['lname']=pSQL($cookie->LastName);

			$lname=$arr['lname'];

			$arr['email']=$email;

			$password = Tools::passwdGen();

			$pass=Tools::encrypt($password);

			$date_added=date("Y-m-d H:i:s",time());

			$date_updated=$date_added;

			$last_pass_gen=$date_added;

			$s_key = md5(uniqid(rand(), true));

			$gender = pSQL ($cookie->Gender);

			$bday = pSQL($cookie->BirthDate);	
			
			$newsletter='0';
	
	         $optin='0';

			//if already in customer table then check if it's verified, if then...

			$email=pSQL($email);
			
			$required_field_check = Db::getInstance()->ExecuteS("SELECT field_name FROM  ".pSQL(_DB_PREFIX_)."required_field");
	
             foreach ($required_field_check AS $item){
			 
	         	if($item['field_name']=='newsletter')
	  
	  				$newsletter='1';
	  
	  			if($item['field_name']=='optin')
	  
	  				$optin='1';
	  
	 		 }
			
			$query= "INSERT into "._DB_PREFIX_."customer (`id_gender`,`id_default_group`,`firstname`,`lastname`,`email`,`passwd`,`last_passwd_gen`,`birthday`,`newsletter`,`optin`,`active`,`date_add`,`date_upd`,`secure_key` ) values ('$gender','1','$fname','$lname','$email','$pass','$last_pass_gen','$bday','$newsletter','$optin','1','$date_added','$date_updated','$s_key') ";

			Db::getInstance()->Execute($query);

			$insert_id=(int)Db::getInstance()->Insert_ID();

			$provider_id=$cookie->ID;
			
			$provider_name=$cookie->Provider;
			//provider id later

			$tbl=pSQL(_DB_PREFIX_.'sociallogin');

			$rand=SL_randomchar();

			$query= "INSERT into $tbl (`id_customer`,`provider_id`,`Provider_name`,`rand`,`verified`) values ('$insert_id','$provider_id','$provider_name','$rand','0') ";

			Db::getInstance()->Execute("DELETE FROM $tbl WHERE provider_id='$provider_id'");

			Db::getInstance()->Execute($query);

			$to=$email;

			$sub="Verify your email id. ";

			$protocol_content = (Configuration::get('PS_SSL_ENABLED')) ? 'https://' : 'http://';

			$link=$protocol_content.$_SERVER['HTTP_HOST'].__PS_BASE_URI__."?SL_VERIFY_EMAIL=$rand&SL_PID=$provider_id";

			$msg="Please paste or  click here to verify your email id. Click $link";

			SL_email($to,$sub,$msg,$fname,$lname);

			if(Configuration::get('SEND_REQ')=="1")

			Admin_email($email,$fname,$lname);

			$tbl=pSQL(_DB_PREFIX_.'customer_group');

			$query= "INSERT into $tbl (`id_customer`,`id_group`) values ('$insert_id','1') ";

			Db::getInstance()->Execute("DELETE FROM $tbl WHERE id_customer='$insert_id'");

			Db::getInstance()->Execute($query);

			extraFields2($insert_id,$fname,$lname);
			}
		}
  	}
}

function popup_verify($msg,$home) {

	?>

	<link rel="stylesheet" type="text/css" href="modules/sociallogin/sociallogin_style.css" />

	<div id="fade" class="LoginRadius_overlay">

	<div id="popupouter">

	<div id="popupinner">

	<div id="textmatter">

	<?php

		echo $msg;

	?>

	<div>

	<input type="button" value="Ok" onclick="javascript:document.location='<?php echo $home; ?>';" class="inputbutton" />

	</div>

	</div>

	</div>

	</div>

	</div>

	<?php

}



function extraFields($obj,$insert_id,$fname,$lname){

	$str="";

	if(!empty($obj->Country->Code)){

		$ISO=$obj->Country->Code;

		$id=pSQL(getIdByCountryISO($ISO));

		$str.="id_country='$id',";

	}elseif(!empty($obj->Country->Name)){

		$country=$obj->Country->Name;

		$id=pSQL(getIdByCountryName($country));

		$str.="id_country='$id',";

	}

	elseif(empty($id)){

		$id = (int)(Configuration::get('PS_COUNTRY_DEFAULT'));

		$str.="id_country='$id',";

	}

	if(isset($obj->State)){

		$state=$obj->State;

		if(isset($id) and (is_numeric($id)) ){

			$iso=pSQL(getIsoByState($state,$id));

		}else{

			$iso=pSQL(getIsoByState($state));

		}		

		$str.="id_state='$iso',";

	}

	if(isset($obj->City)){

		$city=pSQL($obj->City);

		$str.="city='$city',";

	}

	if(isset($obj->Addresses['0']->PostalCode)){

		$zip=pSQL($obj->Addresses['0']->PostalCode);

		$str.="postcode='$zip',";

	}

	if(isset($obj->Addresses['0']->Address1)){

		$address=pSQL($obj->Addresses['0']->Address1);

		$str.="address1='$address',";

	}

	if(isset($obj->PhoneNumbers['0']->PhoneNumber)){

		$phone=pSQL($obj->PhoneNumbers['0']->PhoneNumber);

		$str.="phone='$phone',";

	}

	$date=date("y-m-d h:i:s");

	$str.="date_add='$date',date_upd='$date',";

	$tbl=_DB_PREFIX_."address";

	$fname=pSQL($fname);

	$lname=pSQL($lname);

	$q= "INSERT into $tbl SET ".$str." id_customer='$insert_id', lastname='$fname',firstname='$lname' ";

	$q = Db::getInstance()->Execute($q);

}



function extraFields2($insert_id,$fname,$lname){

	//by using cookie get all data.

	global $cookie;

	$str="";

	//starts here

	if(!empty($cookie->SL_CCode)){

		$ISO=$cookie->SL_CCode;

		$id=pSQL(getIdByCountryISO($ISO));

		$str.="id_country='$id',";

	}elseif(!empty($cookie->SL_CName)){

		$country=$cookie->SL_CName;

		$id=pSQL(getIdByCountryName($country));

		$str.="id_country='$id',";

	}

	elseif(empty($id)){

		$id = (int)(Configuration::get('PS_COUNTRY_DEFAULT'));

		$str.="id_country='$id',";

	}

	if(isset($cookie->SL_State)){

		$state=$cookie->SL_State;

		if(isset($id) and (is_numeric($id)) ){

			$iso=pSQL(getIsoByState($state,$id));

		}else{

			$iso=pSQL(getIsoByState($state));

		}		

		$str.="id_state='$iso',";

	}

	if(isset($cookie->SL_City)){

		$city=pSQL($cookie->SL_City);

		$str.="city='$city',";

	}

	if(isset($cookie->SL_PCode)){

		$zip=pSQL($cookie->SL_PCode);

		$str.="postcode='$zip',";

	}

	if(isset($cookie->SL_Address)){

		$address=pSQL($cookie->SL_Address);

		$str.="address1='$address',";

	}

	if(isset($cookie->SL_Phone)){

		$phone=pSQL($cookie->SL_Phone);

		$str.="phone='$phone',";

	}

	$date=date("y-m-d h:i:s");

	$str.="date_add='$date',date_upd='$date',";

	$tbl=_DB_PREFIX_."address";

	$q= "INSERT into $tbl SET ".$str." id_customer='$insert_id', firstname='$fname',lastname='$lname' ";

	$q = Db::getInstance()->Execute($q);

	//ends here

}



function getIdByCountryISO($ISO){

	$tbl=_DB_PREFIX_."country";

	$field="iso_code";

	$ISO=pSQL(trim($ISO));

	$q="SELECT * from $tbl WHERE $field='$ISO'";

	$q = Db::getInstance()->ExecuteS($q);

	$iso="";

	if(isset($q[0]['iso'])){

		$iso=$q[0]['iso'];

	}

	return($iso);

}

function getIdByCountryName($country){

	$tbl=_DB_PREFIX_."country_lang";

	$country=pSQL(trim($country));

	$q="SELECT * from $tbl WHERE name='$country'";

	$q = Db::getInstance()->ExecuteS($q);

	$iso=$q[0]['id_country'];

	return($id);

}

function getIsoByState($state,$country=""){

	if(strlen(($country)>0)){

		$country=pSQL($country);

		$str="id_country='$country' and ";

	}else{

		$str="";

	}

	if(strlen(($state)>0)){

		$state=pSQL($state);

		$tbl=_DB_PREFIX_."state";

		$q="SELECT * from $tbl WHERE $str name='$id_state'";

		$q = Db::getInstance()->ExecuteS($q);

		$id=$q[0]['id_state'];

		return($id);

	}



}

function remove_special($field){



        if(!empty($field) and ctype_alpha($field)){

            return $field;

        }

        $len = strlen($field);

        $return_val = "";    

        for($i=0;$i<$len;$i++){

            if(ctype_alpha($field[$i])){

                $return_val .= $field[$i];

            }

        }

		if(empty($return_val)){

			$letters = range('a', 'z');

			for($i=0;$i<5;$i++){

				$return_val .= $letters[rand(0,26)];

			}

		}

       return ($return_val);

    }

?>