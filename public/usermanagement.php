<?php
 
/**
 * Shows the list of users
 * @copyright (C) 2013 by OKFN Belgium
 * @license AGPLv3
 * @author Leen De Baets
 * @author Jeppe Knockaert
 * @author Nicolas Dierck
 */

use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Validator\Constraints as Assert;

// Allows to strip the comments from a json file
require_once STARTPATH.'app/core/configurator.php';

// Used to write json to file, formatted to be read by humans
require_once APPPATH.'nicejson-php/nicejson.php';

// Fetch users from the auth.json file
$filename = STARTPATH."app/config/auth.json";
$userObject = json_decode(file_get_contents($filename));

// Fetch routes from file
$routeFile = STARTPATH. "app/config/cores.json";
$routeObject = json_decode(Configurator::stripComments(file_get_contents($routeFile)));
$cores = get_object_vars($routeObject);
$index = 0;

// Save the routes used per user
$userroutes = array();
foreach ($cores as $core) {
	foreach ($core->routes as $route) {
		$routes[$index] = $route;
		foreach ($route->users as $user) {
				$numberofroutes = 0;
				if (isset($userroutes[$user]->routes)){
					$numberofroutes = count($userroutes[$user]->routes);
				}
				$userroutes[$user]->routes[$numberofroutes] = $index;
		}
		$index++;
	}	
}

// List users in auth.json
$app->get('/users', function () use ($app,$userObject,$routes,$userroutes) {
	$data['users'] = get_object_vars($userObject);
	$data['routes'] = $routes;
	$data['userroutes'] = $userroutes;
	return $app['twig']->render('userlist.twig',$data);
});

// Add, edit or remove an existing user
$app->match('/users/edit', function (Request $request) use ($app,$userObject,$filename,$userroutes,$routes,$routeFile,$routeObject,$cores) {

	// Default = no write
	$write = false;

	//If the request comes from the userlist, a parameter oldname will be in the request
	$oldname = $request->get('oldname',null);
	
	//Check if the request is to remove the user
	if ($request->get("remove") != null){
		//Remove the user from the array
		unset($userObject->$oldname);

		// Remove routes for the user
		foreach ($routes as $index => $route) {
			// Look for the user in the user array of the current route
			$oldindex = array_search($oldname,$route->users);
			// If the user has access to the route, remove access
			if ($oldindex !== false){
				unset($route->users[$oldindex]);
			}
		}
		$write = true;
	}
	else{
		// If there is an old username, it means an edit is wanted
		if ($oldname != null){
			$olduser = $userObject->$oldname;

			//Make a list with the routes from the current user
			$number = 0;
			foreach ($userroutes[$oldname]->routes as $userroute) {
         		$routedefaults[$number] = $userroute;
         		$number++;
         	}

			// Enter default data for the form
		    $defaultdata = array(
		    	'function' => 'Edit',
		    	'oldname' => $oldname,
		        'username' => $oldname,
		        'documentation' => $olduser->documentation,
		        'type' => $userObject->$oldname->type,
		        'routes' => $routedefaults
		    );
		    
		    $twigdata['button'] = "Edit";
		}
		// If there is no old username, it means an add is wanted 
		else{
			$defaultdata = array(
				'function' => 'Add',
			);
			$twigdata['button'] = "Add";
		}	 

		// Create the route checkboxes
		foreach ($routes as $index => $route) {
        	$routecheckboxes[$index] = $routes[$index]->documentation;
        }

	    // Create a Silex form with all the needed fields
	    $form = $app['form.factory']->createBuilder('form', $defaultdata)
	    	->add('function','hidden')
	    	->add('oldname','hidden')
	        ->add('username','text',array(
	        	'label' => "Username",
	        	'constraints' => new Assert\NotBlank()
	        	)
        	)
	        ->add('password','password',array(
	        	'label' => "Password",
	        	'constraints' => new Assert\NotBlank()
	        	)
	        )
	        ->add('documentation','textarea',array(
	        	'label' => "Documentation",
	        	'required' => false
	        	)
	        )
	        ->add('authenticationtype', 'choice', array(
	        	'label' => 'Authentication type',
	            'choices' => array('BasicAuth' => 'BasicAuth')
	            )
	        )->add('routes','choice', array(
	        	'label' => 'Routes',
	        	'choices' => $routecheckboxes,
    			'required' => false,
    			'expanded' => true,
    			'multiple' => true
        		)
        	)->getForm();

	    // If the method is POST, validate the form
	    if ('POST' == $request->getMethod() && !isset($userObject->$oldname)) {
	        $form->bind($request);

	        // Retrieve the function (edit or add) and give it to Twig
	        $data = $form->getData();
	    	$twigdata['button'] = $data['function'];

	    	// Validate the form
	        if ($form->isValid()) {
	            // Fetch the correct old name of the user from the form
	        	$oldname = $data['oldname'];
	        	$newname = $data['username'];

	        	// Check if the username has changed, if so, delete the old username
				if (strcmp($oldname, $newname) != 0 && isset($userObject->$oldname)){
					unset($userObject->$oldname);
				}

				// Edit user properties
				$userObject->$newname->type = $data['authenticationtype'];
				$userObject->$newname->documentation = $data['documentation'];
				$userObject->$newname->password = $data['password'];

				// Edit routes
				foreach ($routes as $index => $route) {
					// Look for the user in the user array of the current route
					$newindex = array_search($newname,$route->users);
					$oldindex = array_search($oldname,$route->users);
					// Route was checked
					if (in_array($index, $data['routes'])){
						// If the username has changed, remove the access for the old username first
						if (strcmp($oldname, $newname) != 0 && $oldindex !== false){
							unset($route->users[$oldindex]);
						}
						// If user is not allowed to a route yet, add him to it
						if ($newindex === false){
							$route->users[count($route->users)] = $newname;
						}
					}
					// Route was not checked
					else if ($newindex !== false){
						unset($route->users[$foundindex]);
					}
				}
				$write = true;
	        }
	    }
	}
	// If a remove/edit/add is executed, we need to write to the config files
    if ($write){
    	// Put routes back in the object
		$globalindex = 0;
		foreach ($cores as $coreindex => $core) {
			$localindex = 0;
			foreach ($core->routes as $route) {
				$routeObject->$coreindex->routes[$localindex] = $routes[$globalindex];
				$globalindex++;
				$localindex++;
			}
		}

		// Write to auth.json
		file_put_contents($filename, json_format($userObject));

		// Write to cores.json
		file_put_contents($routeFile, json_format($routeObject));

        // Redirect to the userlist
        return $app->redirect('../../users'); 
    }
    // Show the form
    else{
	 	$twigdata['form'] = $form->createView();
	 	$twigdata['title'] = $twigdata['button']." user";
	 	$twigdata['header'] = $twigdata['title'];
	    // display the form
	    return $app['twig']->render('form.twig', $twigdata);
	}
});