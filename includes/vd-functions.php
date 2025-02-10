<?php
/**
 * Inicializar el plugin
 */
function vd_init() {
    // Registrar el shortcode
    add_shortcode('vd_variable', 'vd_handle_shortcode');
}

add_action('plugins_loaded', 'vd_init');

/**
 * Manejar el shortcode [vd_variable]
 *
 * @param array $atts Atributos del shortcode
 * @return string Valor de la variable configurada
 */
function vd_handle_shortcode($atts) {
    $atts = shortcode_atts(array(
        'nombre' => '',
    ), $atts, 'vd_variable');

    // Aquí agregar lógica para recuperar y devolver el valor de la variable
    return 'Valor de ' . esc_html($atts['nombre']);
}

/**
 * Activación del plugin
 */
function vd_activate() {
    error_log('Activando el plugin.');
    exit;
    require_once(ABSPATH . 'wp-admin/includes/upgrade.php');
    global $wpdb;
    $charset_collate = $wpdb->get_charset_collate();
    $tabla_variables = $wpdb->base_prefix . 'vd_variables';

    add_option('vd_borrar_datos_al_desactivar', 'no'); // Valor predeterminado es 'no'

    $sql = "CREATE TABLE $tabla_variables (
        id mediumint(9) NOT NULL AUTO_INCREMENT,
        blog_id bigint(20) NOT NULL,
        nombre varchar(255) NOT NULL,
        valor varchar(255) DEFAULT '' NOT NULL,
        condiciones text NOT NULL,
        PRIMARY KEY  (id),
        KEY blog_id (blog_id)
    ) $charset_collate;";

    dbDelta($sql);

    // Añadir error logging
    if ($wpdb->last_error !== '') {
        error_log('dbDelta error:' . $wpdb->last_error);
    }
}

register_activation_hook(__FILE__, 'vd_activate');

/**
 * Desactivación del plugin
 */
function vd_deactivate() {
    error_log('Desactivando el plugin.');
    exit;
    if (get_option('vd_borrar_datos_al_desactivar') === 'yes') {
        global $wpdb;
        $tabla_variables = $wpdb->base_prefix . 'vd_variables';
        $sql = "DROP TABLE IF EXISTS $tabla_variables;";
        $wpdb->query($sql);
    }
}

register_deactivation_hook(__FILE__, 'vd_deactivate');

function vd_uninstall() {
    delete_option('vd_borrar_datos_al_desactivar');
    // Cualquier otra limpieza...
}

register_uninstall_hook(__FILE__, 'vd_uninstall');


function vd_admin_menus() {
    // Página principal del plugin
    add_menu_page('Gestión de Variables Dinámicas', 'Variables Dinámicas', 'manage_options', 'vd_main', 'vd_manage_variables', 'dashicons-shortcode');

    // Crear nueva variable
    add_submenu_page('vd_main', 'Añadir Nueva Variable', 'Añadir Nueva Variable', 'manage_options', 'vd_add_variable', 'vd_add_variable_page');

    // Asegúrate de no mostrar en el menú si no deseas un acceso directo desde el menú lateral
    add_submenu_page('vd_hidden_parent_page', 'Editar Variable', '', 'manage_options', 'vd_edit_variable', 'vd_edit_variable_page');

    // Submenú para configuraciones generales
    add_submenu_page('vd_main', 'Configuración General', 'Configuración General', 'manage_options', 'vd_settings', 'vd_general_settings');
}

add_action('admin_menu', 'vd_admin_menus');


function vd_manage_variables() {
    ?>
    <div class="container mx-auto my-4 border-solid border-gray-400 rounded border shadow-sm w-full">
        <div class='px-3 py-8 bg-gray-300 border-solid border-gray-400 border-b'>
            <h1 class='text-3xl'>Gestionar Variables</h1>
        </div>
        <form class="w-full px-3 py-6" method="post" action="<?php echo admin_url('admin-post.php'); ?>">
            <input type="hidden" name="action" value="vd_delete_selected">
            <?php wp_nonce_field('vd_delete_selected_nonce'); ?>
            <br>
            <!-- Botón para agregar una nueva variable -->
            <a href="<?php echo admin_url('admin.php?page=vd_add_variable'); ?>" class="page-title-action bg-blue-500 hover:bg-blue-700 text-white hover:text-white font-bold py-2 px-4 rounded">Añadir Nueva Variable</a>
            <br><br>
            <!-- Tabla para mostrar variables existentes -->
            <?php vd_display_variables_table(); ?>
            <!-- Botón para eliminar variables seleccionadas -->
            <br>
            <input type="submit" name="delete_selected" class="action bg-red-500 hover:bg-red-800 text-white font-bold py-2 px-4 rounded cursor-pointer" value="Eliminar Seleccionadas">
        </form>
    </div>
    <?php
}

