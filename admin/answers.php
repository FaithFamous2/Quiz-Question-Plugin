<?php
function csq_answers_page() {

       // Verify user capabilities
       if (!current_user_can('manage_options')) {
        wp_die(__('You do not have sufficient permissions to access this page.'));
    }

    global $wpdb;
    $table = $wpdb->prefix . 'quiz_answers';

    // Add nonce verification to form handling
    if ($_SERVER['REQUEST_METHOD'] === 'POST') {
        if (!isset($_POST['csq_nonce']) || !wp_verify_nonce($_POST['csq_nonce'], 'csq_answer_nonce')) {
            wp_die('Security check failed');
        }
    }


    global $wpdb;
    $table = $wpdb->prefix . 'quiz_answers';
    $question_id = absint($_GET['question_id']);

    if (!$question_id) {
        wp_die('Invalid question ID');
    }

    // Handle form submissions
    if ($_SERVER['REQUEST_METHOD'] === 'POST') {
        csq_handle_answer_form($question_id);
    }

    // Handle deletions
    if (isset($_GET['action']) && $_GET['action'] === 'delete' && isset($_GET['id'])) {
        csq_delete_answer($_GET['id']);
    }

    $answers = $wpdb->get_results($wpdb->prepare(
        "SELECT * FROM $table WHERE question_id = %d ORDER BY answer_order ASC",
        $question_id
    ));

    $editing = isset($_GET['action']) && $_GET['action'] === 'edit' ?
        $wpdb->get_row($wpdb->prepare("SELECT * FROM $table WHERE id = %d", $_GET['id'])) : null;

    $products = $wpdb->get_results("SELECT id, product_name FROM {$wpdb->prefix}skin_products ORDER BY product_name ASC");
    $selected_products = $editing ? explode(',', $editing->product_suggestions) : [];

    ?>
    <div class="wrap csq-admin">
        <h1 class="csq-admin-title">Manage Answers for Question #<?= $question_id ?></h1>
        <a href="?page=csq-questions" class="button">&laquo; Back to Questions</a>

        <div class="card csq-card mt-4">
            <h2 class="csq-card-title"><?= $editing ? 'Edit Answer' : 'Add New Answer' ?></h2>
            <form method="POST">
                <?php wp_nonce_field('csq_answer_nonce', 'csq_nonce'); ?>
                <!-- <input type="hidden" name="answer_id" value="<?= $editing->id ?? 0 ?>"> -->

                <input type="hidden" name="answer_id" value="<?php echo isset($editing->id) ? absint($editing->id) : 0; ?>">                <div class="form-group">
                    <label>Answer Text</label>
                    <input type="text" name="answer_text" class="form-control"
                           value="<?= $editing->answer_text ?? '' ?>" required>
                </div>

                <div class="form-group">
                    <label>Associated Products</label>
                    <select name="product_suggestions[]" class="form-control select2" multiple required>
                        <?php foreach ($products as $product) : ?>
                        <option value="<?= $product->id ?>" <?= in_array($product->id, $selected_products) ? 'selected' : '' ?>>
                            <?= esc_html($product->product_name) ?>
                        </option>
                        <?php endforeach; ?>
                    </select>
                </div>

                <div class="row">
                    <div class="col-md-6">
                        <div class="form-group">
                            <label>Display Order</label>
                            <input type="number" name="answer_order" class="form-control"
                                   value="<?= $editing->answer_order ?? 0 ?>">
                        </div>
                    </div>
                </div>

                <button type="submit" class="btn btn-primary">Save Answer</button>
                <a href="?page=csq-answers&question_id=<?= $question_id ?>" class="btn btn-secondary">Cancel</a>
            </form>
        </div>

        <div class="card csq-card mt-4">
            <h2 class="csq-card-title">Question Answers</h2>
            <table class="table csq-table">
                <thead>
                    <tr>
                        <th>Answer</th>
                        <th>Products</th>
                        <th>Order</th>
                        <th>Actions</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($answers as $answer) :
                        $product_names = [];
                        foreach (explode(',', $answer->product_suggestions) as $pid) {
                            $product = $wpdb->get_row($wpdb->prepare(
                                "SELECT product_name FROM {$wpdb->prefix}skin_products WHERE id = %d", $pid
                            ));
                            if ($product) $product_names[] = $product->product_name;
                        }
                        ?>
                        <tr>
                            <td><?= esc_html($answer->answer_text) ?></td>
                            <td><?= implode(', ', $product_names) ?></td>
                            <td><?= $answer->answer_order ?></td>
                            <td>
                                <a href="?page=csq-answers&question_id=<?= $question_id ?>&action=edit&id=<?= $answer->id ?>"
                                   class="btn btn-sm btn-primary">Edit</a>
                                <a href="?page=csq-answers&question_id=<?= $question_id ?>&action=delete&id=<?= $answer->id ?>"
                                   class="btn btn-sm btn-danger"
                                   onclick="return confirm('Delete this answer?')">Delete</a>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    </div>
    <?php
}

function csq_handle_answer_form($question_id) {
    global $wpdb;
    $table = $wpdb->prefix . 'quiz_answers';

    if (!wp_verify_nonce($_POST['csq_nonce'], 'csq_answer_nonce')) {
        wp_die('Security check failed');
    }

    // Validate required fields
    if (empty($_POST['answer_text']) || !isset($_POST['product_suggestions'])) {
        wp_die(__('Please fill all required fields'));
    }

    $data = [
        'question_id' => $question_id,
        'answer_text' => sanitize_text_field($_POST['answer_text']),
        'product_suggestions' => implode(',', array_map('absint', $_POST['product_suggestions'])),
        'answer_order' => absint($_POST['answer_order'])
    ];

    $answer_id = isset($_POST['answer_id']) ? absint($_POST['answer_id']) : 0;

    if ($answer_id) {
        // Update existing answer
        $result = $wpdb->update($table, $data, ['id' => $answer_id]);

        if (false === $result) {
            wp_die(__('Database error: ') . $wpdb->last_error);
        }
    } else {
        // Insert new answer
        $result = $wpdb->insert($table, $data);

        if (false === $result) {
            wp_die(__('Database error: ') . $wpdb->last_error);
        }
    }
}
function csq_delete_answer($id) {
    global $wpdb;
    $wpdb->delete($wpdb->prefix . 'quiz_answers', ['id' => absint($id)]);
}
