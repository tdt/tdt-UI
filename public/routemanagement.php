<?php
 
/**
 * Shows the list of routes
 * @copyright (C) 2013 by OKFN Belgium
 * @license AGPLv3
 * @author Leen De Baets
 * @author Jeppe Knockaert
 * @author Nicolas Dierck
 */

use Symfony\Component\HttpFoundation\Request;

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

// Add, edit or remove a route
$app->match('/routes/edit', function (Request $request) use ($app,$routes,$cores,$routeFile,$routeObject) {
	
	// Default = no write
	$write = false;

	//If the request comes from the routelist, a parameter route will be in the request
	$route = $request->get('oldroute',null);

	//Check if the request is to remove the route
	if ($request->get("remove") != null){
		//Set route to null, so it gets removed when writing
		$routes[$route] = null;
		$write = true;
	}
	// // Enter default data for the form
 //    $defaultdata = array(
 //    	'oldroute' => $oldname,
 //    );

	// // Create a Silex form with all the needed fields
 //    $form = $app['form.factory']->createBuilder('form', $defaultdata)
 //    	->add('function','hidden')
 //    	->add('oldroute','hidden')
 //    	->getForm();

	// If a remove/edit/add is executed, we need to write to the config files
    if ($write){
		// Put the info from the routes array into the routeObject
		routesToObject($cores,$routes);

		// Write to cores.json
		file_put_contents($routeFile, json_format($routeObject));

        // Redirect to the userlist
        return $app->redirect('../../routes'); 
    }
    else{
    	return "TODO";
    }
});

