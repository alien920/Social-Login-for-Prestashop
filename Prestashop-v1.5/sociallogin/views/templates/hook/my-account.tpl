
<li class="favoriteproducts">
	<a href="{$link->getModuleLink('sociallogin', 'account')|escape:'htmlall':'UTF-8'}" title="{l s='Social Account Linking' mod='sociallogin'}">
		{if !$in_footer}<img {if isset($mobile_hook)}src="{$module_template_dir}img/socialinking.jpg" class="ui-li-icon ui-li-thumb"{else}src="{$module_template_dir}img/socialinking.jpg" class="icon"{/if} alt="{l s='Social Account Linking' mod='sociallogin'}"/>{/if}
		{l s='Social Account Linking' mod='sociallogin'}
	</a>
</li>
