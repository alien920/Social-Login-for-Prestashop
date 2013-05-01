<?php

class SocialloginActionsModuleFrontController extends ModuleFrontController
{
	/**
	 * @var int
	 */
	public $id_product;

	public function init()
	{
		parent::init();
		include_once(dirname(__FILE__)."/sociallogin_functions.php");
		if(isset($_REQUEST['token'])){
			include_once("sociallogin_user_data.php");
			$obj=new LrUser();
		}
	}
}