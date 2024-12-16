<?php
/*
Plugin Name: WD Dynamic Variable Replacer
Description: Allows dynamic replacement of variables in post content based on user-defined key-value pairs, with Divi editor integration. Additionally, it enables importing and exporting settings in JSON format.
Version: 1.5
Author: Wowk Digital
*/

if (!defined('ABSPATH')) exit; // Ensure the file is accessed through WordPress

class WD_DynamicVariableReplacer {
    private $options;

    public function __construct() {
        $this->options = get_option('wd_dvr_options', []);
        add_action('admin_menu', [$this, 'add_admin_menu']);
        add_action('admin_init', [$this, 'register_settings']);
        add_action('admin_init', [$this, 'import_json']);
        add_filter('the_content', [$this, 'replace_variables']);

        // Add styles and scripts to the Divi editor
        add_action('wp_enqueue_scripts', [$this, 'enqueue_divi_editor_assets']);

        // Add AJAX action for JSON export
        add_action('wp_ajax_wd_dvr_export', [$this, 'export_to_json']);

        // Added: Buffering the entire page to enable replacements even in the footer.
        add_action('template_redirect', [$this, 'start_buffer']);
        add_action('shutdown', [$this, 'end_buffer'], 9999);
    }

    public function start_buffer() {
        // Check if this is not Divi builder mode or WP admin
        if (is_admin() || $this->is_divi_builder()) {
            return; 
        }
        ob_start([$this, 'replace_variables']);
    }

    public function end_buffer() {
        // End buffering (the callback will be executed automatically)
        if (ob_get_level() > 0) {
            ob_end_flush();
        }
    }

    public function enqueue_divi_editor_assets() {
        if ($this->is_divi_builder()) {
            wp_enqueue_style('wd-dvr-editor-style', plugins_url('wd-dvr-editor-style.css', __FILE__));
            wp_enqueue_script('wd-dvr-editor-script', plugins_url('wd-dvr-editor-script.js', __FILE__), ['jquery'], false, true);
        }
    }

    private function is_divi_builder() {
        return isset($_GET['et_fb']) || (function_exists('et_builder_is_active') && et_builder_is_active());
    }

    public function add_admin_menu() {
        add_menu_page(
            'WD Dynamic Variable Replacer',
            'WD Dynamic Variable Replacer',
            'manage_options',
            'wd-dynamic-variable-replacer',
            [$this, 'admin_settings_page'],
            'dashicons-admin-generic'
        );
    }

    public function register_settings() {
        register_setting('wd_dvr_options_group', 'wd_dvr_options');
    }

    /**
     * Import a JSON file uploaded by the user.
     */
    public function import_json() {
        if (!empty($_POST['dvr_import_submit']) && current_user_can('manage_options')) {
            // Check if the file was uploaded without errors
            if (!empty($_FILES['dvr_import_file']) && $_FILES['dvr_import_file']['error'] === UPLOAD_ERR_OK) {
                $file_type = wp_check_filetype($_FILES['dvr_import_file']['name']);
                
                // Check if it is a JSON file
                if ($file_type['ext'] === 'json' || $file_type['type'] === 'application/json') {
                    $file_content = file_get_contents($_FILES['dvr_import_file']['tmp_name']);
                    $import_data = json_decode($file_content, true);

                    if (is_array($import_data)) {
                        // Update options
                        update_option('wd_dvr_options', $import_data);
                        add_settings_error('wd_dvr_options', 'import_success', 'Import completed successfully.', 'updated');
                    } else {
                        add_settings_error('wd_dvr_options', 'import_error', 'Invalid JSON structure.', 'error');
                    }
                } else {
                    add_settings_error('wd_dvr_options', 'import_error', 'Please select a valid JSON file.', 'error');
                }
            } else {
                add_settings_error('wd_dvr_options', 'import_error', 'There was a problem uploading the file.', 'error');
            }

            // Redirect after import to show messages
            wp_redirect(admin_url('admin.php?page=wd-dynamic-variable-replacer'));
            exit;
        }
    }

