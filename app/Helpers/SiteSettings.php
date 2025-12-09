<?php

if (!function_exists('settings')) {
    /**
     * Get site setting value
     */
    function settings(string $key, mixed $default = null): mixed
    {
        return App\Models\SiteSetting::get($key, $default);
    }
}

if (!function_exists('site_name')) {
    /**
     * Get site name setting
     */
    function site_name(): string
    {
        return settings('site_name', config('app.name', 'TallCMS'));
    }
}

if (!function_exists('site_tagline')) {
    /**
     * Get site tagline setting
     */
    function site_tagline(): string
    {
        return settings('site_tagline', 'A modern CMS built with TALL stack');
    }
}

if (!function_exists('contact_email')) {
    /**
     * Get contact email setting
     */
    function contact_email(): string
    {
        return settings('contact_email', config('mail.from.address', 'hello@example.com'));
    }
}