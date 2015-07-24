  <form action="<?=base_url(); ?>" method="post">
    <input type="hidden" name="<?=$this->security->get_csrf_token_name(); ?>" value="<?=$this->security->get_csrf_hash(); ?>">
    URL: <input type="text" value="<?=$this->input->post('url'); ?>" name="url" maxlength="2083"><br>
    E-mail: <input type="text" value="<?=$this->input->post('email'); ?>" name="email" maxlength="254" placeholder="Необязательное поле"><br>
    <?=$captcha; ?><br>
    Код: <input type="text" name="captcha" maxlength="8">
    <input type="submit">
  </form>
