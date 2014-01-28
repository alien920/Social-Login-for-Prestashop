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
<div id="SocialAccountLinking" class="block account">
	<h4>
		<a href="{$base_dir_ssl}/modules/sociallogin/sociallinking.php">{l s='Social Account Linking' mod='sociallogin'}</a>
	</h4>
	<div class="block_content">
		<div id="Social Account Linking" class="expanded">
            {if $socialloginlrmessage}
                <p class="warning">{$socialloginlrmessage|escape:'htmlall':'UTF-8'}</p>
            {/if}
           <br/> <div class="interfacecontainerdiv"></div>
            {if $lr_check}
                <div>
                <div class="favoriteproduct clearfix">
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
                </div>
                </div>
            {/if}
		</div>
	</div>
</div>