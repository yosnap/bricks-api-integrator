<?php
/**
 * Funciones principales para Bricks API Integrator
 */

if (!defined('ABSPATH')) {
    exit; // Evitar el acceso directo
}

// Incluir archivos de componentes
require_once BRICKS_API_INTEGRATOR_PATH . 'includes/sources.php';
require_once BRICKS_API_INTEGRATOR_PATH . 'includes/launcher.php';

/**
 * Callback para la página de API Sources
 */
function bricks_api_integrator_sources_page() {
    // Esta función debe estar definida en sources.php
    if (function_exists('render_api_sources_page')) {
        render_api_sources_page();
    } else {
        echo '<div class="wrap"><h1>API Sources</h1><p>Error: No se pudo cargar la página de API Sources.</p></div>';
    }
}

/**
 * Callback para la página de API Launcher
 */
function bricks_api_integrator_launcher_page() {
    // Esta función debe estar definida en launcher.php
    if (function_exists('render_api_launcher_page')) {
        render_api_launcher_page();
    } else {
        echo '<div class="wrap"><h1>API Launcher</h1><p>Error: No se pudo cargar la página de API Launcher.</p></div>';
    }
}
