<?php
 
/**
 * Shows the list of routes
 * @copyright (C) 2013 by OKFN Belgium
 * @license AGPLv3
 * @author Leen De Baets
 * @author Jeppe Knockaert
 * @author Nicolas Dierck
 */

// Allows to strip the comments from a json file
require_once STARTPATH.'app/core/configurator.php';

// Routes should be defined in usermanagement.php
if (!isset($routes)){
	echo "Make sure usermanagement.php is before routemanagement.php in your index.php! <br />";
	exit(1);
}

// Render a list of routes using Twig
$app->get('/routes', function () use ($app,$routes) {
	// Give the array with cores to Twig, it contains the routes per core
	$data['routes'] = $routes;
	return $app['twig']->render('routelist.twig',$data);
});