function vd_get_variables() {
    global $wpdb;
    $tabla_variables = $wpdb->base_prefix . 'vd_variables';
    
    // Asegurarse de obtener las variables para el sitio actual en Multisite
    $blog_id = get_current_blog_id();
    $sql = $wpdb->prepare("SELECT * FROM $tabla_variables WHERE blog_id = %d", $blog_id);
    
    return $wpdb->get_results($sql);
}

function vd_display_variables_table() {
    // Función simplificada para mostrar las variables
    echo "<div class='container mx-auto'><table class='table-responsive w-full rounded striped'><thead><tr class='bg-grey-darkest text-white'><th class='text-white border w-1/7 px-4 py-2 text-center'>Seleccionar</th><th class='text-white border w-1/7 px-4 py-2'>ID</th><th class='text-white border w-1/3 px-4 py-2'>Nombre</th><th class='text-white border w-1/2 px-4 py-2'>Shortcode</th><th class='text-white border w-1/5 px-4 py-2 text-center'>Acciones</th></tr></thead><tbody>";
    // Suponiendo que hay una función para obtener las variables del sitio actual
    $variables = vd_get_variables(); // Implementar esta función
    foreach ($variables as $var) {
        $urlborrar = wp_nonce_url(admin_url('admin-post.php?action=vd_delete_variable&var_id=' . $var->id), 'vd_delete_variable_nonce', 'vd_nonce');
        echo "<tr><td class='border px-4 py-2 text-center'><input type='checkbox' class='leading-tight' name='selected_vars[]' value='{$var->id}'></td><td class='border px-4 py-2'>{$var->id}</td><td class='border px-4 py-2'>{$var->nombre}</td><td class='border px-4 py-2'>[vd_variable id=\"{$var->id}\"]</td>";
        echo "<td class='border px-4 py-2 text-center'><a href='admin.php?page=vd_edit_variable&var_id={$var->id}' class='bg-teal-300 hover:bg-teal-600 cursor-pointer rounded px-2 py-1 mx-2 text-white hover:text-white'><i class='fas fa-edit'></i></a><a href='{$urlborrar}' class='bg-teal-300 hover:bg-teal-600 cursor-pointer rounded px-2 py-1 mx-2 text-red-500 hover:text-red-500'><i class='fas fa-trash'></i></a></td></tr>";
    }
    echo "</tbody></table></div>";
}

