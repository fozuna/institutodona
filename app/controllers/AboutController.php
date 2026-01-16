<?php
namespace App\Controllers;

use App\Core\BaseController;

class AboutController extends BaseController
{
    public function index(): void
    {
        $this->requireLogin();
        $version = \App\Core\AppVersion::get();
        $changelog = [];
        try {
            $cmd = 'git log -n 12 --pretty=format:"%h|%ad|%s" --date=short';
            $out = @shell_exec($cmd);
            if ($out) {
                $lines = explode("\n", trim($out));
                foreach ($lines as $ln) {
                    $parts = explode('|', $ln, 3);
                    if (count($parts) === 3) {
                        $changelog[] = ['hash' => $parts[0], 'date' => $parts[1], 'subject' => $parts[2]];
                    }
                }
            }
        } catch (\Throwable $e) {}
        $this->render('about/index', ['version' => $version, 'changelog' => $changelog]);
    }
}
