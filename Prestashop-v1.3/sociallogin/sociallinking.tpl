{*
* 2007-2012 PrestaShop
*
* NOTICE OF LICENSE
*
* This source file is subject to the Academic Free License (AFL 3.0)
* that is bundled with this package in the file LICENSE.txt.
* It is also available through the world-wide-web at this URL:
* http://opensource.org/licenses/afl-3.0.php
* If you did not receive a copy of the license and are unable to
* obtain it through the world-wide-web, please send an email
* to license@prestashop.com so we can send you a copy immediately.
*
* DISCLAIMER
*
* Do not edit or add to this file if you wish to upgrade PrestaShop to newer
* versions in the future. If you wish to customize PrestaShop for your
* needs please refer to http://www.prestashop.com for more information.
*
*  @author PrestaShop SA <contact@prestashop.com>
*  @copyright  2007-2012 PrestaShop SA
*  @version  Release: $Revision: 14011 $
*  @license    http://opensource.org/licenses/afl-3.0.php  Academic Free License (AFL 3.0)
*  International Registered Trademark & Property of PrestaShop SA
*}
<div id="SocialAccountLinking" class="block account">
	<h4>
		<a href="{$base_dir_ssl}/modules/sociallogin/sociallinking.php">{l s='Social Account Linking' mod='sociallogin}</a>
	</h4>
	<div class="block_content">
		<div id="Social Account Linking" class="expanded">
		{if $lr_check}
    	{if $socialloginlrmessage}
			<p class="warning">{$socialloginlrmessage}</p>
		{/if}
		<div>
			<div class="favoriteproduct clearfix">
				<br />{$lr_check}
				
			</div>
		</div>
	{else}
		<p class="warning">{l s='Your Api Key is Wrong' mod='sociallogin'}</p>
	{/if}
		</div>
	</div>
</div>