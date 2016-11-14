<div class="payment-form-block">

   <form action='https://auth.robokassa.ru/Merchant/Index.aspx' method=POST>
   <input type=hidden name=MrchLogin value=<?php echo $data['paramArray'][0]['value'] ?>>
   <input type=hidden name=OutSum value=<?php echo $data['summ'] ?>>
   <input type=hidden name=InvId value=<?php echo $data['id'] ?>>
   <input type=hidden name=Desc value='Оплата заказа <?php echo $data['orderNumber'] ?>'>
   <input type=hidden name=SignatureValue value=<?php echo $data['paramArray']['sign'] ?>>
   <input type=hidden name=IncCurrLabel value="">
   <input type=hidden name=Culture value="ru">
   <input type=submit value='Оплатить' style="padding: 10px 20px;">

</form>
<p>
 <em>
 Вы можете изменить способ оплаты данного заказа из Вашего личного кабинета в разделе "<a href="<?php echo SITE?>/personal">История заказов</a>".
 <br/>
 Или перечислить деньги на наш счет в системе <b><span style="color:#0077C0" >Robokassa</span></b> логин <b><?php echo $data['paramArray'][0]['value']?></b> указав номер заказа.
 </em>
 </p>
</div>