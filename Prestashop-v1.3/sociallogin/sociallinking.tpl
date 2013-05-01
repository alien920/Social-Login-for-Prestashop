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