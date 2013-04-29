<?php
if ( !defined( '_PS_VERSION_' ) ){
	exit;
}
/*
* Redirection after login.
*/
function redirectURL(){
  $redirect='';
  $loc=Configuration::get('LoginRadius_redirect');
  if($loc=="profile"){
    $redirect="my-account.php";
  }
  elseif($loc=="url"){
    $custom_url=Configuration::get('redirecturl');
    $redirect = !empty($custom_url)? $custom_url : "my-account.php";
  }
  else {
    $fullurl=$_SERVER['HTTP_REFERER'];
    if(strpos($fullurl,"callback=")){
      $urldata=explode("callback=",$fullurl);
      $url= urldecode($urldata[1]);
	  if(strpos($url,"back=")){
	    $backurldata=explode("back=",$url);
	    $redirect= urldecode($backurldata[1]);
	  }
	  else {
	  $redirect= $url;
	  }
    }
	else {
	  if(strpos($fullurl,"back=")){
	    $redirect= Tools::getValue('back');
	  }
	  else {
	    $redirect=$fullurl;
	  }
	}
  }
  return $redirect;
}

/*
* Save the logged in user credentails in cookie.
*/
function loginRedirect($arr){
  global $cookie;
  $cookie->id_compare = $arr['id'];
  $cookie->id_customer = $arr['id'];
  $cookie->customer_lastname = $arr['lname'];
  $cookie->customer_firstname = $arr['fname'];
  $cookie->logged = 1;
  $cookie->passwd = $arr['pass'];
  $cookie->email = $arr['email'];
  $cookie->loginradius_id= $arr['loginradius_id'];
  $cookie->lr_login="true";
  if ((empty($cookie->id_cart) || Cart::getNbProducts($cookie->id_cart) == 0))
    $cookie->id_cart = (int)Cart::lastNoneOrderedCart($cookie->id_customer);
  //Module::hookExec('authentication');
  Hook::exec('authentication');
  $redirect=redirectURL();
  Tools::redirectLink($redirect);
}

/*
* When user have Email address then check login functionaity
*/
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
	$arr['loginradius_id']=$provider_id;
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
  $lname=(!empty($obj->LastName) ? pSQL($obj->LastName) : pSQL($obj->username));
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
  $address_fields =new stdClass;
  $address_fields->address=(!empty($obj->Addresses['0']->Address1)? $obj->Addresses['0']->Address1 : '');
  $address_fields->zipcode=(!empty($obj->Addresses['0']->PostalCode) ? $obj->Addresses['0']->PostalCode : '');
  // $address_fields->add_alias=$data->ADDRESS_ALIAS;
  $address_fields->country_code=(!empty ($obj->Country->Code ) ? $obj->Country->Code : '');
  $address_fields->country_name=(isset($obj->Country->Name) ? $obj->Country->Name :  $obj->LocalCountry);
  $address_fields->city=(!empty($obj->City) ? $obj->City :'' );
  $address_fields->state=(!empty($obj->State) ? $obj->State : '');
  $address_fields->phone_number= (!empty($obj->PhoneNumbers['0']->PhoneNumber) ? $obj->PhoneNumbers['0']->PhoneNumber : '' );
  extraFields($address_fields,$insert_id,$fname,$lname);
  $arr=array();
  $arr['id']=(string)$insert_id;
  $arr['lname']=$lname;
  $arr['fname']=$fname;
  $arr['pass']=$pass;
  $arr['email']=$email;
  $user['pass']=$password;
  $user['fname']=$arr['fname'];
  $user['lname']=$arr['lname'];
  if(Configuration::get('SEND_REQ')=="1")
    Admin_email($arr['email'],$arr['fname'],$arr['lname']);
  if(Configuration::get('user_notification')=="0")
    user_notification_email($arr['email'],$user);
    $arr['loginradius_id']=$provider_id;
    loginRedirect($arr);	
  }
  
  /*
  * save the user data in cookie.
  */
