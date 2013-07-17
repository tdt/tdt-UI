<?php

/**
 * TODO: class description
 * @copyright (C) 2013 by OKFN Belgium
 * @license AGPLv3
 * @author Leen De Baets
 * @author Jeppe Knockaert
 * @author Nicolas Dierck
 */

namespace tdt\ui\controllers;

use tdt\core\utility\RequestURI;
use tdt\exceptions\TDTException;
use app\core\Config;

class UiResourceController extends \tdt\core\controllers\AController {

    public function __construct() {
        parent::__construct();
    }

    /**
     * When HEAD is received, send the request to index.php from tdt-UI
     */
    function HEAD($matches) {
        $this->GET($matches);
    }

    /**
     * When GET is received, send the request to index.php from tdt-UI
     */
    function GET($matches){
            $data['bootstrap_js'] = "";
            $bootstrap_js = @file_get_contents(__DIR__."/../../../../includes/js/bootstrap.min.js");
            if($bootstrap_js){
                $data['bootstrap_js'] .= $bootstrap_js;
            }
            $data['jquery_js'] = "";
            $jquery_js = @file_get_contents(__DIR__."/../../../../includes/js/jquery.js");
            if($jquery_js){
                $data['jquery_js'] .= $jquery_js;
            }
            $data['bootstrap_css'] = "";
            $bootstrap_css = @file_get_contents(__DIR__."/../../../../includes/css/bootstrap.min.css");
            if($bootstrap_css){
                $data['bootstrap_css'] .= $bootstrap_css;
            }
            $bootstrap_css = @file_get_contents(__DIR__."/../../../../includes/css/bootstrap-responsive.min.css");
            if($bootstrap_css){
                $data['bootstrap_css'] .= $bootstrap_css;
            }
            $data['main_css'] = "";
            $main_css = @file_get_contents(__DIR__."/../../../../includes/css/main.css");
            if($main_css){
                $data['main_css'] .= $main_css;
            }
            $data['logo'] = "";
            $logo = @file_get_contents(__DIR__."/../../../../includes/img/logo.png");
            if ($logo){
                $data['logo'] .= base64_encode($logo);
            }
            $data['remove'] = "";
            $remove = @file_get_contents(__DIR__."/../../../../includes/img/remove.png");
            if ($remove){
                $data['remove'] .= base64_encode($remove);
            }
            $data['edit'] = "";
            $edit = @file_get_contents(__DIR__."/../../../../includes/img/edit.png");
            if ($edit){
                $data['edit'] .= base64_encode($edit);
            }
            include(__DIR__."/../../../../public/index.php");
    }

    /**
     * When POST is received, send the request to index.php from tdt-UI
     */
    function POST($matches){
        $this->GET($matches);
    }

    /**
     * PUT is not supported on the user interface
     */
    function PUT($matches) {
        //get the current URL
        $ru = RequestURI::getInstance(Config::getConfigArray());
        $pageURL = $ru->getURI();
        $exception_config = array();
        $exception_config["log_dir"] = Config::get("general", "logging", "path");
        $exception_config["url"] = Config::get("general", "hostname") . Config::get("general", "subdir") . "error";
        throw new TDTException(450, array("PUT", $pageURL), $exception_config);
    }

    /**
     * DELETE is not supported on the user interface
     */
    function DELETE($matches) {
        //get the current URL
        $ru = RequestURI::getInstance(Config::getConfigArray());
        $pageURL = $ru->getURI();
        $exception_config = array();
        $exception_config["log_dir"] = Config::get("general", "logging", "path");
        $exception_config["url"] = Config::get("general", "hostname") . Config::get("general", "subdir") . "error";
        throw new TDTException(450, array("DELETE", $pageURL), $exception_config);
    }

    /**
     * PATCH is not supported on the user interface
     */
    public function PATCH($matches) {
        //get the current URL
        $ru = RequestURI::getInstance(Config::getConfigArray());
        $pageURL = $ru->getURI();
        $exception_config = array();
        $exception_config["log_dir"] = Config::get("general", "logging", "path");
        $exception_config["url"] = Config::get("general", "hostname") . Config::get("general", "subdir") . "error";
        throw new TDTException(450, array("PATCH", $pageURL), $exception_config);
    }

}
