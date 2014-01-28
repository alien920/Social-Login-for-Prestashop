{*
* @package sociallogin
* @license GNU GENERAL PUBLIC LICENSE Version 2, June 1991
*
* This program is free software; you can redistribute it and/or modify
* it under the terms of the GNU General Public License as published by
* the Free Software Foundation; either version 2 of the License, or
* (at your option) any later version.
*
* This program is distributed in the hope that it will be useful,
* but WITHOUT ANY WARRANTY; without even the implied warranty of
* MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
* GNU General Public License for more details.
*
* You should have received a copy of the GNU General Public License along
* with this program; if not, write to the Free Software Foundation, Inc.,
* 51 Franklin Street, Fifth Floor, Boston, MA 02110-1301 USA.
*}
<div id="mywishlist">
	{capture name=path}<a href="{$link->getPageLink('my-account.php', true)}">{l s='My Account' mod='sociallogin'}</a><span class="navigation-pipe">{$navigationPipe}</span>{l s='Social Account Linking' mod='sociallogin'}{/capture}
	{include file="$tpl_dir./breadcrumb.tpl"}
<div id="favoriteproducts_block_account">
	<h2>{l s='Social Account Linking' mod='sociallogin'}</h2>
    {if $socialloginlrmessage}
        <p class="warning">{$socialloginlrmessage|escape:'htmlall':'UTF-8'}</p>
    {/if}
		<div>
			<div class="favoriteproduct clearfix">
                <div class="interfacecontainerdiv"></div>

                {if $lr_check}
                <ul style="list-style:none">

                    {foreach from=$lr_check item='provider' name=provider}
                        <li style='width:280px;float:left;'>
                            <img src='img/{$provider.Provider_name|escape:'htmlall':'UTF-8'}.png'>
                            {if !($cookie->lr_login)}
                                $cookie->loginradius_id = '';
                            {/if}
                            {if ($provider.provider_id == $cookie->loginradius_id)}
                                <label style="color:green;"> Currently connected with </label><label>{$provider.Provider_name|escape:'htmlall':'UTF-8'}</label>
                            {else}
                                <label> Connected with {$provider.Provider_name|escape:'htmlall':'UTF-8'}</label>
                            {/if}
                            <a href='?id_provider={$provider.provider_id|escape:'htmlall':'UTF-8'}'>
                                <input name='submit' type='button' value='remove' style='background:#666666; color:#FFF; text-decoration:none;cursor: pointer; float:right;'>
                            </a></li>
                    {/foreach}
                </ul>
                {/if}
			</div>
		</div>
</div>

	<ul class="footer_links">
		<li><a href="{$link->getPageLink('my-account.php', true)}"><img src="{$img_dir}icon/my-account.gif" alt="" class="icon" /></a><a href="{$link->getPageLink('my-account.php', true)}">{l s='Back to Your Account' mod='sociallogin'}</a></li>
		<li><a href="{$base_dir}"><img src="{$img_dir}icon/home.gif" alt="" class="icon" /></a><a href="{$base_dir}">{l s='Home' mod='sociallogin'}</a></li>
    </ul>
</div>
