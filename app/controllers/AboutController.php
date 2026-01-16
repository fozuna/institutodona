<?php
namespace App\Controllers;

use App\Core\BaseController;

class AboutController extends BaseController
{
    public function index(): void
    {
        $this->requireLogin();
        $version = \App\Core\AppVersion::get();
        $this->render('about/index', ['version' => $version]);
    }
}
