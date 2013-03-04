<?php

if ( !defined( '_PS_VERSION_' ) ){

	exit;

}



class LrUser{

	function __construct(){

		include_once("LoginRadius.php");

		$secret = trim(Configuration::get('API_SECRET'));
		
		$lr_obj=new LoginRadius();

		$userprofile=$lr_obj->loginradius_get_data($secret);

		$provider = $userprofile->Provider;

		if($provider=="yahoo" or $provider=="facebook" or $provider=="aol"){

			$dob = $userprofile->BirthDate;
            if(!empty($dob))
			{
			$dobArr = explode("/",$dob);

			$dob = $dobArr[2]."-".$dobArr[0]."-".$dobArr[1];

			$userprofile->BirthDate = $dob;
			}

		}else{

			$currentTime = time();

			$time = $currentTime - 599184000;

			$userprofile->BirthDate = date("Y-m-d",$time);

		}

		if(!empty($userprofile->Gender) and (strpos($userprofile->Gender, "f") !== false)){

			$userprofile->Gender = "2";

		}else{

			$userprofile->Gender = "1";

		}

		if (!empty($userprofile->FirstName) && !empty( $userprofile->LastName )) {

			$userprofile->username= $userprofile->FirstName. ' ' . $userprofile->LastName ;

		}

		elseif (!empty($userprofile->FullName)) {

			$userprofile->username= $userprofile->FullName;

		}

		elseif (!empty($userprofile->ProfileName)) {

			$userprofile->username = $userprofile->ProfileName;

		}

		elseif (!empty($userprofile->Email['0']->Value)) {

			$user_name = explode('@',  $userprofile->Email['0']->Value);

			$userprofile->username = $user_name[0];

		} 

    	else {

			$userprofile->username = $userprofile->ID;

		}

		$userprofile->FirstName = $this->remove_special($userprofile->FirstName);

		$userprofile->LastName = $this->remove_special($userprofile->LastName);

		$userprofile->username = $this->remove_special($userprofile->username);

		$userprofile->Email=(!empty($userprofile->Email['0']->Value)?$userprofile->Email['0']->Value:"");

		if(isset($userprofile->ID)){

			$dbObj=$this->query($userprofile->ID);
			
            $pid=(!empty($dbObj['0']['provider_id']) ? $dbObj['0']['provider_id'] : "");
			
			$td_user="";

			$id=(!empty($dbObj[0]['id_customer'])?$dbObj[0]['id_customer']:"");
			
            $num=$id;
			 
			if($id>=1){
			
				$active_user=(!empty($dbObj['0']['active'])? $dbObj['0']['active'] :"");
			 
			 }
			if($id<1){

				if(!empty($userprofile->Email)){

			    	$query3 = Db::getInstance()->ExecuteS('SELECT * FROM '.pSQL(_DB_PREFIX_.'customer').' as c WHERE c.email='." '$userprofile->Email' ".' LIMIT 0,1');

					$num=(!empty($query3['0']['id_customer'])?$query3['0']['id_customer']:"");
					
					$active_user=(!empty($query3['0']['active'])? $query3['0']['active'] :"");
			
					if($num>=1) {
					
						$td_user="yes";
					
				   		if($this->deletedUser($query3)){

                   			$home = getHome();

				   			$msg= "<p style ='color:red;'>Authentication failed.</p>";

				   			popup_verify($msg,$home);

							return;

						}
					
						if(Configuration::get('ACC_MAP')==0){
						
						$tbl=pSQL(_DB_PREFIX_.'sociallogin');

			            $query= "INSERT into $tbl (`id_customer`,`provider_id`,`Provider_name`,`verified`,`rand`) values ('$num','".$userprofile->ID."' , '".$userprofile->Provider."','1','') ";
						
						Db::getInstance()->Execute($query);
						
						}
						
						$this->login_verify($num,$pid,$td_user);
						
					}

				}

				//new user. user not found in database. set all details

				if(Configuration::get('EMAIL_REQ')=="0" and empty($userprofile->Email)){

					storeInCookie($userprofile);

					popUpWindow();

					return;

					}elseif(Configuration::get('EMAIL_REQ')=="1" and empty($userprofile->Email)){

					$this->email_rand($userprofile);

					storeAndLogin($userprofile);

					return;					

				}

				storeAndLogin($userprofile);

			}elseif($this->deletedUser($dbObj)){

		         	$home = getHome();

					$msg= "<p style ='color:red;'><b>Authentication failed.</b></p>";

					popup_verify($msg,$home);

					return;

			}
			
			if($active_user==0){
			   
				$home = getHome();

				$msg= "<p style ='color:red;'><b>User has been disbled or blocked.</b></p>";

				popup_verify($msg,$home);
		
                return;
			  
			}
	
			$this->login_verify($num,$pid);

		}

	}



