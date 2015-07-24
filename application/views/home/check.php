  Дата проверки: <b><?=$date; ?></b><br>
  <form action="<?=base_url(); ?>recheck/<?=$hash; ?>" method="post">
    <input type="hidden" name="<?=$this->security->get_csrf_token_name(); ?>" value="<?=$this->security->get_csrf_hash(); ?>">
    E-mail: <input type="text" value="<?=$this->input->post('email'); ?>" name="email" maxlength="254" placeholder="Необязательное поле">
    <?=$captcha; ?>
    Код: <input type="text" name="captcha" maxlength="8">
    <input type="submit" value="Проверить ещё раз">
  </form>
  <hr>
  URL: <b><?=$url; ?></b><br>
<?php
  $avdetect_result = array();
  foreach($avdetect as $key => $value) {
    if($value === 'OK') {
      continue;
    }
    $beautify_av_title = _beautify_av_title($key);
    $avdetect_result[] = ((is_array($beautify_av_title)) ? '<img src="//s2.googleusercontent.com/s2/favicons?domain='.$beautify_av_title['domain'].'" width="16" height="16" alt="'.$beautify_av_title['title'].'"> <a href="//'.((isset($beautify_av_title['url'])) ? $beautify_av_title['url'].$url : $beautify_av_title['domain']).'">'.$beautify_av_title['title'].'</a>' : $beautify_av_title).': '.$value;
  }

  $davdetect_result = array();
  foreach($davdetect as $key => $value) {
    if($value === 'OK') {
      continue;
    }
    $beautify_av_title = _beautify_av_title($key);
    $davdetect_result[] = ((is_array($beautify_av_title)) ? '<img src="//s2.googleusercontent.com/s2/favicons?domain='.$beautify_av_title['domain'].'" width="16" height="16" alt="'.$beautify_av_title['title'].'"> <a href="//'.((isset($beautify_av_title['url'])) ? $beautify_av_title['url'].'http://'.$domain : $beautify_av_title['domain']).'">'.$beautify_av_title['title'].'</a>' : $beautify_av_title).': '.$value;
  }

  if(isset($js) && is_array($js) && !empty($js)) {
    $js_result = array();
    foreach($js as $result) {
      $result_avdetect_result = array();
      foreach($result['avdetect'] as $key => $value) {
        if($value === 'OK') {
          continue;
        }
        $beautify_av_title = _beautify_av_title($key);
        $result_avdetect_result[] = ((is_array($beautify_av_title)) ? '<img src="//s2.googleusercontent.com/s2/favicons?domain='.$beautify_av_title['domain'].'" width="16" height="16" alt="'.$beautify_av_title['title'].'"> <a href="//'.((isset($beautify_av_title['url'])) ? $beautify_av_title['url'].$result['url'] : $beautify_av_title['domain']).'">'.$beautify_av_title['title'].'</a>' : $beautify_av_title).': '.$value;
      }
      if(!empty($result_avdetect_result)) {
        $js_result[] = array(
          'url' => $result['url'],
          'result' => $result_avdetect_result,
        );
        $js_result_malware = 1;
      }
      else {
        $js_result[] = array(
          'url' => $result['url'],
          'result' => array(),
        );
      }
    }
  }

  if(empty($avdetect_result) && empty($davdetect_result) && !isset($js_result_malware)) {
    echo "  Угроз не обнаружено.\n";
  }
  elseif(!empty($avdetect_result)) {
    foreach($avdetect_result as $value) {
      echo '  '.$value."<br>\n";
    }
  }
  if(!empty($davdetect_result)) {
    echo "  <hr>\n";
    echo '  Информация о домене: <b>'.$domain."</b><br>\n";
    foreach($davdetect_result as $value) {
      echo '  '.$value."<br>\n";
    }
  }
  if(isset($js_result) && !empty($js_result)) {
    echo "  <hr>\n";
    echo "  JavaScript: <br>\n";
    foreach($js_result as $value) {
      echo '  <a href="'.$value['url'].'">'.$value['url']."</a><br>\n";
      if(isset($value['result']) && !empty($value['result'])) {
        foreach($value['result'] as $value_result) {
          echo '  '.$value_result."<br>\n";
        }
      }
      else {
        echo "  <em>Вредоносный код не найден</em><br>\n";
      }
    }
  }
