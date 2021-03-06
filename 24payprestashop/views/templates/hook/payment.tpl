<style>
	.twentyfourpay a {
		background: #fbfbfb url(http://icons.24-pay.sk/images/24PAY_FARBA.png) no-repeat 17px 50% / 65px auto;
	}

	.twentyfourpay a::after {
		display: block;
		content: "\f054";
		position: absolute;
		right: 15px;
		margin-top: -11px;
		top: 50%;
		font-family: "FontAwesome";
		font-size: 25px;
		height: 22px;
		width: 14px;
		color: #777777;
	}
</style>

<div class="row">
	<div class="col-xs-12">
        <p class="payment_module twentyfourpay">
            <a id="pay24-btn" href="{$link->getModuleLink('twentyfourpay', 'validation', [], true)|escape:'html'}" title="{l s='Pay by 24pay gateways.' mod='twentyfourpay'}"><!-- payment -->
            	{l s='Pay by 24pay gateways' mod='twentyfourpay'} 
            </a>
        </p>
    </div>
</div>

<script>
	var clicked24 = false;
	$(function(){
		$('body').on('click',"#pay24-btn",function(event){
			if (clicked24)
				event.preventDefault();
			else
				clicked24 = true;
		});
	});
</script>
