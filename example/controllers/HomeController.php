<?php

use PaigeJulianne\NanoMVC\Controller;
use PaigeJulianne\NanoMVC\Request;
use PaigeJulianne\NanoMVC\Response;

class HomeController extends Controller
{
    /**
     * Display the home page
     */
    public function index(Request $request): Response
    {
        return $this->view('home', [
            'title' => 'Welcome to NanoMVC',
            'message' => 'A lightweight MVC framework for PHP',
        ]);
    }

    /**
     * Display the about page
     */
    public function about(Request $request): Response
    {
        return $this->view('about', [
            'title' => 'About NanoMVC',
        ]);
    }
}
