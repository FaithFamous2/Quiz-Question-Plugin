<?php
/*
Plugin Name: Advanced Skin Care Quiz
Description: Professional skincare quiz with product recommendations and analytics. Includes multi-product associations and threshold-based suggestions.
Version: 4.1
Author: Olowookere Faith Famouzcoder
*/

defined('ABSPATH') || exit;

// =============================================================================
// CONSTANTS & SETUP
// =============================================================================
define('CSQ_PLUGIN_PATH', plugin_dir_path(__FILE__));
define('CSQ_PLUGIN_URL', plugin_dir_url(__FILE__));

// =============================================================================
// DATABASE SETUP
// =============================================================================
register_activation_hook(__FILE__, 'csq_activate_plugin');
register_deactivation_hook(__FILE__, 'csq_deactivate_plugin');

function csq_activate_plugin() {
    csq_create_database();
    update_option('csq_plugin_version', '4.1');
    flush_rewrite_rules();
}

function csq_deactivate_plugin() {
    flush_rewrite_rules();
}

add_action('plugins_loaded', 'csq_check_database');

function csq_check_database() {
    $current_version = get_option('csq_plugin_version', '1.0');

    if (version_compare($current_version, '4.1', '<')) {
        csq_create_database();
        update_option('csq_plugin_version', '4.1');
    }
}

function csq_create_database() {
    global $wpdb;
    $charset_collate = $wpdb->get_charset_collate();
    $table_prefix = $wpdb->prefix;

    $tables = [
        "{$table_prefix}skin_products" => "(
            id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
            product_name VARCHAR(255) NOT NULL,
            image_url VARCHAR(255),
            product_details TEXT,
            product_link VARCHAR(255),
            count_threshold INT DEFAULT 1,
            product_order INT DEFAULT 0,
            tags VARCHAR(255),
            PRIMARY KEY (id)
        ) $charset_collate",

        "{$table_prefix}quiz_questions" => "(
            id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
            question_text TEXT NOT NULL,
            image_url VARCHAR(255),
            question_order INT DEFAULT 0,
            PRIMARY KEY (id)
        ) $charset_collate",

        "{$table_prefix}quiz_answers" => "(
            id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
            question_id BIGINT UNSIGNED NOT NULL,
            answer_text VARCHAR(255) NOT NULL,
            product_suggestions VARCHAR(255),
            answer_order INT DEFAULT 0,
            PRIMARY KEY (id),
            FOREIGN KEY (question_id) REFERENCES {$table_prefix}quiz_questions(id) ON DELETE CASCADE
        ) $charset_collate",

        "{$table_prefix}quiz_responses" => "(
            id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
            user_email VARCHAR(100) NOT NULL,
            gender VARCHAR(50),
            fullname VARCHAR(200),
            responses TEXT NOT NULL,
            product_votes TEXT NOT NULL,
            final_product VARCHAR(255),
            created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
            ip_address VARCHAR(45) DEFAULT '',
            user_agent TEXT DEFAULT '',
            PRIMARY KEY (id)
        ) $charset_collate"
    ];

    require_once(ABSPATH . 'wp-admin/includes/upgrade.php');

    foreach ($tables as $table_name => $table_sql) {
        if ($wpdb->get_var("SHOW TABLES LIKE '$table_name'") != $table_name) {
            dbDelta("CREATE TABLE $table_name $table_sql;");
        } else {
            // Add new columns to existing table
            $columns = $wpdb->get_col("DESC $table_name", 0);

            if (!in_array('ip_address', $columns)) {
                $wpdb->query("ALTER TABLE $table_name ADD ip_address VARCHAR(45) DEFAULT ''");
            }

            if (!in_array('user_agent', $columns)) {
                $wpdb->query("ALTER TABLE $table_name ADD user_agent TEXT DEFAULT ''");
            }
        }
    }

    // Add error logging for debugging
    error_log('Skin Care Quiz tables created/updated');
}

