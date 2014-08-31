<?php

/* 判断是否在PC端*/
$http_user_agent = isset($_SERVER['HTTP_USER_AGENT']) ? strtolower($_SERVER['HTTP_USER_AGENT']): '';
$http_accept = isset($_SERVER['HTTP_ACCEPT']) ? strtolower($_SERVER['HTTP_ACCEPT']): '';
$pos_hua = strpos($http_user_agent, 'mobi');
$pos_a_wap = strpos($http_accept, 'wap');
$pos_a_wml = strpos($http_accept, 'wml');

if($pos_hua !== FALSE) {

} elseif($pos_a_wap !== FALSE) {

} elseif($pos_a_wml !== FALSE) {

} else {
    echo 'PC';
	header("Location: 404/index.php");
	exit();
}

/*没有安装则加载安装文件*/
if (!file_exists(__DIR__ . '/../app/data/install.lock')) {
	header("Location: install/install.php");
	exit(); 
}

use Symfony\Component\ClassLoader\ApcClassLoader;
use Symfony\Component\HttpFoundation\Request;
use Topxia\Service\Common\ServiceKernel;
use Topxia\Service\User\CurrentUser;

fix_gpc_magic();

$loader = require_once __DIR__.'/../app/bootstrap.php.cache';

require_once __DIR__.'/../app/AppKernel.php';

$kernel = new AppKernel('prod', false);
$kernel->loadClassCache();
Request::enableHttpMethodParameterOverride();
$request = Request::createFromGlobals();

$kernel->boot();

$serviceKernel = ServiceKernel::create($kernel->getEnvironment(), $kernel->isDebug());
$serviceKernel->setParameterBag($kernel->getContainer()->getParameterBag());
$serviceKernel->setConnection($kernel->getContainer()->get('database_connection'));
$serviceKernel->getConnection()->exec('SET NAMES UTF8');

$currentUser = new CurrentUser();
$currentUser->fromArray(array(
    'id' => 0,
    'userName' => '游客',
    'currentIp' =>  $request->getClientIp(),
    'roles' => array(),
));
$serviceKernel->setCurrentUser($currentUser);
try {
	$response = $kernel->handle($request);
} catch (\RuntimeException $e) {
    echo "Error!  ". $e->getMessage();
    die();
}

$response->send();
$kernel->terminate($request, $response);

function _fix_gpc_magic(&$item) {
  if (is_array($item)) {
    array_walk($item, '_fix_gpc_magic');
  }
  else {
    $item = stripslashes($item);
  }
}

function _fix_gpc_magic_files(&$item, $key) {
  if ($key != 'tmp_name') {
    if (is_array($item)) {
      array_walk($item, '_fix_gpc_magic_files');
    }
    else {
      $item = stripslashes($item);
    }
  }
}

function fix_gpc_magic() {
  if (ini_get('magic_quotes_gpc')) {
    array_walk($_GET, '_fix_gpc_magic');
    array_walk($_POST, '_fix_gpc_magic');
    array_walk($_COOKIE, '_fix_gpc_magic');
    array_walk($_REQUEST, '_fix_gpc_magic');
    array_walk($_FILES, '_fix_gpc_magic_files');
  }
}