function vd_add_variable_page() {
    ?>
    <div class="container mx-auto my-4 border-solid border-gray-400 rounded border shadow-sm w-full">
        <div class='px-3 py-8 bg-gray-300 border-solid border-gray-400 border-b'>
            <h1 class='text-3xl'>Añadir Nueva Variable</h1>
        </div>
        <form class="w-full px-3 py-6" method="post" action="<?php echo esc_url(admin_url('admin-post.php')); ?>">
            <input type="hidden" name="action" value="vd_save_variable">
            <?php wp_nonce_field('vd_add_variable_nonce'); ?>
            <div class='flex flex-1 flex-col md:flex-row lg:flex-row'>
                <div class="w-full mr-3">
                    <label for="nombre" class="block uppercase tracking-wide text-sm font-bold mb-3 text-blue-950">Nombre</label>
                    <input type="text" name="nombre" class='appearance-none block w-full bg-grey-200 text-grey-darker border border-grey-200 rounded py-3 px-4 h-12 leading-tight focus:outline-none focus:bg-white focus:border-grey' placeholder="Nombre de la variable" required />
                </div>
                <div class="w-full">
                    <label for="valor_por_defecto" class="block uppercase tracking-wide text-sm font-bold mb-3 text-blue-950">Valor por Defecto</label>
                    <input type="text" name="valor_por_defecto" class='appearance-none block w-full bg-grey-200 text-grey-darker border border-grey-200 rounded py-3 px-4 h-12 leading-tight focus:outline-none focus:bg-white focus:border-grey' placeholder="Valor por Defecto" required />
                </div>
            </div>
            <div class='m-2'>
                <label class='block uppercase tracking-wide text-sm font-bold mb-1 text-blue-950 pt-10'>Valores Condicionales</label>
                <div id="valores_condicionales_container" class='pt-6'></div>
                <button type="button" class="bg-green-500 hover:bg-green-700 text-white hover:text-white font-bold py-2 px-4 rounded h-12" onclick="agregarValorCondicion();">Añadir Valor Condicional</button>
            </div>
            <div class='m-2 mt-12 text-center'>
                <input type='submit' name='submit' id='submit' class='bg-blue-500 hover:bg-blue-800 text-white font-bold py-2 px-4 rounded cursor-pointer mx-auto w-full md:w-1/3 h-12' value='Guardar Variable'>
            </div>
        </form>
    </div>
    <script>
let valorIndex = 0;

function agregarValorCondicion() {
    const container = document.getElementById('valores_condicionales_container');
    const valorDiv = document.createElement('div');
    valorDiv.className = 'valor-condicional bg-gray-300 rounded border shadow p-3 mb-6';
    valorDiv.innerHTML = `
        <div class='flex flex-1 flex-col md:flex-row lg:flex-row'>
        <input type="text" name="valores[${valorIndex}]" placeholder="Valor" class='appearance-none block w-full bg-grey-200 text-grey-darker border border-grey-200 rounded py-3 px-4 mr-3 h-10 leading-tight focus:outline-none focus:bg-white focus:border-grey' required />
        <select name="logica_condiciones[${valorIndex}]" class="mr-3 h-10">
            <option value="AND">AND</option>
            <option value="OR">OR</option>
        </select>
        <button type="button" class="bg-green-500 hover:bg-green-700 text-white hover:text-white font-bold py-2 px-4 h-10 rounded w-1/3" onclick="agregarCondicion(${valorIndex});">Añadir Condición</button>
        </div>
        <div class="condiciones-container w-full p-2" id="condiciones-container-${valorIndex}"></div>
        <div class="w-full my-2 justify-end">
            <button type="button" class="bg-red-500 hover:bg-red-700 text-white hover:text-white font-bold py-2 px-4 h-10 rounded pull-right" onclick="this.parentElement.parentElement.remove();">Eliminar Valor Condicional</button>
        </div>
        </div>
    `;
    container.appendChild(valorDiv);
    valorIndex++;
}

function agregarCondicion(index) {
    const condicionesContainer = document.getElementById(`condiciones-container-${index}`);
    const condicionDiv = document.createElement('div');
    condicionDiv.className = 'condicion';
    condicionDiv.innerHTML = `
        <div class='flex flex-1 flex-col md:flex-row lg:flex-row py-2'>
        <input type="text" name="parametros[${index}][]" placeholder="Parámetro" class="appearance-none block w-full bg-grey-200 text-grey-darker border border-grey-200 rounded py-3 px-4 mr-3 h-10 leading-tight focus:outline-none focus:bg-white focus:border-grey" required />
        <select name="tipos[${index}][]" class="mr-3 h-10">
            <option value="exacto">Exacto</option>
            <option value="contiene">Contiene</option>
            <option value="comienza_con">Comienza con</option>
            <option value="no_igual">No es igual</option>
        </select>
        <input type="text" name="valores_condiciones[${index}][]" placeholder="Valor para Condición" class="appearance-none block w-full bg-grey-200 text-grey-darker border border-grey-200 rounded py-3 px-4 mr-3 h-10 leading-tight focus:outline-none focus:bg-white focus:border-grey" />
        <button type="button" class="bg-red-500 hover:bg-red-700 text-white hover:text-white font-bold py-2 px-4 h-10 rounded pull-right w-1/3" onclick="this.parentElement.parentElement.remove();">Eliminar Condición</button>
        </div>
    `;
    condicionesContainer.appendChild(condicionDiv);
}
</script>

    <?php
}

function vd_register_save_action() {
    add_action('admin_post_vd_save_variable', 'vd_save_variable_handler');
}

add_action('admin_init', 'vd_register_save_action');

function vd_save_variable_handler() {

    if (!current_user_can('manage_options')) {
        wp_die('No tienes suficientes permisos para acceder a esta página.');
    }

    $var_id = isset($_POST['var_id']) ? intval($_POST['var_id']) : 0;

    if($var_id > 0) {
        check_admin_referer('vd_edit_variable_nonce');
    } else {
        check_admin_referer('vd_add_variable_nonce');
    }


    $nombre = sanitize_text_field($_POST['nombre']);
    $valor_por_defecto = sanitize_text_field($_POST['valor_por_defecto']);
    $valores = $_POST['valores'] ?? [];
    $logica_condiciones = $_POST['logica_condiciones'] ?? [];

    $valores_condicionales = [];
    foreach ($valores as $index => $valor) {
        $parametros = $_POST['parametros'][$index] ?? [];
        $tipos = $_POST['tipos'][$index] ?? [];
        $valores_condiciones = $_POST['valores_condiciones'][$index] ?? [];
        
        $condiciones = [];
        foreach ($parametros as $i => $parametro) {
            if (isset($tipos[$i]) && isset($valores_condiciones[$i])) {
                $condiciones[] = [
                    'parametro' => sanitize_text_field($parametro),
                    'tipo' => sanitize_text_field($tipos[$i]),
                    'valor' => sanitize_text_field($valores_condiciones[$i])
                ];
            }
        }
        
        $valores_condicionales[] = [
            'valor' => sanitize_text_field($valor),
            'logica' => sanitize_text_field($logica_condiciones[$index] ?? 'AND'),  // Default to AND if not specified
            'condiciones' => $condiciones
        ];
    }

    $variable_json = json_encode($valores_condicionales);
    $blog_id = get_current_blog_id();

    global $wpdb;
    $tabla_variables = $wpdb->base_prefix . 'vd_variables';

    // Insertar o actualizar en la base de datos
    if ($var_id > 0) {
        // Actualizar la variable existente
        $inserted = $wpdb->update($tabla_variables, [
            'blog_id' => $blog_id,
            'nombre' => $nombre,
            'valor' => $valor_por_defecto,
            'condiciones' => $variable_json
        ], ['id' => $var_id], ['%d', '%s', '%s', '%s'], ['%d']);
    } else {
        // Crear una nueva variable
        $inserted = $wpdb->insert($tabla_variables, [
            'blog_id' => $blog_id,
            'nombre' => $nombre,
            'valor' => $valor_por_defecto,
            'condiciones' => $variable_json
        ], ['%d', '%s', '%s', '%s']);
    }

    $wpdb->show_errors();
    // Redirigir o manejar el post-procesamiento
    if ($inserted) {
        // Redirigir a algún lugar o mostrar mensaje de éxito
        echo "Inserción exitosa, ID: " . $wpdb->insert_id;
        wp_redirect(admin_url('admin.php?page=vd_main&success=1'));
        exit;
    } else {
        // Manejo de errores, mostrar mensaje de error
        echo "Error en la inserción: " . $wpdb->last_error;
        echo "SQL ejecutado: " . $wpdb->last_query;
        wp_redirect(admin_url('admin.php?page=vd_main&error=1'));
        exit;
    }
}



