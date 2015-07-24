<?php if ( ! defined('BASEPATH')) exit('No direct script access allowed');

class home_model extends CI_Model {

  public function __construct()
  {
    parent::__construct();
    $this->db->query("SET time_zone='+0:00'");
  }

/*
|--------------------------------------------------------------------------
| already_checked
|--------------------------------------------------------------------------
*/
  function _already_checked($data)
  {
    if(!isset($data) || !is_array($data)) {
      return TRUE;
    }
    else {
      if(isset($data['url'])) {
        $query = $this->db->get_where($this->config->item('table_queue'),array(
          'hash' => md5($data['url'].$this->config->item('secret_url')),
        ));
      }
      elseif(isset($data['hash'])) {
        $this->db->select($this->config->item('table_queue').'.id as id,'.
                          $this->config->item('table_queue').'.url as url,'.
                          $this->config->item('table_queue').'.date as date,'.
                          $this->config->item('table_result_domain').'.domain as domain');
        $this->db->from($this->config->item('table_queue'));
        $this->db->join($this->config->item('table_result'),$this->config->item('table_result').'.url = '.$this->config->item('table_queue').'.id','left');
        $this->db->join($this->config->item('table_result_domain'),$this->config->item('table_result_domain').'.id = '.$this->config->item('table_result').'.domain','left');
        $this->db->where($this->config->item('table_queue').'.hash',$data['hash']);
        $query = $this->db->get();
      }
      if($query->num_rows() > 0) {
        return $query;
      }
      else {
        $query->free_result();
        unset($query);
        return FALSE;
      }
    }
  }

/*
|--------------------------------------------------------------------------
| add_queue
|--------------------------------------------------------------------------
*/
  function _add_queue($data)
  {
    if(!isset($data) || !is_array($data)) {
      return FALSE;
    }
    else {
      $query = $this->db->get_where($this->config->item('table_queue'),array(
        'hash' => md5($data['url'].$this->config->item('secret_url')),
      ));
      if($query->num_rows() > 0) {
        $query_row = $query->row();
        $table_queue_js = $this->db->get_where($this->config->item('table_queue_js'),array(
          'parent' => $query_row->id,
        ));
        if($table_queue_js->num_rows() > 0) {
          foreach($table_queue_js->result() as $table_queue_js_result) {
            $this->db->delete($this->config->item('table_result_js'),array(
              'url' => $table_queue_js_result->id,
            ));
          }
          $this->db->delete($this->config->item('table_queue_js'),array(
            'parent' => $query_row->id,
          ));
        }
        $table_queue_js->free_result();
        unset($table_queue_js);
        $this->db->set('ipv4','INET_ATON("'.$data['ip'].'")',FALSE);
        $this->db->update($this->config->item('table_queue'),array(
          'status' => '0',
          'email' => $data['email'],
        ),array(
          'id' => $query_row->id,
        ));
        $query->free_result();
        unset($query,$query_row);
        return TRUE;
      }
      else {
        $this->db->set('ipv4','INET_ATON("'.$data['ip'].'")',FALSE);
        $this->db->insert($this->config->item('table_queue'),array(
          'url' => $data['url'],
          'hash' => md5($data['url'].$this->config->item('secret_url')),
          'email' => $data['email'],
        ));
        $query->free_result();
        unset($query);
        return TRUE;
      }
    }
  }

/*
|--------------------------------------------------------------------------
| get_queue
|--------------------------------------------------------------------------
*/
  function _get_queue()
  {
    /*$query = $this->db->get_where($this->config->item('table_queue'),array(
      'status' => '0',
    ),10);*/
    $this->db->select($this->config->item('table_queue').'.id as id,'.
                      $this->config->item('table_queue').'.url as url,'.
                      $this->config->item('table_queue').'.email as email,'.
                      $this->config->item('table_queue').'.hash as hash,'.
                      $this->config->item('table_result').'.url as scanned');
    $this->db->from($this->config->item('table_queue'));
    $this->db->join($this->config->item('table_result'),$this->config->item('table_queue').'.id = '.$this->config->item('table_result').'.url','left');
    $this->db->where($this->config->item('table_queue').'.status','0');
    $this->db->limit($this->config->item('queue_limit'));
    $query = $this->db->get();
    if($query->num_rows() > 0) {
      return $query;
    }
    else {
      $query->free_result();
      unset($query);
      return FALSE;
    }
  }

/*
|--------------------------------------------------------------------------
| get_old_queue
|--------------------------------------------------------------------------
*/
  function _get_old_queue()
  {
    $this->db->select($this->config->item('table_queue').'.id as id,'.
                      $this->config->item('table_result_domain').'.domain as domain');
    $this->db->from($this->config->item('table_queue'));
    $this->db->join($this->config->item('table_result'),$this->config->item('table_result').'.url = '.$this->config->item('table_queue').'.id','left');
    $this->db->join($this->config->item('table_result_domain'),$this->config->item('table_result_domain').'.id = '.$this->config->item('table_result').'.domain','left');
    /*$this->db->where($this->config->item('table_queue').'.date <','(NOW() - INTERVAL 30 DAY)');*/
    $this->db->where($this->config->item('table_queue').'.date < DATE_SUB(NOW(), INTERVAL 30 DAY)',NULL,FALSE);
    $query = $this->db->get();
    if($query->num_rows() > 0) {
      return $query;
    }
    else {
      $query->free_result();
      unset($query);
      return FALSE;
    }
  }

/*
|--------------------------------------------------------------------------
| delete_from_queue
|--------------------------------------------------------------------------
*/
  function _delete_from_queue($data)
  {
    if(!isset($data) || !is_array($data) || !isset($data['id'])) {
      return FALSE;
    }
    else {
      $this->db->delete($this->config->item('table_queue'),array(
        'id' => $data['id'],
      ));
      $this->db->delete($this->config->item('table_result'),array(
        'url' => $data['id'],
      ));
      $table_queue_js = $this->db->get_where($this->config->item('table_queue_js'),array(
        'parent' => $data['id'],
      ));
      if($table_queue_js->num_rows() > 0) {
        foreach($table_queue_js->result() as $table_queue_js_result) {
          $this->db->delete($this->config->item('table_result_js'),array(
            'url' => $table_queue_js_result->id,
          ));
        }
        $this->db->delete($this->config->item('table_queue_js'),array(
          'parent' => $data['id'],
        ));
      }
      $table_queue_js->free_result();
      unset($table_queue_js);
      if(isset($data['host']) && !empty($data['host'])) {
        $this->db->select($this->config->item('table_result_domain').'.id as id');
        $this->db->from($this->config->item('table_result_domain'));
        $this->db->join($this->config->item('table_result'),$this->config->item('table_result').'.domain = '.$this->config->item('table_result_domain').'.id','left');
        $this->db->where($this->config->item('table_result_domain').'.domain',$data['host']);
        $this->db->where($this->config->item('table_result').'.domain',$this->config->item('table_result_domain').'.id');
        $this->db->limit(1);
        $query = $this->db->get();
        if($query->num_rows() === 0) {
          $this->db->delete($this->config->item('table_result_domain'),array(
            'domain' => $data['host'],
          ));
        }
        $query->free_result();
        unset($query);
      }
      return TRUE;
    }
  }

/*
|--------------------------------------------------------------------------
| save_result
|--------------------------------------------------------------------------
*/
  function _save_result($data)
  {
    if(!isset($data) || !is_array($data) || !isset($data['id']) || !isset($data['result']) || !is_object($data['result']) || !isset($data['domain'])) {
      return FALSE;
    }
    else {
      $query = $this->db->get_where($this->config->item('table_queue'),array(
        'id' => $data['id'],
      ));
      if($query->num_rows() > 0) {
        $query_result = $this->db->get_where($this->config->item('table_result'),array(
          'url' => $data['id'],
        ));
        if($query_result->num_rows() > 0) {
          $this->db->update($this->config->item('table_result'),array(
            /*'virustotal' => serialize($data['result']),*/
            'avdetect' => serialize($data['result']),
            'domain' => (int)$data['domain'],
          ),array(
            'url' => $data['id'],
          ));
        }
        else {
          $this->db->insert($this->config->item('table_result'),array(
            'url' => $data['id'],
            /*'virustotal' => serialize($data['result']),*/
            'avdetect' => serialize($data['result']),
            'domain' => (int)$data['domain'],
          ));
        }
        $query->free_result();
        $query_result->free_result();
        unset($query,$query_result);
        return TRUE;
      }
      else {
        $query->free_result();
        unset($query);
        return FALSE;
      }
    }
  }

/*
|--------------------------------------------------------------------------
| get_result
|--------------------------------------------------------------------------
*/
  function _get_result($data)
  {
    if(!isset($data) || !is_array($data) || !isset($data['hash'])) {
      return FALSE;
    }
    else {
      $this->db->select($this->config->item('table_queue').'.url as url,'.
                        $this->config->item('table_queue').'.date as date,'.
                        $this->config->item('table_result').'.avdetect as avdetect,'.
                        $this->config->item('table_queue_js').'.url as jsurl,'.
                        $this->config->item('table_result_js').'.avdetect as jsavdetect,'.
                        $this->config->item('table_result_domain').'.domain as domain,'.
                        $this->config->item('table_result_domain').'.avdetect as davdetect');
      $this->db->from($this->config->item('table_queue'));
      $this->db->join($this->config->item('table_result'),$this->config->item('table_result').'.url = '.$this->config->item('table_queue').'.id','left');
      $this->db->join($this->config->item('table_queue_js'),$this->config->item('table_queue_js').'.parent = '.$this->config->item('table_queue').'.id AND '.$this->config->item('table_queue_js').'.status = 1','left');
      $this->db->join($this->config->item('table_result_js'),$this->config->item('table_result_js').'.url = '.$this->config->item('table_queue_js').'.id','left');
      $this->db->join($this->config->item('table_result_domain'),$this->config->item('table_result_domain').'.id = '.$this->config->item('table_result').'.domain','left');
      $this->db->where($this->config->item('table_queue').'.hash',$data['hash']);
      $this->db->where($this->config->item('table_queue').'.status','1');
      $query = $this->db->get();
      if($query->num_rows() > 0) {
        return $query;
      }
      else {
        $query->free_result();
        $this->db->query('SET @qorder=0');
        $query = $this->db->query('SELECT id, url, date, hash, email, @qorder:=@qorder+1 AS qorder
                                   FROM `virus_queue` WHERE status=0
                                   GROUP BY id
                                   ORDER BY id ASC');
        if($query->num_rows() > 0) {
          return $query;
        }
        else {
          $query->free_result();
          unset($query);
          return FALSE;
        }
      }
    }
  }

/*
|--------------------------------------------------------------------------
| add_queue_js
|--------------------------------------------------------------------------
*/
  function _add_queue_js($data)
  {
    if(!isset($data) || !is_array($data) || !isset($data['urls']) || !is_array($data['urls']) || !isset($data['parent'])) {
      return FALSE;
    }
    else {
      /*$this->db->insert($this->config->item('table_queue_js'),array(
        'parent' => $data['parent'],
        'url' => $data['url'],
      ));*/
      $query = array();
      foreach($data['urls'] as $url) {
        $query[] = array(
          'parent' => $data['parent'],
          'url' => $url,
        );
      }
      $this->db->insert_batch($this->config->item('table_queue_js'),$query);
      unset($query);
      return TRUE;
    }
  }

/*
|--------------------------------------------------------------------------
| get_queue_js
|--------------------------------------------------------------------------
*/
  function _get_queue_js($data)
  {
    if(!isset($data) || !is_array($data) || !isset($data['id'])) {
      return FALSE;
    }
    else {
      $query = $this->db->get_where($this->config->item('table_queue_js'),array(
        'parent' => $data['id'],
        /*'status' => '0',*/
      ));
      if($query->num_rows() > 0) {
        return $query;
      }
      else {
        $query->free_result();
        unset($query);
        return FALSE;
      }
    }
  }

/*
|--------------------------------------------------------------------------
| delete_from_queue_js
|--------------------------------------------------------------------------
*/
  function _delete_from_queue_js($data)
  {
    if(!isset($data) || !is_array($data) || !isset($data['id'])) {
      return FALSE;
    }
    else {
      $this->db->delete($this->config->item('table_queue_js'),array(
        'id' => $data['id'],
      ));
      $this->db->delete($this->config->item('table_result_js'),array(
        'url' => $data['id'],
      ));
      return TRUE;
    }
  }

/*
|--------------------------------------------------------------------------
| save_result_js
|--------------------------------------------------------------------------
*/
  function _save_result_js($data)
  {
    if(!isset($data) || !is_array($data) || !isset($data['id']) || !isset($data['result']) || !is_object($data['result'])) {
      return FALSE;
    }
    else {
      $this->db->insert($this->config->item('table_result_js'),array(
        'url' => $data['id'],
        /*'virustotal' => serialize($result),*/
        'avdetect' => serialize($data['result']),
      ));
      $this->db->update($this->config->item('table_queue_js'),array(
        'status' => '1',
      ),array(
        'id' => $data['id'],
      ));
      return TRUE;
    }
  }

/*
|--------------------------------------------------------------------------
| get_result_domain
|--------------------------------------------------------------------------
*/
  function _get_result_domain($data)
  {
    if(!isset($data) || !is_array($data) || !isset($data['domain'])) {
      return FALSE;
    }
    else {
      $query = $this->db->get_where($this->config->item('table_result_domain'),array(
        'domain' => $data['domain'],
      ));
      /*$this->db->select('id, date');
      $this->db->where('domain',$domain);
      $query = $this->db->get($this->config->item('table_result_domain'));*/
      if($query->num_rows() > 0) {
        return $query;
      }
      else {
        $query->free_result();
        unset($query);
        return FALSE;
      }
    }
  }

/*
|--------------------------------------------------------------------------
| save_result_domain
|--------------------------------------------------------------------------
*/
  function _save_result_domain($data)
  {
    if(!isset($data) || !is_array($data) || !isset($data['domain']) || !isset($data['result']) || !is_object($data['result'])) {
      return FALSE;
    }
    else {
      $query = $this->db->get_where($this->config->item('table_result_domain'),array(
        'domain' => $data['domain'],
      ));
      if($query->num_rows() > 0) {
        $this->db->update($this->config->item('table_result_domain'),array(
          'avdetect' => serialize($data['result']),
        ),array(
          'domain' => $data['domain'],
        ));
      }
      else {
        $this->db->insert($this->config->item('table_result_domain'),array(
          'domain' => $data['domain'],
          'avdetect' => serialize($data['result']),
        ));
      }
      $query->free_result();
      unset($query);
      return TRUE;
    }
  }

/*
|--------------------------------------------------------------------------
| queue_completed
|--------------------------------------------------------------------------
*/
  function _queue_completed($data)
  {
    if(!isset($data) || !is_array($data) || !isset($data['id'])) {
      return FALSE;
    }
    else {
      $query = $this->db->get_where($this->config->item('table_queue_js'),array(
        'parent' => $data['id'],
        'status' => '0',
      ));
      if($query->num_rows() > 0) {
        $query->free_result();
        unset($query);
        return FALSE;
      }
      else {
        $this->db->update($this->config->item('table_queue'),array(
          'status' => '1',
        ),array(
          'id' => $data['id'],
        ));
        $query->free_result();
        unset($query);
        return TRUE;
      }
    }
  }

}