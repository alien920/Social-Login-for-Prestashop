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
	var provider = $("#shareprovider").find(":checkbox");
	var checkCount = 0;
	for(var i = 0; i < provider.length; i++){
		if(provider[i].checked){
			// count checked providers
			checkCount++;
			if(checkCount >= 10){
				elem.checked = false;
				$("#loginRadiusSharingLimit").show('slow');
				setTimeout(function() {
					$("#loginRadiusSharingLimit").hide('slow');
				}, 5000);
				//document.getElementById('loginRadiusSharingLimit').style.display = 'block';
				return;
			}
		}
	}
}
function Makevertivisible() {
  $('#sharevertical').show();
  $('#sharehorizontal').hide();
  $('#share_theme_poition').show();
  $('#arrow').css({"position":"absolute", "border-bottom":"8px solid #ffffff", "border-right":"8px solid transparent", "border-left":"8px solid transparent", "margin":"-18px 0 0 70px"});
}
function Makehorivisible() {
  $('#sharehorizontal').show();
  $('#sharevertical').hide();
  $('#share_theme_poition').hide();
  $('#arrow').css({"position":"absolute", "border-bottom":"8px solid #ffffff", "border-right":"8px solid transparent", "border-left":"8px solid transparent", "margin":"-18px 0 0 2px"});
}
function Makecvertivisible() {
  $('#countervertical').show();
  $('#counterhorizontal').hide();
  $('#counter_theme_poition').show();
  $('#carrow').css({"position":"absolute", "border-bottom":"8px solid #ffffff", "border-right":"8px solid transparent", "border-left":"8px solid transparent", "margin":"-18px 0 0 70px"});
}

function Makechorivisible() {
  $('#counterhorizontal').show();
  $('#countervertical').hide();
  $('#counter_theme_poition').hide();
  $('#carrow').css({"position":"absolute", "border-bottom":"8px solid #ffffff", "border-right":"8px solid transparent", "border-left":"8px solid transparent", "margin":"-18px 0 0 2px"});
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