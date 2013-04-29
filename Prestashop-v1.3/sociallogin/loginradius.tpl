<!-- Block mymodule -->
{if $right}
<div id="mymodule_block_left" class="block" >
  <h4>Social Login</h4>
  <div class="block_content">
    <ul>
      <li>
	  <div>{$iframe}</div>
	  </li>
    </ul>
  </div>
</div>
{else}
<div id="mymodule_block_left"  {$margin_style}>
  <div class="block_content">
    <ul>
	  <div style="padding-top:5px;">{$iframe}</div>
    </ul>
  </div>
</div>
{/if}

<!-- /Block mymodule -->


