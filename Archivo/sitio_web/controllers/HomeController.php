<?php
/**
 * ============================================================================
 * PROYECTO: SFS ACCESS CONTROL - I.E. JORGE ROBLEDO
 * ARCHIVO: controllers/HomeController.php
 * DESCRIPCIÓN: Controlador para las páginas públicas institucionales (Inicio, Nosotros, Blog).
 * ============================================================================
 */

require_once __DIR__ . '/../config/auth.php';

class HomeController {

    /**
     * Muestra la página principal / Landing.
     */
    public function index(): void {
        require_once __DIR__ . '/../views/home/index.php';
    }

    /**
     * Muestra la página de información institucional Nosotros.
     */
    public function nosotros(): void {
        require_once __DIR__ . '/../views/pages/nosotros.php';
    }

    /**
     * Muestra la página de Blog de noticias y novedades.
     */
    public function blog(): void {
        require_once __DIR__ . '/../views/pages/blog.php';
    }
}
