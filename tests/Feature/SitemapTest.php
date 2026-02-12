<?php

test('sitemap returns 200 and valid xml', function () {
    $response = $this->get(route('sitemap'));

    $response->assertSuccessful();
    $response->assertHeader('Content-Type', 'application/xml');
    $content = $response->getContent();
    expect($content)->toContain('<?xml version="1.0"');
    expect($content)->toContain('<urlset xmlns="http://www.sitemaps.org/schemas/sitemap/0.9">');
});

test('sitemap includes homepage', function () {
    $baseUrl = rtrim(config('app.url'), '/');

    $response = $this->get(route('sitemap'));

    $response->assertSuccessful();
    expect($response->getContent())->toContain('<loc>'.$baseUrl.'/</loc>');
});

test('robots.txt returns 200 and references sitemap', function () {
    $response = $this->get('/robots.txt');

    $response->assertSuccessful();
    expect(str_contains($response->headers->get('Content-Type', ''), 'text/plain'))->toBeTrue();
    expect($response->getContent())->toContain('Sitemap:');
    expect($response->getContent())->toContain('sitemap.xml');
});