// =============================================================================
// ADMIN INTERFACE
// =============================================================================
function csq_dashboard_page() {
    ?>
    <div class="wrap csq-admin">
        <h1 class="csq-admin-title">Skin Care Quiz Dashboard</h1>
        <div class="card csq-card">
            <p>Welcome to the Skin Care Quiz plugin! Use the navigation menu to manage:</p>
            <ul>
                <li>📦 Products - Add and manage skincare products</li>
                <li>❓ Questions - Create quiz questions and answers</li>
                <li>📊 Responses - View user quiz results</li>
            </ul>
        </div>
    </div>
    <?php
}

function csq_admin_menu() {
    add_menu_page(
        'Skin Quiz',
        'Skin Quiz',
        'manage_options',
        'csq-dashboard',
        'csq_dashboard_page',
        'dashicons-clipboard',
        30
    );
    add_submenu_page('csq-dashboard', 'Dashboard', 'Dashboard', 'manage_options', 'csq-dashboard');
    add_submenu_page('csq-dashboard', 'Products', 'Products', 'manage_options', 'csq-products', 'csq_products_page');
    add_submenu_page('csq-dashboard', 'Questions', 'Questions', 'manage_options', 'csq-questions', 'csq_questions_page');
    add_submenu_page('csq-dashboard', 'Responses', 'Responses', 'manage_options', 'csq-responses', 'csq_responses_page');
    add_submenu_page(null, 'Answers', 'Answers', 'manage_options', 'csq-answers', 'csq_answers_page');
}
add_action('admin_menu', 'csq_admin_menu');

// Include admin pages
require_once CSQ_PLUGIN_PATH . 'admin/products.php';
require_once CSQ_PLUGIN_PATH . 'admin/questions.php';
require_once CSQ_PLUGIN_PATH . 'admin/answers.php';
require_once CSQ_PLUGIN_PATH . 'admin/responses.php';

// =============================================================================
// FRONTEND QUIZ
// =============================================================================
add_shortcode('skin_quiz', 'csq_quiz_shortcode');

function csq_quiz_shortcode() {
    wp_enqueue_style('csq-frontend');
    wp_enqueue_script('csq-frontend');

    $questions = csq_get_questions();
    $answers   = csq_get_answers();

    ob_start();
    include CSQ_PLUGIN_PATH . 'templates/quiz-form.php';
    return ob_get_clean();
}

// =============================================================================
// AJAX HANDLERS
// =============================================================================
add_action('wp_ajax_csq_save_contact', 'csq_save_contact');
add_action('wp_ajax_nopriv_csq_save_contact', 'csq_save_contact');
add_action('wp_ajax_csq_email_results', 'csq_email_results');
add_action('wp_ajax_nopriv_csq_email_results', 'csq_email_results');
add_action('wp_ajax_csq_process_quiz', 'csq_process_quiz');
add_action('wp_ajax_nopriv_csq_process_quiz', 'csq_process_quiz');

function csq_save_contact() {
    check_ajax_referer('csq_quiz_nonce', 'security');

    if (empty($_POST['email'])) {
        wp_send_json_error('Email required', 400);
    }

    $email  = sanitize_email($_POST['email']);
    $gender = sanitize_text_field($_POST['gender'] ?? '');
    $fullname = sanitize_text_field($_POST['fullname'] ?? '');
    $ip_address = $_SERVER['REMOTE_ADDR'];
    $user_agent = substr($_SERVER['HTTP_USER_AGENT'], 0, 500); // Truncate to 500 chars

    // Enhanced email validation
    if (!is_email($email)) {
        wp_send_json_error('Invalid email format', 400);
    }

    global $wpdb;
    $table = $wpdb->prefix . 'quiz_responses';

    // Check if session already exists
    $existing = $wpdb->get_row($wpdb->prepare(
        "SELECT id FROM $table WHERE user_email = %s",
        $email
    ));

    if ($existing) {
        $session_id = $existing->id;
        $wpdb->update($table, [
            'gender' => $gender,
            'fullname' => $fullname,
            'ip_address' => $ip_address,
            'user_agent' => $user_agent
        ], ['id' => $session_id]);
    } else {
        $insert = $wpdb->insert($table, [
            'user_email'    => $email,
            'gender'        => $gender,
            'fullname'      => $fullname,
            'responses'     => maybe_serialize([]),
            'product_votes' => maybe_serialize([]),
            'final_product' => '',
            'created_at'    => current_time('mysql'),
            'ip_address'    => $ip_address,
            'user_agent'    => $user_agent
        ]);

        if (!$insert) {
            error_log('DB Error: ' . $wpdb->last_error);
            wp_send_json_error('Database error: ' . $wpdb->last_error, 500);
        }
        $session_id = $wpdb->insert_id;
    }

    wp_send_json_success(['session_id' => (int)$session_id]);
}

