<? if ( ! defined('BASEPATH')) exit('No direct script access allowed');

class worker extends CI_Controller {

  public function __construct()
  {
    parent::__construct();
  }

/*
|--------------------------------------------------------------------------
| index
|--------------------------------------------------------------------------
*/
  public function index()
  {
    /*if($this->input->is_cli_request()) {
      return FALSE;
    }*/
    if($this->input->is_cli_request() && !isset($_SERVER['REMOTE_ADDR'])) {
      ignore_user_abort(true);
      set_time_limit(0);

      $this->load->helper('virus');

      define('LOCK_FILE',$this->config->item('lockdir').'worker.lock');
      if(!_trylock()) {
        die();
      }
      register_shutdown_function('unlink',LOCK_FILE);

      $this->load->model('home_model');

      /*$virustotal_requests = 0;*/

      $get_queue = $this->home_model->_get_queue();
      if($get_queue) {
        $this->load->helper('simple_html_dom');
        $this->load->library(array(
          'punycode',
          'email',
        ));
        /*$this->load->library('virustotal',array(
          'key' => $this->config->item('virustotal_key'),
        ));*/

        foreach($get_queue->result() as $queue) {
          if(isset($save_result)) {
            unset($save_result);
          }
          if(!$queue->scanned) {
            if(!_is_url_exist($queue->url)) {
              $get_domain = split_url($queue->url);
              if(isset($get_domain['host'])) {
                $this->home_model->_delete_from_queue(array(
                  'id' => $queue->id,
                  'host' => $get_domain['host'],
                ));
              }
              else {
                $this->home_model->_delete_from_queue(array(
                  'id' => $queue->id,
                ));
              }
              unset($get_domain);
              continue;
            }
            $get_domain = split_url($queue->url);
            if(isset($get_domain['host'])) {
              $get_domain_host = $get_domain['host'];
              $get_domain = $this->home_model->_get_result_domain(array(
                'domain' => $get_domain_host,
              ));
              if($get_domain) {
                $get_domain_row = $get_domain->row();
                if(date_diff(date_create($get_domain_row->date),date_create(date('Y-m-d H:i:s',time())))->days > 0) {
                  $get_domain_data = _avdetect($get_domain_host,'domain');
                  usleep(500000);
                  if($get_domain_data === FALSE) {
                    $this->home_model->_delete_from_queue(array(
                      'id' => $queue->id,
                      'host' => $get_domain_host,
                    ));
                    /*unset($get_domain,$get_domain_row,$get_domain_data,$get_domain_host);*/
                    $get_domain = NULL;
                    $get_domain_row = NULL;
                    unset($get_domain_data,$get_domain_host);
                    time_nanosleep(0,10000000);
                    continue;
                  }
                  $this->home_model->_save_result_domain(array(
                    'domain' => $get_domain_host,
                    'result' => $get_domain_data,
                  ));
                  /*unset($get_domain_data);*/
                  $get_domain_data = NULL;
                }
              }
              else {
                $get_domain_data = _avdetect($get_domain_host,'domain');
                usleep(500000);
                if($get_domain_data === FALSE) {
                  $this->home_model->_delete_from_queue(array(
                    'id' => $queue->id,
                    'host' => $get_domain_host,
                  ));
                  /*unset($get_domain,$get_domain_data,$get_domain_host);*/
                  $get_domain = NULL;
                  unset($get_domain_data,$get_domain_host);
                  time_nanosleep(0,10000000);
                  continue;
                }
                $this->home_model->_save_result_domain(array(
                  'domain' => $get_domain_host,
                  'result' => $get_domain_data,
                ));
                unset($get_domain_data);
                $get_domain = $this->home_model->_get_result_domain(array(
                  'domain' => $get_domain_host,
                ));
                $get_domain_row = $get_domain->row();
              }
              $get_domain = NULL;
            }
            else {
              unset($get_domain);
              $this->home_model->_delete_from_queue(array(
                'id' => $queue->id,
              ));
              time_nanosleep(0,10000000);
              continue;
            }
            /*if($virustotal_requests < (int)$this->config->item('virustotal_limit')) {*/
              _request_timeout(10);
              /*$report = $this->virustotal->getURLReport($queue->url);
              $virustotal_requests++;
              if($report && is_object($report)) {
                if((int)$report->response_code === 0) {
                  $this->virustotal->scanURL($queue->url);
                  $virustotal_requests++;
                }
                elseif((int)$report->response_code === 1) {
                  if(date_diff(date_create($this->virustotal->getSubmissionDate($report)),date_create(date('Y-m-d H:i:s',time())))->days === 0) {
                    $save_result = $this->home_model->_save_result($queue->id,$report);
                  }
                  else {
                    $this->virustotal->scanURL($queue->url);
                    $virustotal_requests++;
                  }
                }
                else {
                  continue;
                }
              }*/
              $report = _avdetect($queue->url);
              usleep(500000);
              if($report) {
                $save_result = $this->home_model->_save_result(array(
                  'id' => $queue->id,
                  'result' => $report,
                  'domain' => $get_domain_row->id,
                ));
              }
              else {
                if(isset($get_domain_host)) {
                  $this->home_model->_delete_from_queue(array(
                    'id' => $queue->id,
                    'host' => $get_domain_host,
                  ));
                  unset($get_domain_host);
                }
                else {
                  $this->home_model->_delete_from_queue(array(
                    'id' => $queue->id,
                  ));
                }
                /*unset($get_domain_row,$save_result,$report);*/
                $get_domain_row = NULL;
                unset($save_result,$report);
                time_nanosleep(0,10000000);
                continue;
              }
              /*unset($report);*/
              $report = NULL;
              /*if($virustotal_requests >= (int)$this->config->item('virustotal_limit')) {
                die();
              }
            }*/
          }

          $get_queue_js = $this->home_model->_get_queue_js(array(
            'id' => $queue->id,
          ));
          if(!$get_queue_js) {
            $external_js = _parse_external_js($queue->url);
            if($external_js) {
              /*foreach($external_js as $url) {
                $this->home_model->_add_queue_js(array(
                  'parent' => $queue->id,
                  'url' => $url,
                ));
              }*/
              $this->home_model->_add_queue_js(array(
                'urls' => $external_js,
                'parent' => $queue->id,
              ));
              $get_queue_js = $this->home_model->_get_queue_js(array(
                'id' => $queue->id,
              ));
            }
            unset($external_js);
          }
          if($get_queue_js) {
            foreach($get_queue_js->result() as $queue_js) {
              if((int)$queue_js->status === 0) {
                if(!_is_url_exist($queue_js->url)) {
                  $this->home_model->_delete_from_queue_js(array(
                    'id' => $queue_js->id,
                  ));
                  continue;
                }
                /*if($virustotal_requests < (int)$this->config->item('virustotal_limit')) {*/
                  /*$report_js = $this->virustotal->getURLReport($queue_js->url);
                  $virustotal_requests++;
                  if($report_js && is_object($report_js)) {
                    if((int)$report_js->response_code === 0) {
                      $this->virustotal->scanURL($queue_js->url);
                      $virustotal_requests++;
                    }
                    elseif((int)$report_js->response_code === 1) {
                      if(date_diff(date_create($this->virustotal->getSubmissionDate($report_js)),date_create(date('Y-m-d H:i:s',time())))->days === 0) {
                        $this->home_model->_save_result_js($queue_js->id,$report_js);
                      }
                      else {
                        $this->virustotal->scanURL($queue_js->url);
                        $virustotal_requests++;
                      }
                    }
                    else {
                      continue;
                    }
                  }*/
                  $report_js = _avdetect($queue_js->url);
                  usleep(500000);
                  if($report_js) {
                    $this->home_model->_save_result_js(array(
                      'id' => $queue_js->id,
                      'result' => $report_js,
                    ));
                  }
                  else {
                    $this->home_model->_delete_from_queue_js(array(
                      'id' => $queue_js->id,
                    ));
                    unset($report_js);
                    continue;
                  }
                  unset($report_js);
                  /*if($virustotal_requests >= (int)$this->config->item('virustotal_limit')) {
                    die();
                  }
                }*/
              }
            }
            /*unset($get_queue_js);*/
            $get_queue_js = NULL;
          }
          if($queue->scanned || (isset($save_result) && $save_result === TRUE)) {
            $this->home_model->_queue_completed(array(
              'id' => $queue->id,
            ));
            _send_mail_url($queue->email,$queue->hash,$queue->url);
          }
          time_nanosleep(0,10000000);
        }
      }
      /*unset($get_queue);*/
      $get_queue = NULL;
      time_nanosleep(0,10000000);

      $get_old_queue = $this->home_model->_get_old_queue();
      if($get_old_queue) {
        foreach($get_old_queue->result() as $old_queue) {
          $this->home_model->_delete_from_queue(array(
            'id' => $old_queue->id,
            'host' => $old_queue->domain,
          ));
        }
      }
      $get_old_queue = NULL;

      exit(0);
    }
  }

}