// Eliminar variable individual
add_action('admin_post_vd_delete_variable', 'vd_delete_variable');

function vd_delete_variable() {
    if (!isset($_GET['var_id']) || !check_admin_referer('vd_delete_variable_nonce', 'vd_nonce')) {
        wp_die('No tienes permisos suficientes o la verificación ha fallado.');
    }

    global $wpdb;
    $tabla_variables = $wpdb->base_prefix . 'vd_variables';
    $id = intval($_GET['var_id']);

    $wpdb->delete($tabla_variables, ['id' => $id], ['%d']);

    wp_redirect(admin_url('admin.php?page=vd_main'));
    exit;
}


// Eliminar variables seleccionadas desde la página de gestión
add_action('admin_post_vd_delete_selected', 'vd_delete_selected_variables');
function vd_delete_selected_variables() {
    if (!current_user_can('manage_options') || !isset($_POST['selected_vars']) || !check_admin_referer('vd_delete_selected_nonce')) {
        wp_die('No tienes permisos suficientes o la verificación ha fallado.');
    }

    global $wpdb;
    $tabla_variables = $wpdb->base_prefix . 'vd_variables';
    $variable_ids = array_map('intval', $_POST['selected_vars']);

    foreach ($variable_ids as $id) {
        $wpdb->delete($tabla_variables, ['id' => $id], ['%d']);
    }

    wp_redirect(admin_url('admin.php?page=vd_main'));
    exit;
}


function vd_general_settings() {
    // Aquí iría el formulario de configuraciones generales, incluyendo la opción de borrado al desactivar
    ?>
    <div class="container mx-auto my-4 border-solid border-gray-400 rounded border shadow-sm w-full">
        <div class='px-3 py-8 bg-gray-300 border-solid border-gray-400 border-b'>
            <h1 class='text-3xl'>Configuración General</h1>
        </div> 
        <form class="w-full px-3 py-6" method="post" action="options.php">
        <?php settings_fields('vd_settings_group'); ?>
        <div class='m-2 mt-12'>
            <?php do_settings_sections('vd_settings'); ?>
        </div>
        <div class='m-2 mt-12 text-center'>
            <input type='submit' name='submit' id='submit' class='bg-blue-500 hover:bg-blue-800 text-white font-bold py-2 px-4 rounded cursor-pointer mx-auto w-full md:w-1/3 h-12' value='Guardar configuración'>
        </div>
        </form>
    </div>  
    <?php
}


function vd_register_settings() {
    register_setting('vd_settings_group', 'vd_delete_data_on_uninstall');
    add_settings_section('vd_general_settings_section', '', '', 'vd_settings');
    add_settings_field('vd_delete_data_field', 'Eliminar datos al desinstalar', 'vd_delete_data_field_render', 'vd_settings', 'vd_general_settings_section');
}



function vd_delete_data_field_render() {
    $option = get_option('vd_delete_data_on_uninstall');
    echo '<input type="checkbox" name="vd_delete_data_on_uninstall" value="1" ' . checked(1, $option, false) . '>';
}

add_action('admin_init', 'vd_register_settings');

function vd_init_shortcodes() {
    add_shortcode('vd_variable', 'vd_variable_shortcode');
}

add_action('init', 'vd_init_shortcodes');

function vd_variable_shortcode($atts) {
    $atts = shortcode_atts(['id' => ''], $atts, 'vd_variable');
    return vd_get_variable_value($atts['id']);
}
add_action('init', function() {
    if (!function_exists('vd_variable_fget')) {
        function vd_variable_fget($id) {
            return vd_get_variable_value($id);
        }
    }
});

