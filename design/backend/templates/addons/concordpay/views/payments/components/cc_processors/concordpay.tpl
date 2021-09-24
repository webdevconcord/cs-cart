{include file="common/subheader.tpl" title=__("concordpay.settings.connection") target="#concordpay_connection"}
<div id="concordpay_connection">
  <div class="control-group">
    <label class="control-label cm-required" for="concordpay_merchantAccount">
        {__("concordpay.merchantAccount")}:</label>
    <div class="controls">
      <input type="text" name="payment_data[processor_params][merchantAccount]" id="concordpay_merchantAccount"
             value="{$processor_params.merchantAccount}" size="60">
    </div>
  </div>

  <div class="control-group">
    <label class="control-label cm-required" for="concordpay_merchantSecretKey">
        {__("concordpay.merchantSecretKey")}:</label>
    <div class="controls">
      <input type="text" name="payment_data[processor_params][merchantSecretKey]"
             id="concordpay_merchantSecretKey" value="{$processor_params.merchantSecretKey}" size="60">
    </div>
  </div>
</div>

{include file="common/subheader.tpl" title=__("concordpay.settings.transaction") target="#concordpay_transaction"}
<div id="concordpay_transaction">
  <div class="control-group">
    <label class="control-label cm-required" for="concordpay_currency">{__("concordpay.currency")}:</label>
    <div class="controls">
        {assign var="currencies" value=""|fn_concordpay_get_currencies}
      <select name="payment_data[processor_params][currency]" id="concordpay_currency">
          {foreach from=$currencies item="c" key="k"}
            <option value="{$k}"{if $processor_params.currency == $k} selected="selected"{/if}>{$c.description}</option>
          {/foreach}
      </select>
    </div>
  </div>
</div>

{include file="common/subheader.tpl" title=__("concordpay.settings.order_statuses") target="#concordpay_order_statuses"}
<div id="concordpay_order_statuses">
    {assign var="statuses" value=$smarty.const.STATUSES_ORDER|fn_get_simple_statuses}
  <div class="control-group">
    <label class="control-label" for="concordpay_order_Created">{__("concordpay.order_status.Created")}:</label>
    <div class="controls">
      <select name="payment_data[processor_params][order_status][Created]" id="concordpay_order_Created">
          {foreach from=$statuses item="s" key="k"}
            <option value="{$k}"{if $processor_params.order_status.Created == $k || !$processor_params.order_status.Created && $k == 'B'} selected="selected"{/if}>{$s}</option>
          {/foreach}
      </select>
      <p class="muted description">{__("ttc_concordpay.order_status.Created")}</p>
    </div>
  </div>

  <div class="control-group">
    <label class="control-label" for="concordpay_order_InProcessing">{__("concordpay.order_status.InProcessing")}
      :</label>
    <div class="controls">
      <select name="payment_data[processor_params][order_status][InProcessing]"
              id="concordpay_order_InProcessing">
          {foreach from=$statuses item="s" key="k"}
            <option value="{$k}"{if $processor_params.order_status.InProcessing == $k || !$processor_params.order_status.InProcessing && $k == 'O'} selected="selected"{/if}>{$s}</option>
          {/foreach}
      </select>
      <p class="muted description">{__("ttc_concordpay.order_status.InProcessing")}</p>
    </div>
  </div>

  <div class="control-group">
    <label class="control-label" for="concordpay_order_Approved">{__("concordpay.order_status.Approved")}:</label>
    <div class="controls">
      <select name="payment_data[processor_params][order_status][Approved]" id="concordpay_order_Approved">
          {foreach from=$statuses item="s" key="k"}
            <option value="{$k}"{if $processor_params.order_status.Approved == $k || !$processor_params.order_status.Approved && $k == 'P'} selected="selected"{/if}>{$s}</option>
          {/foreach}
      </select>
      <p class="muted description">{__("ttc_concordpay.order_status.Approved")}</p>
    </div>
  </div>

  <div class="control-group">
    <label class="control-label" for="concordpay_order_Pending">{__("concordpay.order_status.Pending")}:</label>
    <div class="controls">
      <select name="payment_data[processor_params][order_status][Pending]" id="concordpay_order_Pending">
          {foreach from=$statuses item="s" key="k"}
            <option value="{$k}"{if $processor_params.order_status.Pending == $k || !$processor_params.order_status.Pending && $k == 'O'} selected="selected"{/if}>{$s}</option>
          {/foreach}
      </select>
      <p class="muted description">{__("ttc_concordpay.order_status.Pending")}</p>
    </div>
  </div>

  <div class="control-group">
    <label class="control-label" for="concordpay_order_Expired">{__("concordpay.order_status.Expired")}:</label>
    <div class="controls">
      <select name="payment_data[processor_params][order_status][Expired]" id="concordpay_order_Expired">
          {foreach from=$statuses item="s" key="k"}
            <option value="{$k}"{if $processor_params.order_status.Expired == $k || !$processor_params.order_status.Expired && $k == 'F'} selected="selected"{/if}>{$s}</option>
          {/foreach}
      </select>
      <p class="muted description">{__("ttc_concordpay.order_status.Expired")}</p>
    </div>
  </div>

  <div class="control-group">
    <label class="control-label"
           for="concordpay_order_RefundedVoided">{__("concordpay.order_status.RefundedVoided")}:</label>
    <div class="controls">
      <select name="payment_data[processor_params][order_status][RefundedVoided]"
              id="concordpay_order_RefundedVoided">
          {foreach from=$statuses item="s" key="k"}
            <option value="{$k}"{if $processor_params.order_status.RefundedVoided == $k || !$processor_params.order_status.RefundedVoided && $k == 'E'} selected="selected"{/if}>{$s}</option>
          {/foreach}
      </select>
      <p class="muted description">{__("ttc_concordpay.order_status.RefundedVoided")}</p>
    </div>
  </div>

  <div class="control-group">
    <label class="control-label" for="concordpay_order_Declined">{__("concordpay.order_status.Declined")}:</label>
    <div class="controls">
      <select name="payment_data[processor_params][order_status][Declined]" id="concordpay_order_Declined">
          {foreach from=$statuses item="s" key="k"}
            <option value="{$k}"{if $processor_params.order_status.Declined == $k || !$processor_params.order_status.Declined && $k == 'D'} selected="selected"{/if}>{$s}</option>
          {/foreach}
      </select>
      <p class="muted description">{__("concordpay.order_status.Declined")}</p>
    </div>
  </div>

  <div class="control-group">
    <label class="control-label"
           for="concordpay_order_RefundInProcessing">{__("concordpay.order_status.RefundInProcessing")}:</label>
    <div class="controls">
      <select name="payment_data[processor_params][order_status][RefundInProcessing]"
              id="concordpay_order_RefundInProcessing">
          {foreach from=$statuses item="s" key="k"}
            <option value="{$k}"{if $processor_params.order_status.RefundInProcessing == $k || !$processor_params.order_status.RefundInProcessing && $k == 'E'} selected="selected"{/if}>{$s}</option>
          {/foreach}
      </select>
      <p class="muted description">{__("ttc_concordpay.order_status.RefundInProcessing")}</p>
    </div>
  </div>
</div>