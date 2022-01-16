# 使用php为docsify添加阅读密码支持

由于docsify是运行时库,出于安全考虑我使用了php对加密文件进行处理,否则加密没有存在意义了.

## DEMO

Password: 123

[https://retrocode.io/#/%E6%8A%80%E5%B7%A7/password-123.auth.md](https://retrocode.io/#/%E6%8A%80%E5%B7%A7/password-123.auth.md)

## 演示

![filename](./res/1.png)

![demo](./res/demo.gif)

## 使用方式

在md文件后缀追加 .password.auth 即可.

> filename.passwprd.auth.md

例如:

> testPassword.123.auth.md

同时请将**_sidebar.md**中的文件路径修改为

> filename.md.auth

即**移除加密密码(加密密码只存在于源文件中)**

例如:

```markdown

* category
    * [testPassword](category/testPassword.auth.md)

```

## 配置nginx伪静态,将关于加密文件的请求转发给auth.php

```yaml
location ~* \.(auth.md)$ {
  if (!-e $request_filename){
    rewrite  ^(.*)$  /auth.php?s=$1  last;   break;
  }
}
```

## 创建auth.php并放置到与docsify的index.html同目录下

```php
<?php
function redirect($url) {
  header("Location: $url");
  exit();
}
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
```

## 注意

如果你使用的**_sidebar.md**,则需要在生成**_sidebar.md**文件时对文件名进行处理.

例如:

```php
    $files = scandir($path);
    foreach ($files as $filename) {
        $suffix =  pathinfo($filename, PATHINFO_EXTENSION);
        if ($filename !== "." && $filename !== ".." && $suffix === "md") {
            // 删除加密MD文件中的密码
            if (endWith($filename, '.auth.md')) {
              $tmpfns = explode(".", $filename);
              unset($tmpfns[count($tmpfns)-3]);
              $filename = implode(".",$tmpfns);
            }
            $fileRealPath = $categoryName . "/" . $filename;
            file_put_contents($GLOBALS['sidebarPath'], "    * [" . $filename . "](" . $fileRealPath . ")\r\n", FILE_APPEND);
        }
    }
```