function csq_email_results() {
    check_ajax_referer('csq_quiz_nonce', 'security');

    $session_id = isset($_POST['session_id']) ? absint($_POST['session_id']) : 0;
    $email = isset($_POST['email']) ? sanitize_email($_POST['email']) : '';

    if (!$session_id || !$email) {
        wp_send_json_error('Invalid request');
    }

    global $wpdb;
    $response = $wpdb->get_row($wpdb->prepare(
        "SELECT * FROM {$wpdb->prefix}quiz_responses WHERE id = %d",
        $session_id
    ));

    if (!$response) {
        wp_send_json_error('Session not found');
    }

    $products = explode(',', $response->final_product);
    $subject = 'Your Skincare Recommendations';

    ob_start();
    ?>
    <html>
    <body>
        <h2>Your Personalized Skincare Recommendations</h2>
        <p>Hello <?php echo esc_html($response->fullname); ?>,</p>
        <p>Based on your skin analysis, we recommend these products:</p>
        <ul>
            <?php foreach ($products as $product): ?>
                <li><?php echo esc_html($product); ?></li>
            <?php endforeach; ?>
        </ul>
        <p>Thank you for using our skin analysis system!</p>
    </body>
    </html>
    <?php
    $message = ob_get_clean();

    $headers = array('Content-Type: text/html; charset=UTF-8');

    if (wp_mail($email, $subject, $message, $headers)) {
        wp_send_json_success();
    } else {
        wp_send_json_error('Failed to send email');
    }
}

function csq_process_quiz() {
    check_ajax_referer('csq_quiz_nonce', 'security');
    global $wpdb;

    $session_id = isset($_POST['session_id']) ? absint($_POST['session_id']) : 0;
    $answers = isset($_POST['answers']) ? array_map('absint', (array) $_POST['answers']) : [];

    if (!$session_id) {
        wp_send_json_error('Session ID missing', 400);
    }

    if (empty($answers)) {
        wp_send_json_error('No answers provided', 400);
    }

    // Get user info from session
    $response = $wpdb->get_row($wpdb->prepare(
        "SELECT * FROM {$wpdb->prefix}quiz_responses WHERE id = %d",
        $session_id
    ));

    if (!$response) {
        wp_send_json_error('Session not found', 404);
    }

    // Calculate recommendations
    $results = csq_calculate_results($answers);
    $names   = isset($results['products']) ? wp_list_pluck($results['products'], 'name') : [];
    $final   = implode(', ', $names);

    // Prepare DB update
    $data = [
        'responses'     => maybe_serialize($answers),
        'product_votes' => maybe_serialize($results['total_votes'] ?? []),
        'final_product' => $final,
    ];

    $updated = $wpdb->update($wpdb->prefix . 'quiz_responses', $data, ['id' => $session_id]);

    if ($updated === false) {
        error_log('Failed to update quiz response: ' . $wpdb->last_error);
        wp_send_json_error('Database update failed', 500);
    }

    // Send follow-up email
    $subject = !empty($results['products'])
        ? 'Your Skincare Product Recommendations'
        : 'Finish Your Skincare Quiz';

    $message = !empty($results['products'])
        ? "Hello {$response->fullname},\n\nBased on your answers, we recommend these products:\n\n" . implode("\n", $names)
        : "Hello {$response->fullname},\n\nIt looks like you didn't complete the quiz. Click here to finish it: " . site_url('/your-quiz-page');

    wp_mail($response->user_email, $subject, $message);

    wp_send_json_success([
        'products'   => $results['products'] ?? [],
        'session_id' => $session_id
    ]);
}