function vd_get_variable_value($id) {
    global $wpdb;
    $blog_id = get_current_blog_id();
    $tabla_variables = $wpdb->base_prefix . 'vd_variables';
    $variable = $wpdb->get_row($wpdb->prepare("SELECT * FROM $tabla_variables WHERE id = %d AND blog_id	 = %d", $id, $blog_id));

    if (!$variable) {
        error_log("No se encontró la variable con ID: $id");
        return 'Variable no encontrada';  // Para propósitos de depuración, puedes cambiar esto más tarde.
    }

    // Decodificar el JSON de condiciones
    $data = json_decode($variable->condiciones, true);
    if (json_last_error() !== JSON_ERROR_NONE) {
        error_log("Error decodificando JSON: " . json_last_error_msg());
        return 'Error de formato JSON';  // Para depuración
    }

    $valorPorDefecto = $variable->valor ?? 'Valor por defecto no definido';
    $valoresCondicionales = $data ?? [];


    foreach ($valoresCondicionales as $valorCondicional) {
        if (vd_evaluar_condiciones($valorCondicional['condiciones'], $valorCondicional['logica'])) {
            return $valorCondicional['valor'];  // Retorna el valor si las condiciones se cumplen
        }
    }

    return $valorPorDefecto;  // Retorna el valor por defecto si ninguna condición se cumple
}

function vd_evaluar_condiciones($condiciones, $logica) {
    $resultado = ($logica === 'AND') ? true : false;

    foreach ($condiciones as $condicion) {
        $actual = $_GET[$condicion['parametro']] ?? 'no definido';
        $cumple = false;

        switch ($condicion['tipo']) {
            case 'exacto':
                $cumple = ($actual === $condicion['valor']);
                break;
            case 'contiene':
                $cumple = is_string($actual) && strpos($actual, $condicion['valor']) !== false;
                break;
            case 'comienza_con':
                $cumple = is_string($actual) && strpos($actual, $condicion['valor']) === 0;
                break;
            case 'no_igual':
                $cumple = ($actual !== $condicion['valor']);
                break;
        }

        error_log("Evaluar: actual=$actual, esperado={$condicion['valor']}, resultado=" . ($cumple ? 'true' : 'false'));

        if ($logica === 'AND') {
            $resultado = $resultado && $cumple;
        } else {
            $resultado = $resultado || $cumple;
        }
    }

    return $resultado;
}

