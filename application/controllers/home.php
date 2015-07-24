<?php if ( ! defined('BASEPATH')) exit('No direct script access allowed');

class home extends CI_Controller {

  public function __construct()
  {
    parent::__construct();
    $this->load->helper('virus');
  }

/*
|--------------------------------------------------------------------------
| index
|--------------------------------------------------------------------------
*/
  public function index()
  {
    $this->load->helper(array(
      'security',
      'form',
      'captcha',
    ));
    $this->load->library(array(
      'form_validation',
      'punycode',
      'email',
      'session',
    ));

    $data = array(
      'title' => 'Проверка URL',
      'error' => '',
      'output' => '',
    );

    $this->form_validation->set_rules(array(
      array(
        'field' => 'url',
        'label' => 'URL',
        'rules' => 'required|trim|xss_clean|max_length[2083]|check_url',
      ),
      array(
        'field' => 'email',
        'label' => 'E-mail',
        'rules' => 'trim|xss_clean|max_length[254]|valid_email',
      ),
      array(
        'field' => 'captcha',
        'label' => 'Проверочный код',
        'rules' => 'required|trim|xss_clean|max_length[8]|check_captcha',
      ),
    ));

    if($this->form_validation->run() == FALSE) {
      $data['output'] = $this->load->view('home/home',array(
        'captcha' => _captcha_activate(),
      ),TRUE);
    }

    else {
      _captcha_destroy();
      $this->load->model('home_model');
      $cleaned_url = _make_clean_url($this->input->post('url'));
      $cleaned_url_hash = md5($cleaned_url.$this->config->item('secret_url'));

      $already_checked = $this->home_model->_already_checked(array(
        'url' => $cleaned_url,
      ));
      if($already_checked) {
        if($this->session->flashdata('last_check') && $this->session->flashdata('last_check') === $cleaned_url) {
          $this->session->keep_flashdata('last_check');
        }
        else {
          if($this->input->post('email')) {
            _send_mail_url($this->input->post('email'),$cleaned_url_hash,$cleaned_url);
            $this->session->set_flashdata('last_check',$cleaned_url);
          }
        }
        redirect('check/'.$cleaned_url_hash);
        /*if(is_object($already_checked)) {
          $already_checked_row = $already_checked->row();
          $data['title'] .= ': '.$cleaned_url;
          $data['error'] = 'Ссылка на результаты проверки будет выслана на e-mail: '._mask_email($this->input->post('email'));
          if($this->session->flashdata('last_check') && $this->session->flashdata('last_check') === $cleaned_url) {
            $this->session->keep_flashdata('last_check');
          }
          else {
            _send_mail_url($this->input->post('email'),$cleaned_url_hash,$cleaned_url);
            $this->session->set_flashdata('last_check',$cleaned_url);
          }
          $already_checked = NULL;
          $already_checked_row = NULL;
        }
        else {
          $data['error'] = 'Ошибка отправки данных. (#1)';
        }*/
      }

      else {
        if(!_is_url_exist($cleaned_url)) {
          $data['error'] = 'URL недоступен, не существует, либо слишком большой объём данных. (#2)';
          $data['output'] = $this->load->view('home/home',array(
            'captcha' => _captcha_activate(),
          ),TRUE);
        }

        else {
          $add_queue = $this->home_model->_add_queue(array(
            'url' => $cleaned_url,
            'ip' => $this->input->ip_address(),
            'email' => (($this->input->post('email')) ? $this->input->post('email') : ''),
          ));
          $add_queue = NULL;
          $this->session->set_flashdata('last_check',$cleaned_url);
          redirect('check/'.$cleaned_url_hash);
          /*$result = $this->home_model->_get_result(array(
            'hash' => $cleaned_url_hash,
          ));
          if($result) {
            $result_row = $result->row();
            $rumonth = $this->config->item('rumonth');
            if(isset($result_row->domain)) {
              $data['title'] .= ': '.$cleaned_url;
              $data['error'] = 'Ссылка на результаты проверки будет выслана на e-mail: '._mask_email($this->input->post('email'));
              $this->session->set_flashdata('last_check',$cleaned_url);
            }
            else {
              foreach($result->result() as $row) {
                if($row->hash === $cleaned_url_hash) {
                  $data['title'] .= ': '.$row->url;
                  $data['error'] = $row->url." в очереди на проверку (".$row->qorder." из ".$result->num_rows().").<br>\n".
                                   "Добавлен в базу ".date("j ".$rumonth[date("n",strtotime($row->date))]." Y г. в H:i",strtotime($row->date))." (прошло "._relative_time($row->date).").<br>\n".
                                   "Ссылка на результаты проверки будет выслана на e-mail: "._mask_email($row->email);
                  $this->session->set_flashdata('last_check',$row->url);
                  break;
                }
              }
              if(empty($data['error'])) {
                $data['error'] = 'Ошибка отправки данных. (#3)';
              }
            }
            $result_row = NULL;
          }
          else {
            $data['error'] = 'Ошибка отправки данных. (#4)';
            $data['output'] = $this->load->view('home/home','',TRUE);
          }
          $result = NULL;*/
        }
      }

      unset($cleaned_url,$cleaned_url_hash,$already_checked);
    }

    _no_cache();
    $this->load->view('home/index',array(
      'auto_version_js' => _auto_version('js/app.js'),
      'auto_version_css' => _auto_version('css/app.css'),
      'title' => $data['title'],
      'output' => $data['output'],
      'error' => $data['error'],
    ));
  }

/*
|--------------------------------------------------------------------------
| check
|--------------------------------------------------------------------------
*/
  public function check($hash)
  {
    $this->load->model('home_model');
    $this->load->helper(array(
      'security',
      'captcha',
    ));
    $this->load->library('session');

    $data = array(
      'title' => 'Проверка URL',
      'error' => '',
      'output' => '',
    );

    $result = $this->home_model->_get_result(array(
      'hash' => $hash,
    ));
    if($result) {
      $result_row = $result->row();
      $rumonth = $this->config->item('rumonth');
      if(isset($result_row->avdetect)) {
        $result_js = array();
        if($result_row->jsurl && $result_row->jsavdetect) {
          foreach($result->result() as $row) {
            $row->jsavdetect = unserialize($row->jsavdetect);
            $row->jsavdetect->result = (array)$row->jsavdetect->result;
            ksort($row->jsavdetect->result);
            $result_js[] = array(
              'url' => $row->jsurl,
              'avdetect' => $row->jsavdetect->result,
            );
          }
        }
        $result_row->avdetect = unserialize($result_row->avdetect);
        $result_row->davdetect = unserialize($result_row->davdetect);
        $result_row->avdetect->result = (array)$result_row->avdetect->result;
        $result_row->davdetect->result = (array)$result_row->davdetect->result;
        ksort($result_row->avdetect->result);
        ksort($result_row->davdetect->result);
        $data['title'] .= ': '.$result_row->url;
        $data['output'] = $this->load->view('home/check',array(
          'url' => $result_row->url,
          'date' => date('j '.$rumonth[date('n',strtotime($result_row->date))].' Y г. в H:i',strtotime($result_row->date)).' (UTC)',
          'avdetect' => $result_row->avdetect->result,
          'domain' => $result_row->domain,
          'davdetect' => $result_row->davdetect->result,
          'js' => $result_js,
          'hash' => $hash,
          'captcha' => _captcha_activate(),
        ),TRUE);
        unset($result_js);
      }
      else {
        foreach($result->result() as $row) {
          if($row->hash === $hash) {
            $data['title'] .= ': '.$row->url;
            $data['error'] = $row->url." в очереди на проверку (".$row->qorder." из ".$result->num_rows().").<br>\n".
                             'Добавлен в базу '.date('j '.$rumonth[date('n',strtotime($row->date))].' Y г. в H:i',strtotime($row->date)).' (прошло '._relative_time($row->date).').'.
                             ((isset($row->email) && !empty($row->email)) ? "<br>\nУведомление об окончании проверки будет выслано на "._mask_email($row->email) : '');
            break;
          }
        }
        if(empty($data['error'])) {
          $data['error'] = 'URL не существует в базе. (#5)';
        }
        _captcha_destroy();
      }
      $result_row = NULL;
    }
    else {
      $data['error'] = 'URL не существует в базе. (#6)';
      _captcha_destroy();
    }
    /*unset($result);*/
    $result = NULL;

    _no_cache();
    $this->load->view('home/index',array(
      'auto_version_js' => _auto_version('js/app.js'),
      'auto_version_css' => _auto_version('css/app.css'),
      'title' => $data['title'],
      'output' => $data['output'],
      'error' => $data['error'],
    ));
  }

/*
|--------------------------------------------------------------------------
| recheck
|--------------------------------------------------------------------------
*/
  public function recheck($hash)
  {
    $this->load->helper(array(
      'security',
      'form',
      'captcha',
    ));
    $this->load->library(array(
      'form_validation',
      'session',
    ));

    $data = array(
      'title' => 'Повторная проверка URL',
      'error' => '',
      'output' => '',
    );

    $this->form_validation->set_rules(array(
      array(
        'field' => 'email',
        'label' => 'E-mail',
        'rules' => 'trim|xss_clean|max_length[254]|valid_email',
      ),
      array(
        'field' => 'captcha',
        'label' => 'Проверочный код',
        'rules' => 'required|trim|xss_clean|max_length[8]|check_captcha',
      ),
    ));

    if($this->form_validation->run() === TRUE) {
      _captcha_destroy();
      $this->load->model('home_model');
      $already_checked = $this->home_model->_already_checked(array(
        'hash' => $hash,
      ));
      if($already_checked) {
        $already_checked_row = $already_checked->row();
        $data['title'] .= ': '.$already_checked_row->url;
        if(date_diff(date_create($already_checked_row->date),date_create(date('Y-m-d H:i:s',time())))->days > 0) {
          $this->home_model->_delete_from_queue(array(
            'id' => $already_checked_row->id,
            'host' => $already_checked_row->domain,
          ));
          $this->home_model->_add_queue(array(
            'url' => $already_checked_row->url,
            'ip' => $this->input->ip_address(),
            'email' => (($this->input->post('email')) ? $this->input->post('email') : ''),
          ));
          redirect('check/'.$hash);
        }
        else {
          $data['error'] = 'Минимальный интервал между проверками 1 день. (#7)';
        }
        $already_checked_row = NULL;
      }
      else {
        $data['error'] = 'URL не существует в базе. (#8)';
      }
      $already_checked = NULL;
    }
    else {
      $data['error'] = 'Ошибка отправки данных. (#9)';
    }

    _no_cache();
    $this->load->view('home/index',array(
      'auto_version_js' => _auto_version('js/app.js'),
      'auto_version_css' => _auto_version('css/app.css'),
      'title' => $data['title'],
      'output' => $data['output'],
      'error' => $data['error'],
    ));
  }

}
