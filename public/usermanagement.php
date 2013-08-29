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
use Symfony\Component\Form\FormError;
use Symfony\Component\Validator\Constraints as Assert;

// Allows to strip the comments from a json file
require_once STARTPATH.'app/core/configurator.php';

// Used to write json to file, formatted to be read by humans
require_once __DIR__.'/../src/nicejson-php/nicejson.php';

// Load users from file
// Fetch users from the auth.json file
$filename = STARTPATH."app/config/auth.json";
// This object will be used by user management and route management, so don't delete it
$userObject = json_decode(file_get_contents($filename));
$users = get_object_vars($userObject);

// Fetch routes from file
$routeFile = STARTPATH. "app/config/cores.json";
$routeObject = json_decode(Configurator::stripComments(file_get_contents($routeFile)));
$routes = get_object_vars($routeObject);

// Save the routes used per user
$userroutes = array();
// Loop through all cores
foreach ($routes as $namespace => $core) {
    // Loop through all routes within a core object
    foreach ($core->routes as $index => $route) {
        // Loop through all users within a route object
        foreach ($route->users as $user) {

            // Create array if empty
            if(!isset($userroutes[$user])){
                $userroutes[$user] = new \stdClass();
                $userroutes[$user]->routes = array();
            }

            $user_route = new \stdClass();
            $user_route->namespace = $namespace;
            $user_route->index = $index;


            array_push($userroutes[$user]->routes, $user_route);
        }
    }
}

// List users in auth.json
$app->get('/ui/users{url}', function () use ($app,$userObject,$routes,$userroutes,$data) {
    $data['users'] = get_object_vars($userObject);
    $data['routes'] = $routes;
    $data['userroutes'] = $userroutes;
    return $app['twig']->render('userlist.twig',$data);
})->value('url', '');;

