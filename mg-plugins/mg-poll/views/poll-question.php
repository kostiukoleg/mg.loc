<?php if (!empty($data['answers'])): ?>
  <div class="mg-poll-container">
    <div class="mg-poll-header">
      <?php echo $data['question'] ?>
    </div>
    <form class="mgPollForm">
      <div class="mg-poll-body">
        <input type="hidden" name="question-id" value="<?php echo $data['id'] ?>"/>
        <ul class="mg-poll-variants">
          <?php foreach ($data['answers'] as $id => $item): ?>
            <li>
              <label for="poll-answer-<?php echo $item['id']; ?>">
                <input type="radio" name="poll-answer" id="poll-answer-<?php echo $item['id']; ?>"
                       value="<?php echo $item['id']; ?>"/>
                <?php echo $item['answer'] ?></label>
            </li>
          <?php endforeach; ?>
        </ul>
      </div>
      <div class="mg-poll-footer">
        <button type="submit" class="button-vote" data-id="<?php echo $data['id'] ?>">Голосовать</button>
      </div>
    </form>
  </div>
<?php endif; ?>