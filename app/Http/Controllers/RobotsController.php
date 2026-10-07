<?php

namespace App\Http\Controllers;

use Illuminate\Http\Response;

class RobotsController extends Controller
{
    /**
     * Chemins exclus de l'exploration par les robots.
     */
    private const DISALLOWED = ['/admin', '/profile', '/login', '/register', '/dashboard'];

    /**
     * Génère robots.txt avec l'URL absolue du sitemap de l'environnement
     * courant (jamais de domaine codé en dur).
     */
    public function __invoke(): Response
    {
        $lines = ['User-agent: *'];

        foreach (self::DISALLOWED as $path) {
            $lines[] = 'Disallow: '.$path;
        }

        $lines[] = '';
        $lines[] = 'Sitemap: '.route('sitemap');

        return response(implode("\n", $lines)."\n", 200)->header('Content-Type', 'text/plain');
    }
}
