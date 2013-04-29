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

<div id="mywishlist">
	{capture name=path}<a href="{$link->getPageLink('my-account.php', true)}">{l s='My Account' mod='sociallogin'}</a><span class="navigation-pipe">{$navigationPipe}</span>{l s='Social Account Linking' mod='sociallogin'}{/capture}
	{include file="$tpl_dir./breadcrumb.tpl"}
<div id="favoriteproducts_block_account">
	<h2>{l s='Social Account Linking' mod='sociallogin'}</h2>
	{if $lr_check}
    	{if $socialloginlrmessage}
			<p class="warning">{$socialloginlrmessage}</p>
		{/if}
		<div>
			<div class="favoriteproduct clearfix">
				{$lr_check}
				
			</div>
		</div>
	{else}
		<p class="warning">{l s='Your Api Key is Wrong' mod='sociallogin'}</p>
	{/if}
</div>

	<ul class="footer_links">
		<li><a href="{$link->getPageLink('my-account.php', true)}"><img src="{$img_dir}icon/my-account.gif" alt="" class="icon" /></a><a href="{$link->getPageLink('my-account.php', true)}">{l s='Back to Your Account' mod='blockwishlist'}</a></li>
		<li><a href="{$base_dir}"><img src="{$img_dir}icon/home.gif" alt="" class="icon" /></a><a href="{$base_dir}">{l s='Home' mod='blockwishlist'}</a></li>
    </ul>
</div>
