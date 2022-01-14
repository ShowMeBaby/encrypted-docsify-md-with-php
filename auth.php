<?php
function startWith($str, $needle) {
  return strpos($str, $needle) === 0;
}
function endWith($haystack, $needle) {
  $length = strlen($needle);
  if ($length == 0) {
    return true;
  }
  return (substr($haystack, -$length) === $needle);
}
function echoForm($requestUri) {
  echo <<<form
  <form action="$requestUri" onsubmit="location.reload();" target="nm_iframe" method='get'>
    阅读密码: <input type='text' name='auth'>
    <input type='submit' id="id_submit" value='提交'>
  </form>
  <iframe id="id_iframe" name="nm_iframe" style="display:none;"></iframe>
  form;
  exit;
}
$requestUri = urldecode($_SERVER['REQUEST_URI']);
$requestUri = parse_url($requestUri)['path'];
if (isset($_COOKIE['auth']) || isset($_GET['auth'])) {
  $cookieValue = $_COOKIE['auth'];
  if (isset($_GET['auth'])) {
    $cookieValue = md5($_GET['auth']);
    setcookie('auth', $cookieValue, time() + 3600 * 12);
  }
  $pathinfo = pathinfo($requestUri);
  $patharr = explode("/", $requestUri);
  $basename = str_replace(".auth.md", "", end($patharr));
  unset($patharr[count($patharr)-1]);
  $mdpath = __DIR__ . implode("/", $patharr);
  $files = scandir($mdpath);
  foreach ($files as $filename) {
    if (startWith($filename, $basename) && endWith($filename, '.auth.md')) {
      $password = str_replace($basename . ".", "", $filename);
      $password = str_replace(".auth.md", "", $password);
      if (md5($password) === $cookieValue) {
        echo $filecontent = file_get_contents($mdpath . "/" . $filename);
        exit;
      }
    }
  }
  echoForm($requestUri);
} else {
  echoForm($requestUri);
}
