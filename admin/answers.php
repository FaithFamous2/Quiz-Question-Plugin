<?php
// File: wp-content/plugins/custom-skin-quiz/admin/answers.php

function csq_answers_page()
{
    if (!current_user_can('manage_options')) {
        wp_die(__('You do not have sufficient permissions to access this page.'));
    }

    global $wpdb;
    $question_id = absint($_GET['question_id'] ?? 0);
    if (!$question_id)
        wp_die('Invalid question ID');

    $ans_table = $wpdb->prefix . 'quiz_answers';
    $prod_table = $wpdb->prefix . 'skin_products';

    // handle save/delete
    if ($_SERVER['REQUEST_METHOD'] === 'POST') {
        if (!wp_verify_nonce($_POST['csq_nonce'] ?? '', 'csq_answer_nonce')) {
            wp_die('Security check failed');
        }
        csq_handle_answer_form($question_id);
    }
    if (isset($_GET['action'], $_GET['id']) && $_GET['action'] === 'delete') {
        csq_delete_answer(absint($_GET['id']));
    }

    $answers = $wpdb->get_results(
        $wpdb->prepare("SELECT * FROM $ans_table WHERE question_id = %d ORDER BY answer_order ASC", $question_id)
    );
    $editing = (isset($_GET['action'], $_GET['id']) && $_GET['action'] === 'edit')
        ? $wpdb->get_row($wpdb->prepare("SELECT * FROM $ans_table WHERE id = %d", $_GET['id']))
        : null;

    $products = $wpdb->get_results("SELECT id, product_name FROM $prod_table ORDER BY product_name");
    $selected = $editing ? explode(',', $editing->product_suggestions) : [];
    ?>
    <div class="wrap csq-admin">
        <h1 class="csq-admin-title">
            Manage Answers for Question #<?= $question_id ?>
        </h1>
        <a href="?page=csq-questions" class="btn btn-secondary">&laquo; Back to Questions</a>

        <div class="csq-flex">
            <!-- Left: Add/Edit Form -->
            <div class="csq-col">
                <div class="csq-card">
                    <h2 class="csq-card-title">
                        <?= $editing ? 'Edit Answer' : 'Add New Answer' ?>
                    </h2>
                    <form method="POST">
                        <?php wp_nonce_field('csq_answer_nonce', 'csq_nonce'); ?>
                        <input type="hidden" name="answer_id" value="<?= intval($editing->id ?? 0) ?>">

                        <div class="form-group">
                            <label>Answer Text</label>
                            <input type="text" name="answer_text" class="form-control"
                                value="<?= esc_attr($editing->answer_text ?? '') ?>" required>
                        </div>

                        <div class="form-group">
                            <label>Associated Products</label>
                            <select name="product_suggestions[]" class="form-control select2-tags" multiple="multiple"
                                data-placeholder="Select products…" required>
                                <?php foreach ($products as $p): ?>
                                    <option value="<?= $p->id ?>" <?= in_array($p->id, $selected) ? 'selected' : '' ?>>
                                        <?= esc_html($p->product_name) ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </div>

                        <div class="form-group">
                            <label>Display Order</label>
                            <input type="number" name="answer_order" class="form-control"
                                value="<?= intval($editing->answer_order ?? 0) ?>">
                        </div>

                        <button type="submit" class="btn btn-primary">
                            <?= $editing ? 'Update Answer' : 'Save Answer' ?>
                        </button>
                        <?php if ($editing): ?>
                            <a href="?page=csq-answers&question_id=<?= $question_id ?>" class="btn btn-secondary">
                                Cancel
                            </a>
                        <?php endif; ?>
                    </form>
                </div>
            </div>

            <!-- Right: Answers Grid -->
            <div class="csq-col">
                <h2 class="csq-section-title">
                    Answers (<?= count($answers) ?>)
                </h2>
                <div class="csq-answer-grid">
                    <?php foreach ($answers as $a):
                        $names = array_map('trim', explode(',', $a->product_suggestions));
                        ?>
                        <div class="csq-answer-card">
                            <div class="csq-answer-text">
                                <?= esc_html(wp_trim_words($a->answer_text, 12, '…')) ?>
                            </div>
                            <div class="csq-answer-products">
                                <?php foreach ($names as $id): ?>
                                    <span class="csq-badge"><?= esc_html($wpdb->get_var(
                                        $wpdb->prepare("SELECT product_name FROM $prod_table WHERE id=%d", absint($id))
                                    )) ?></span>
                                <?php endforeach; ?>
                            </div>
                            <div class="csq-answer-footer">
                                <span>Order: <?= intval($a->answer_order) ?></span>
                                <div class="csq-card-actions">
                                    <a href="?page=csq-answers&question_id=<?= $question_id ?>&action=edit&id=<?= $a->id ?>"
                                        class="btn btn-sm btn-info">Edit</a>
                                    <a href="?page=csq-answers&question_id=<?= $question_id ?>&action=delete&id=<?= $a->id ?>"
                                        class="btn btn-sm btn-danger" onclick="return confirm('Delete this answer?')">Delete</a>
                                </div>
                            </div>
                        </div>
                    <?php endforeach; ?>
                    <?php if (empty($answers)): ?>
                        <p>No answers yet. Add one via the form.</p>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>
    <?php
}

function csq_handle_answer_form($question_id)
{
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
function csq_delete_answer($id)
{
    global $wpdb;
    $wpdb->delete($wpdb->prefix . 'quiz_answers', ['id' => absint($id)]);
}
