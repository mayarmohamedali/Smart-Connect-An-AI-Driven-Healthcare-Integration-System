<?php

/**
 * LandingController
 * Serves the public landing page — no authentication required.
 * Route: / (default) or /?url=landing/index
 */
class LandingController {

    public function index(): void {
        require_once ROOT . '/app/views/landing_page.php';
    }
}