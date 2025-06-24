<?php
function csq_responses_page() {
    global $wpdb;
    $table_name = $wpdb->prefix . 'quiz_responses';

    // Add new columns if they don't exist
    $ip_exists = $wpdb->get_var("SHOW COLUMNS FROM $table_name LIKE 'ip_address'");
    if (!$ip_exists) {
        $wpdb->query("ALTER TABLE $table_name ADD ip_address VARCHAR(45) DEFAULT ''");
        $wpdb->query("ALTER TABLE $table_name ADD user_agent TEXT DEFAULT ''");
    }

    $responses = $wpdb->get_results("SELECT * FROM $table_name ORDER BY created_at DESC");

    ?>
    <div class="wrap csq-admin">
        <h1 class="csq-admin-title">User Responses</h1>

        <div class="card csq-card">
            <table class="table csq-table">
                <thead>
                    <tr>
                        <th>Email</th>
                        <th>Gender</th>
                        <th>Name</th>
                        <th>Recommended Products</th>
                        <th>IP Address</th>
                        <th>Date</th>
                        <th>Actions</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($responses as $response) :
                        $product_votes = maybe_unserialize($response->product_votes);
                        ?>
                        <tr>
                            <td><?= esc_html($response->user_email) ?></td>
                            <td><?= esc_html($response->gender) ?></td>
                            <td><?= esc_html($response->fullname) ?></td>
                            <td>
                                <?php if (!empty($response->final_product)) : ?>
                                    <div class="product-tags">
                                        <?php foreach (explode(', ', $response->final_product) as $product) : ?>
                                            <span class="product-tag"><?= esc_html($product) ?></span>
                                        <?php endforeach; ?>
                                    </div>
                                <?php else : ?>
                                    <span class="text-muted">No products matched</span>
                                <?php endif; ?>
                            </td>
                            <td><?= esc_html($response->ip_address) ?></td>
                            <td><?= date('M j, Y H:i', strtotime($response->created_at)) ?></td>
                            <td>
                                <button type="button" class="btn btn-sm btn-info view-details"
                                        data-response="<?= htmlspecialchars(json_encode([
                                            'responses' => maybe_unserialize($response->responses),
                                            'product_votes' => $product_votes,
                                            'ip_address' => $response->ip_address,
                                            'user_agent' => $response->user_agent
                                        ]), ENT_QUOTES, 'UTF-8') ?>">
                                    View Details
                                </button>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    </div>

    <!-- Modal -->
    <div class="modal fade" id="responseDetailsModal" tabindex="-1">
        <div class="modal-dialog modal-lg">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">Response Details</h5>
                    <button type="button" class="close" data-dismiss="modal">&times;</button>
                </div>
                <div class="modal-body">
                    <div id="responseDetailsContent"></div>
                </div>
            </div>
        </div>
    </div>
    <style>
        .product-tags {
            display: flex;
            flex-wrap: wrap;
            gap: 4px;
        }

        .product-tag {
            display: inline-block;
            background: #f0f0f1;
            border-radius: 4px;
            padding: 4px 8px;
            font-size: 0.85em;
            color: #2d2d2d;
            border: 1px solid #e3e3f5;
        }

        .text-muted {
            color: #666;
            font-style: italic;
        }
    </style>
    <script>
    (function($) {
        'use strict';

        // Initialize modal functionality
        $(document).ready(function() {
            // Handle view details click
            $('.view-details').on('click', function() {
                const responseData = $(this).data('response');
                const modalContent = $('#responseDetailsContent');

                // Clear previous content
                modalContent.html('');

                // Build response details HTML
                let html = '<div class="response-details">';

                // IP and User Agent
                html += '<div class="mb-4">';
                html += '<h4 class="mb-3">Technical Info</h4>';
                html += '<ul class="list-group">';
                html += `<li class="list-group-item"><strong>IP Address:</strong> ${responseData.ip_address || 'N/A'}</li>`;
                html += `<li class="list-group-item"><strong>User Agent:</strong> ${responseData.user_agent || 'N/A'}</li>`;
                html += '</ul>';
                html += '</div>';

                // Answers Section
                html += '<div class="mb-4">';
                html += '<h4 class="mb-3">Quiz Answers</h4>';
                if (responseData.responses && responseData.responses.length > 0) {
                    html += '<ul class="list-group">';
                    responseData.responses.forEach((answerId, index) => {
                        html += `<li class="list-group-item">Answer ${index + 1}: ${answerId}</li>`;
                    });
                    html += '</ul>';
                } else {
                    html += '<p class="text-muted">No answers recorded</p>';
                }
                html += '</div>';

                // Product Votes Section
                html += '<div class="product-votes">';
                html += '<h4 class="mb-3">Product Votes</h4>';
                if (responseData.product_votes && Object.keys(responseData.product_votes).length > 0) {
                    html += '<div class="row">';
                    Object.entries(responseData.product_votes).forEach(([productId, votes]) => {
                        html += `
                            <div class="col-md-4 mb-3">
                                <div class="card">
                                    <div class="card-body">
                                        <h5 class="card-title">Product ID: ${productId}</h5>
                                        <p class="card-text">Votes: ${votes}</p>
                                    </div>
                                </div>
                            </div>
                        `;
                    });
                    html += '</div>';
                } else {
                    html += '<p class="text-muted">No product votes recorded</p>';
                }
                html += '</div>';

                html += '</div>'; // Close response-details

                // Insert content and show modal
                modalContent.html(html);
                $('#responseDetailsModal').modal('show');
            });
        });
    })(jQuery);
    </script>
    <?php
}
