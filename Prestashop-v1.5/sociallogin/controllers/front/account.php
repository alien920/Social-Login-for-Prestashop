<?php

class SocialloginAccountModuleFrontController extends ModuleFrontController
{
	public $ssl = true;

	public function init()
	{
		parent::init();

		require_once($this->module->getLocalPath().'sociallogin.php');
		require_once($this->module->getLocalPath().'sociallogin_user_data.php');
	}

	public function initContent()
	{
		parent::initContent();

		if (!Context::getContext()->customer->isLogged())
			Tools::redirect('index.php?controller=authentication&redirect=module&module=sociallogin&action=account');

		if (Context::getContext()->customer->id)
		{global $cookie,$smarty;
		
		//	LrUser::linking($cookie);
		if(isset($cookie->lrmessage) && $cookie->lrmessage != ''){
		$this->context->smarty->assign('socialloginlrmessage', $cookie->lrmessage);
		$cookie->lrmessage='';
		}
		else 
		$this->context->smarty->assign('socialloginlrmessage', '');
			$this->context->smarty->assign('sociallogin', sociallogin::jsinterface());
			
			$this->setTemplate('sociallogin-account.tpl');
		}
	}
}