<img src="{$img_header}"/>

<h2>{l s='Payment using Webpay KCC' mod='webpaykcc'}</h2>

<fieldset>
    <legend><img src="../img/admin/warning.gif"/>{l s='Information' mod='webpaykcc'}</legend>
    <div class="margin-form">{l s='Module version' mod='webpaykcc'}: {$version}</div>
    <div class="margin-form">{l s='Base Path' mod='webpaykcc'}: {$base_path}</div>
    <div class="margin-form">{l s='Validation Page' mod='webpaykcc'} (HTML_TR_NORMAL): {$validation_url}</div>
</fieldset>

<form action="{$post_url}" method="post" style="clear: both; margin-top: 10px;">
    <fieldset>
        <legend><img src="../img/admin/contact.gif"/>{l s='Settings' mod='webpaykcc'}</legend>
        
        {if isset($errors.kccPath)}
            <div class="error">
                <p>{$errors.kccPath}</p>
            </div>
        {/if}



        <label for="kccPath">{l s='KCC Path' mod='webpaykcc'}</label>

        <div class="margin-form"><input type="text" size="33" id="kccPath" name="kccPath"
                                        value="{$data_kccPath}" placeholder="{$cgi_path}"/></div>
        
        {if isset($errors.kccURL)}
            <div class="error">
                <p>{$errors.kccURL}</p>
            </div>
        {/if}


        <label for="kccURL">{l s='KCC URL' mod='webpaykcc'}</label>

        <div class="margin-form"><input type="text" size="33" name="kccURL"
                                        id="kccURL" value="{$data_kccURL}" placeholder="{$cgi_url}"/></div>

        {if isset($errors.kccLogPath)}
            <div class="error">
                <p>{$errors.kccLogPath}</p>
            </div>
        {/if}

        <label for="kccLogPath">{l s='KCC Log Path' mod='webpaykcc'}</label>

        <div class="margin-form">
            <input type="text" size="33" name="kccLogPath" id="kccLogPath" value="{$data_kccLogPath}" placeholder="{$log_path}"/>
        </div>


        {if isset($errors.kccTocPage)}
            <div class="error">
                <p>{$errors.kccTocPage}</p>
            </div>
        {/if}

        <label for="kccTocPage">{l s='Terms and Conditions Page URL' mod='webpaykcc'}</label>

        <div class="margin-form">
            <input type="text" size="33" name="kccTocPage" id="kccTocPage" value="{$data_kccTocPage}" placeholder="{$toc_url}"/>
        </div>


        <center><input type="submit" name="webpaykcc_updateSettings" value="{l s='Save Settings' mod='webpaykcc'}"
                       class="button" style="cursor: pointer; display:"/></center>
    </fieldset>
</form>