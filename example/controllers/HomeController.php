<?php

use PaigeJulianne\PicoMVC\Controller;
use PaigeJulianne\PicoMVC\Request;
use PaigeJulianne\PicoMVC\Response;

class HomeController extends Controller
{
    /**
     * Display the home page
     */
    public function index(Request $request): Response
    {
        return $this->view('home', [
            'title' => 'Welcome to PicoMVC',
            'message' => 'A lightweight MVC framework for PHP',
        ]);
    }

    /**
     * Display the about page
     */
    public function about(Request $request): Response
    {
        return $this->view('about', [
            'title' => 'About PicoMVC',
        ]);
    }
}