    public function admin_settings_page() {
        // Display import messages
        settings_errors('wd_dvr_options');
        $options = get_option('wd_dvr_options', []);
        ?>
        <div class="wrap">
            <h1>WD Dynamic Variable Replacer</h1>
            
            <h2>User Guide</h2>
            <p><strong>How to Use:</strong></p>
            <ol>
                <li>Define key-value pairs below. Each pair consists of a <strong>Key</strong> and a <strong>Value</strong>.</li>
                <li>In your post or page content, use the following syntax to insert a variable: <code>$$key$$</code>. For example, if your key is <code>company_name</code>, use <code>$$company_name$$</code> in the content.</li>
                <li>The plugin will dynamically replace <code>$$key$$</code> placeholders with the corresponding values defined here.</li>
                <li>Integration with the Divi editor allows you to visually identify variables. They will be highlighted while editing your content.</li>
                <li>You can import or export your configuration using JSON files for easy backups or transfers.</li>
            </ol>
            
            <form method="post" action="options.php">
                <?php
                settings_fields('wd_dvr_options_group');
                do_settings_sections('wd_dvr_options_group');
                ?>
                <h2>Variable Settings</h2>
                <table class="form-table" style="width: 100%;">
                    <thead>
                        <tr>
                            <th>Key</th>
                            <th>Value</th>
                            <th>Action</th>
                        </tr>
                    </thead>
                    <tbody id="dvr-rows">
                        <?php if (!empty($options)): ?>
                            <?php foreach ($options as $key => $value): ?>
                                <tr>
                                    <td><input type="text" name="wd_dvr_options[<?php echo esc_attr($key); ?>][key]" value="<?php echo esc_attr($value['key']); ?>" /></td>
                                    <td><textarea name="wd_dvr_options[<?php echo esc_attr($key); ?>][value]" style="width: 100%; height: 60px;"><?php echo esc_textarea($value['value']); ?></textarea></td>
                                    <td><button type="button" class="button dvr-remove-row">Remove</button></td>
                                </tr>
                            <?php endforeach; ?>
                        <?php endif; ?>
                    </tbody>
                </table>
                <button type="button" class="button" id="dvr-add-row">Add a New Pair</button>
                <?php submit_button('Save Changes'); ?>
                <button type="button" class="button" id="dvr-export-json">Export to JSON</button>
            </form>

            <hr />

            <h2>Import from JSON File</h2>
            <p>You can import settings from a JSON file. This will overwrite the current key-value pairs.</p>
            <form method="post" enctype="multipart/form-data" action="">
                <input type="file" name="dvr_import_file" accept=".json" />
                <?php submit_button('Import JSON', 'primary', 'dvr_import_submit'); ?>
            </form>
        </div>
        <script>
            document.addEventListener('DOMContentLoaded', function() {
                document.getElementById('dvr-add-row').addEventListener('click', function() {
                    const tbody = document.getElementById('dvr-rows');
                    const now = Date.now();
                    const row = document.createElement('tr');
                    row.innerHTML = `<td><input type="text" name="wd_dvr_options[new_${now}][key]" value="" /></td>
                                    <td><textarea name="wd_dvr_options[new_${now}][value]" style="width: 100%; height: 60px;"></textarea></td>
                                    <td><button type="button" class="button dvr-remove-row">Remove</button></td>`;
                    tbody.appendChild(row);
                });

                document.getElementById('dvr-rows').addEventListener('click', function(e) {
                    if (e.target.classList.contains('dvr-remove-row')) {
                        e.target.closest('tr').remove();
                    }
                });

                // Export to JSON
                document.getElementById('dvr-export-json').addEventListener('click', function() {
                    window.location.href = '<?php echo admin_url('admin-ajax.php?action=wd_dvr_export'); ?>';
                });
            });
        </script>
        <style>
            #dvr-rows input[type="text"], #dvr-rows textarea {
                width: 100%;
                box-sizing: border-box;
            }
            .dvr-remove-row { background-color: #f44336; color: #fff; }
            .wd-variable-highlight {
                background-color: #d4edda;
                color: #155724;
                padding: 2px 4px;
                border-radius: 3px;
                font-weight: bold;
            }
        </style>
        <script>
        document.addEventListener('DOMContentLoaded', function () {
            function highlightVariablesInDiviEditor() {
                let editorContents = document.querySelectorAll('.et-fb-settings__content, .et_pb_text_inner, .et_pb_module');

                editorContents.forEach(function (editorContent) {
                    if (!editorContent.classList.contains('highlighted')) {
                        let content = editorContent.innerHTML;
                        content = content.replace(/\$\$(.*?)\$\$/g, function (match) {
                            return '<span class="wd-variable-highlight">' + match + '</span>';
                        });
                        editorContent.innerHTML = content;
                        editorContent.classList.add('highlighted');
                    }
                });
            }

            highlightVariablesInDiviEditor();
            document.addEventListener('input', function () {
                highlightVariablesInDiviEditor();
            });
        });
        </script>
        <?php
    }

    public function replace_variables($content) {
        $options = get_option('wd_dvr_options', []);
        if (empty($options)) return $content;

        foreach ($options as $option) {
            $key = isset($option['key']) ? $option['key'] : '';
            $value = isset($option['value']) ? $option['value'] : '';

            if ($key && $value) {
                $pattern = '/\$\$' . preg_quote($key, '/') . '\$\$/';
                $content = preg_replace($pattern, wp_kses_post($value), $content);
            }
        }
        return $content;
    }

    public function export_to_json() {
        if (!current_user_can('manage_options')) {
            wp_die(__('You do not have sufficient permissions to access this page.'));
        }

        $options = get_option('wd_dvr_options', []);
        $json_data = json_encode($options);

        // Ensure nothing has been sent already
        if (ob_get_length()) ob_end_clean();

        header('Content-Type: application/json; charset=utf-8');
        header('Content-Disposition: attachment; filename="dynamic-variable-replacer-export.json"');
        echo $json_data;
        exit;
    }
}

new WD_DynamicVariableReplacer();
