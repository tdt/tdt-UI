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

//Allows to strip the comments from a json file
require_once STARTPATH.'app/core/configurator.php';

//Used to write json to file, formatted to be read by humans
require_once APPPATH.'nicejson-php/nicejson.php';

//Fetch users from the auth.json file
$filename = STARTPATH."app/config/auth.json";
$users = get_object_vars(json_decode(file_get_contents($filename)));

//Check if another file has already set the cores variable (for example routemanagement.php)
if (!isset($cores)){
	//Fetch routes from file
	$routeFile = STARTPATH. "app/config/cores.json";
	$routeObject = json_decode(Configurator::stripComments(file_get_contents($routeFile)));
	$cores = get_object_vars($routeObject);
}

// List users in auth.json
$app->get('/users', function () use ($app,$users) {
	$data['users'] = $users;
	return $app['twig']->render('userlist.twig',$data);
});

// Add or edit an existing user
$app->match('/users/edit', function (Request $request) use ($app,$users,$filename) {

	//If the request comes from the userlist, a parameter oldname will be in the request
	$oldname = $request->get('oldname',null);
	
	//Check if the request is to remove the user
	if ($request->get("remove") != null){
		//Remove the user from the array
		unset($users[$oldname]);	
		//Write to file
		file_put_contents($filename, json_format($users));
		return $app->redirect('../../users');
	}
	else{
		if ($oldname != null){
			$olduser = $users[$oldname];
			// Enter default data for the form
		    $defaultdata = array(
		    	'oldname' => $oldname,
		        'username' => $oldname,
		        'documentation' => $olduser->documentation,
		        'type' => $users[$oldname]->type
		    );
		    $twigdata['function'] = "Edit";
		}
		else{
			$defaultdata = array();
			$twigdata['function'] = "Add";
		}	 

	    // Create a Silex form with all the needed fields
	    $form = $app['form.factory']->createBuilder('form', $defaultdata)
	    	->add('oldname','hidden')
	        ->add('username','text',array('constraints' => new Assert\NotBlank()))
	        ->add('password','password',array('constraints' => new Assert\NotBlank()))
	        ->add('documentation','textarea')
	        ->add('authenticationtype', 'choice', array(
	            'choices' => array('BasicAuth' => 'BasicAuth'),
	        ))
	        ->getForm();

	    // If the method is POST, validate the form
	    if ('POST' == $request->getMethod() && !isset($users[$oldname])) {
	        $form->bind($request);

	        if ($form->isValid()) {
	            $data = $form->getData();
	            
	            //Fetch the correct old name of the user from the form
	        	$oldname = $data['oldname'];
	        	$newname = $data['username'];

	        	// Check if the username has changed, if so, delete the old username
				if (strcmp($oldname, $newname) != 0){
					unset($users[$oldname]);
				}

				// Edit user properties
				$users[$newname]->type = $data['authenticationtype'];
				$users[$newname]->documentation = $data['documentation'];
				$users[$newname]->password = $data['password'];

				// Write to file
				file_put_contents($filename, json_format($users));

	            // Redirect to the userlist
	            return $app->redirect('../../users'); 
	        }
	    }
	 	$twigdata['form'] = $form->createView();

	    // display the form
	    return $app['twig']->render('edituser.twig', $twigdata);
	}
});