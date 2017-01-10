{if $status == "OK" || $status == "PENDING"}
<h2>
	{l s='Transaction completed successfully' mod='twentyfourpay'}
</h2>

<p>
	Payment for your order ID {$orderId} has been successful.
	<!-- Payment for your <a href="{$link->getPageLink('order-history')}">order ID {$orderId}</a> has been successful. -->
	<!-- Payment for your <a href="{$link->getModuleLink('order-history')}">order ID {$orderId}</a> has been successful. -->

	<!-- <a href="{$link->getPageLink('order-confirmation', true, NULL, 'step=3&id_module=')|escape:'html'}">xxx</a> -->
	<!-- order-confirmation?id_cart=12&id_module=68&id_order=12&key=35e0978350f89ec728d5855dfc839873 -->
</p>

{else}

<h2>
	{l s='Transaction was not completed' mod='twentyfourpay'}
</h2>

<p>
	Payment for your order ID {$orderId} has failed.
</p>

{/if}