	function query($id){
	
		$slTbl=pSQL(_DB_PREFIX_).'sociallogin';

		$cusTbl=pSQL(_DB_PREFIX_).'customer';

		$id=pSQL($id);

		$q="SELECT * FROM `$slTbl` as sl INNER JOIN `$cusTbl` as c WHERE sl.provider_id='$id' and c.id_customer=sl.id_customer  LIMIT 0,1";

		$dbObj=Db::getInstance()->ExecuteS($q);
		
		return($dbObj);

	}
	

	function login_verify($num,$pid,$td_user=""){
		
		if($this->verifiedUser($num,$pid,$td_user)){

			$this->loginUser($num);

			return;

		}else{

			$home = getHome();

			$msg= "Please verify your email.";

			popup_verify($msg,$home);

			return;

		}
	}

	function remove_special($field){

		$len = strlen($field);

		$return = "";

		for($i=0;$i<$len;$i++){

			if(ctype_alpha($field[$i])){

				$return .=$field[$i];

			}

		}

		$len = strlen($return);

		if($len>0){

			return $return;

		}

		$return = "";

		$letters = range('a', 'z');

		for($i=0;$i<5;$i++){

			$return .= $letters[rand(0,25)];

		}

		return $return;

	}

	

	function deletedUser($dbObj){

		$deleted=$dbObj['0']['deleted'];

		if($deleted==1){

			return true;

		}

		return false;

	}



	function verifiedUser($num,$pid,$td_user){

	$dbObj = Db::getInstance()->ExecuteS('SELECT * FROM '.pSQL(_DB_PREFIX_.'sociallogin').' as c WHERE c.id_customer='." '$num'".' AND c.provider_id='." '$pid'".' LIMIT 0,1');
	
    $verified=$dbObj['0']['verified'];
	
		$rand=$dbObj['0']['rand'];

		if($verified==1 || $td_user=="yes"){

			return true;

		}

		return false;

	}



	function loginUser($num){
	
		$dbObj = Db::getInstance()->ExecuteS('SELECT * FROM '.pSQL(_DB_PREFIX_.'customer').' as c WHERE c.id_customer='." '$num' ".' LIMIT 0,1');
		
		$arr=array();

		$arr['id']=$dbObj['0']['id_customer'];

		$arr['fname']=$dbObj['0']['firstname'];

		$arr['lname']=$dbObj['0']['lastname'];

		$arr['email']=$dbObj['0']['email'];

		$arr['pass']=$dbObj['0']['passwd'];        

		loginRedirect($arr);

	}



	function email_rand($userprofile){

     	switch( $userprofile->Provider) {

      		case 'twitter':

      		$userprofile->Email= $userprofile->ID.'@'.$userprofile->Provider.'.com';

      		break;

      		case 'linkedin':

        	$userprofile->Email = $userprofile->ID.'@'.$userprofile->Provider.'.com';

      		break;

      		default:

        	$Email_id = substr( $userprofile->ID,7);

        	$Email_id2 = str_replace("/","_",$Email_id);

        	$userprofile->Email = str_replace(".","_",$Email_id2).'@'. $userprofile->Provider .'.com';

        	break;

    	}

		

	}

}

?>