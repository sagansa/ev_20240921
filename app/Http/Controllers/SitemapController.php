<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;

class SitemapController extends Controller
{
    public function index()
    {
        $urls = [
            [
                'loc' => url('/'),
                'lastmod' => date('c'),
                'changefreq' => 'daily',
                'priority' => '1.0'
            ],
            [
                'loc' => route('map'),
                'lastmod' => date('c'),
                'changefreq' => 'daily',
                'priority' => '0.9'
            ],
            [
                'loc' => route('pln-map'),
                'lastmod' => date('c'),
                'changefreq' => 'daily',
                'priority' => '0.9'
            ],
            [
                'loc' => route('providers'),
                'lastmod' => date('c'),
                'changefreq' => 'weekly',
                'priority' => '0.8'
            ],
            [
                'loc' => route('chargers'),
                'lastmod' => date('c'),
                'changefreq' => 'weekly',
                'priority' => '0.8'
            ],
            [
                'loc' => route('youtube.index'),
                'lastmod' => date('c'),
                'changefreq' => 'weekly',
                'priority' => '0.7'
            ],
            [
                'loc' => route('find.nearby.chargers.page'),
                'lastmod' => date('c'),
                'changefreq' => 'monthly',
                'priority' => '0.7'
            ],
            [
                'loc' => route('contact'),
                'lastmod' => date('c'),
                'changefreq' => 'monthly',
                'priority' => '0.6'
            ],
        ];

        return response()
            ->view('sitemap.index', ['urls' => $urls])
            ->header('Content-Type', 'application/xml');
    }
}