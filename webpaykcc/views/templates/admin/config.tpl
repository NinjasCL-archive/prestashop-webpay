<img src="{$img_header}"/>

<h2>{l s='Payment using Webpay KCC' mod='webpaykcc'}</h2>

<fieldset>
    <legend><img src="../img/admin/warning.gif"/>{l s='Information' mod='webpaykcc'}</legend>
    <div class="margin-form">Module version: {$version}</div>
</fieldset>

<form action="{$post_url}" method="post" style="clear: both; margin-top: 10px;">
    <fieldset>
        <legend><img src="../img/admin/contact.gif"/>{l s='Settings' mod='webpaykcc'}</legend>
        
        {if isset($errors.kccPath)}
            <div class="error">
                <p>{$errors.kccPath}</p>
            </div>
        {/if}

        {if isset($errors.kccURL)}
            <div class="error">
                <p>{$errors.kccURL}</p>
            </div>
        {/if}

        {if isset($errors.kccLogPath)}
            <div class="error">
                <p>{$errors.kccLogPath}</p>
            </div>
        {/if}

        <label for="kccPath">{l s='KCC Path' mod='webpaykcc'}</label>

        <div class="margin-form"><input type="text" size="33" id="kccPath" name="kccPath"
                                        value="{$data_kccPath}"/></div>
        
        <label for="kccURL">{l s='KCC URL' mod='webpaykcc'}</label>

        <div class="margin-form"><input type="text" size="33" name="kccURL"
                                        id="kccURL" value="{$data_kccURL}"/></div>

        <label for="kccLogPath">{l s='KCC Log Path' mod='webpaykcc'}</label>

        <div class="margin-form"><input type="text" size="33" name="kccLogPath"
                                        id="kccLogPath" value="{$data_kccLogPath}"/></div>

        <center><input type="submit" name="webpaykcc_updateSettings" value="{l s='Save Settings' mod='webpaykcc'}"
                       class="button" style="cursor: pointer; display:"/></center>
    </fieldset>
</form>