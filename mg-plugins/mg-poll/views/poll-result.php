<?php if (!$data['ajax']): ?>
  <div class="mg-poll-container">
    <div class="mg-poll-header">
    <?php echo $data['question'] ?>
    </div>
<?php endif; ?>
    <div class="mg-poll-body">
      <ul class="mg-poll-variants">
        <?php foreach ($data['answers'] as $item): 
          $percent = round(100/$data['votes']*$item['votes'], 1);
          ?>
          <li>
              <div class="mg-poll-title">
                  <?php echo $item['answer'] ?>
              </div>
              <div class="mg-poll-progress">
                  <div class="mg-poll-progress-bar" style="width: <?php echo $percent;?>%;"></div>
              </div>
              <span class="mg-poll-total"><?php echo $item['votes'] ?> (<?php echo $percent;?>%)</span>
              <div class="clear"></div>
          </li>
        <?php endforeach; ?>
      </ul>
      <div class="mg-poll-total bottom-total">
        Всего проголосовало: <?php echo $data['votes'] ?>
      </div>
    </div>
<?php if (!$data['ajax']): ?>
  </div>
<?php endif; ?>