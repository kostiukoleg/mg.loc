<?php if (!empty($data)): ?>
    <div class="apply-filter-line">
        <form class="apply-filter-form" data-print-res="<?php echo MG::getSetting('printFilterResult') ?>">
            <ul class="filter-tags">
                <?php foreach ($data as $property) {
                    $cellCount = 0;
                    ?>
                    <li class="apply-filter-item">
                        <span class="filter-property-name">
                            <?php echo $property['name'] . ": ";?>
                        </span>

                        <?php if ($property['values'][0] == "slider") {
                            ?>
                            <span class="filter-price-range">
                                <?php echo "от " . $property['values'][1] . " до " . $property['values'][2]; ?>
                                <a href="javascript:void(0);" class="removeFilter">&#10006;</a>
                            </span>

                            <?php if ($property['code'] != "price_course"): ?>
                                <input name="<?php echo $property['code'] . "[" . $cellCount . "]" ?>"
                                       value="<?php echo $property['values'][0] ?>" type="hidden"/>
                                <?php $cellCount++;?>
                            <?php endif; ?>

                            <input name="<?php echo $property['code'] . "[" . $cellCount . "]" ?>"
                                   value="<?php echo $property['values'][1] ?>" type="hidden"/>
                            <input name="<?php echo $property['code'] . "[" . ($cellCount + 1) . "]" ?>"
                                   value="<?php echo $property['values'][2] ?>" type="hidden"/>
                        <?php } else {?>
                            <ul class="filter-values">
                            <?php foreach ($property['values'] as $cell => $value) {
                                ?>
                                <li class="apply-filter-item-value">
                                     <?php echo $value; ?>
                                    <a href="javascript:void(0);" class="removeFilter">&#10006;</a>
                                      <input name="<?php echo $property['code'] . "[" . $cell . "]" ?>"
                                             value="<?php echo $property['values'][$cell] ?>" type="hidden"/>
                                </li>
                                <?php } ?>
                            </ul>
                        <?php } ?>

                    </li>
                <?php } ?>
            </ul>
          <a href="<?php echo SITE.URL::getClearUri()?>" class="refreshFilter">Cбросить все</a>
            <input type="hidden" name="applyFilter" value="1"/>
        </form>
    </div>
<?php endif; ?>