<?php
// File: wp-content/plugins/custom-skin-quiz/admin/questions.php

function csq_questions_page() {
    global $wpdb;
    $table = $wpdb->prefix . 'quiz_questions';

    // Handle form submissions
    if ($_SERVER['REQUEST_METHOD'] === 'POST') {
        csq_handle_question_form();
    }

    // Handle deletions
    if (isset($_GET['action'], $_GET['id']) && $_GET['action'] === 'delete') {
        csq_delete_question(absint($_GET['id']));
    }

    $questions = $wpdb->get_results("SELECT * FROM $table ORDER BY question_order ASC");
    $editing = ( isset($_GET['action'], $_GET['id']) && $_GET['action'] === 'edit' )
        ? $wpdb->get_row($wpdb->prepare("SELECT * FROM $table WHERE id = %d", $_GET['id']))
        : null;
    ?>
    <div class="wrap csq-admin">
        <h1 class="csq-admin-title">Manage Questions</h1>

        <div class="csq-flex">
            <!-- LEFT: Form -->
            <div class="csq-col">
                <div class="csq-card">
                    <h2 class="csq-card-title">
                        <?= $editing ? 'Edit Question' : 'Add New Question' ?>
                    </h2>
                    <form method="POST">
                        <?php wp_nonce_field('csq_question_nonce','csq_nonce'); ?>
                        <input type="hidden" name="question_id" value="<?= $editing->id ?? 0 ?>">

                        <div class="form-group">
                            <label>Question Text</label>
                            <textarea name="question_text" class="form-control" rows="3" required><?= esc_textarea($editing->question_text ?? '') ?></textarea>
                        </div>

                        <div class="form-group">
                            <label>Image URL</label>
                            <input type="url" name="image_url" class="form-control"
                                   value="<?= esc_url($editing->image_url ?? '') ?>">
                        </div>

                        <div class="form-group">
                            <label>Display Order</label>
                            <input type="number" name="question_order" class="form-control"
                                   value="<?= intval($editing->question_order ?? 0) ?>">
                        </div>

                        <button type="submit" class="btn btn-primary">
                            <?= $editing ? 'Update Question' : 'Save Question' ?>
                        </button>
                        <?php if ($editing): ?>
                            <a href="?page=csq-questions" class="btn btn-secondary">Cancel</a>
                        <?php endif; ?>
                    </form>
                </div>
            </div>

            <!-- RIGHT: Question Cards -->
            <div class="csq-col">
                <h2 class="csq-section-title">
                    All Questions (<?= count($questions) ?>)
                </h2>
                <div class="csq-question-grid">
                    <?php foreach ($questions as $q): ?>
                        <div class="csq-question-card">
                            <?php if ($q->image_url): ?>
                                <div class="csq-question-image" style="background-image:url('<?= esc_url($q->image_url) ?>')"></div>
                            <?php endif; ?>
                            <div class="csq-question-content">
                                <p><?= esc_html(wp_trim_words($q->question_text, 15, '…')) ?></p>
                                <span class="csq-badge">Order: <?= intval($q->question_order) ?></span>
                            </div>
                            <div class="csq-question-actions">
                                <a href="?page=csq-questions&action=edit&id=<?= $q->id ?>" class="btn btn-sm btn-info">Edit</a>
                                <a href="?page=csq-questions&action=delete&id=<?= $q->id ?>"
                                   class="btn btn-sm btn-danger"
                                   onclick="return confirm('Delete this question?');">Delete</a>
                                <a href="?page=csq-answers&question_id=<?= $q->id ?>"
                                   class="btn btn-sm btn-primary">Manage Answers</a>
                            </div>
                        </div>
                    <?php endforeach; ?>
                    <?php if (empty($questions)): ?>
                        <p>No questions yet. Use the form to add one.</p>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>
    <?php
}

function csq_handle_question_form() {
    global $wpdb;
    $table = $wpdb->prefix . 'quiz_questions';

    if (!wp_verify_nonce($_POST['csq_nonce'], 'csq_question_nonce')) {
        wp_die('Security check failed');
    }

    $data = [
        'question_text' => sanitize_textarea_field($_POST['question_text']),
        'image_url' => esc_url_raw($_POST['image_url']),
        'question_order' => absint($_POST['question_order'])
    ];

    if ($_POST['question_id']) {
        $wpdb->update($table, $data, ['id' => absint($_POST['question_id'])]);
    } else {
        $wpdb->insert($table, $data);
    }
}

function csq_delete_question($id) {
    global $wpdb;
    $wpdb->delete($wpdb->prefix . 'quiz_questions', ['id' => absint($id)]);
}
