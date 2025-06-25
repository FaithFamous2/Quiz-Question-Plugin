<?php
function csq_responses_page() {
    global $wpdb;
    $table_name = $wpdb->prefix . 'quiz_responses';

    // Get response stats
    $total_responses = $wpdb->get_var("SELECT COUNT(*) FROM $table_name");

    // Gender stats with proper handling
    $gender_stats = $wpdb->get_results("
        SELECT
            CASE WHEN gender IS NULL OR gender = '' THEN 'Not Specified' ELSE gender END AS gender,
            COUNT(*) as count
        FROM $table_name
        GROUP BY gender
    ");

    // Top products with proper grouping
    $top_products = $wpdb->get_results("
        SELECT
            TRIM(final_product) AS product_name,
            COUNT(*) as count
        FROM $table_name
        WHERE final_product IS NOT NULL AND final_product != ''
        GROUP BY TRIM(final_product)
        ORDER BY count DESC
        LIMIT 5
    ");

    // Date filter
    $current_month = date('Y-m');
    $month_filter = isset($_GET['month']) ? sanitize_text_field($_GET['month']) : $current_month;

    // Get monthly stats
    $monthly_stats = $wpdb->get_results("
        SELECT
            DATE_FORMAT(created_at, '%Y-%m') as month,
            COUNT(*) as count
        FROM $table_name
        GROUP BY month
        ORDER BY month DESC
    ");

    // Get responses for selected month
    $filtered_responses = $wpdb->get_results($wpdb->prepare("
        SELECT *
        FROM $table_name
        WHERE DATE_FORMAT(created_at, '%%Y-%%m') = %s
        ORDER BY created_at DESC
    ", $month_filter));

    // Get product distribution
    $product_distribution = [];
    foreach($filtered_responses as $response) {
        if (!empty($response->final_product)) {
            $products = explode(',', $response->final_product);
            foreach($products as $product) {
                $product = trim($product);
                if ($product) {
                    if (!isset($product_distribution[$product])) {
                        $product_distribution[$product] = 0;
                    }
                    $product_distribution[$product]++;
                }
            }
        }
    }
    arsort($product_distribution);

    // Prepare chart data
    $gender_labels = [];
    $gender_data = [];
    $gender_colors = ['#4361ee', '#3a0ca3', '#f72585', '#7209b7', '#4cc9f0'];

    foreach ($gender_stats as $index => $stat) {
        $gender_labels[] = esc_js($stat->gender);
        $gender_data[] = $stat->count;
    }

    $product_labels = [];
    $product_data = [];

    foreach ($top_products as $product) {
        $product_labels[] = esc_js($product->product_name);
        $product_data[] = $product->count;
    }

    $timeline_labels = [];
    $timeline_data = [];

    foreach ($monthly_stats as $stat) {
        $timeline_labels[] = esc_js(date('M Y', strtotime($stat->month . '-01')));
        $timeline_data[] = $stat->count;
    }

    ?>
    <div class="wrap csq-admin">
        <h1 class="csq-admin-title">User Responses & Analytics</h1>

        <!-- Summary Cards -->
        <div class="csq-summary-cards">
            <div class="csq-card csq-summary-card">
                <div class="csq-card-icon">
                    <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                        <path d="M17 21v-2a4 4 0 0 0-4-4H5a4 4 0 0 0-4 4v2"></path>
                        <circle cx="9" cy="7" r="4"></circle>
                        <path d="M23 21v-2a4 4 0 0 0-3-3.87"></path>
                        <path d="M16 3.13a4 4 0 0 1 0 7.75"></path>
                    </svg>
                </div>
                <div class="csq-card-content">
                    <h3>Total Responses</h3>
                    <p><?php echo number_format($total_responses); ?></p>
                </div>
            </div>

            <div class="csq-card csq-summary-card">
                <div class="csq-card-icon">
                    <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                        <line x1="12" y1="1" x2="12" y2="23"></line>
                        <path d="M17 5H9.5a3.5 3.5 0 0 0 0 7h5a3.5 3.5 0 0 1 0 7H6"></path>
                    </svg>
                </div>
                <div class="csq-card-content">
                    <h3>Current Month</h3>
                    <p><?php echo date('F Y', strtotime($month_filter)); ?></p>
                </div>
            </div>

            <div class="csq-card csq-summary-card">
                <div class="csq-card-icon">
                    <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                        <circle cx="12" cy="12" r="10"></circle>
                        <polyline points="12 6 12 12 16 14"></polyline>
                    </svg>
                </div>
                <div class="csq-card-content">
                    <h3>Avg. Time</h3>
                    <p>2.5 min</p>
                </div>
            </div>
        </div>

        <!-- Analytics Section -->
        <div class="csq-analytics-section">
            <div class="csq-card">
                <div class="csq-card-header">
                    <h2 class="csq-card-title">Response Analytics</h2>
                    <div class="csq-date-filter">
                        <form method="get">
                            <input type="hidden" name="page" value="csq-responses">
                            <select name="month" class="form-control" onchange="this.form.submit()">
                                <?php foreach($monthly_stats as $stat): ?>
                                    <option value="<?php echo esc_attr($stat->month); ?>" <?php selected($month_filter, $stat->month); ?>>
                                        <?php echo date('F Y', strtotime($stat->month . '-01')); ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </form>
                    </div>
                </div>

                <div class="csq-analytics-grid">
                    <div class="csq-analytics-chart">
                        <h3>Gender Distribution</h3>
                        <div class="csq-chart-container">
                            <canvas id="genderChart"></canvas>
                        </div>
                    </div>

                    <div class="csq-analytics-chart">
                        <h3>Top Recommended Products</h3>
                        <div class="csq-chart-container">
                            <canvas id="productsChart"></canvas>
                        </div>
                    </div>

                    <div class="csq-analytics-chart">
                        <h3>Response Timeline</h3>
                        <div class="csq-chart-container">
                            <canvas id="timelineChart"></canvas>
                        </div>
                    </div>

                    <div class="csq-analytics-list">
                        <h3>Top Products This Month</h3>
                        <div class="csq-product-distribution">
                            <?php if (!empty($product_distribution)): ?>
                                <ul>
                                    <?php $counter = 1; ?>
                                    <?php foreach($product_distribution as $product => $count): ?>
                                        <li>
                                            <span class="csq-product-rank"><?php echo $counter++; ?></span>
                                            <span class="csq-product-name"><?php echo esc_html($product); ?></span>
                                            <span class="csq-product-count"><?php echo number_format($count); ?></span>
                                        </li>
                                    <?php endforeach; ?>
                                </ul>
                            <?php else: ?>
                                <p>No product recommendations this month</p>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Responses Table -->
        <div class="csq-card mt-4">
            <div class="csq-card-header">
                <h2 class="csq-card-title">User Responses</h2>
            </div>

            <table class="table csq-table" id="responsesTable">
                <thead>
                    <tr>
                        <th>Email</th>
                        <th>Gender</th>
                        <th>Name</th>
                        <th>Recommended Products</th>
                        <th>Date</th>
                        <th>Actions</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach($filtered_responses as $response):
                        $product_votes = maybe_unserialize($response->product_votes);
                        $final_products = !empty($response->final_product) ? explode(',', $response->final_product) : [];
                    ?>
                        <tr>
                            <td><?php echo esc_html($response->user_email); ?></td>
                            <td><?php echo esc_html($response->gender); ?></td>
                            <td><?php echo esc_html($response->fullname); ?></td>
                            <td>
                                <?php if (!empty($final_products)): ?>
                                    <div class="csq-tag-container">
                                        <?php foreach($final_products as $product):
                                            $product = trim($product);
                                            if (!empty($product)): ?>
                                                <span class="csq-tag"><?php echo esc_html($product); ?></span>
                                            <?php endif; ?>
                                        <?php endforeach; ?>
                                    </div>
                                <?php else: ?>
                                    <span class="text-muted">No products matched</span>
                                <?php endif; ?>
                            </td>
                            <td><?php echo date('M j, Y H:i', strtotime($response->created_at)); ?></td>
                            <td>
                                <button type="button" class="btn btn-sm btn-info view-details"
                                        data-response="<?php echo htmlspecialchars(json_encode([
                                            'responses' => maybe_unserialize($response->responses),
                                            'product_votes' => $product_votes,
                                            'ip_address' => $response->ip_address,
                                            'user_agent' => $response->user_agent,
                                            'created_at' => $response->created_at,
                                            'gender' => $response->gender,
                                            'fullname' => $response->fullname,
                                            'email' => $response->user_email
                                        ]), ENT_QUOTES, 'UTF-8'); ?>">
                                    View Details
                                </button>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    </div>

    <!-- Response Details Modal -->
    <div class="csq-modal" id="responseDetailsModal">
        <div class="csq-modal-content">
            <div class="csq-modal-header">
                <h3 class="csq-modal-title">Response Details</h3>
                <button class="csq-modal-close">&times;</button>
            </div>
            <div class="csq-modal-body">
                <div id="responseDetailsContent"></div>
            </div>
        </div>
    </div>

    <script>
    (function($) {
        'use strict';

        // Event delegation for dynamic elements
        $(document)
            .on('click', '.view-details', handleViewDetails)
            .on('click', '.csq-modal-close, .csq-modal', handleCloseModal);

        // Handle view details
        function handleViewDetails() {
            const responseData = $(this).data('response');
            const modalContent = $('#responseDetailsContent');

            // Clear previous content
            modalContent.html('');

            // Sanitize data for display
            const safeData = {
                fullname: responseData.fullname || 'N/A',
                email: responseData.email || 'N/A',
                gender: responseData.gender || 'N/A',
                created_at: responseData.created_at ? new Date(responseData.created_at).toLocaleString() : 'N/A',
                ip_address: responseData.ip_address || 'N/A',
                user_agent: responseData.user_agent || 'N/A',
                responses: responseData.responses || {},
                product_votes: responseData.product_votes || {}
            };

            // Build response details HTML
            let html = '<div class="response-details">';

            // User info section
            html += '<div class="csq-detail-section">';
            html += '<h4>User Information</h4>';
            html += '<div class="csq-detail-grid">';
            html += '<div><label>Name:</label><p>' + safeData.fullname + '</p></div>';
            html += '<div><label>Email:</label><p>' + safeData.email + '</p></div>';
            html += '<div><label>Gender:</label><p>' + safeData.gender + '</p></div>';
            html += '<div><label>Date:</label><p>' + safeData.created_at + '</p></div>';
            html += '</div></div>';

            // Technical info section
            html += '<div class="csq-detail-section">';
            html += '<h4>Technical Information</h4>';
            html += '<div class="csq-detail-grid">';
            html += '<div><label>IP Address:</label><p>' + safeData.ip_address + '</p></div>';
            html += '<div><label>User Agent:</label><p>' + safeData.user_agent + '</p></div>';
            html += '</div></div>';

            // Answers Section
            html += '<div class="csq-detail-section">';
            html += '<h4>Quiz Answers</h4>';
            if (Object.keys(safeData.responses).length > 0) {
                html += '<div class="csq-answer-grid">';
                Object.entries(safeData.responses).forEach(([qid, aid]) => {
                    html += `
                        <div class="csq-answer-card">
                            <div class="csq-answer-header">
                                <span>Question ${qid.replace('question_', '')}</span>
                            </div>
                            <div class="csq-answer-body">
                                ${aid ? 'Answer ID: ' + aid : 'No answer recorded'}
                            </div>
                        </div>
                    `;
                });
                html += '</div>';
            } else {
                html += '<p class="csq-no-data">No answers recorded</p>';
            }
            html += '</div>';

            // Product Votes Section
            html += '<div class="csq-detail-section">';
            html += '<h4>Product Recommendations</h4>';
            if (Object.keys(safeData.product_votes).length > 0) {
                html += '<div class="csq-product-grid">';
                Object.entries(safeData.product_votes).forEach(([productId, votes]) => {
                    // Calculate max votes for relative progress
                    const maxVotes = Math.max(...Object.values(safeData.product_votes));
                    const percentage = maxVotes > 0 ? (votes / maxVotes) * 100 : 0;

                    html += `
                        <div class="csq-product-card">
                            <div class="csq-product-header">
                                <span>Product ID: ${productId}</span>
                                <span class="csq-vote-count">${votes} votes</span>
                            </div>
                            <div class="csq-progress-bar">
                                <div class="csq-progress" style="width: ${percentage}%"></div>
                            </div>
                        </div>
                    `;
                });
                html += '</div>';
            } else {
                html += '<p class="csq-no-data">No product votes recorded</p>';
            }
            html += '</div>';

            html += '</div>'; // Close response-details

            // Insert content and show modal
            modalContent.html(html);
            $('#responseDetailsModal').addClass('active');
        }

        // Handle modal close
        function handleCloseModal(e) {
            if (e.target === this || $(e.target).hasClass('csq-modal-close')) {
                $('#responseDetailsModal').removeClass('active');
            }
        }

        // Initialize charts after full page load
        $(window).on('load', function() {
            if (typeof Chart !== 'undefined') {
                initializeCharts();
            } else {
                console.error('Chart.js is not loaded!');
            }
        });

        function initializeCharts() {
            // Gender Distribution Chart
            const genderCtx = document.getElementById('genderChart');
            if (genderCtx) {
                new Chart(genderCtx, {
                    type: 'doughnut',
                    data: {
                        labels: <?php echo json_encode($gender_labels); ?>,
                        datasets: [{
                            data: <?php echo json_encode($gender_data); ?>,
                            backgroundColor: <?php echo json_encode($gender_colors); ?>,
                            borderWidth: 1
                        }]
                    },
                    options: {
                        responsive: true,
                        maintainAspectRatio: false,
                        plugins: {
                            legend: {
                                position: 'bottom',
                                labels: {
                                    padding: 20,
                                    font: {
                                        size: 12
                                    }
                                }
                            }
                        }
                    }
                });
            }

            // Top Products Chart
            const productsCtx = document.getElementById('productsChart');
            if (productsCtx) {
                new Chart(productsCtx, {
                    type: 'bar',
                    data: {
                        labels: <?php echo json_encode($product_labels); ?>,
                        datasets: [{
                            label: 'Recommendations',
                            data: <?php echo json_encode($product_data); ?>,
                            backgroundColor: '#3a0ca3',
                            borderWidth: 0
                        }]
                    },
                    options: {
                        indexAxis: 'y',
                        responsive: true,
                        maintainAspectRatio: false,
                        plugins: {
                            legend: { display: false }
                        },
                        scales: {
                            x: {
                                beginAtZero: true,
                                grid: {
                                    display: false
                                },
                                ticks: {
                                    stepSize: 1
                                }
                            },
                            y: {
                                grid: {
                                    display: false
                                }
                            }
                        }
                    }
                });
            }

            // Timeline Chart
            const timelineCtx = document.getElementById('timelineChart');
            if (timelineCtx) {
                new Chart(timelineCtx, {
                    type: 'line',
                    data: {
                        labels: <?php echo json_encode($timeline_labels); ?>,
                        datasets: [{
                            label: 'Responses',
                            data: <?php echo json_encode($timeline_data); ?>,
                            borderColor: '#4361ee',
                            backgroundColor: 'rgba(67, 97, 238, 0.1)',
                            borderWidth: 2,
                            pointBackgroundColor: '#4361ee',
                            pointRadius: 4,
                            pointHoverRadius: 6,
                            tension: 0.4,
                            fill: true
                        }]
                    },
                    options: {
                        responsive: true,
                        maintainAspectRatio: false,
                        plugins: {
                            legend: { display: false }
                        },
                        scales: {
                            y: {
                                beginAtZero: true,
                                grid: {
                                    color: 'rgba(0, 0, 0, 0.05)'
                                },
                                ticks: {
                                    precision: 0
                                }
                            },
                            x: {
                                grid: {
                                    display: false
                                }
                            }
                        }
                    }
                });
            }
        }
    })(jQuery);
    </script>

    <style>
    .csq-summary-cards {
        display: grid;
        grid-template-columns: repeat(3, 1fr);
        gap: 1.5rem;
        margin-bottom: 2rem;
    }

    .csq-summary-card {
        display: flex;
        align-items: center;
        padding: 1.5rem;
        border-radius: 12px;
        background: #fff;
        box-shadow: 0 4px 6px rgba(0, 0, 0, 0.05);
        transition: transform 0.3s ease;
    }

    .csq-summary-card:hover {
        transform: translateY(-5px);
        box-shadow: 0 8px 15px rgba(0, 0, 0, 0.08);
    }

    .csq-card-icon {
        width: 50px;
        height: 50px;
        display: flex;
        align-items: center;
        justify-content: center;
        background: rgba(67, 97, 238, 0.1);
        border-radius: 50%;
        margin-right: 1.25rem;
    }

    .csq-card-icon svg {
        width: 24px;
        height: 24px;
        color: #4361ee;
    }

    .csq-card-content h3 {
        font-size: 1rem;
        font-weight: 500;
        color: #6c757d;
        margin-bottom: 0.25rem;
    }

    .csq-card-content p {
        font-size: 1.75rem;
        font-weight: 700;
        color: #2b2d42;
        margin: 0;
    }

    .csq-analytics-section .csq-card-header {
        display: flex;
        justify-content: space-between;
        align-items: center;
        margin-bottom: 1.5rem;
    }

    .csq-date-filter select {
        max-width: 200px;
    }

    .csq-analytics-grid {
        display: grid;
        grid-template-columns: 1fr 1fr;
        gap: 1.5rem;
    }

    .csq-analytics-chart {
        background: #fff;
        border-radius: 12px;
        padding: 1.5rem;
        box-shadow: 0 2px 4px rgba(0, 0, 0, 0.05);
    }

    .csq-analytics-chart h3 {
        font-size: 1.1rem;
        margin-bottom: 1.25rem;
        color: #2b2d42;
    }

    .csq-chart-container {
        height: 250px;
        position: relative;
    }

    .csq-analytics-list {
        background: #fff;
        border-radius: 12px;
        padding: 1.5rem;
        box-shadow: 0 2px 4px rgba(0, 0, 0, 0.05);
    }

    .csq-product-distribution ul {
        list-style: none;
        padding: 0;
        margin: 0;
    }

    .csq-product-distribution li {
        display: flex;
        align-items: center;
        padding: 0.75rem 0;
        border-bottom: 1px solid #eee;
    }

    .csq-product-rank {
        display: flex;
        align-items: center;
        justify-content: center;
        width: 28px;
        height: 28px;
        background: #4361ee;
        color: white;
        border-radius: 50%;
        font-weight: 600;
        font-size: 0.85rem;
        margin-right: 1rem;
    }

    .csq-product-name {
        flex: 1;
        font-weight: 500;
    }

    .csq-product-count {
        background: rgba(67, 97, 238, 0.1);
        color: #4361ee;
        padding: 0.25rem 0.75rem;
        border-radius: 20px;
        font-weight: 600;
        font-size: 0.9rem;
    }

    .response-details .csq-detail-grid {
        display: grid;
        grid-template-columns: repeat(2, 1fr);
        gap: 1rem;
        margin-bottom: 1.5rem;
    }

    .response-details .csq-detail-grid > div {
        background: #f8f9fa;
        border-radius: 8px;
        padding: 1rem;
    }

    .response-details label {
        font-weight: 500;
        color: #6c757d;
        display: block;
        margin-bottom: 0.25rem;
        font-size: 0.875rem;
    }

    .csq-answer-grid {
        display: grid;
        grid-template-columns: repeat(auto-fill, minmax(150px, 1fr));
        gap: 1rem;
    }

    .csq-answer-card {
        background: white;
        border: 1px solid #eee;
        border-radius: 8px;
        overflow: hidden;
    }

    .csq-answer-header {
        background: #f8f9fa;
        padding: 0.75rem;
        font-weight: 500;
        text-align: center;
    }

    .csq-answer-body {
        padding: 1rem;
        text-align: center;
    }

    .csq-product-grid {
        display: grid;
        grid-template-columns: 1fr;
        gap: 1rem;
    }

    .csq-product-card {
        background: white;
        border: 1px solid #eee;
        border-radius: 8px;
        padding: 1rem;
    }

    .csq-product-header {
        display: flex;
        justify-content: space-between;
        margin-bottom: 0.75rem;
    }

    .csq-vote-count {
        background: rgba(67, 97, 238, 0.1);
        color: #4361ee;
        padding: 0.25rem 0.75rem;
        border-radius: 20px;
        font-weight: 600;
        font-size: 0.9rem;
    }

    .csq-progress-bar {
        height: 8px;
        background: #f0f0f0;
        border-radius: 4px;
        overflow: hidden;
    }

    .csq-progress {
        height: 100%;
        background: #4361ee;
        border-radius: 4px;
    }

    .csq-tag-container {
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

    @media (max-width: 1200px) {
        .csq-analytics-grid {
            grid-template-columns: 1fr;
        }
    }

    @media (max-width: 768px) {
        .csq-summary-cards {
            grid-template-columns: 1fr;
        }

        .response-details .csq-detail-grid {
            grid-template-columns: 1fr;
        }
    }
    </style>
    <?php
}
