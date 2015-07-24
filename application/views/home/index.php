<!DOCTYPE html>
<!--[if lt IE 7]> <html class="index no-js lt-ie9 lt-ie8 lt-ie7" lang="ru"> <![endif]-->
<!--[if IE 7]>    <html class="index no-js lt-ie9 lt-ie8" lang="ru"> <![endif]-->
<!--[if IE 8]>    <html class="index no-js lt-ie9" lang="ru"> <![endif]-->
<!--[if gt IE 8]><!--> <html class="index no-js" lang="ru"> <!--<![endif]-->
<head>

  <meta charset="utf-8">

  <title><?=$title; ?></title>

  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <!--[if IE]>
    <meta http-equiv="imagetoolbar" content="no">
    <meta http-equiv="X-UA-Compatible" content="IE=edge,chrome=1">
  <![endif]-->

  <link rel="dns-prefetch" href="//yandex.st">
  <link rel="shortcut icon" href="<?=base_url(); ?>favicon.ico" type="image/x-icon">
  <link href="//yandex.st/bootstrap/3.0.3/css/bootstrap.min.css" rel="stylesheet" media="screen">
  <link href="<?=base_url().str_replace($this->config->item('rel_base_url'),'',$auto_version_css); ?>" rel="stylesheet" media="screen">

  <script>
    var base_url = '<?=base_url(); ?>';
  </script>
  <script src="<?=base_url().str_replace($this->config->item('rel_base_url'),'',$auto_version_js); ?>"></script>

</head>
<body>

<?php
  if((function_exists('validation_errors') && validation_errors()) || !empty($error)) {
    echo '  <div class="alert alert-block alert-error fade in"><button type="button" class="close" data-dismiss="alert">&times;</button><!--<h4 class="alert-heading"></h4>--><ul class="unstyled">';
    if(function_exists('validation_errors') && validation_errors()) {
      echo validation_errors('<li>','</li>');
    }
    elseif(!empty($error)) {
      echo '<li>'.$error.'</li>';
    }
    echo '  </ul></div>'."\r\n";
  }
  if(isset($output) && !empty($output)) {
    echo $output;
  }
?>

</body>
</html>
