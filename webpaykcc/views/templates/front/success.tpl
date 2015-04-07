<div style="text-align: center;">

  <img src='{$logo}' />
  <h1>{l s='Compra Exitosa' mod='webpaykcc'}</h1>
  <a href='{$toc_page}' target="_blank">{l s='Ver La Política de Devoluciones y/o Reembolsos' mod='webpaykcc'}</a><br/>
  <a href='{$order_history}' target="_blank">{l s='Ver Detalles de la Compra' mod='webpaykcc'}</a>
  <hr />
  <table style="margin-left: auto; margin-right: auto; text-align: left;">
    <tr>
      <th colspan="2" style="text-align: center;">{l s='Datos de la compra' mod='webpaykcc'}</th>
    </tr>
    <tr>
      <td>{l s='Nombre del comercio' mod='webpaykcc'}:</td>
      <td>{$shop_name}</td>
    </tr>
    <tr>
      <td>{l s='URL del comercio' mod='webpaykcc'}:</td>
      <td><a href='{$shop_url}'>{$shop_url}</a></td>
    </tr>
    <tr>
      <td>{l s='Nombre del comprador' mod='webpaykcc'}:</td>
      <td>{$customer_name}</td>
    </tr>
    <tr>
      <td>{l s='Número del Pedido' mod='webpaykcc'}:</td>
      <td>{$tbk_cart_id}</td>
    </tr>
    <tr>
      <td>{l s='Monto (pesos chilenos)' mod='webpaykcc'}:</td>
      <td>${$tbk_amount}</td>
    </tr>
    <tr>
      <th colspan="2" style="text-align: center;">{l s='Datos de la transacción' mod='webpaykcc'}</th>
    </tr>
    <tr>
      <td>{l s='Código de autorización' mod='webpaykcc'}:</td>
      <td>{$tbk_auth_code}</td>
    </tr>

    <tr>
      <td>{l s='Fecha de la transacción' mod='webpaykcc'}:</td>
      <td>{$tbk_transaction_date}</td>
    </tr>
    <tr>
      <td>{l s='Hora de la transacción' mod='webpaykcc'}:</td>
      <td>{$tbk_transaction_time}</td>
    </tr>
    <tr>
      <td>{l s='Número de Tarjeta' mod='webpaykcc'}:</td>
      <td>{$tbk_card_last_digit}</td>
    </tr>
    <tr>
      <td>{l s='Tipo de transacción' mod='webpaykcc'}:</td>
      <td>{$tbk_transaction_type}</td>
    </tr>
    <tr>
      <td>{l s='Tipo de pago' mod='webpaykcc'}:</td>
      <td>{$tbk_payment_type}</td>
    </tr>
    <tr>
      <td>{l s='Número de cuotas' mod='webpaykcc'}:</td>
      <td>{$tbk_installment_quantity}</td>
    </tr>
    <tr>
      <td>{l s='Tipo de cuotas' mod='webpaykcc'}:</td>
      <td>{$tbk_installment_type}</td>
    </tr>
  </table>

  
 </div>