function vd_edit_variable_page() {
    if (!isset($_GET['var_id']) || !current_user_can('manage_options')) {
        wp_die('No tienes permisos suficientes para editar esta variable.');
    }

    $var_id = intval($_GET['var_id']);
    $blog_id = get_current_blog_id();
    global $wpdb;
    $tabla_variables = $wpdb->base_prefix . 'vd_variables';
    $variable = $wpdb->get_row($wpdb->prepare("SELECT * FROM $tabla_variables WHERE id = %d AND blog_id = %d", $var_id, $blog_id));

    if (!$variable) {
        wp_die('La variable solicitada no existe.');
    }

    $data = json_decode($variable->condiciones, true);
    $valorPorDefecto = $variable->valor;
    $valoresCondicionales = $data;
    $lastindex = 0;

    ?>
    
    <div class="container mx-auto my-4 border-solid border-gray-400 rounded border shadow-sm w-full">
        <div class='px-3 py-8 bg-gray-300 border-solid border-gray-400 border-b'>
            <h1 class='text-3xl'>Editar Variable</h1>
        </div>
        <form class="w-full px-3 py-6" method="post" action="<?php echo esc_url(admin_url('admin-post.php')); ?>">
            <input type="hidden" name="action" value="vd_save_variable">
            <input type="hidden" name="var_id" value="<?php echo esc_attr($var_id); ?>">
            <?php wp_nonce_field('vd_edit_variable_nonce'); ?>
            <div class='flex flex-1 flex-col md:flex-row lg:flex-row'>
                <div class="w-full mr-3">
                    <label for="nombre" class="block uppercase tracking-wide text-sm font-bold mb-3 text-blue-950">Nombre</label>
                    <input type="text" name="nombre" class='appearance-none block w-full bg-grey-200 text-grey-darker border border-grey-200 rounded py-3 px-4 h-12 leading-tight focus:outline-none focus:bg-white focus:border-grey' placeholder="Nombre de la variable" value="<?php echo esc_attr($variable->nombre); ?>" required />
                </div>
                <div class="w-full">
                    <label for="valor_por_defecto" class="block uppercase tracking-wide text-sm font-bold mb-3 text-blue-950">Valor por Defecto</label>
                    <input type="text" name="valor_por_defecto" class='appearance-none block w-full bg-grey-200 text-grey-darker border border-grey-200 rounded py-3 px-4 h-12 leading-tight focus:outline-none focus:bg-white focus:border-grey' placeholder="Valor por Defecto" value="<?php echo esc_attr($valorPorDefecto); ?>" required />
                </div>
            </div>
            <div class='m-2'>
                <label class='block uppercase tracking-wide text-sm font-bold mb-1 text-blue-950 pt-10'>Valores Condicionales</label>
                <div id="valores_condicionales_container" class='pt-6'>
                    <?php foreach ($valoresCondicionales as $index => $vc) : ?>
                        <?php $lastindex = $index; ?>
                        <div class="valor-condicional bg-gray-300 rounded border shadow p-3 mb-6">
                            <div class='flex flex-1 flex-col md:flex-row lg:flex-row'>
                                <input type="text" name="valores[<?php echo $index; ?>]" value="<?php echo esc_attr($vc['valor']); ?>" placeholder="Valor" class='appearance-none block w-full bg-grey-200 text-grey-darker border border-grey-200 rounded py-3 px-4 mr-3 h-10 leading-tight focus:outline-none focus:bg-white focus:border-grey' required />
                                <select name="logica_condiciones[<?php echo $index; ?>]" class="mr-3 h-10">
                                    <option value="AND" <?php echo ($vc['logica'] == 'AND' ? 'selected' : ''); ?>>AND</option>
                                    <option value="OR" <?php echo ($vc['logica'] == 'OR' ? 'selected' : ''); ?>>OR</option>
                                </select>
                                <button type="button" class="bg-green-500 hover:bg-green-700 text-white hover:text-white font-bold py-2 px-4 h-10 rounded w-1/3" onclick="agregarCondicion(<?php echo $index; ?>);">Añadir Condición</button>
                            </div>
                            <div class="condiciones-container w-full p-2" id="condiciones-container-<?php echo $index; ?>">
                                <?php foreach ($vc['condiciones'] as $cond) : ?>
                                    <div class="condicion">
                                        <div class='flex flex-1 flex-col md:flex-row lg:flex-row py-2'>
                                            <input type="text" name="parametros[<?php echo $index; ?>][]" value="<?php echo esc_attr($cond['parametro']); ?>" placeholder="Parámetro" class="appearance-none block w-full bg-grey-200 text-grey-darker border border-grey-200 rounded py-3 px-4 mr-3 h-10 leading-tight focus:outline-none focus:bg-white focus:border-grey" required />
                                            <select name="tipos[<?php echo $index; ?>][]" class="mr-3 h-10">
                                                <option value="exacto" <?php echo ($cond['tipo'] == 'exacto' ? 'selected' : ''); ?>>Exacto</option>
                                                <option value="contiene" <?php echo ($cond['tipo'] == 'contiene' ? 'selected' : ''); ?>>Contiene</option>
                                                <option value="comienza_con" <?php echo ($cond['tipo'] == 'comienza_con' ? 'selected' : ''); ?>>Comienza con</option>
                                                <option value="no_igual" <?php echo ($cond['tipo'] == 'no_igual' ? 'selected' : ''); ?>>No es igual</option>
                                            </select>
                                            <input type="text" name="valores_condiciones[<?php echo $index; ?>][]" value="<?php echo esc_attr($cond['valor']); ?>" placeholder="Valor para Condición" class="appearance-none block w-full bg-grey-200 text-grey-darker border border-grey-200 rounded py-3 px-4 mr-3 h-10 leading-tight focus:outline-none focus:bg-white focus:border-grey" />
                                            <button type="button" class="bg-red-500 hover:bg-red-700 text-white hover:text-white font-bold py-2 px-4 h-10 rounded pull-right w-1/3" onclick="this.parentElement.parentElement.remove();">Eliminar Condición</button>
                                        </div>
                                    </div>
                                <?php endforeach; ?>
                            </div>
                            <div class="w-full my-2 justify-end">
                                <button type="button" class="bg-red-500 hover:bg-red-700 text-white hover:text-white font-bold py-2 px-4 h-10 rounded pull-right" onclick="this.parentElement.parentElement.remove();">Eliminar Valor Condicional</button>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>
                <button type="button" class="bg-green-500 hover:bg-green-700 text-white hover:text-white font-bold py-2 px-4 rounded h-12" onclick="agregarValorCondicion();">Añadir Valor Condicional</button>
            </div>
            <div class='m-2 mt-12 text-center'>
                <input type='submit' name='submit' id='submit' class='bg-blue-500 hover:bg-blue-800 text-white font-bold py-2 px-4 rounded cursor-pointer mx-auto w-full md:w-1/3 h-12' value='Actualizar Variable'>
            </div>
        </form>
    </div>

    <script>
let valorIndex = <?php echo $lastindex+1; ?>;

function agregarValorCondicion() {
    const container = document.getElementById('valores_condicionales_container');
    const valorDiv = document.createElement('div');
    valorDiv.className = 'valor-condicional bg-gray-300 rounded border shadow p-3 mb-6';
    valorDiv.innerHTML = `
        <div class='flex flex-1 flex-col md:flex-row lg:flex-row'>
        <input type="text" name="valores[${valorIndex}]" placeholder="Valor" class='appearance-none block w-full bg-grey-200 text-grey-darker border border-grey-200 rounded py-3 px-4 mr-3 h-10 leading-tight focus:outline-none focus:bg-white focus:border-grey' required />
        <select name="logica_condiciones[${valorIndex}]" class="mr-3 h-10">
            <option value="AND">AND</option>
            <option value="OR">OR</option>
        </select>
        <button type="button" class="bg-green-500 hover:bg-green-700 text-white hover:text-white font-bold py-2 px-4 h-10 rounded w-1/3" onclick="agregarCondicion(${valorIndex});">Añadir Condición</button>
        </div>
        <div class="condiciones-container w-full p-2" id="condiciones-container-${valorIndex}"></div>
        <div class="w-full my-2 justify-end">
            <button type="button" class="bg-red-500 hover:bg-red-700 text-white hover:text-white font-bold py-2 px-4 h-10 rounded pull-right" onclick="this.parentElement.parentElement.remove();">Eliminar Valor Condicional</button>
        </div>
        </div>
    `;
    container.appendChild(valorDiv);
    valorIndex++;
}

function agregarCondicion(index) {
    const condicionesContainer = document.getElementById(`condiciones-container-${index}`);
    const condicionDiv = document.createElement('div');
    condicionDiv.className = 'condicion';
    condicionDiv.innerHTML = `
        <div class='flex flex-1 flex-col md:flex-row lg:flex-row py-2'>
        <input type="text" name="parametros[${index}][]" placeholder="Parámetro" class="appearance-none block w-full bg-grey-200 text-grey-darker border border-grey-200 rounded py-3 px-4 mr-3 h-10 leading-tight focus:outline-none focus:bg-white focus:border-grey" required />
        <select name="tipos[${index}][]" class="mr-3 h-10">
            <option value="exacto">Exacto</option>
            <option value="contiene">Contiene</option>
            <option value="comienza_con">Comienza con</option>
            <option value="no_igual">No es igual</option>
        </select>
        <input type="text" name="valores_condiciones[${index}][]" placeholder="Valor para Condición" class="appearance-none block w-full bg-grey-200 text-grey-darker border border-grey-200 rounded py-3 px-4 mr-3 h-10 leading-tight focus:outline-none focus:bg-white focus:border-grey" />
        <button type="button" class="bg-red-500 hover:bg-red-700 text-white hover:text-white font-bold py-2 px-4 h-10 rounded pull-right w-1/3" onclick="this.parentElement.parentElement.remove();">Eliminar Condición</button>
        </div>
    `;
    condicionesContainer.appendChild(condicionDiv);
}
</script>

    <?php
}

