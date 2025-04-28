
<?php
/*
Plugin Name: Advanced Skin Care Quiz
Description: Professional skincare quiz with product recommendations and analytics. Includes multi-product associations and threshold-based suggestions.
Version: 2.0
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
register_activation_hook(__FILE__, 'csq_create_database');

function csq_create_database() {
    global $wpdb;
    $charset_collate = $wpdb->get_charset_collate();

    $tables = [
        "skin_products" => "(
            id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
            product_name VARCHAR(255) NOT NULL,
            image_url VARCHAR(255),
            product_details TEXT,
            product_link VARCHAR(255),
            count_threshold INT DEFAULT 1,
            product_order INT DEFAULT 0,
            tags VARCHAR(255),
            PRIMARY KEY (id)
        )",
        "quiz_questions" => "(
            id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
            question_text TEXT NOT NULL,
            image_url VARCHAR(255),
            question_order INT DEFAULT 0,
            PRIMARY KEY (id)
        )",
        "quiz_answers" => "(
            id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
            question_id BIGINT UNSIGNED NOT NULL,
            answer_text VARCHAR(255) NOT NULL,
            product_suggestions VARCHAR(255),
            answer_order INT DEFAULT 0,
            PRIMARY KEY (id)
        )",
        "quiz_responses" => "(
            id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
            user_email VARCHAR(100) NOT NULL,
            gender VARCHAR(50),
            responses TEXT NOT NULL,
            product_votes TEXT NOT NULL,
            final_product VARCHAR(255),
            created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
            PRIMARY KEY (id)
        )"
    ];

    require_once(ABSPATH . 'wp-admin/includes/upgrade.php');

    foreach ($tables as $name => $structure) {
        $table_name = $wpdb->prefix . $name;
        $sql = "CREATE TABLE $table_name $structure $charset_collate;";
        dbDelta($sql);
    }
}

// =============================================================================
// DASHBOARD PAGE & ADMIN INTERFACE
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

    // Hidden answer page
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
add_action('wp_ajax_csq_process_quiz', 'csq_process_quiz');
add_action('wp_ajax_nopriv_csq_process_quiz', 'csq_process_quiz');

function csq_quiz_shortcode() {
    wp_enqueue_style('csq-frontend');
    wp_enqueue_script('csq-frontend');

    $questions = csq_get_questions();
    $answers   = csq_get_answers();

    ob_start(); ?>
    <div id="csq-quiz-container" class="csq-container">
        <div class="csq-progress">
            <div class="csq-progress-bar"></div>
        </div>
        <form id="csq-quiz-form" class="csq-quiz-form">
            <?php include CSQ_PLUGIN_PATH . 'templates/quiz-form.php'; ?>
        </form>
    </div>
    <?php
    return ob_get_clean();
}



// AJAX hooks
add_action('wp_ajax_csq_save_contact',   'csq_save_contact');
add_action('wp_ajax_nopriv_csq_save_contact', 'csq_save_contact');
add_action('wp_ajax_csq_process_quiz',   'csq_process_quiz');
add_action('wp_ajax_nopriv_csq_process_quiz', 'csq_process_quiz');


/**
 * Step 1: preliminarily save email + gender
 */
function csq_save_contact() {
    check_ajax_referer('csq_quiz_nonce','security');
    if ( empty($_POST['email']) ) {
      wp_send_json_error('Email required', 400);
    }
    $email  = sanitize_email($_POST['email']);
    $gender = sanitize_text_field($_POST['gender'] ?? '');
    if ( ! is_email($email) ) {
      wp_send_json_error('Invalid email', 400);
    }

    global $wpdb;
    $table = $wpdb->prefix . 'quiz_responses';
    $insert = $wpdb->insert($table, [
      'user_email'    => $email,
      'gender'        => $gender,
      'responses'     => maybe_serialize([]),
      'product_votes' => maybe_serialize([]),
      'final_product' => '',
      'created_at'    => current_time('mysql'),
    ]);
    if ( ! $insert ) {
      wp_send_json_error('DB Error', 500);
    }

    wp_send_json_success([ 'session_id' => (int)$wpdb->insert_id ]);
  }

  /**
   * Step 2: finalize answers, calculate + email
   */
  function csq_process_quiz() {
    check_ajax_referer('csq_quiz_nonce','security');
    global $wpdb;

    $session_id = isset($_POST['session_id']) ? absint($_POST['session_id']) : 0;
    $email      = sanitize_email($_POST['email']);
    $gender     = sanitize_text_field($_POST['gender'] ?? '');
    $answers    = array_map('absint', (array) $_POST['answers']);

    if ( ! is_email($email) || empty($answers) ) {
      wp_send_json_error('Missing data', 400);
    }

    // calculate recommendations
    $results = csq_calculate_results($answers);
    $names   = wp_list_pluck($results['products'], 'name');
    $final   = implode(', ', $names);

    // prepare DB update
    $data = [
      'responses'     => maybe_serialize($answers),
      'product_votes' => maybe_serialize($results['total_votes']),
      'final_product' => $final,
    ];

    if ( $session_id ) {
      $wpdb->update($wpdb->prefix . 'quiz_responses', $data, ['id' => $session_id]);
    } else {
      $data['user_email'] = $email;
      $data['gender']     = $gender;
      $data['created_at'] = current_time('mysql');
      $wpdb->insert($wpdb->prefix . 'quiz_responses', $data);
      $session_id = $wpdb->insert_id;
    }

    // send follow-up email
    $subject = $results['products']
      ? 'Your Skincare Product Recommendations'
      : 'Finish Your Skincare Quiz';
    $message = $results['products']
      ? "Hello,\n\nBased on your answers, we recommend:\n" . implode("\n", $names)
      : "Hello,\n\nIt looks like you didn't complete the quiz. Click here to finish it: " . site_url('/your-quiz-page');

    wp_mail($email, $subject, nl2br($message));

    wp_send_json_success([
      'products'   => $results['products'],
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
        'nonce'   => wp_create_nonce('csq_quiz_nonce')
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

/**
 * Fetch answers from the database and group them by their question ID.
 */
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
/**
 * Calculate quiz results:
 * - For each answer ID, add votes from its product suggestions.
 * - Only include products that meet their count_threshold.
 * - Return the top three suggestions.
 */
// Enhanced product calculation
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

                // Get product details with caching
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

    // Filter and sort products
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
