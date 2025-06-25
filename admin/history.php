<?php
function csq_history_page() {
    global $wpdb;
    $email = isset($_GET['email']) ? sanitize_email($_GET['email']) : '';

    if (empty($email)) {
        echo '<div class="wrap"><div class="error"><p>No email specified</p></div></div>';
        return;
    }

    // Get user info
    $user_responses = $wpdb->get_row($wpdb->prepare(
        "SELECT * FROM {$wpdb->prefix}quiz_responses WHERE user_email = %s",
        $email
    ));

    // Return error if user not found
    if (!$user_responses) {
        echo '<div class="wrap"><div class="error"><p>No user found with this email address</p></div></div>';
        return;
    }

    // Get history
    $history = $wpdb->get_results($wpdb->prepare(
        "SELECT h.*, r.created_at as response_date
        FROM {$wpdb->prefix}quiz_history h
        JOIN {$wpdb->prefix}quiz_responses r ON h.response_id = r.id
        WHERE r.user_email = %s
        ORDER BY h.created_at DESC",
        $email
    ));

    // Handle database errors
    if ($wpdb->last_error) {
        error_log('Database error: ' . $wpdb->last_error);
        echo '<div class="wrap"><div class="error"><p>Database error occurred. Please check logs.</p></div></div>';
        return;
    }

    ?>
    <div class="wrap csq-admin">
        <h1 class="csq-admin-title">
            <a href="<?php echo admin_url('admin.php?page=csq-responses'); ?>" class="button">
                &larr; Back to Responses
            </a>
            Quiz History for <?php echo esc_html($email); ?>
        </h1>

        <div class="csq-user-card">
            <div class="csq-user-info">
                <h3>User Information</h3>
                <p><strong>Name:</strong> <?php echo esc_html($user_responses->fullname); ?></p>
                <p><strong>Gender:</strong> <?php echo esc_html($user_responses->gender); ?></p>
                <p><strong>Total Attempts:</strong> <?php echo (int)$user_responses->attempt_count; ?></p>
                <p><strong>First Quiz:</strong> <?php echo date('M j, Y', strtotime($user_responses->created_at)); ?></p>
                <p><strong>Last Activity:</strong> <?php echo date('M j, Y H:i', strtotime($user_responses->updated_at)); ?></p>
            </div>
            <div class="csq-user-stats">
                <h3>Activity Stats</h3>
                <div class="csq-stats-grid">
                    <div class="csq-stat-card">
                        <span class="csq-stat-value"><?php echo count($history); ?></span>
                        <span class="csq-stat-label">Completed Quizzes</span>
                    </div>
                    <div class="csq-stat-card">
                        <span class="csq-stat-value"><?php echo $user_responses->attempt_count; ?></span>
                        <span class="csq-stat-label">Total Attempts</span>
                    </div>
                    <div class="csq-stat-card">
                        <span class="csq-stat-value">
                            <?php
                            $days = $user_responses->created_at ?
                                round((time() - strtotime($user_responses->created_at)) / (60 * 60 * 24)) :
                                0;
                            echo (int)$days;
                            ?>
                        </span>
                        <span class="csq-stat-label">Days Active</span>
                    </div>
                </div>
            </div>
        </div>

        <div class="csq-card">
            <h2 class="csq-card-title">Quiz Attempt History</h2>

            <table class="wp-list-table widefat fixed striped">
                <thead>
                    <tr>
                        <th>Attempt Date</th>
                        <th>Recommended Products</th>
                        <th>Response ID</th>
                        <th>Actions</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (empty($history)): ?>
                        <tr>
                            <td colspan="4">No quiz attempts found for this user</td>
                        </tr>
                    <?php else: ?>
                        <?php foreach ($history as $entry): ?>
                        <tr>
                            <td><?php echo date('M j, Y H:i', strtotime($entry->created_at)); ?></td>
                            <td>
                                <div class="csq-product-tags">
                                    <?php
                                    $products = !empty($entry->products) ? explode(',', $entry->products) : [];
                                    foreach ($products as $product):
                                        $product = trim($product);
                                        if (!empty($product)): ?>
                                            <span class="csq-tag"><?php echo esc_html($product); ?></span>
                                        <?php endif; ?>
                                    <?php endforeach; ?>
                                </div>
                            </td>
                            <td>#<?php echo $entry->response_id; ?></td>
                            <td>
                                <button class="button button-small view-attempt-details"
                                        data-answers="<?php echo esc_attr($entry->answers); ?>"
                                        data-products="<?php echo esc_attr($entry->products); ?>">
                                    View Details
                                </button>
                            </td>
                        </tr>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>

    <!-- Details Modal -->
    <div class="csq-modal" id="attemptDetailsModal">
        <div class="csq-modal-content">
            <div class="csq-modal-header">
                <h3>Quiz Attempt Details</h3>
                <button class="csq-modal-close">&times;</button>
            </div>
            <div class="csq-modal-body">
                <div class="csq-detail-section">
                    <h4>Selected Answers</h4>
                    <div id="csq-answers-container"></div>
                </div>
                <div class="csq-detail-section">
                    <h4>Recommended Products</h4>
                    <div id="csq-products-container"></div>
                </div>
            </div>
        </div>
    </div>

    <script>
    jQuery(document).ready(function($) {
        $('.view-attempt-details').on('click', function() {
            try {
                const answers = JSON.parse($(this).data('answers'));
                const products = $(this).data('products').split(',');

                // Render answers
                let answersHtml = '<div class="csq-answers-grid">';
                if (answers && typeof answers === 'object') {
                    Object.keys(answers).forEach(key => {
                        answersHtml += `
                            <div class="csq-answer-card">
                                <div class="csq-question-id">${key.replace('question_', 'Q')}</div>
                                <div class="csq-answer-id">Answer ID: ${answers[key]}</div>
                            </div>
                        `;
                    });
                } else {
                    answersHtml += '<p>No answer data available</p>';
                }
                answersHtml += '</div>';
                $('#csq-answers-container').html(answersHtml);

                // Render products
                let productsHtml = '<div class="csq-products-grid">';
                products.forEach(product => {
                    const trimmed = product.trim();
                    if (trimmed) {
                        productsHtml += `
                            <div class="csq-product-card">
                                <div class="csq-product-name">${trimmed}</div>
                            </div>
                        `;
                    }
                });
                productsHtml += '</div>';
                $('#csq-products-container').html(productsHtml);

                // Show modal
                $('#attemptDetailsModal').show();
            } catch (e) {
                console.error('Error loading attempt details:', e);
                alert('Error loading attempt details. Please check console for details.');
            }
        });

        $('.csq-modal-close, .csq-modal').on('click', function(e) {
            if (e.target === this || $(e.target).hasClass('csq-modal-close')) {
                $('#attemptDetailsModal').hide();
            }
        });
    });
    </script>

    <style>
    .csq-user-card {
        display: flex;
        gap: 2rem;
        margin-bottom: 2rem;
        background: white;
        padding: 1.5rem;
        border-radius: 8px;
        box-shadow: 0 2px 10px rgba(0,0,0,0.05);
    }

    .csq-user-info, .csq-user-stats {
        flex: 1;
    }

    .csq-stats-grid {
        display: grid;
        grid-template-columns: repeat(3, 1fr);
        gap: 1rem;
        margin-top: 1rem;
    }

    .csq-stat-card {
        background: #f8f9fa;
        border-radius: 8px;
        padding: 1rem;
        text-align: center;
    }

    .csq-stat-value {
        display: block;
        font-size: 1.8rem;
        font-weight: 700;
        color: #4361ee;
    }

    .csq-stat-label {
        font-size: 0.85rem;
        color: #6c757d;
    }

    .csq-product-tags {
        display: flex;
        flex-wrap: wrap;
        gap: 0.5rem;
    }

    .csq-tag {
        background: rgba(67, 97, 238, 0.1);
        color: #4361ee;
        padding: 0.25rem 0.75rem;
        border-radius: 20px;
        font-size: 0.85rem;
    }

    .csq-answers-grid, .csq-products-grid {
        display: grid;
        grid-template-columns: repeat(auto-fill, minmax(120px, 1fr));
        gap: 1rem;
        margin-top: 1rem;
    }

    .csq-answer-card, .csq-product-card {
        background: white;
        border: 1px solid #eee;
        border-radius: 8px;
        padding: 1rem;
        text-align: center;
    }

    .csq-question-id {
        font-weight: 700;
        margin-bottom: 0.5rem;
    }

    .csq-modal {
        display: none;
        position: fixed;
        top: 0;
        left: 0;
        width: 100%;
        height: 100%;
        background: rgba(0,0,0,0.5);
        z-index: 9999;
        align-items: center;
        justify-content: center;
    }

    .csq-modal-content {
        background: white;
        width: 80%;
        max-width: 800px;
        max-height: 80vh;
        border-radius: 8px;
        overflow: hidden;
        display: flex;
        flex-direction: column;
    }

    .csq-modal-header {
        padding: 1rem 1.5rem;
        background: #4361ee;
        color: white;
        display: flex;
        justify-content: space-between;
        align-items: center;
    }

    .csq-modal-body {
        padding: 1.5rem;
        overflow-y: auto;
    }

    .csq-modal-close {
        background: none;
        border: none;
        color: white;
        font-size: 1.5rem;
        cursor: pointer;
    }

    .csq-detail-section {
        margin-bottom: 2rem;
    }

    .csq-detail-section h4 {
        margin-bottom: 1rem;
        padding-bottom: 0.5rem;
        border-bottom: 1px solid #eee;
    }
    </style>
    <?php
}
