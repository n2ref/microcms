<?php
namespace Microcms;

require_once 'core/classes/Common.php';


/**
 * Class Controller
 * @package Microcms
 */
class Controller  extends Common{

    /**
     * @return string
     */
    public function dispatch() {

        header('Content-Type: text/html; charset=utf-8');

        if (DOC_PATH == $_SERVER['REQUEST_URI'] ||
            DOC_PATH == rtrim($_SERVER['REQUEST_URI'], '/') ||
            preg_match('~^/index.html([^?]*?)(?:/|)(?:\?|$)~', $_SERVER['REQUEST_URI'])
        ) {
            return $this->pageIndex();
        }


        http_response_code(404);
        return '';
    }


    /**
     * @return string
     */
    public function pageIndex() {

        $path_file = DOC_ROOT . '/index.html';

        return file_get_contents($path_file);
    }
}