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
