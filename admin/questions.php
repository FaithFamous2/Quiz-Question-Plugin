<?php
function csq_questions_page() {
    global $wpdb;
    $table = $wpdb->prefix . 'quiz_questions';

    // Handle form submissions
    if ($_SERVER['REQUEST_METHOD'] === 'POST') {
        csq_handle_question_form();
    }

    // Handle deletions
    if (isset($_GET['action']) && $_GET['action'] === 'delete' && isset($_GET['id'])) {
        csq_delete_question($_GET['id']);
    }

    $questions = $wpdb->get_results("SELECT * FROM $table ORDER BY question_order ASC");
    $editing = isset($_GET['action']) && $_GET['action'] === 'edit' ? $wpdb->get_row($wpdb->prepare("SELECT * FROM $table WHERE id = %d", $_GET['id'])) : null;

    ?>
    <div class="wrap csq-admin">
        <h1 class="csq-admin-title">Manage Questions</h1>

        <div class="card csq-card">
            <h2 class="csq-card-title"><?= $editing ? 'Edit Question' : 'Add New Question' ?></h2>
            <form method="POST">
                <?php wp_nonce_field('csq_question_nonce', 'csq_nonce'); ?>
                <input type="hidden" name="question_id" value="<?= $editing->id ?? 0 ?>">

                <div class="form-group">
                    <label>Question Text</label>
                    <textarea name="question_text" class="form-control" rows="3" required><?=
                        $editing->question_text ?? '' ?></textarea>
                </div>

                <div class="row">
                    <div class="col-md-6">
                        <div class="form-group">
                            <label>Image URL</label>
                            <input type="url" name="image_url" class="form-control"
                                   value="<?= $editing->image_url ?? '' ?>">
                        </div>
                    </div>
                    <div class="col-md-6">
                        <div class="form-group">
                            <label>Display Order</label>
                            <input type="number" name="question_order" class="form-control"
                                   value="<?= $editing->question_order ?? 0 ?>">
                        </div>
                    </div>
                </div>

                <button type="submit" class="btn btn-primary">Save Question</button>
                <a href="?page=csq-questions" class="btn btn-secondary">Cancel</a>
            </form>
        </div>

        <div class="card csq-card mt-4">
            <h2 class="csq-card-title">All Questions</h2>
            <table class="table csq-table">
                <thead>
                    <tr>
                        <th>Question</th>
                        <th>Order</th>
                        <th>Actions</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($questions as $question) : ?>
                    <tr>
                        <td><?= esc_html(wp_trim_words($question->question_text, 10)) ?></td>
                        <td><?= $question->question_order ?></td>
                        <td>
                            <a href="?page=csq-questions&action=edit&id=<?= $question->id ?>"
                               class="btn btn-sm btn-primary">Edit</a>
                            <a href="?page=csq-questions&action=delete&id=<?= $question->id ?>"
                               class="btn btn-sm btn-danger"
                               onclick="return confirm('Delete this question?')">Delete</a>
                            <a href="?page=csq-answers&question_id=<?= $question->id ?>"
                               class="btn btn-sm btn-info">Manage Answers</a>
                        </td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
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
