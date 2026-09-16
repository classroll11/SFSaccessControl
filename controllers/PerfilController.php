<?php
/**
 * ============================================================================
 * PROYECTO: SFS ACCESS CONTROL - I.E. JORGE ROBLEDO
 * ARCHIVO: controllers/PerfilController.php
 * DESCRIPCIÓN: Controlador para la vista y gestión del perfil de usuario.
 * ============================================================================
 */

require_once __DIR__ . '/../config/auth.php';

class PerfilController {

    public function index(): void {
        requireLogin();
        require_once __DIR__ . '/../views/perfil/index.php';
    }
}
