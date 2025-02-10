<?php
/**
 * Plugin Name: Variables Dinámicas
 * Plugin URI: https://ofertas.energiaelcorteingles.es
 * Description: Plugin para configurar y mostrar variables dinámicas basadas en parámetros de URL.
 * Version: 1.1.2
 * Author: Chris Escalante
 * Author URI: https://ofertas.energiaelcorteingles.es
 */

// Evitar el acceso directo al archivo
if (!defined('ABSPATH')) {
    exit; // Salir si se accede directamente
}

// Include mfp-functions.php, use require_once to stop the script if mfp-functions.php is not found
require_once plugin_dir_path(__FILE__) . 'includes/vd-functions.php';