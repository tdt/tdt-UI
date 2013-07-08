<?php
 
/**
 * Shows the list of users
 * @copyright (C) 2013 by OKFN Belgium
 * @license AGPLv3
 * @author Leen De Baets
 * @author Jeppe Knockaert
 * @author Nicolas Dierck
 */

error_reporting(E_ALL);
ini_set('display_errors', True);

require_once 'vendor/autoload.php';
require_once 'ConfigLoader.php';

// Making Silex application
$app = new Silex\Application();
$app['debug'] = true;

//Register the Twig service provider
$app->register(new Silex\Provider\TwigServiceProvider(), array(
	'twig.path' => __DIR__.'/views',
));

//Make a config loader
$configloader = new ConfigLoader();

//Fetch users from file (existence is already checked when loading configurationfiles)
$filename = $configloader->getSettings("startpath"). "app/config/auth.json";

$users = json_decode(file_get_contents($filename));
$usernames = array_keys(get_object_vars($users));

// Render a list of users using Twig
$app->get('/users', function () use ($app,$usernames) {
	$data['usernames'] = $usernames;
	return $app['twig']->render('userlist.twig',$data);
});

// Remove a user from the auth.json
$app->get('/users/remove/{username}', function ($username) use ($app,$users,$filename) {
	unset($users->$username);
	file_put_contents($filename, json_encode($users));
	return $app->redirect('../../users');
});

// running the application
$app->run();