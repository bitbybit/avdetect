<?php if ( ! defined('BASEPATH')) exit('No direct script access allowed');

class MY_Form_validation extends CI_Form_validation {

  public function __construct()
  {
    parent::__construct();
  }

  protected function check_url($v)
  {
    $CI =& get_instance();
    if(strpos($v,str_replace(array('http://','https://'),'',$CI->config->item('base_url')))) {
      return FALSE;
    }
    else {
      if(filter_var(_make_clean_url($v),FILTER_VALIDATE_URL) === FALSE) {
        return FALSE;
      }
      else {
        return TRUE;
      }
    }
  }

  protected function check_captcha($v){
    $CI =& get_instance();
    if($CI->session->userdata['captcha'] && $v === $CI->session->userdata['captcha']) {
      return TRUE;
    }
    else {
      return FALSE;
    }
  }

}
