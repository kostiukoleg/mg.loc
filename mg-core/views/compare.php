<?php
mgSEO($data);
$prodIds = array();
$propTable = array();
?>

<?php mgTitle('Сравнение товаров'); ?>

<?php mgAddMeta('<link type="text/css" href="'.SCRIPT.'standard/css/compare.css" rel="stylesheet"/>'); ?>


<div class="mg-compare-products">
  <h1>Сравнение товаров</h1>
  <?php if($data['error']): ?>
    <div class="alert-info">
      <?php echo $data['error'] ?>
    </div>
  <?php endif; ?>
  <div class="mg-compare-left-side">
    <?php if(!empty($_SESSION['compareList'])){ ?>
      <?php if(MG::getSetting('compareCategory')!='true'){ ?><h2>Показать сравнение из категории:</h2><?php } ?> 
      <div class="mg-category-list-compare">
        <?php if(MG::getSetting('compareCategory')!='true'){ ?>
          <form >
            <select name="viewCategory" onChange="this.form.submit()">
              <?php foreach($data['arrCategoryTitle'] as $id => $value): ?>
                <option value ='<?php echo $id ?>' <?php
                if($_GET['viewCategory']==$id){
                  echo "selected=selected";
                }
                ?> ><?php echo $value ?></option>
                      <?php endforeach; ?>
            </select>
          </form>
        <?php } ?> 
        <a href="<?php echo SITE ?>/compare?delCompare=1" class="mg-clear-compared-products">Очистить весь список сравнений</a>
      </div>
    <?php } ?> 

  </div>

  <div class="mg-compare-center" id="doublescroll">
    <div class="mg-compare-product-wrapper">
      <div class="mg-inner-scroll">
        <?php
        if(!empty($data['catalogItems'])){
          foreach($data['catalogItems'] as $item){
            ?>
            <div class="mg-compare-product">
              <a class="mp-remove-compared-product" href="<?php echo SITE ?>/compare?delCompareProductId=<?php echo $item['id'] ?>"></a>
              <div class="mg-compare-product-inner">
                <h2><a href="<?php echo $item['link'] ?>"><?php echo $item['title'] ?></a></h2>
                <div class="mg-compare-product-image">
                  <a href="<?php echo $item['link'] ?>">
                    <?php echo mgImageProduct($item); ?>
                  </a>
                </div>
                <ul class="mg-compare-product-list product-status-list">
                  <!--если не установлен параметр - старая цена, то не выводим его-->
                  <li <?php echo (!$item['old_price'])?'style="display:none"':'style="display:block"' ?>>
                    Старая цена: <span class="old-price"><?php echo MG::numberFormat($item['old_price'])." ".$item['currency']; ?></span></li>
                  <li>Цена: <span class="price"><?php echo $item['price'] ?> <?php echo $item['currency']; ?></span></li>
                  <li class="count-product-info"><?php layout('count_product', $item); ?>
                  </li>
                  <li <?php echo (!$item['weight'])?'style="display:none"':'style="display:block"' ?>>Вес: <span class="label-black weight"><?php echo $item['weight'] ?></span> кг. </li>
                  <li>Артикул: <span class="label-article code"><?php echo $item['code'] ?></span></li>
                </ul>
                <?php echo $item['propertyForm'] ?>

                <?php
                foreach($item['stringsProperties'] as $key => $val){

                  $propTable[$key][$item['id']] = $val;
                }
                ?>
              </div>
            </div>

            <?php
            $prodIds[] = $item['id'];
          }
        }
        ?>
      </div>
    </div>       
    <?php
    foreach($propTable as $key => $prop){
      foreach($prodIds as $id){
        if(empty($prop[$id])){
          $propTable[$key][$id] = '-';
          ksort($propTable[$key]);
        }
      }
    }
    ?>

    <div class="mg-compare-fake-table-right">
        <?php foreach($propTable as $key => $prop){ ?>
        <div class="mg-compare-fake-table-row">
            <?php foreach($prop as $prodId => $val){ ?>
            <div class="mg-compare-fake-table-cell">
            <?php echo $val ?>

            </div>
        <?php } ?>
        </div>
    <?php } ?>          
    </div>

  </div>
  <div class="mg-compare-fake-table">
    <div class="mg-compare-fake-table-left <?php echo $data['moreThanThree'] ?>">
        <?php foreach($propTable as $key => $prop){ ?>
        <div class="mg-compare-fake-table-cell <?php if(trim($data['property'][$key])!=='') : ?>with-tooltip<?php endif; ?>">
          <?php if(trim($data['property'][$key])!=='') : ?>
            <div class="mg-tooltip">?<div class="mg-tooltip-content" style="display:none;"><?php echo $data['property'][$key] ?></div></div>
            <?php endif; ?>
          <div class="compare-text" title="<?php echo $key ?>">
          <?php echo $key ?>
          </div>

        </div>
        <?php } ?>
    </div>
  </div>
</div>