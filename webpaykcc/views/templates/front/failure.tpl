<div style="text-align: center;">

  <img src='{$logo}' />

  <table style="margin-left: auto; margin-right: auto; text-align: left;">
    <tr>
      <th colspan="2" style="text-align: center;">{l s='Transacción Fallida' mod='webpaykcc'}</th>
    </tr>
    <tr>
      <td>{l s='Número de la Compra' mod='webpaykcc'}:</td>
      <td>{$cart_id}</td>
    </tr>
    <tr>
    	<td colspan="2">
    		{l s='Las posibles causas de este rechazo son' mod='webpaykcc'}:
    		<ul>
    		  <li>{l s='Error en el ingreso de los datos de su tarjeta de crédito o débito (fecha y/o código de seguridad).' mod='webpaykcc'}</li>
				  <li>{l s='Su tarjeta de crédito o débito no cuenta con el cupo necesario para pagar la compra.' mod='webpaykcc'}</li>
				  <li>{l s='Tarjeta aún no habilitada en el sistema financiero.' mod='webpaykcc'}</li>
			   </ul>
    	</td>
    </tr>
  </table>

  </div>