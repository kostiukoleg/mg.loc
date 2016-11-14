<?php $remInfo =  false; 
$style = 'style="display:none;"';
if (MG::getSetting('printRemInfo') == "true") {
        $message = 'Здравствуйте, меня интересует товар "'.str_replace("'", "&quot;", $data['title']).'" с артикулом "'.$data['code'].'", но его нет в наличии.
        Сообщите, пожалуйста, о поступлении этого товара на склад. ';
        if($data['count'] == '0'){
          $style = 'style="display:block;"';        
        }
        $remInfo = $data['remInfo'] !='false' ? true : false;
      }?>
<span class="count"> 
<?php if ($data['count'] == 'много' || $data['count'] == -1) : ?>
  <span> <span itemprop="availability" class="count"><span class="sign">&#10004;</span> Много</span> </span> 
<?php elseif ($data['count']!=0): ?>
  <span>  В наличии: <span itemprop="availability" class="label-black count"><?php echo $data['count'] ?></span> шт. </span>
<?php else : ?>
 <span>  Нет в наличии</span>
  <?php endif;?>
</span>
<?php 
if ($remInfo && MG::get('controller')=="controllers_product"): ?>
 <noindex>
     <span class='rem-info' <?php echo $style ?>>
         Товара временно нет на складе!<br/><a rel='nofollow' href='<?php echo SITE."/feedback?message=".$message?>'>
             Сообщить когда будет в наличии.</a>
     </span>
 </noindex>
<?php endif; 