add_filter('nonce_life', function () {
    return 12 * HOUR_IN_SECONDS; // 12 horas
});

function vd_enqueue_admin_styles($hook_suffix) {
    if (strpos($hook_suffix, 'vd_') !== false) {
        // Encola los estilos CSS
        wp_enqueue_style('vd-main-style', plugins_url('variables-dinamicas/assets/styles.css'));
        wp_enqueue_style('vd-theme-style', plugins_url('variables-dinamicas/assets/all.css'));

        // Encola el script JS
        wp_enqueue_script('vd-main-script', plugins_url('variables-dinamicas/assets/main.js'), array('jquery'), false, true);

        // Encola las fuentes de Google Fonts
        wp_enqueue_style('google-fonts', 'https://fonts.googleapis.com/css?family=Source+Sans+Pro:400,400i,600,600i,700,700i&display=swap', false);
    }
}

add_action('admin_enqueue_scripts', 'vd_enqueue_admin_styles');

/**
 * Configuración para actualizaciones remotas desde GitHub
 */
function vd_plugin_updater() {
    // URL del archivo JSON con la información de la actualización
    $update_url = 'https://raw.githubusercontent.com/escalantech/variables-dinamicas/main/update-info.json';
    
    // Obtener la información del plugin actual
    if (!function_exists('get_plugin_data')) {
        require_once(ABSPATH . 'wp-admin/includes/plugin.php');
    }
    $plugin_data = get_plugin_data(WP_PLUGIN_DIR . '/variables-dinamicas/variables-dinamicas.php');
    $current_version = $plugin_data['Version'];
    
    error_log('VD Plugin: Versión actual del plugin: ' . $current_version);
    
    // Verificar actualizaciones cada 12 horas
    $last_check = get_transient('vd_update_check');
    if (false === $last_check) {
        try {
            error_log('VD Plugin: Verificando actualizaciones desde GitHub');
            
            $response = wp_remote_get($update_url, array(
                'timeout' => 30,
                'sslverify' => true
            ));
            
            if (is_wp_error($response)) {
                throw new Exception('Error al verificar actualizaciones: ' . $response->get_error_message());
            }
            
            $response_code = wp_remote_retrieve_response_code($response);
            if ($response_code !== 200) {
                throw new Exception('Error HTTP al obtener actualizaciones. Código: ' . $response_code);
            }
            
            $body = wp_remote_retrieve_body($response);
            $update_data = json_decode($body, true);
            
            if (json_last_error() !== JSON_ERROR_NONE) {
                throw new Exception('Error al decodificar la respuesta JSON: ' . json_last_error_msg());
            }
            
            // Validar que los campos requeridos existen
            if (!isset($update_data['version']) || !isset($update_data['download_url'])) {
                throw new Exception('Información de actualización incompleta');
            }
            
            error_log('VD Plugin: Nueva versión disponible: ' . $update_data['version']);
            error_log('VD Plugin: URL de descarga: ' . $update_data['download_url']);
            
            // Guardar la información de actualización
            set_transient('vd_update_info', $update_data, 12 * HOUR_IN_SECONDS);
            set_transient('vd_update_check', time(), 12 * HOUR_IN_SECONDS);
            
        } catch (Exception $e) {
            error_log('Error en vd_plugin_updater: ' . $e->getMessage());
            return;
        }
    }
}

