{capture name=path}{l s='Payment Using Webpay' mod='webpaykcc'}{/capture}
<h2>{l s='Order summary' mod='webpaykcc'}</h2>

{assign var='current_step' value='payment'}
{include file="$tpl_dir./order-steps.tpl"}
{include file="$tpl_dir./errors.tpl"}

<form method="post" action="{$action}">

<input type="hidden" name="TBK_TIPO_TRANSACCION" value="{$transaction_type}" />

<input type="hidden" name="TBK_MONTO" value="{$tbk_total_amount}" />

<input type="hidden" name="TBK_ORDEN_COMPRA" value="{$order_id}" />

<input type="hidden" name="TBK_ID_SESION" value="{$session_id}" /> 

<input type="hidden" name="TBK_URL_FRACASO" value="{$failure_page}" />

<input type="hidden" name="TBK_URL_EXITO" value="{$success_page}" />

	<div class="row row-margin-bottom">
		<div class="col-sm-6">
			<img src="{$logo}">
			{l s='You will be charged with the following amount' mod='webpaykcc'} &nbsp; <strong>${$total_amount}</strong>
		</div>
		<div class="col-sm-6">
			<button type="submit" class="button btn btn-default standard-checkout button-medium pull-right">
				<span>{l s='Pay Cart' mod='webpaykcc'} <i class="icon-chevron-right right"></i></span>
			</button>
		</div>
	</div>
</form>
