<img src="{$img_header}"/>

<h2>{l s='Pago con tarjetas de crédito o débito usando Webpay' mod='webpaykcc'}</h2>

<fieldset>
    <legend><img src="../img/admin/warning.gif"/>{l s='Information' mod='webpaykcc'}</legend>
    <div class="margin-form">Module version: {$version}</div>
    
</fieldset>

<form action="{$post_url}" method="post" enctype="multipart/form-data" style="clear: both; margin-top: 10px;">
    <fieldset>
    	    <legend><img src="../img/admin/contact.gif"/>{l s='Settings' mod='webpaykcc'}</legend>
	    
	    <!-- Add more config -->

        <center><input type="submit" name="webpaykcc_updateSettings" value="{l s='Save Settings' mod='webpaykcc'}"
                       class="button" style="cursor: pointer; display:"/></center>
    </fieldset>
</form>