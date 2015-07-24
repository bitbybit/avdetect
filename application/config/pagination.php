<?php  if ( ! defined('BASEPATH')) exit('No direct script access allowed');

$config['per_page'] = 50;
$config['uri_segment'] = 3;
$config['num_links']  = 5;
$config['use_page_numbers'] = TRUE;
$config['first_link'] = 'первая';
$config['last_link'] = 'последняя';
$config['total_rows'] = 0;
$config['full_tag_open'] = '<div id="pagination" class="pagination pagination-large pagination-centered"><ul>';
$config['full_tag_close'] = '</ul></div>';
$config['first_tag_open'] = '<li>';
$config['first_tag_close'] = '</li>';
$config['last_tag_open'] = '<li>';
$config['last_tag_close'] = '</li>';
$config['next_tag_open'] = '<li>';
$config['next_tag_close'] = '</li>';
$config['prev_tag_open'] = '<li>';
$config['prev_tag_close'] = '</li>';
$config['cur_tag_open'] = '<li class="active"><a href="javascript:void(0);">';
$config['cur_tag_close'] = '</a></li>';
$config['num_tag_open'] = '<li>';
$config['num_tag_close'] = '</li>';