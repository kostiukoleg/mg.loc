<div class="mg-contacts-block desktop" itemscope itemtype="http://schema.org/Organization">
  <div class="address" itemprop="address" itemscope itemtype="http://schema.org/PostalAddress">
      <span>Адрес:</span>
      <div class="bold" itemprop="streetAddress"><?php echo MG::getSetting('shopAddress') ?></div>
  </div>
  <div class="phone">
      <span>Телефон:</span>
      <div class="bold" itemprop="telephone"><?php echo MG::getSetting('shopPhone') ?></div>
      <?php if (class_exists('BackRing')): ?>
        [back-ring]
      <?php else: ?>
        <div style="height:17px;"> </div>
      <?php endif; ?>
  </div>
</div>
