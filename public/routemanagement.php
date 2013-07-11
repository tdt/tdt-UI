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
use Symfony\Component\Validator\Constraints as Assert;

// Allows to strip the comments from a json file
require_once STARTPATH.'app/core/configurator.php';

// Routes should be defined in usermanagement.php
if (!isset($routes)){
	echo "Make sure usermanagement.php is before routemanagement.php in your index.php! <br />";
	exit(1);
}

// Render a list of routes using Twig
$app->get('/ui/routes', function () use ($app,$routes) {
	// Give the array with cores to Twig, it contains the routes per core
	$data['routes'] = $routes;
	return $app['twig']->render('routelist.twig',$data);
});

// Add, edit or remove a route
$app->match('/ui/routes/edit', function (Request $request) use ($app,$routes,$routeFile,$routeObject,$userObject) {
	
	// Default = no write
	$write = false;

	// If the request comes from the routelist, a parameter route will be in the request
	$oldroute = $request->get('oldroute',null);
	if ($oldroute != null){
		$exploded = explode("//",$oldroute);
		$oldnamespace = $exploded[count($exploded)-2];
		$oldindex = $exploded[count($exploded)-1];
		$oldroute = $routes[$oldnamespace]->routes[$oldindex];
		// Request came from the route list
		$fromroutelist = true;
	}
	else{
		$fromroutelist = false;
	}

	// Check if the request is to remove the route
	if ($request->get("remove") != null){
		unset($routes[$oldnamespace]->routes[$oldindex]);
		$write = true;
	}
	// Add or edit a route
	else{
		// If there is an old route number, it means an edit is wanted
		if ($oldroute != null){
			// Enter default data for the form
		    $defaultdata = array(
		    	'function' => 'Edit',
		    	'index' => $oldindex,
		    	'namespace' => $oldnamespace,
		    	'description' => $oldroute->documentation,
		    	'route' => $oldroute->route,
		    	'controller' => $oldroute->controller,
		        'method' => $oldroute->method,
		        'users' => $oldroute->users
		    );
		    $twigdata['button'] = "Edit";
		}
		// If there is no old route, it means an add is wanted 
		else{
			$defaultdata = array(
				'function' => 'Add'
			);
			$twigdata['button'] = "Add";
		}

		// Prepare to give the userlist to the form, users should be keys as well as values
		$userlist = array_keys(get_object_vars($userObject));
		foreach ($userlist as $user) {
			$users[$user] = $user;
		}

		// Create a Silex form with all the needed fields
	    $form = $app['form.factory']->createBuilder('form', $defaultdata)
	    	->add('function','hidden')
	    	->add('index','hidden')
	    	->add('namespace','choice', array(
	        	'label' => 'Namespace',
	            'choices' => array('core' => 'Core', 'input' => 'Input'),
	            'expanded' => false,
	            'multiple' => false
	            )
	      	)
	    	->add('description','text',array(
	        	'label' => 'Description',
	        	'constraints' => new Assert\NotBlank()
	        	)
	    	)
	    	->add('route','text',array(
	    		'label' => 'Route regex',
	    		// TODO: add regex constraint
	    		'constraints' => new Assert\NotBlank() 
	    		)
	    	)
	    	->add('method', 'choice', array(
	        	'label' => 'Method',
	            'choices' => array('GET' => 'GET', 'POST' => 'POST', 'PUT' => 'PUT', 'PATCH' => 'PATCH', 'DELETE' => 'DELETE'),
	            'expanded' => false,
	            'multiple' => false
	            )
	      	)
	        ->add('controller', 'choice', array(
	        	'label' => 'Controller',
	            'choices' => array(
	            	'controllers\RController' => 'RController',
	            	'controllers\CUDController' => 'CUDController',
	            	'controllers\SPECTQLController' => 'SPECTQLController',
	            	'controllers\RedirectController' => 'RedirectController',
	            	'scheduler\controllers\Worker' => 'Worker',
	            	'scheduler\controllers\InputResourceController' => 'InputResourceController',
            	 ),
	            'expanded' => false,
	            'multiple' => false,
	            )
	        )
	        ->add('users', 'choice', array(
	        	'label' => 'Users',
	        	'choices' => $users,
	        	'expanded' => true,
	            'multiple' => true,
	            'required' => false
	        	)
	      	)->getForm();
      	
      	// If the method is POST, validate the form
	    if ('POST' == $request->getMethod() && !$fromroutelist) {
	        $form->bind($request);

	        // Retrieve the function (edit or add) and give it to Twig
	        $data = $form->getData();
	    	$twigdata['button'] = $data['function'];

	    	// Validate the form
	        if ($form->isValid()) {
	            // Fetch the correct old route of the route from the form
				$oldnamespace = $data['namespace'];
				$oldindex = $data['index'];

				// If this is an add, add the route to the end of the list
				if ($oldindex == null){
					$oldindex = count($routes[$oldnamespace]->routes);
					$routes[$oldnamespace]->routes[$oldindex] = new stdClass();
				}
				$oldroute = $routes[$oldnamespace]->routes[$oldindex];

	         	// Edit the route properties
	         	$oldroute->documentation = $data['description'];
	         	$oldroute->route = $data['route'];
	         	$oldroute->method = $data['method'];
	         	$oldroute->controller = $data['controller'];
	         	$oldroute->users = $data['users'];

				$write = true;
	        }
	    }
	}

	// If a remove/edit/add is executed, we need to write to the config files
    if ($write){
		// Write to cores.json
		file_put_contents($routeFile, json_format($routeObject));

        // Redirect to the userlist
        return $app->redirect('../../ui/routes'); 
    }
    // Show the form
    else{
    	$twigdata['form'] = $form->createView();
	 	$twigdata['title'] = $twigdata['button']." route";
	 	$twigdata['header'] = $twigdata['title'];
	    // display the form
	    return $app['twig']->render('form.twig', $twigdata);
    }
});

