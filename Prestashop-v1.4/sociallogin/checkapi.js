var selectedProviderList= document.getElementsByName('rearrange_settings[]');
var checkedhorizontal= document.getElementsByName('chooseshare');
var checkedvertical= document.getElementsByName('chooseverticalshare');
var selectedverticalProviderList= document.getElementsByName('vertical_rearrange_settings[]');
document.write("<script type='text/javascript'>var islrsharing = true; var islrsocialcounter = true;</script>");
document.write("<script src='//share.loginradius.com/Content/js/LoginRadius.js' type='text/javascript'></script>");
function verticalsharingproviderlist(){
	var sharing =  $SS.Providers.More;
	var div = document.getElementById('verticalshareprovider');
	 var rowitem = document.createElement('tr');
  		 rowitem.setAttribute('class', 'row_white verticalshareprovider');
	for( var i= 0 ; i< sharing.length;i++){
		var colitem = document.createElement('td');
		 var provider = document.createElement('input');
		 provider.setAttribute('type', 'checkbox');
		 provider.setAttribute('id', 'edit-vertical-'+sharing[i].toLowerCase());
		 provider.setAttribute('onchange', 'loginRadiusverticalSharingLimit(this);loginRadiusverticalRearrangeProviderList(this);');
		 provider.setAttribute('name', 'socialshare_providers_list['+sharing[i].toLowerCase()+']');
		 provider.setAttribute('value', sharing[i].toLowerCase());
		 var label = document.createElement('span');
		  label.setAttribute('for', 'edit-vertical-'+sharing[i].toLowerCase());
		  label.setAttribute('class','option');
		   var labeltext = document.createTextNode(sharing[i]);
          label.appendChild(labeltext);
		  colitem.appendChild(provider);
		   colitem.appendChild(label);
		    rowitem.appendChild(colitem);
		  div.appendChild(rowitem);
	}
	if(selectedverticalProviderList.length >0) {
		for(var i = 0; i < selectedverticalProviderList.length; i++){
			if(!selectedverticalProviderList[i].checked){
				$('#edit-vertical-'+selectedverticalProviderList[i].value.toLowerCase()).attr('checked','checked');
			}
		}
	}
}
function verticalcounterproviderlist(selectedcounterProviderList) {
	var counter = $SC.Providers.All;
	var div = document.getElementById('verticalshareprovider');
	 var rowitem = document.createElement('tr');
  		 rowitem.setAttribute('class', 'row_white verticalcounterprovider');
		for( var i= 0 ; i< counter.length;i++){
		value = counter[i].split(' ').join('');
		value = value.replace("++", "plusplus");
        value = value.replace("+", "plus");
		var colitem = document.createElement('td');
		 var provider = document.createElement('input');
		 provider.setAttribute('type', 'checkbox');
		 provider.setAttribute('id', 'edit-vertical-counter-'+value);
		 provider.setAttribute('name', 'socialshare_counter_list[]');
		 provider.setAttribute('value', counter[i]);
		 var label = document.createElement('span');
		  label.setAttribute('for', 'edit-vertical-counter-'+counter[i]);
		  label.setAttribute('class','option');
		   var labeltext = document.createTextNode(counter[i]);
          label.appendChild(labeltext);
		  colitem.appendChild(provider);
		   colitem.appendChild(label);
		    rowitem.appendChild(colitem);
		  div.appendChild(rowitem);  
	}
	if(selectedcounterProviderList.length >0) {
		for(var i = 0; i < selectedcounterProviderList.length; i++){
			if(!selectedcounterProviderList[i].checked){
				value =selectedcounterProviderList[i].split(' ').join('');
				value = value.replace("++", "plusplus");
                value = value.replace("+", "plus");
				$('#edit-vertical-counter-'+value).attr('checked','checked');
			}
		}
	}
}
function sharingproviderlist(){
	var sharing =  $SS.Providers.More;
	var div = document.getElementById('shareprovider');
	 var rowitem = document.createElement('tr');
  		 rowitem.setAttribute('class', 'row_white shareprovider');
	for( var i= 0 ; i< sharing.length;i++){
		var colitem = document.createElement('td');
		 var provider = document.createElement('input');
		 provider.setAttribute('type', 'checkbox');
		 provider.setAttribute('id', 'edit-'+sharing[i].toLowerCase());
		 provider.setAttribute('onchange', 'loginRadiusSharingLimit(this);loginRadiusRearrangeProviderList(this);');
		 provider.setAttribute('name', 'socialshare_show_providers_list['+sharing[i].toLowerCase()+']');
		 provider.setAttribute('value', sharing[i].toLowerCase());
		 var label = document.createElement('span');
		  label.setAttribute('for', 'edit-'+sharing[i].toLowerCase());
		  label.setAttribute('class','option');
		   var labeltext = document.createTextNode(sharing[i]);
          label.appendChild(labeltext);
		  colitem.appendChild(provider);
		   colitem.appendChild(label);
		    rowitem.appendChild(colitem);
		  div.appendChild(rowitem);
	}
	if(selectedProviderList.length >0) {
		for(var i = 0; i < selectedProviderList.length; i++){
			if(!selectedProviderList[i].checked){
				$('#edit-'+selectedProviderList[i].value.toLowerCase()).attr('checked','checked');
			}
		}
	}
}
function counterproviderlist(selectedcounterProviderList) {
	var counter = $SC.Providers.All;
	var div = document.getElementById('shareprovider');
	 var rowitem = document.createElement('tr');
  		 rowitem.setAttribute('class', 'row_white counterprovider');
		for( var i= 0 ; i< counter.length;i++){
		value = counter[i].split(' ').join('');
		value = value.replace("++", "plusplus");
        value = value.replace("+", "plus");
		var colitem = document.createElement('td');
		 var provider = document.createElement('input');
		 provider.setAttribute('type', 'checkbox');
		 provider.setAttribute('id', 'edit-counter-'+value);
		 provider.setAttribute('name', 'socialshare_show_counter_list[]');
		 provider.setAttribute('value', counter[i]);
		 var label = document.createElement('span');
		  label.setAttribute('for', 'edit-'+counter[i]);
		  label.setAttribute('class','option');
		   var labeltext = document.createTextNode(counter[i]);
          label.appendChild(labeltext);
		  colitem.appendChild(provider);
		   colitem.appendChild(label);
		    rowitem.appendChild(colitem);
		  div.appendChild(rowitem);  
	}
	if(selectedcounterProviderList.length >0) {
		for(var i = 0; i < selectedcounterProviderList.length; i++){
			if(!selectedcounterProviderList[i].checked){
				value =selectedcounterProviderList[i].split(' ').join('');
				value = value.replace("++", "plusplus");
                value = value.replace("+", "plus");
				$('#edit-counter-'+value).attr('checked','checked');
			}
		}
	}
}
function loginradius_vertical_sharing() {
	$('.label_verticalsharing_networks').show();
	$('#verticalshareprovider').show();
	$('.row_white.verticalcounterprovider').hide();
	$('.row_white.verticalshareprovider').show();
	$('.loginradius_verticalrearrange_icons').show();
	$('.label_sharing_networks').css({"background":"#EBEBEB"});
	$('.loginradius_verticalrearrange_icons').css({"background":"#EBEBEB"});
	$('.vertical_location').css({"background":"#FFFFFF"});
	
}
function loginradius_vertical_counter(){
	
	$('.vertical_location').css({"background":"#EBEBEB"});
	$('.label_verticalsharing_networks').show();
	$('#verticalshareprovider').show();
 $('.row_white.verticalcounterprovider').show();
 $('.row_white.verticalshareprovider').hide();
 $('.loginradius_verticalrearrange_icons').hide();
}
function loginradius_horizontal_sharing() {
	$('.label_sharing_networks').show();
	$('#shareprovider').show();
	$('.row_white.counterprovider').hide();
	$('.row_white.shareprovider').show();
	$('.loginradius_rearrange_icons').show();
	$('.label_sharing_networks').css({"background":"#FFFFFF"});
	$('.loginradius_rearrange_icons').css({"background":"#EBEBEB"});
	$('.horizontal_location').css({"background":"#FFFFFF"});
}
function loginradius_horizontal_simple() {
	$('.horizontal_location').css({"background":"#FFFFFF"});
	$('.label_sharing_networks').hide();
	$('#shareprovider').hide();
	$('.loginradius_rearrange_icons').hide();
}
function loginradius_horizontal_hybrid() {
	$('.horizontal_location').css({"background":"#EBEBEB"});
	$('.label_sharing_networks').show();
	$('#shareprovider').show();
 $('.row_white.counterprovider').show();
 $('.row_white.shareprovider').hide();
 $('.loginradius_rearrange_icons').hide();
}

