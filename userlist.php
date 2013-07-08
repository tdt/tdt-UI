<?php
 
/**
 * Shows the list of users
 * @copyright (C) 2013 by OKFN Belgium
 * @license AGPLv3
 * @author Leen De Baets
 * @author Jeppe Knockaert
 * @author Nicolas Dierck
 */

// Making Silex application
require_once 'vendor/autoload.php';
$app = new Silex\Application();
$app['debug'] = true;

//Register the Twig service provider
$app->register(new Silex\Provider\TwigServiceProvider(), array(
	'twig.path' => __DIR__.'/views'
));

//Fetch users from file (existence is already checked when loading configurationfiles)
// $filename = APPPATH. "config/auth.json";
// $users = json_decode(Configurator::stripComments(file_get_contents($filename)));
// var_dump($users);
$data = array();

// Representing the data in twig.
$app->get('/users', function () use ($app) {
	return $app['twig']->render('userlist.twig',$data);
});

// running the application
$app->run();