// =============================================================================
// ASSETS MANAGEMENT
// =============================================================================
add_action('wp_enqueue_scripts', 'csq_enqueue_assets');
add_action('admin_enqueue_scripts', 'csq_admin_assets');

function csq_enqueue_assets() {
    wp_register_style(
        'csq-frontend',
        CSQ_PLUGIN_URL . 'assets/css/frontend.css',
        [],
        filemtime(CSQ_PLUGIN_PATH . 'assets/css/frontend.css')
    );

    wp_register_script(
        'csq-frontend',
        CSQ_PLUGIN_URL . 'assets/js/frontend.js',
        ['jquery'],
        filemtime(CSQ_PLUGIN_PATH . 'assets/js/frontend.js'),
        true
    );

    wp_localize_script('csq-frontend', 'csqData', [
        'ajaxurl' => admin_url('admin-ajax.php'),
        'nonce'   => wp_create_nonce('csq_quiz_nonce'),
        'assets'  => CSQ_PLUGIN_URL . 'assets/'
    ]);
}

function csq_admin_assets($hook) {
    if (strpos($hook, 'csq-') === false) return;

    wp_enqueue_style(
        'csq-admin',
        CSQ_PLUGIN_URL . 'assets/css/admin.css',
        [],
        filemtime(CSQ_PLUGIN_PATH . 'assets/css/admin.css')
    );

    wp_enqueue_style('select2', 'https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/css/select2.min.css');
    wp_enqueue_script('select2', 'https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/js/select2.min.js', ['jquery']);

    wp_enqueue_script(
        'csq-admin',
        CSQ_PLUGIN_URL . 'assets/js/admin.js',
        ['jquery', 'select2'],
        filemtime(CSQ_PLUGIN_PATH . 'assets/js/admin.js'),
        true
    );
}

// =============================================================================
// CORE FUNCTIONS
// =============================================================================
function csq_get_products() {
    global $wpdb;
    return $wpdb->get_results("SELECT * FROM {$wpdb->prefix}skin_products ORDER BY product_order ASC");
}

function csq_get_questions() {
    global $wpdb;
    return $wpdb->get_results("SELECT * FROM {$wpdb->prefix}quiz_questions ORDER BY question_order ASC");
}

function csq_get_answers() {
    global $wpdb;
    $rows = $wpdb->get_results("SELECT * FROM {$wpdb->prefix}quiz_answers ORDER BY answer_order ASC");
    $answers = [];

    if (!empty($rows)) {
        foreach ($rows as $row) {
            $answers[$row->question_id][] = $row;
        }
    }
    return $answers;
}

function csq_calculate_results($answer_ids) {
    global $wpdb;
    $product_votes = [];
    $product_cache = [];

    foreach ($answer_ids as $answer_id) {
        $products = $wpdb->get_var($wpdb->prepare(
            "SELECT product_suggestions FROM {$wpdb->prefix}quiz_answers WHERE id = %d",
            $answer_id
        ));

        if ($products) {
            foreach (explode(',', $products) as $product_id) {
                $product_id = absint($product_id);

                if (!isset($product_cache[$product_id])) {
                    $product_cache[$product_id] = $wpdb->get_row($wpdb->prepare(
                        "SELECT * FROM {$wpdb->prefix}skin_products WHERE id = %d",
                        $product_id
                    ));
                }

                $product = $product_cache[$product_id];

                if ($product) {
                    $product_votes[$product_id] = ($product_votes[$product_id] ?? 0) + 1;
                }
            }
        }
    }

    $qualified = [];
    foreach ($product_votes as $product_id => $votes) {
        $product = $product_cache[$product_id];

        if ($product && $votes >= $product->count_threshold) {
            $qualified[] = [
                'id' => $product->id,
                'name' => $product->product_name,
                'image' => $product->image_url,
                'details' => $product->product_details,
                'link' => $product->product_link,
                'votes' => $votes
            ];
        }
    }

    usort($qualified, function($a, $b) {
        return $b['votes'] - $a['votes'] ?: $a['product_order'] - $b['product_order'];
    });

    return [
        'products' => array_slice($qualified, 0, 3),
        'total_votes' => $product_votes
    ];
}