/**
 * Filtrar la información de actualizaciones de plugins
 */
function vd_check_for_plugin_update($checked_data) {
    if (empty($checked_data->checked)) {
        return $checked_data;
    }
    
    $plugin_slug = 'variables-dinamicas/variables-dinamicas.php';
    $update_info = get_transient('vd_update_info');
    
    if (false === $update_info) {
        return $checked_data;
    }
    
    try {
        // Verificar si hay una versión más reciente
        if (isset($update_info['version']) && version_compare($update_info['version'], $checked_data->checked[$plugin_slug], '>')) {
            $update_package = isset($update_info['download_url']) ? $update_info['download_url'] : '';
            
            // Verificar que la URL del paquete es válida
            if (empty($update_package)) {
                throw new Exception('URL de descarga no válida');
            }
            
            error_log('VD Plugin: Preparando actualización a la versión ' . $update_info['version']);
            error_log('VD Plugin: Paquete de actualización: ' . $update_package);
            
            $update_object = new stdClass();
            $update_object->id = $plugin_slug;
            $update_object->slug = dirname($plugin_slug);
            $update_object->plugin = $plugin_slug;
            $update_object->new_version = $update_info['version'];
            $update_object->url = isset($update_info['details_url']) ? $update_info['details_url'] : '';
            $update_object->package = $update_package;
            $update_object->tested = isset($update_info['tested']) ? $update_info['tested'] : '';
            $update_object->requires = isset($update_info['requires']) ? $update_info['requires'] : '';
            $update_object->requires_php = isset($update_info['requires_php']) ? $update_info['requires_php'] : '';
            
            // Forzar la actualización incluso si la transient existe
            wp_clean_plugins_cache(true);
            
            $checked_data->response[$plugin_slug] = $update_object;
            
            error_log('VD Plugin: Objeto de actualización preparado correctamente');
        } else {
            error_log('VD Plugin: No hay actualización disponible o la versión actual es más reciente');
        }
    } catch (Exception $e) {
        error_log('Error en vd_check_for_plugin_update: ' . $e->getMessage());
    }
    
    return $checked_data;
}

/**
 * Mostrar información del plugin en la página de plugins
 */
function vd_plugin_update_information($result, $action, $args) {
    if ($action !== 'plugin_information') {
        return $result;
    }
    
    if ('variables-dinamicas' !== $args->slug) {
        return $result;
    }
    
    $update_info = get_transient('vd_update_info');
    if (false === $update_info) {
        return $result;
    }
    
    $information = new stdClass();
    $information->name = 'Variables Dinámicas';
    $information->slug = 'variables-dinamicas';
    $information->version = $update_info['version'];
    $information->author = 'Chris Escalante';
    $information->requires = isset($update_info['requires']) ? $update_info['requires'] : '';
    $information->tested = isset($update_info['tested']) ? $update_info['tested'] : '';
    $information->last_updated = isset($update_info['last_updated']) ? $update_info['last_updated'] : '';
    $information->sections = array(
        'description' => isset($update_info['description']) ? $update_info['description'] : '',
        'changelog' => isset($update_info['changelog']) ? $update_info['changelog'] : ''
    );
    $information->download_link = isset($update_info['download_url']) ? $update_info['download_url'] : '';
    
    return $information;
}

// Registrar los hooks para las actualizaciones
add_action('init', 'vd_plugin_updater');
add_filter('pre_set_site_transient_update_plugins', 'vd_check_for_plugin_update');
add_filter('plugins_api', 'vd_plugin_update_information', 20, 3);

// Función para mostrar el valor de un campo personalizado
function mostrar_campo_personalizado($campo, $post_id = null) {
    if (!$post_id) {
        $post_id = get_the_ID();
    }
    $valor_campo = get_post_meta($post_id, $campo, true);
    if (!empty($valor_campo)) {
        return esc_html($valor_campo);
    }
    return '';
}