// JavaScript Document
function getXMLHttp()
{
  var xmlHttp
  try
  {
    //Firefox, Opera 8.0+, Safari
    xmlHttp = new XMLHttpRequest();
  }
  catch(e)
  {
    //Internet Explorer
    try
    {
      xmlHttp = new ActiveXObject("Msxml2.XMLHTTP");
    }
    catch(e)
    {
      try
      {
        xmlHttp = new ActiveXObject("Microsoft.XMLHTTP");
      }
      catch(e)
      {
        alert("Your browser does not support AJAX!")
        return false;
      }
    }
  }
  return xmlHttp;
}
// prepare rearrange provider list
function loginRadiusverticalRearrangeProviderList(elem){
	var ul = $('#verticalsortable');
	if(elem.checked){
		var listItem = document.createElement('li');
		listItem.setAttribute('id', 'loginRadiusverticalLI'+elem.value);
		listItem.setAttribute('title', elem.value);
		listItem.setAttribute('class', 'lrshare_iconsprite32 lrshare_'+elem.value);
		// append hidden field
		var provider = document.createElement('input');
		provider.setAttribute('type', 'hidden');
		provider.setAttribute('name', 'vertical_rearrange_settings[]');
		provider.setAttribute('value', elem.value);
		listItem.appendChild(provider);
		ul.append(listItem);
	}else{
		$('#loginRadiusverticalLI'+elem.value).remove();
	}
}
// check provider more then 9 select
function loginRadiusverticalSharingLimit(elem){
	var checkCount = selectedverticalProviderList.length;
     if(elem.checked){
			// count checked providers
			checkCount++;
			if(checkCount >= 10){
				elem.checked = false;
					jQuery("#loginRadiusverticalSharingLimit").show('slow');
				setTimeout(function() {
					jQuery("#loginRadiusverticalSharingLimit").hide('slow');
				}, 2000);
				return;
			}
	}
}
// prepare rearrange provider list
function loginRadiusRearrangeProviderList(elem){
	var ul = $('#sortable');
	if(elem.checked){
		var listItem = document.createElement('li');
		listItem.setAttribute('id', 'loginRadiusLI'+elem.value);
		listItem.setAttribute('title', elem.value);
		listItem.setAttribute('class', 'lrshare_iconsprite32 lrshare_'+elem.value);
		// append hidden field
		var provider = document.createElement('input');
		provider.setAttribute('type', 'hidden');
		provider.setAttribute('name', 'rearrange_settings[]');
		provider.setAttribute('value', elem.value);
		listItem.appendChild(provider);
		ul.append(listItem);
	}else{
		$('#loginRadiusLI'+elem.value).remove();
	}
}
// check provider more then 9 select
function loginRadiusSharingLimit(elem){
	var checkCount = selectedProviderList.length;
     if(elem.checked){
			// count checked providers
			checkCount++;
			if(checkCount >= 10){
				elem.checked = false;
					jQuery("#loginRadiusSharingLimit").show('slow');
				setTimeout(function() {
					jQuery("#loginRadiusSharingLimit").hide('slow');
				}, 2000);
				return;
			}
	}
}
function Makevertivisible() {
		$('.label_sharing_networks').hide();
	$('#shareprovider').hide();
	$('.loginradius_rearrange_icons').hide();
	for(var i = 0; i < checkedvertical.length; i++){
		if(checkedvertical[i].checked){
			if(checkedvertical[i].value == 6 || checkedvertical[i].value == 7)
			loginradius_vertical_counter();
			else if(checkedvertical[i].value == 4 || checkedvertical[i].value == 5)
			loginradius_vertical_sharing();	
	       }
	}
	//$('.loginradius_verticalrearrange_icons').show();
	//$('.label_verticalsharing_networks').show();
	//$('#verticalshareprovider').show();
	$('#horizontal_text_label').hide();
	$('#enable_social_horizontal_sharing').hide();
	$('#enable_social_vertical_sharing').show();
	$('.vertical_location').show();
	$('.horizontal_location').hide();
  $('#sharevertical').show();
  $('#sharehorizontal').hide();
  $('#share_theme_poition').show();
  $('#arrow').css({"margin-left":"-28px"});
   $('.sharing_block').css({"background":"#FFFFFF"});
}
function Makehorivisible() {
	for(var i = 0; i < checkedhorizontal.length; i++){
		if(checkedhorizontal[i].checked){
			if(checkedhorizontal[i].value == 2 || checkedhorizontal[i].value == 3)
			loginradius_horizontal_simple();
		   	else if(checkedhorizontal[i].value == 8 || checkedhorizontal[i].value == 9) 
			loginradius_horizontal_hybrid();
			else if (checkedhorizontal[i].value == 0 || checkedhorizontal[i].value == 1)
			loginradius_horizontal_sharing();	
	       }
	}
	$('#horizontal_text_label').show();
	$('.vertical_location').hide();
	$('#enable_social_horizontal_sharing').show();
	$('#enable_social_vertical_sharing').hide();
	$('.horizontal_location').show();
	$('.label_verticalsharing_networks').hide();
	$('.loginradius_verticalrearrange_icons').hide();
	$('#verticalshareprovider').hide();
  $('#sharehorizontal').show();
  $('#sharevertical').hide();
  $('#share_theme_poition').hide();
 $('#arrow').css({"margin-left":"-106px"});
 $('.sharing_block').css({"background":"#EBEBEB"});
}
function MakeRequest()
{
   $('#ajaxDiv').html('<div id ="wait">Contacting API - please wait ...</div>');	
   var connection_url = $('#connection_url').val();
   var apikey = $('#API_KEY').val();
   var apisecret = $('#API_SECRET').val();
   if (apikey == '') {
	   $('#ajaxDiv').html('<div id="Error">please enter api key</div>');
	   return false;
   }
   if (apisecret == '') {
	   $('#ajaxDiv').html('<div id="Error">please enter api secret</div>');
	   return false;
   }
   if ($('#CURL_REQ').is(':checked')) {
	   var api_request = 'curl';
   }
   else {
	   var api_request = 'fsockopen';   
   }
   
   $.ajax({
  type: "GET",
  url: connection_url+"modules/sociallogin/checkapi.php",
  data: "apikey=" + apikey +"&apisecret="+apisecret+"&api_request="+api_request,
  success: function(msg){
	$("#ajaxDiv").html(msg);
  }
});
}
function show_profilefield(elem){
	if(elem == 1){
		  $('#profilefield_display').css({"display":"block"});
	}
	else {
		 $('#profilefield_display').css({"display":"none"});
	}
}