// Add, edit or remove a user
$app->match('/ui/users/edit{url}', function (Request $request) use ($app,$userObject,$filename,$userroutes,$routes,$routeFile,$routeObject,$data) {
    // Default = no write
    $write = false;

    //If the request comes from the userlist, a parameter oldname will be in the request
    $oldname = $request->get('oldname',null);

    // Check if the request is to remove the user
    if ($request->get("remove") != null){
        //Remove the user from the array
        unset($userObject->$oldname);

        // Remove routes for the user
        foreach ($routes as $core) {
            foreach ($core->routes as $index => $route) {
                // Look for the user in the user array of the current route
                $oldindex = array_search($oldname,$route->users);
                // If the user has access to the route, remove access
                if ($oldindex !== false){
                    unset($route->users[$oldindex]);
                }
            }
        }
        $write = true;
    }
    // Add or edit a user
    else{
        // If there is an old username, it means an edit is wanted
        if ($oldname != null){
            $olduser = $userObject->$oldname;

            //Make a list with the routes from the current user
            $number = 0;
            $routedefaults = array();

            if (!empty($userroutes[$oldname])){
                foreach ($userroutes[$oldname]->routes as $userroute) {
                    $routedefaults[count($routedefaults)] = $userroute->namespace.'//'.$userroute->index;
                    $number++;
                }
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

            $data['button'] = "Edit";
        }
        // If there is no old username, it means an add is wanted
        else{
            $defaultdata = array(
                'function' => 'Add',
            );
            $data['button'] = "Add";
        }

        // Create the route checkboxes
        $routecheckboxes = array();
        $globalindex = 0;
        foreach ($routes as $namespace => $core) {
            foreach ($core->routes as $index => $route) {
                $routecheckboxes[$namespace.'//'.$index] = $routes[$namespace]->routes[$index]->documentation;
                // Add infotext for infobuttons
                $controllerstring = str_replace('\\', '\\\\', $routes[$namespace]->routes[$index]->controller);
                $data['infobuttons'][$globalindex] = "Method: ".$routes[$namespace]->routes[$index]->method."<br />Route: ".$routes[$namespace]->routes[$index]->route."<br />Controller: ".$controllerstring;
                $globalindex++;
            }
        }

        // Create a Silex form with all the needed fields
        $form = $app['form.factory']->createBuilder('form', $defaultdata)
            ->add('function','hidden')
            ->add('oldname','hidden')
            ->add('username','text',array(
                'label' => "Username",
                'attr' => array('formtitlelabel' => 'formtitlelabel'),
                'constraints' => new Assert\NotBlank()
                )
            )
            ->add('password','password',array(
                'label' => "Password",
                'attr' => array('formtitlelabel' => 'formtitlelabel'),
                'constraints' => new Assert\NotBlank()
                )
            )
            ->add('documentation','textarea',array(
                'label' => "Documentation",
                'attr' => array('formtitlelabel' => 'formtitlelabel'),
                'required' => false
                )
            )
            ->add('authenticationtype', 'choice', array(
                'label' => 'Authentication type',
                'attr' => array('formtitlelabel' => 'formtitlelabel'),
                'choices' => array('BasicAuth' => 'BasicAuth'),
                )
            )->add('routes','choice', array(
                'label' => "Routes",
                'choices' => $routecheckboxes,
                'required' => false,
                'expanded' => true,
                'multiple' => true,
                'attr' => array('class' => 'infobuttonlist', 'formtitlelabel' => 'formtitlelabel')
                )
            )->getForm();
        // If the method is POST, validate the form
        if ('POST' == $request->getMethod() && !isset($userObject->$oldname)) {
            $form->bind($request);

            // Retrieve the function (edit or add) and give it to Twig
            $formdata = $form->getData();
            $data['button'] = $formdata['function'];

            //Validate the form
            if ($form->isValid()) {
                // Fetch the correct old name of the user from the form
                $oldname = $formdata['oldname'];
                $newname = $formdata['username'];

                // Check if the username is already in use (if it has changed)
                if (strcmp($oldname, $newname) != 0 && isset($userObject->$newname)){
                    $form->get('username')->addError(new FormError('Username is already in use'));
                }
                else {
                    // Check if the username has changed, if so, delete the old username
                    if (strcmp($oldname, $newname) != 0 && isset($userObject->$oldname)){
                        unset($userObject->$oldname);
                    }

                    // Edit user properties
                    $userObject->$newname->type = $formdata['authenticationtype'];
                    $userObject->$newname->documentation = $formdata['documentation'];
                    $userObject->$newname->password = $formdata['password'];

                    // Read route data
                    $routedata = array();
                    foreach ($formdata['routes'] as $element) {
                        $exploded = explode("//", $element);
                        $namespace = $exploded[count($exploded)-2];
                        if (!isset($routedata[$namespace])){
                            $routedata[$namespace] = array();
                        }
                        $routedata[$namespace][count($routedata[$namespace])] = $exploded[count($exploded)-1];
                    }

                    // Edit routes
                    foreach ($routes as $namespace => $core) {
                        foreach ($core->routes as $index => $route) {
                            // Look for the user in the user array of the current route
                            $newindex = array_search($newname,$route->users);
                            $oldindex = array_search($oldname,$route->users);

                            // Route was checked
                            if (in_array($index, $routedata[$namespace])){
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
                                unset($route->users[$newindex]);
                            }
                        }
                    }
                    $write = true;
                }
            }
        }
    }
    // If a remove/edit/add is executed, we need to write to the config files
    if ($write){
        // Write to auth.json
        file_put_contents($filename, json_format($userObject));

        // Write to cores.json
        file_put_contents($routeFile, json_format($routeObject));

        // Redirect to the userlist
        return $app->redirect(BASE_URL . ' /users');
    }
    // Show the form
    else{
        $data['form'] = $form->createView();
        $data['title'] = $data['button']." user";
        $data['header'] = $data['title'];
        // display the form
        return $app['twig']->render('form.twig', $data);
    }
})->value('url', '');