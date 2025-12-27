<?php

use PaigeJulianne\PicoMVC\Controller;
use PaigeJulianne\PicoMVC\Request;
use PaigeJulianne\PicoMVC\Response;

class HomeController extends Controller
{
    public function index(Request $request): Response
    {
        return $this->view('home', [
            'title' => 'Welcome to PicoMVC',
            'message' => 'Smarty Templating Example',
            'baseUrl' => rtrim(dirname($_SERVER['SCRIPT_NAME']), '/'),
        ]);
    }

    public function about(Request $request): Response
    {
        return $this->view('about', [
            'title' => 'About',
            'baseUrl' => rtrim(dirname($_SERVER['SCRIPT_NAME']), '/'),
        ]);
    }
}
