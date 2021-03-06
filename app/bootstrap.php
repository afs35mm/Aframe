<?php

namespace Aframe;
use Aframe\Utils\Util;

require ROOT . '/vendor/autoload.php';

if (strpos(Util::getFullUrl(), '.dev')) {
    ini_set('display_errors',1);
    error_reporting(E_ALL);
    define('ENV', 'dev');
} else {
    define('ENV', 'prod');
}

require ROOT . '/config/config.php';



$injector = include('dependencies.php');

$request = $injector->make('Http\HttpRequest');
$response = $injector->make('Http\HttpResponse');

foreach ($response->getHeaders() as $header) {
    header($header, false);
}

/**
* Register the error handler
*/
$whoops = new \Whoops\Run;

if (defined('ENV') && ENV === 'dev') {
    $whoops->pushHandler(new \Whoops\Handler\PrettyPageHandler);
} else {
    $whoops->pushHandler(new \Whoops\Handler\PrettyPageHandler);
}

$whoops->register();

$routeDefinitionCallback = function (\FastRoute\RouteCollector $r) {
    $routes = include('Routes.php');
    foreach ($routes as $route) {
        $r->addRoute($route[0], $route[1], $route[2]);
    }
};

$dispatcher = \FastRoute\simpleDispatcher($routeDefinitionCallback);

$routeInfo = $dispatcher->dispatch($request->getMethod(), $request->getPath());

switch ($routeInfo[0]) {
    case \FastRoute\Dispatcher::NOT_FOUND:
        http_response_code(404);
        echo '404';
        $response->setContent('404 - Page not found');
        $response->setStatusCode(404);
        break;
    case \FastRoute\Dispatcher::METHOD_NOT_ALLOWED:
        echo '405';
        $response->setContent('405 - Method not allowed');
        $response->setStatusCode(405);
        break;
    case \FastRoute\Dispatcher::FOUND:
        $className = $routeInfo[1][0];
        $method = (isset($routeInfo[1][1])) ? $routeInfo[1][1]: 'index';
        $vars = $routeInfo[2];
        $class = $injector->make( __NAMESPACE__ . '\Controllers\\' . $className);
        $class->$method($vars);
        break;
}
