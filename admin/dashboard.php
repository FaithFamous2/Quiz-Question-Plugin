<?php
function csq_dashboard_page() {
    global $wpdb;

    // Get counts
    $product_count = $wpdb->get_var("SELECT COUNT(*) FROM {$wpdb->prefix}skin_products");
    $question_count = $wpdb->get_var("SELECT COUNT(*) FROM {$wpdb->prefix}quiz_questions");
    $response_count = $wpdb->get_var("SELECT COUNT(*) FROM {$wpdb->prefix}quiz_responses");

    // Get recent responses (last 5)
    $recent_responses = $wpdb->get_results(
        "SELECT * FROM {$wpdb->prefix}quiz_responses
        ORDER BY created_at DESC LIMIT 5"
    );

    // Get top products
    $top_products = $wpdb->get_results(
        "SELECT final_product, COUNT(*) as count
        FROM {$wpdb->prefix}quiz_responses
        WHERE final_product != ''
        GROUP BY final_product
        ORDER BY count DESC LIMIT 5"
    );

    // Get response trends (last 7 days)
    $response_trends = $wpdb->get_results(
        "SELECT DATE(created_at) as date, COUNT(*) as count
        FROM {$wpdb->prefix}quiz_responses
        WHERE created_at >= CURDATE() - INTERVAL 7 DAY
        GROUP BY DATE(created_at)
        ORDER BY DATE(created_at)"
    );

    // Prepare chart data
    $trend_labels = [];
    $trend_data = [];

    // Create a date range for the last 7 days
    $dates = [];
    for ($i = 6; $i >= 0; $i--) {
        $date = date('Y-m-d', strtotime("-$i days"));
        $dates[$date] = 0;
    }

    // Fill with actual data
    foreach ($response_trends as $trend) {
        $dates[$trend->date] = (int)$trend->count;
    }

    // Convert to chart format
    foreach ($dates as $date => $count) {
        $trend_labels[] = date('M j', strtotime($date));
        $trend_data[] = $count;
    }

    // Enqueue Chart.js only for this page
    wp_enqueue_script('chart-js', 'https://cdn.jsdelivr.net/npm/chart.js@3.7.0/dist/chart.min.js', [], '3.7.0', true);
    ?>
    <div class="wrap csq-admin">
        <h1 class="csq-admin-title">Skin Care Quiz Dashboard</h1>

        <!-- Summary Cards -->
        <div class="csq-summary-cards">
            <div class="csq-card csq-summary-card">
                <div class="csq-card-icon" style="background-color: rgba(67, 97, 238, 0.1);">
                    <svg width="24" height="24" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg">
                        <path d="M21 16V8a2 2 0 00-1-1.73l-7-4a2 2 0 00-2 0l-7 4A2 2 0 003 8v8a2 2 0 001 1.73l7 4a2 2 0 002 0l7-4A2 2 0 0021 16z" stroke="#4361ee" stroke-width="2"/>
                        <path d="M3.27 6.96L12 12.01l8.73-5.05M12 22.08V12" stroke="#4361ee" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/>
                    </svg>
                </div>
                <div class="csq-card-content">
                    <h3>Products</h3>
                    <p><?php echo number_format($product_count); ?></p>
                </div>
            </div>

            <div class="csq-card csq-summary-card">
                <div class="csq-card-icon" style="background-color: rgba(58, 12, 163, 0.1);">
                    <svg width="24" height="24" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg">
                        <path d="M12 22c5.523 0 10-4.477 10-10S17.523 2 12 2 2 6.477 2 12s4.477 10 10 10z" stroke="#3a0ca3" stroke-width="2"/>
                        <path d="M8 12h8" stroke="#3a0ca3" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/>
                        <path d="M12 16V8" stroke="#3a0ca3" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/>
                    </svg>
                </div>
                <div class="csq-card-content">
                    <h3>Questions</h3>
                    <p><?php echo number_format($question_count); ?></p>
                </div>
            </div>

            <div class="csq-card csq-summary-card">
                <div class="csq-card-icon" style="background-color: rgba(247, 37, 133, 0.1);">
                    <svg width="24" height="24" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg">
                        <path d="M17 21v-2a4 4 0 00-4-4H5a4 4 0 00-4 4v2" stroke="#f72585" stroke-width="2"/>
                        <path d="M9 11a4 4 0 100-8 4 4 0 000 8z" stroke="#f72585" stroke-width="2"/>
                        <path d="M23 21v-2a4 4 0 00-3-3.87" stroke="#f72585" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/>
                        <path d="M16 3.13a4 4 0 010 7.75" stroke="#f72585" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/>
                    </svg>
                </div>
                <div class="csq-card-content">
                    <h3>Responses</h3>
                    <p><?php echo number_format($response_count); ?></p>
                </div>
            </div>
        </div>

        <!-- Analytics Grid -->
        <div class="csq-analytics-grid">
            <!-- Response Trends -->
            <div class="csq-card">
                <div class="csq-card-header">
                    <h2 class="csq-card-title">Response Trends (Last 7 Days)</h2>
                </div>
                <div class="csq-chart-container" style="height: 300px;">
                    <canvas id="responseTrendChart"></canvas>
                </div>
            </div>

            <!-- Top Products -->
            <div class="csq-card">
                <div class="csq-card-header">
                    <h2 class="csq-card-title">Top Recommended Products</h2>
                </div>
                <div class="csq-table-container">
                    <table class="wp-list-table widefat fixed striped">
                        <thead>
                            <tr>
                                <th>Product</th>
                                <th>Recommendations</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if (empty($top_products)): ?>
                                <tr>
                                    <td colspan="2">No recommendations yet</td>
                                </tr>
                            <?php else: ?>
                                <?php foreach ($top_products as $product): ?>
                                    <tr>
                                        <td><?php echo esc_html($product->final_product); ?></td>
                                        <td><?php echo number_format($product->count); ?></td>
                                    </tr>
                                <?php endforeach; ?>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>

        <!-- Recent Responses -->
        <div class="csq-card">
            <div class="csq-card-header">
                <h2 class="csq-card-title">Recent Responses</h2>
                <a href="<?php echo admin_url('admin.php?page=csq-responses'); ?>" class="button">View All</a>
            </div>
            <div class="csq-table-container">
                <table class="wp-list-table widefat fixed striped">
                    <thead>
                        <tr>
                            <th>Name</th>
                            <th>Email</th>
                            <th>Products</th>
                            <th>Date</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (empty($recent_responses)): ?>
                            <tr>
                                <td colspan="4">No responses yet</td>
                            </tr>
                        <?php else: ?>
                            <?php foreach ($recent_responses as $response): ?>
                                <tr>
                                    <td><?php echo esc_html($response->fullname); ?></td>
                                    <td><?php echo esc_html($response->user_email); ?></td>
                                    <td>
                                        <div class="csq-tag-container">
                                            <?php
                                            $products = !empty($response->final_product) ?
                                                explode(',', $response->final_product) : [];

                                            foreach ($products as $product):
                                                $product = trim($product);
                                                if (!empty($product)): ?>
                                                    <span class="csq-tag"><?php echo esc_html($product); ?></span>
                                                <?php endif; ?>
                                            <?php endforeach; ?>
                                        </div>
                                    </td>
                                    <td><?php echo date('M j, Y', strtotime($response->created_at)); ?></td>
                                </tr>
                            <?php endforeach; ?>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>

        <!-- Quick Actions -->
        <div class="csq-card">
            <div class="csq-card-header">
                <h2 class="csq-card-title">Quick Actions</h2>
            </div>
            <div class="csq-quick-actions">
                <a href="<?php echo admin_url('admin.php?page=csq-products'); ?>" class="csq-action-card">
                    <div class="csq-action-icon">
                        <svg width="24" height="24" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg">
                            <path d="M21 16V8a2 2 0 00-1-1.73l-7-4a2 2 0 00-2 0l-7 4A2 2 0 003 8v8a2 2 0 001 1.73l7 4a2 2 0 002 0l7-4A2 2 0 0021 16z" stroke="#4361ee" stroke-width="2"/>
                            <path d="M3.27 6.96L12 12.01l8.73-5.05M12 22.08V12" stroke="#4361ee" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/>
                        </svg>
                    </div>
                    <h3>Manage Products</h3>
                    <p>Add, edit, or remove skincare products</p>
                </a>

                <a href="<?php echo admin_url('admin.php?page=csq-questions'); ?>" class="csq-action-card">
                    <div class="csq-action-icon">
                        <svg width="24" height="24" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg">
                            <path d="M12 22c5.523 0 10-4.477 10-10S17.523 2 12 2 2 6.477 2 12s4.477 10 10 10z" stroke="#3a0ca3" stroke-width="2"/>
                            <path d="M8 12h8" stroke="#3a0ca3" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/>
                            <path d="M12 16V8" stroke="#3a0ca3" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/>
                        </svg>
                    </div>
                    <h3>Manage Questions</h3>
                    <p>Edit quiz questions and answers</p>
                </a>

                <a href="<?php echo admin_url('admin.php?page=csq-responses'); ?>" class="csq-action-card">
                    <div class="csq-action-icon">
                        <svg width="24" height="24" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg">
                            <path d="M17 21v-2a4 4 0 00-4-4H5a4 4 0 00-4 4v2" stroke="#f72585" stroke-width="2"/>
                            <path d="M9 11a4 4 0 100-8 4 4 0 000 8z" stroke="#f72585" stroke-width="2"/>
                            <path d="M23 21v-2a4 4 0 00-3-3.87" stroke="#f72585" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/>
                            <path d="M16 3.13a4 4 0 010 7.75" stroke="#f72585" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/>
                        </svg>
                    </div>
                    <h3>View Responses</h3>
                    <p>See all user responses and analytics</p>
                </a>
            </div>
        </div>

        <script>
        (function($) {
            'use strict';

            // Initialize after full page load
            $(window).on('load', function() {
                if (typeof Chart !== 'undefined') {
                    // Response Trends Chart
                    const trendCtx = document.getElementById('responseTrendChart');
                    if (trendCtx) {
                        new Chart(trendCtx, {
                            type: 'line',
                            data: {
                                labels: <?php echo json_encode($trend_labels); ?>,
                                datasets: [{
                                    label: 'Responses',
                                    data: <?php echo json_encode($trend_data); ?>,
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
                } else {
                    console.error('Chart.js is not loaded!');
                }
            });
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
            border-radius: 50%;
            margin-right: 1.25rem;
        }

        .csq-card-icon svg {
            width: 24px;
            height: 24px;
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

        .csq-analytics-grid {
            display: grid;
            grid-template-columns: 2fr 1fr;
            gap: 1.5rem;
            margin-bottom: 2rem;
        }

        .csq-card {
            background: #fff;
            border-radius: 12px;
            padding: 1.5rem;
            box-shadow: 0 4px 6px rgba(0, 0, 0, 0.05);
        }

        .csq-card-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 1.5rem;
        }

        .csq-card-header .button {
            margin-top: -0.5rem;
        }

        .csq-chart-container {
            height: 300px;
            position: relative;
        }

        .csq-table-container {
            overflow-x: auto;
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
            white-space: nowrap;
        }

        .csq-quick-actions {
            display: grid;
            grid-template-columns: repeat(3, 1fr);
            gap: 1.5rem;
        }

        .csq-action-card {
            display: block;
            padding: 1.5rem;
            border-radius: 12px;
            background: #f8f9fa;
            text-decoration: none;
            color: #2b2d42;
            transition: all 0.3s ease;
        }

        .csq-action-card:hover {
            transform: translateY(-5px);
            box-shadow: 0 8px 15px rgba(0, 0, 0, 0.08);
            background: #fff;
        }

        .csq-action-icon {
            width: 50px;
            height: 50px;
            display: flex;
            align-items: center;
            justify-content: center;
            border-radius: 50%;
            margin-bottom: 1rem;
            background: rgba(67, 97, 238, 0.1);
        }

        .csq-action-icon svg {
            width: 24px;
            height: 24px;
        }

        .csq-action-card:nth-child(2) .csq-action-icon {
            background: rgba(58, 12, 163, 0.1);
        }

        .csq-action-card:nth-child(3) .csq-action-icon {
            background: rgba(247, 37, 133, 0.1);
        }

        .csq-action-card h3 {
            font-size: 1.1rem;
            margin-bottom: 0.5rem;
        }

        .csq-action-card p {
            font-size: 0.9rem;
            color: #6c757d;
            margin: 0;
        }

        @media (max-width: 1200px) {
            .csq-analytics-grid {
                grid-template-columns: 1fr;
            }

            .csq-quick-actions {
                grid-template-columns: 1fr;
            }
        }

        @media (max-width: 768px) {
            .csq-summary-cards {
                grid-template-columns: 1fr;
            }
        }
        </style>
    </div>
    <?php
}
