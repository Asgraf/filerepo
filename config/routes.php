<?php
use Cake\Routing\RouteBuilder;
use Cake\Routing\Router;
use Cake\Routing\Route\DashedRoute;

Router::scope('/files', ['plugin' => 'Filerepo', 'controller' => 'Fileobjects'], function (RouteBuilder $routes) {
	$routes->connect('/files/download/{id}/{name}', ['action' => 'download'], ['routeClass' => 'EntityRoute']);
	$routes->connect('/files/{id}/{name}', ['action' => 'view'], ['routeClass' => 'EntityRoute']);
	$routes->fallbacks(DashedRoute::class);
});