function storeInCookie($arr){
//save details in cookie
  global $cookie;
  $cookie->ID=$arr->ID;
  $arr->username=remove_special($arr->username);
  $cookie->FirstName=remove_special($arr->FirstName);
  $cookie->LastName=remove_special($arr->LastName);
  $cookie->FirstName= (!empty($cookie->FirstName) ? $cookie->FirstName : $arr->username); 
  $cookie->LastName= (!empty($cookie->LastName) ? $cookie->LastName : $arr->username); 
  $cookie->Gender=$arr->Gender;
  $cookie->Email=$arr->Email;
  $cookie->Provider=$arr->Provider;
  $cookie->BirthDate=$arr->BirthDate;
  if(!empty($arr->Country->Code)){
    $cookie->SL_CCode=$arr->Country->Code;
  }elseif(isset($arr->Country->Name)){
    $cookie->SL_CName=$arr->Country->Name;
  }
  elseif($arr->LocalCountry) {
  $cookie->SL_LocalName=$arr->LocalCountry;
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

/*
* Return Home Url.
*/
function getHome(){
  $http =(Configuration::get('PS_SSL_ENABLED'));
  if($http==0){$http ='http://';}else{$http ='https://';}
    $home=$http.$_SERVER['HTTP_HOST'].$_SERVER['REQUEST_URI'];
  return($home);
}

/*
* GET Index URL.
*/
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

/*
* Show poup window for Email and Required fields.
*/

function popUpWindow($msg='',$data=array()){
  $home = getHome();
  $url= geturl();
//Tools::addCSS(__PS_BASE_URI__.'modules/sociallogin/sociallogin_style.css');
?>
  <link rel="stylesheet" type="text/css" href="modules/sociallogin/sociallogin_style.css" />
  <script src="//ajax.googleapis.com/ajax/libs/jquery/1.8.0/jquery.min.js"></script>
  <script language="javascript">
  jQuery(document).ready(function($){
	/*The country onchange starts here*/
	var orig_html;
	var orig_value;
	var state_value;
	var us_states = {AL: 'Alabama', AK: 'Alaska', AZ: 'Arizona', AR: 'Arkansas', CA: 'California', CO: 'Colorado', CT: 'Connecticut', DE: 'Delaware', DC: 'District of Columbia', FL: 'Florida', GA: 'Georgia', HI: 'Hawaii', ID: 'Idaho', IL: 'Illinois', IN: 'Indiana', IA: 'Iowa', KS: 'Kansas', KY: 'Kentucky', LA: 'Louisiana', ME: 'Maine', MD: 'Maryland', MA: 'Massachusetts', MI: 'Michigan', MN: 'Minnesota', MS: 'Mississippi', MO: 'Missouri', MT: 'Montana', NE: 'Nebraska', NV: 'Nevada', NH: 'New Hampshire', NJ: 'New Jersey', NM: 'New Mexico', NY: 'New York', NC: 'North Carolina', ND: 'North Dakota', OH: 'Ohio', OK: 'Oklahoma', OR: 'Oregon', PA: 'Pennsylvania', RI: 'Rhode Island', SC: 'South Carolina', SD: 'South Dakota', TN: 'Tennessee', TX: 'Texas', UT: 'Utah', VT: 'Vermont', VA: 'Virginia', WA: 'Washington', WV: 'West Virginia', WI: 'Wisconsin', WY: 'Wyoming'};
	var ca_states = {BC:"British Columbia",ON:"Ontario",NF:"Newfoundland",NS:"Nova Scotia",PE:"Prince Edward Island",NB:"New Brunswick",QC:"Quebec",MB:"Manitoba",SK:"Saskatchewan",AB:"Alberta",NT:"Northwest Territories",YT:"Yukon Territory"};
	var mx_states = {AGS:"Aguascalientes",BCN: "Baja California",BCS: "Baja California Sur", CAM:"Campeche",CHP: "Chiapas", CHH:"Chihuahua",COA: "Coahuila", COL:"Colima", DIF:"Distrito Federal", DUR:"Durango",GUA: "Guanajuato",GRO: "Guerrero", HID:"Hidalgo", JAL:"Jalisco", MEX:"Estado de Mexico",MIC: "Michoacan de Ocampo", MOR:"Morelos", NAY:"Nayarit", NLE:"Nuevo Leon", OAX:"Oaxaca", PUE:"Puebla", QUE:"Queretaro de Arteaga",ROO: "Quintana Roo", SLP:"San Luis Potosi",SIN: "Sinaloa", SON:"Sonora", TAB:"Tabasco", TAM:"Tamaulipas", TLA:"Tlaxcala", VER:"Veracruz-Llave", YUC:"Yucatan", ZAC:"Zacatecas"};
	var ar_states = {B:"Buenos Aires",K:"Catamarca", H:"Chaco", U:"Chubut", C:"Ciudad de Buenos Aires", X:"Córdoba", W: "Corrientes" , E:"Entre Rios", P:"Formosa", Y:"Jujuy",L: "La Pampa", F:"La Rioja", M:"Mendoza", N:"Misiones", Q:"Neuquen", R:"Rio Negro",A:"Salta", J:"San Juan",D: "San Luis", Z:"Santa Cruz",S: "Santa Fe", G:"Santiago del Estero", V:"Tierra del Fuego", T:"Tucuman"};
	var it_states = {AG:"Agrigento" ,AL:"Alessandria" ,AN: "Ancona", AO:"Aosta" ,AR:"Arezzo",AP: "Ascoli Piceno", AT: "Asti", AV: "Avellino" ,BA: "Bari" ,BT:"Barletta-Andria-Trani" ,BL:"Belluno" ,BN:"Benevento" ,BG:"Bergamo" ,BI:"Biella" ,BO:"Bologna" ,BZ:"Bolzano" ,BS: "Brescia" ,BR :"Brindisi" ,CA :"Cagliari", CL: "Caltanissetta" ,CB :"Campobasso" ,CI:"Carbonia-Iglesias" ,CE:"Caserta" ,CT:"Catania" ,CZ:"Catanzaro", CH: "Chieti" ,CO: "Como" ,CS: "Cosenza" ,CR: "Cremona" ,KR: "Crotone" ,CN: "Cuneo" ,EN: "Enna" ,FM: "Fermo" ,FE: "Ferrara" ,FI: "Firenze" ,FG: "Foggia", FC:"Forlì-Cesena" ,FR:"Frosinone" ,GE:"Genova" ,GO:"Gorizia" ,GR:"Grosseto" ,IM:"Imperia" ,IS:"Isernia" ,AQ:"L'Aquila" ,SP:"La Spezia" ,LT:"Latina" ,LE:"Lecce" ,LC: "Lecco" ,LI: 	"Livorno" ,LO: "Lodi" ,LU:"Lucca" ,MC: "Macerata" ,MN:"Mantova" ,MS: "Massa" ,MT: "Matera" ,VS: 	"Medio Campidano" ,ME:	"Messina" ,MT:"Milano" ,MO:"Modena" ,MB:"Monza e della Brianza" ,NA:"Napoli" ,NO:	"Novara" ,NU:"Nuoro" ,OG:"Ogliastra",OT:	"Olbia-Tempio",OR:"Oristano" ,PD:"Padova" ,PA: "Palermo" ,PR: "Parma" ,PV: "Pavia" ,PG:"Perugia" ,PU: "Pesaro-Urbino",PE:"Pescara" ,PC: "Piacenza" ,PI: "Pisa" ,PT: "Pistoia" ,PN:"Pordenone" ,PZ:"Potenza" ,PO:"Prato" ,RG:"Ragusa" ,RA:"Ravenna" ,RC: "Reggio Calabria" ,RE: "Reggio Emilia",RI:"Rieti" ,RN: "Rimini",RM: "Roma" ,RO:"Rovigo",SA: "Salerno" ,SS: "Sassari" ,SV: "Savona" ,SI: "Siena" ,SR:"Siracusa" ,SO: "Sondrio" ,TA:"Taranto" ,TE: "Teramo" ,TR:"Terni" ,TO: "Torino" ,TP: "Trapani" ,TN: "Trento" ,TV:"Treviso" ,TS:"Trieste" ,UD: "Udine" ,VA: "Varese",VE: "Venezia" ,VB:"Verbano-Cusio-Ossola" ,VC:"Vercelli" ,VR:"Verona",VV:"Vibo Valentia" ,VI: "Vicenza" ,VT:"Viterbo"};
	var id_states= {	AC:"Aceh",BA: "Bali", BB:"Bangka",BT: "Banten",BE:"Bengkulu", JT:"Central Java",KT:"Central Kalimantan",ST:"Central Sulawesi",JI:"Coat of arms of East Java",KI:"East kalimantan",NT:"East Nusa Tenggara",GO:"Lambang propinsi",JK:"Jakarta",JA:"Jambi",LA:"Lampung",MA:"Maluku",MU:"North Maluku",SA:"North Sulawesi",SU:"North Sumatra",PA:"Papua",RI:"Riau",KR:"Lambang Riau",SG:"Southeast Sulawesi",KS:"South Kalimantan",SN:"South Sulawesi",SS:"South Sumatra",JB:"West Java",KB:"West Kalimantan",NB:"West Nusa Tenggara",PB:"Lambang Provinsi Papua Barat",SR:"West Sulawesi",SB:"West Sumatra",YO:"Yogyakarta"}; 
	var jp_states={  01 : "Aichi",  02 : "Akita",03 : "Aomori",04 : "Chiba",05 : "Ehime",06 : "Fukui", 07 : "Fukuoka",08 : "Fukushima",09 : "Gifu", 10 : "Gumma",11 : "Hiroshima",12 : "Hokkaido", 13 : "Hyogo",14 : "Ibaraki",15 : "Ishikawa", 16 : "Iwate",17 : "Kagawa",18 : "Kagoshima",19 : "Kanagawa",20 : "Kochi",21 : "Kumamoto",22 : "Kyoto",23 : "Mie",24 : "Miyagi",25 : "Miyazaki",26 : "Nagano",27 : "Nagasaki",28 : "Nara",29 : "Niigata",30 : "Oita",31 : "Okayama",32 : "Osaka",33 : "Saga",34 : "Saitama",35 : "Shiga",36 : "Shimane",37 : "Shizuoka",38 : "Tochigi",39 : "Tokushima",40 : "Tokyo",41 : "Tottori",42 : "Toyama",43 : "Wakayama",44 : "Yamagata",45 : "Yamaguchi",46 : "Yamanashi",47 : "Okinawa"};
	var $el = $("#location-country");
	$el.data('oldval', $el.val());
	$el.change(function(){
	var $this = $(this);
	if(this.value=="US"){
	document.getElementById('location-state-div').style.display='block';
	var str = '<span class="spantxt">State:</span><select class="inputtxt" name="location-state" id="location-state">';
	orig_html = $("#location-state-div").html();
	orig_value = $("#location-state").val();
	for(var st in us_states){
	if(st == state_value)
	str += '<option value="'+st+'" selected="selected">'+us_states[st]+'</option>';
	else
	str += '<option value="'+st+'">'+us_states[st]+'</option>';
	}
	str += "</select>";
	$("#location-state-div").html(str);
	$this.data('oldval', $this.val());
	}
	else if(this.value=="CA"){
	document.getElementById('location-state-div').style.display='block';
	var str = '<span class="spantxt">State:</span><select class="inputtxt" name="location-state" id="location-state">';
	orig_html = $("#location-state-div").html();
	orig_value = $("#location-state").val();
	for(var st in ca_states){
	if(st == state_value)
	str += '<option value="'+st+'" selected="selected">'+ca_states[st]+'</option>';
	else
	str += '<option value="'+st+'">'+ca_states[st]+'</option>'; 
	}
	str += "</select>";
	$("#location-state-div").html(str);
	$this.data('oldval', $this.val());
	}
	else if(this.value=="MX"){
	document.getElementById('location-state-div').style.display='block';
	var str = '<span class="spantxt">State:</span><select class="inputtxt" name="location-state" id="location-state">';
	orig_html = $("#location-state-div").html();
	orig_value = $("#location-state").val();
	for(var st in mx_states){
	if(st == state_value)
	str += '<option value="'+st+'" selected="selected">'+mx_states[st]+'</option>';
	else
	str += '<option value="'+st+'">'+mx_states[st]+'</option>';
	}
	str += "</select>";
	$("#location-state-div").html(str);
	$this.data('oldval', $this.val());
	}
	else if(this.value=="AR"){
	document.getElementById('location-state-div').style.display='block';
	var str = '<span class="spantxt">State:</span><select class="inputtxt" name="location-state" id="location-state">';
	orig_html = $("#location-state-div").html();
	orig_value = $("#location-state").val();
	for(var st in ar_states){
	if(st == state_value)
	str += '<option value="'+st+'" selected="selected">'+ar_states[st]+'</option>';
	else
	str += '<option value="'+st+'">'+ar_states[st]+'</option>';
	}
	str += "</select>";
	$("#location-state-div").html(str);
	$this.data('oldval', $this.val());
	}
	else if(this.value=="JP"){
	document.getElementById('location-state-div').style.display='block';
	var str = '<span class="spantxt">State:</span><select class="inputtxt" name="location-state" id="location-state">';
	orig_html = $("#location-state-div").html();
	orig_value = $("#location-state").val();
	for(var st in jp_states){
	if(st == state_value)
	str += '<option value="'+st+'" selected="selected">'+jp_states[st]+'</option>';
	else
	str += '<option value="'+st+'">'+jp_states[st]+'</option>';
	}
	str += "</select>";
	$("#location-state-div").html(str);
	$this.data('oldval', $this.val());
	}
	else if(this.value=="ID"){
	document.getElementById('location-state-div').style.display='block';
	var str = '<span class="spantxt">State:</span><select class="inputtxt" name="location-state" id="location-state">';
	orig_html = $("#location-state-div").html();
	orig_value = $("#location-state").val();
	for(var st in id_states){
	if(st == state_value)
	str += '<option value="'+st+'" selected="selected">'+id_states[st]+'</option>';
	else
	str += '<option value="'+st+'">'+id_states[st]+'</option>';
	}
	str += "</select>";
	$("#location-state-div").html(str);
	$this.data('oldval', $this.val());
	}
	else if(this.value=="IT"){
	document.getElementById('location-state-div').style.display='block';
	var str = '<span class="spantxt">State:</span><select class="inputtxt" name="location-state" id="location-state">';
	orig_html = $("#location-state-div").html();
	orig_value = $("#location-state").val();
	for(var st in it_states){
	if(st == state_value)
	str += '<option value="'+st+'" selected="selected">'+it_states[st]+'</option>';
	else
	str += '<option value="'+st+'">'+it_states[st]+'</option>';
	}
	str += "</select>";
	$("#location-state-div").html(str);
	$this.data('oldval', $this.val());
	}
	else {
	document.getElementById('location-state-div').style.display='none';
	}
	});
	
});

  function showHome(){
	document.location="<?php echo $home; ?>";
  }

  function popupvalidation() {
	var filter = /^([a-zA-Z0-9_\.\-])+\@(([a-zA-Z0-9\-])+\.)+([a-zA-Z0-9]{2,4})+$/;
	var loginRadiusForm=document.getElementById("validfrm");
	for(var i = 0; i < loginRadiusForm.elements.length; i++){
	if(loginRadiusForm.elements[i].id=="location-country") {
	  if(loginRadiusForm.elements[i].value == 0){
	    document.getElementById('textmatter').style.color="#ff0000";
	    loginRadiusForm.elements[i].style.borderColor="#ff0000";
	    loginRadiusForm.elements[i].focus();
	    return false;
	  }
	}
	if(loginRadiusForm.elements[i].value.trim() == ""){
	  document.getElementById('textmatter').style.color="#ff0000";
	  loginRadiusForm.elements[i].style.borderColor="#ff0000";
	  loginRadiusForm.elements[i].focus();
	  return false;
	}
	else {
	  document.getElementById('textmatter').style.color="#666666";
	  loginRadiusForm.elements[i].style.borderColor="#E5E5E5";
	}
	if(loginRadiusForm.elements[i].id=="SL_PHONE") {
	if(isNaN(loginRadiusForm.elements[i].value)==true) {
	  document.getElementById('textmatter').style.color="#ff0000";
	  loginRadiusForm.elements[i].style.borderColor="#ff0000";
	  loginRadiusForm.elements[i].focus();
	  return false;
	}
	}
	if(loginRadiusForm.elements[i].id=="SL_EMAIL") {
	var email=loginRadiusForm.elements[i].value;
	var atPosition = email.indexOf("@");
	var dotPosition = email.lastIndexOf(".");
	if(atPosition < 1 || dotPosition < atPosition+2 || dotPosition+2>=email.length) {
	document.getElementById('textmatter').style.color="#ff0000";
	loginRadiusForm.elements[i].style.borderStyle="solid";
	loginRadiusForm.elements[i].style.borderColor="#ff0000";
	loginRadiusForm.elements[i].focus();
	return false;
	}
	else
	{
	document.getElementById('textmatter').style.color="#666666";
	loginRadiusForm.elements[i].style.borderColor="#E5E5E5";
	}
	}
	}
	return true;
	}
	</script>
	<?php
	global $cookie;
	$cookie->SL_hidden=microtime();
	?>
	<div id="fade" class="LoginRadius_overlay">
	<div id="popupouter" style="margin-top:-260px">
	<div id="popupinner" style="padding:10px 11px 10px 30px">
	<div id="textmatter"><strong>
	<?php
	if($msg==''){
	//echo "Please fill the following details to complete the registration";
	$show_msg=Configuration::get('POPUP_TITLE');
	echo $msg= ( !empty($show_msg) ? $show_msg :  'Please fill the following details to complete the registration') ;
	}
	else
	{
	echo $msg;
	}
	?>
	</strong></div>
	<form method="post" name="validfrm" id="validfrm" action="" onsubmit="return popupvalidation();">
	<?php
	$html="";
	if(Configuration::get('user_require_field')=="1") {
	
	$html .= '<div id="location-state-div" style="display:none;">
	<input id="location-state" type="text" name="location-state" value="empty" />
	</div>';
	
	$countries = Db::getInstance()->executeS('
	SELECT *
	FROM '._DB_PREFIX_.'country c WHERE c.active =1');
	if (is_array($countries) AND !empty($countries))
	{
	$list = '';
	$html .= '<div id="location-country-div">
	<span class="spantxt">Country</span> <select id="location-country" name="location_country" class="inputtxt"><option value="0">None</option>';
	
	foreach ($countries AS $country) {
	$country_name = new Country($country['id_country']);
	$html .= '<option value="'.($country['iso_code']).'"'.(isset($_GET['iso_code']) ? ' selected="selected"' : '').'>'.$country_name->name['1'].'</option>'."\n";
	}
	$html.='</select></div>';
	}
	}
	if((Configuration::get('EMAIL_REQ')=="0" && $data->Email=='')){
	$html.='<div><span class="spantxt">Email</span>
	<input type="text" name="SL_EMAIL" id="SL_EMAIL" placeholder="Email" value= "'.(isset($_POST['SL_EMAIL'])?htmlspecialchars($_POST['SL_EMAIL']):'').'" class="inputtxt" />
	</div>';
	}
	if(Configuration::get('user_require_field')=="1") {
	$html.='<div>
	<span class="spantxt">City</span><input type="text" name="SL_CITY" id="SL_CITY" placeholder="City" value= "'.(isset($_POST['SL_CITY'])?htmlspecialchars($_POST['SL_CITY']):'').'" class="inputtxt" />
	</div>';
	
	$html.='<div><span class="spantxt">Mobile Number</span>
	<input type="text" name="SL_PHONE" id="SL_PHONE" placeholder="Mobile Number" value= "'.(isset($_POST['SL_PHONE'])?htmlspecialchars($_POST['SL_PHONE']):'').'" class="inputtxt" />
	</div>';
	
	$html.='<div><span class="spantxt">Address</span>
	<input type="text" name="SL_ADDRESS" id="SL_ADDRESS" placeholder="Address" value= "'.(isset($_POST['SL_ADDRESS'])?htmlspecialchars($_POST['SL_ADDRESS']):'').'" class="inputtxt" />
	</div>';
	
	$html.='<div><span class="spantxt">ZIP code</span>
	<input type="text" name="SL_ZIP_CODE" id="SL_ZIP_CODE" placeholder="Zip Code" value= "'.(isset($_POST['SL_ZIP_CODE'])?htmlspecialchars($_POST['SL_ZIP_CODE']):'').'" class="inputtxt" />
	</div>';
	
	
	$html.='<div><span class="spantxt">Address Title</span><input type="text" name="SL_ADDRESS_ALIAS" id="SL_ADDRESS_ALIAS" placeholder="Please assign an address title for future reference" value= "'.(isset($_POST['SL_ADDRESS_ALIAS'])?htmlspecialchars($_POST['SL_ADDRESS_ALIAS']):'').'" class="inputtxt" />
	</div>';
	
	}
	$html.='<div><input type="hidden" name="hidden_val" value="'.$cookie->SL_hidden.'" />
	<input type="submit" id="LoginRadius" name="LoginRadius" value="Submit" class="inputbutton">
	<input type="button" value="Cancel" class="inputbutton" onClick="showHome()" />
	</div></div>
	</form>
	</div>
	</div>
	</div>';
	echo $html;
}
// Verify email-address.
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

// send credenntials to customer.
function user_notification_email($email,$user) {
	$protocol_content = (Configuration::get('PS_SSL_ENABLED')) ? 'https://' : 'http://';
	$link=$protocol_content.$_SERVER['HTTP_HOST'];
	$sub="Thank You For Registration";
	$vars = array(
	        '{firstname}' => $user['fname'], 
			'{lastname}' => $user['lname'], 
			'{email}' => $email,
			'{passwd}'=> $user['pass']
	);
          $id_lang = (int)Configuration::get('PS_LANG_DEFAULT');
		Mail::Send($id_lang, 'account',$sub, $vars, $email);
}

// Notify admin when new user register.
function Admin_email($email,$firstname,$lastname) {
	$protocol_content = (Configuration::get('PS_SSL_ENABLED')) ? 'https://' : 'http://';
	$link=$protocol_content.$_SERVER['HTTP_HOST'];
	$sub="New User Registration";
	$msg="New User Registered to your site";
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
// Send mail.
function SL_email($to,$sub,$msg,$firstname,$lastname){
	if($_SERVER['HTTP_HOST']=="localhost"){
		echo "Email will work at online only.";
	}else{
		$home = getHome();
		$msgg="Your Confirmation link Has Been Sent To Your Email Address. Please verify your email by clicking on confirmation link";
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
// Get random character.
function SL_randomchar(){
	$char="";
	for($i=0;$i<20;$i++){
		$char.=rand(0,9);
	}
	return($char);
}
//Save data after POPup call.
function SL_data_save($data=array()){
global $cookie;
if($_POST['hidden_val']!=$cookie->SL_hidden){
 $home = getHome();
 $msgg="Cookie has been deleted, please try again.";
 popup_verify($msgg,$home);
}
elseif(!Context:: getContext()->customer->isLogged()) {
  if(isset($data->Email) && !empty($data->Email)){
    $email=$data->Email;
  }
  else {
    if(empty($email) && Configuration::get('EMAIL_REQ')=="1"){
      email_rand($cookie);
    }
    $email= $cookie->Email;
  }
  $cookie->SL_hidden='';
  $provider_name=$cookie->Provider;
  $query="SELECT c.id_customer from "._DB_PREFIX_."customer AS c INNER JOIN "._DB_PREFIX_."sociallogin AS sl ON sl.id_customer=c.id_customer  WHERE c.email='$email' AND sl.Provider_name='$provider_name' AND verified='1'";
  $query = Db::getInstance()->ExecuteS($query);
  if(!empty($query['0']['id_customer'])){
    $error_msg="This email id already exist";
    $data->Email='';
    popUpWindow($error_msg,$data);
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
	$msg="Please click on the following link or paste it in browser to verify your email: click $link";
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
	$address_fields =new stdClass;
	$address_fields->address=(isset($data->ADDRESS) && !empty($data->ADDRESS)) ?$data->ADDRESS: $cookie->SL_Address;
	$address_fields->zipcode=(isset($data->ZIPCODE) && !empty($data->ZIPCODE)) ? $data->ZIPCODE: $cookie->SL_PCode ;
	$address_fields->add_alias=(!empty($data->ADDRESS_ALIAS) ? $data->ADDRESS_ALIAS : '') ;
	$address_fields->country_code=(isset($data->Country) && !empty($data->Country)) ? $data->Country: $cookie->SL_CCode;
	$address_fields->country_name=(isset($cookie->Country->Name) && $cookie->Country->Name!="unknown" && !empty($cookie->SL_CName)) ? $cookie->SL_CName : 
	$cookie->SL_LocalName;
	$address_fields->city=(isset($data->CITY) && !empty($data->CITY)) ? $data->CITY : $cookie->SL_City;
	$address_fields->state=(isset($data->STATE) && !empty($data->STATE)) ? $data->STATE : $cookie->SL_State ;
	$address_fields->phone_number=(isset($data->PhoneNumbers) &&  !empty($data->PhoneNumbers)) ? $data->PhoneNumbers : $cookie->SL_Phone ;
	$password = Tools::passwdGen();
	$pass=Tools::encrypt($password);
	$date_added=date("Y-m-d H:i:s",time());
	$date_updated=$date_added;
	$last_pass_gen=$date_added;
	$s_key = md5(uniqid(rand(), true));
	$gender = pSQL ($cookie->Gender);
	$bday = pSQL($cookie->BirthDate);
	$ran_arr['fname']=	$fname;
	$ran_arr['lname']=	$lname;
	$ran_arr['pass']=	$password;
	$newsletter='0';
	$optin='0';
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
	if(isset($data->Email) && !empty($data->Email)){
	$rand=SL_randomchar();
	$query= "INSERT into $tbl (`id_customer`,`provider_id`,`Provider_name`,`rand`,`verified`) values ('$insert_id','$provider_id','$provider_name','$rand','0') ";
	Db::getInstance()->Execute("DELETE FROM $tbl WHERE provider_id='$provider_id'");
	Db::getInstance()->Execute($query);
	$to=$email;
	$sub="Verify your email id. ";
	$protocol_content = (Configuration::get('PS_SSL_ENABLED')) ? 'https://' : 'http://';
	$link=$protocol_content.$_SERVER['HTTP_HOST'].__PS_BASE_URI__."?SL_VERIFY_EMAIL=$rand&SL_PID=$provider_id";
	$msg="Please click on the following link or paste it in browser to verify your email: click $link";
	SL_email($to,$sub,$msg,$fname,$lname);
	if(Configuration::get('SEND_REQ')=="1")
	Admin_email($email,$fname,$lname);
	if(Configuration::get('user_notification')=="0")
	user_notification_email($email,$ran_arr);
	$tbl=pSQL(_DB_PREFIX_.'customer_group');
	$query= "INSERT into $tbl (`id_customer`,`id_group`) values ('$insert_id','1') ";
	Db::getInstance()->Execute("DELETE FROM $tbl WHERE id_customer='$insert_id'");
	Db::getInstance()->Execute($query);
	extraFields($address_fields,$insert_id,$fname,$lname);
	}
	else {
	Db::getInstance()->Execute("DELETE FROM $tbl WHERE provider_id='$provider_id'");
	$query= "INSERT into $tbl (`id_customer`,`provider_id`,`Provider_name`,`verified`,`rand`) values ('$insert_id','$provider_id','$provider_name','1','') ";
	Db::getInstance()->Execute($query);
	//extra data from here later to complete
	$tbl=pSQL(_DB_PREFIX_.'customer_group');
	Db::getInstance()->Execute("DELETE FROM $tbl WHERE id_customer='$insert_id'");
	$query= "INSERT into $tbl (`id_customer`,`id_group`) values ('$insert_id','1') ";
	Db::getInstance()->Execute($query);
	extraFields($address_fields,$insert_id,$fname,$lname);
	$arr=array();
	$arr['id']=(string)$insert_id;
	$arr['lname']=$lname;
	$arr['fname']=$fname;
	$arr['pass']=$pass;
	$arr['email']=$email;
	$user['pass']=$password;
	$user['fname']=$arr['fname'];
	$user['lname']=$arr['lname'];
	if(Configuration::get('SEND_REQ')=="1")
	Admin_email($arr['email'],$arr['fname'],$arr['lname']);
	if(Configuration::get('user_notification')=="0")
	user_notification_email($arr['email'],$user);
	$arr['loginradius_id']=$provider_id;
	loginRedirect($arr);
	}
	}
  }
  }
}

//Show Error Message.
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

//Insert popup optionla fields.
function extraFields($obj,$insert_id,$fname,$lname){
	$str="";
	if(!empty($obj->country_code)){
		 $id=pSQL(getIdByCountryISO($obj->country_code));
		$str.="id_country='$id',";
	}
	elseif(!empty($obj->country_name) && empty($id)){
		$country=$obj->country_name;
		$id=pSQL(getIdByCountryName($country));
		$str.="id_country='$id',";
	}
	elseif(empty($id)){
		$id = (int)(Configuration::get('PS_COUNTRY_DEFAULT'));
		$str.="id_country='$id',";
	}
	if(isset($obj->state) && $obj->state != 'empty'){
		$state=$obj->state;
		$iso=pSQL(getIsoByState($state));
		$str.="id_state='$iso',";
	}
	if(isset($obj->city)){
		$city=pSQL($obj->city);
		$str.="city='$city',";
	}
	if(isset($obj->zipcode)){
		$zip=trim(pSQL($obj->zipcode));
		$str.="postcode='$zip',";
	}
	if(isset($obj->address)){
		$address=pSQL($obj->address);
		$str.="address1='$address',";
	}
	if(isset($obj->phone_number)){
		$phone=pSQL($obj->phone_number);
		$str.="phone_mobile='$phone',";
	}
	if(isset($obj->add_alias)){
		$add_alias=pSQL($obj->add_alias);
		$str.="alias='$add_alias',";
	}
	$date=date("y-m-d h:i:s");
	$str.="date_add='$date',date_upd='$date',";
	$tbl=_DB_PREFIX_."address";
	$fname=pSQL($fname);
	$lname=pSQL($lname);
     $q= "INSERT into $tbl SET ".$str." id_customer='$insert_id', lastname='$fname',firstname='$lname' ";
	$q = Db::getInstance()->Execute($q);
}

//Get country name by Counter ISo=code.
function getIdByCountryISO($ISO){
	$tbl=_DB_PREFIX_."country";
	$field="iso_code";
	$ISO=pSQL(trim($ISO));
	  $q="SELECT * from $tbl WHERE $field='$ISO'";
	$q = Db::getInstance()->ExecuteS($q);
	$iso="";
	$iso=$q[0]['id_country'];
	return($iso);
}

// Get Counter name by ID.
function getIdByCountryName($country){
	$tbl=_DB_PREFIX_."country_lang";
	$country=pSQL(trim($country));
	$q="SELECT * from $tbl WHERE name='$country'";
	$q = Db::getInstance()->ExecuteS($q);
	$iso=$q[0]['id_country'];
	return($iso);
}

// Get State. from ISO-code.
function getIsoByState($state){

		$tbl=_DB_PREFIX_."state";
		 $q="SELECT * from $tbl WHERE  iso_code ='$state'";
		$q = Db::getInstance()->ExecuteS($q);
		if(!empty($q)) {
		$id=$q[0]['id_state'];
		return($id);
		}
}

// remove special character from name.
function remove_special($field){
 $in_str = str_replace(array('<', '>', '&', '{', '}', '*', '/', '(', '[', ']' , '@', '!', ')', '&', '*', '#', '$', '%', '^', '|','?', '+', '=','"',','), array(''), $field);
	   $cur_encoding = mb_detect_encoding($in_str) ;
       if($cur_encoding == "UTF-8" && mb_check_encoding($in_str,"UTF-8"))
         return $in_str;
       else
         return utf8_encode($in_str);
    }
/*
* Retrieve random Email address.
*/	
  function email_rand($userprofile){
	switch( $userprofile->Provider) {
	  case 'twitter':
	    $userprofile->Email= $userprofile->ID.'@'.$userprofile->Provider.'.com';
	  break;
	  default:
	  $Email_id = substr( $userprofile->ID,7);
      $Email_id2 = str_replace("/","_",$Email_id);
	  $userprofile->Email = str_replace(".","_",$Email_id2).'@'. $userprofile->Provider .'.com';
	  break;
	}
  }
?>