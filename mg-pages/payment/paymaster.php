<div class="payment-form-block">
<form id="pay" name="pay" method="POST" action="https://paymaster.ru/Payment/Init">
<input type="hidden" name="LMI_MERCHANT_ID" value="<?php echo $data['paramArray'][0]['value']?>">
<input type="hidden" name="LMI_PAYMENT_AMOUNT" value="<?php echo $data['summ']?>">
<input type="hidden" name="LMI_CURRENCY" value="<?php echo (MG::getSetting('currencyShopIso')=="RUR")?"RUB":MG::getSetting('currencyShopIso');?>">
<input type="hidden" name="LMI_PAYMENT_NO" value="<?php echo $data['id']?>">
<input type="hidden" name="LMI_PAYMENT_DESC" value="Oplata zakaza # <?php echo $data['orderNumber']?>">
  
   <input type="submit" class="btn" value="Оплатить" style="padding: 10px 20px;">
  
 </form>
 <p>
 <em>
 Вы можете изменить способ оплаты данного заказа из Вашего личного кабинета в разделе "<a href="<?php echo SITE?>/personal">История заказов</a>".

 </em>
 </p>

</div>