<?php

use PaigeJulianne\NanoMVC\Controller;
use PaigeJulianne\NanoMVC\Request;
use PaigeJulianne\NanoMVC\Response;

class HomeController extends Controller
{
    public function index(Request $request): Response
    {
        return $this->view('home', [
            'title' => 'Welcome to NanoMVC',
            'message' => 'Blade Templating Example',
        ]);
    }

    public function about(Request $request): Response
    {
        return $this->view('about', [
            'title' => 'About',
        ]);
    }
}
