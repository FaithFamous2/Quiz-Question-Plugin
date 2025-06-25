<?php
// File: wp-content/plugins/custom-skin-quiz/admin/products.php

function csq_products_page() {
    global $wpdb;
    $table = $wpdb->prefix . 'skin_products';

    // Handle form submissions
    if ($_SERVER['REQUEST_METHOD'] === 'POST') {
        csq_handle_product_form();
    }

    // Handle deletions
    if (isset($_GET['action'], $_GET['id']) && $_GET['action'] === 'delete') {
        csq_delete_product(absint($_GET['id']));
    }

    $products = $wpdb->get_results("SELECT * FROM $table ORDER BY product_order ASC");
    $editing = (isset($_GET['action'], $_GET['id']) && $_GET['action'] === 'edit')
        ? $wpdb->get_row($wpdb->prepare("SELECT * FROM $table WHERE id = %d", $_GET['id']))
        : null;
    ?>
    <div class="wrap csq-admin">
        <h1 class="csq-admin-title">Manage Products</h1>

        <div class="csq-flex">
            <!-- LEFT COLUMN: Form -->
            <div class="csq-col">
                <div class="csq-card">
                    <h2 class="csq-card-title"><?= $editing ? 'Edit Product' : 'Add New Product' ?></h2>
                    <form method="POST">
                        <?php wp_nonce_field('csq_product_nonce', 'csq_nonce'); ?>
                        <input type="hidden" name="product_id" value="<?= $editing->id ?? 0 ?>">

                        <div class="form-group">
                            <label>Product Name</label>
                            <input type="text" name="product_name" class="form-control"
                                   value="<?= esc_attr($editing->product_name ?? '') ?>" required>
                        </div>

                        <div class="form-group">
                            <label>Image URL</label>
                            <input type="url" name="image_url" class="form-control"
                                   value="<?= esc_url($editing->image_url ?? '') ?>">
                        </div>

                        <div class="form-group">
                            <label>Product Link</label>
                            <input type="url" name="product_link" class="form-control"
                                   value="<?= esc_url($editing->product_link ?? '') ?>">
                        </div>

                        <div class="csq-row">
                            <div class="csq-half">
                                <div class="form-group">
                                    <label>Threshold</label>
                                    <input type="number" name="count_threshold" class="form-control"
                                           value="<?= intval($editing->count_threshold ?? 1) ?>"
                                           min="1" required>
                                </div>
                            </div>
                            <div class="csq-half">
                                <div class="form-group">
                                    <label>Order</label>
                                    <input type="number" name="product_order" class="form-control"
                                           value="<?= intval($editing->product_order ?? 0) ?>">
                                </div>
                            </div>
                        </div>

                        <div class="form-group">
                            <label>Product Details</label>
                            <textarea name="product_details" class="form-control" rows="4"><?= esc_textarea($editing->product_details ?? '') ?></textarea>
                        </div>

                        <button type="submit" class="btn btn-primary">
                            <?= $editing ? 'Update Product' : 'Save Product' ?>
                        </button>
                        <?php if ($editing): ?>
                            <a href="?page=csq-products" class="btn btn-secondary">Cancel</a>
                        <?php endif; ?>
                    </form>
                </div>
            </div>

            <!-- RIGHT COLUMN: Product Grid -->
            <div class="csq-col">
                <h2 class="csq-section-title">All Products (<?= count($products) ?>)</h2>
                <div class="csq-product-grid">
                    <?php foreach ($products as $p): ?>
                        <div class="csq-product-card">
                            <?php if ($p->image_url): ?>
                                <div class="csq-product-image" style="background-image:url('<?= esc_url($p->image_url) ?>')"></div>
                            <?php endif; ?>
                            <div class="csq-product-info">
                                <h3><?= esc_html($p->product_name) ?></h3>
                                <p><strong>Threshold:</strong> <?= intval($p->count_threshold) ?></p>
                                <p><strong>Order:</strong> <?= intval($p->product_order) ?></p>
                            </div>
                            <div class="csq-product-actions">
                                <a href="?page=csq-products&action=edit&id=<?= $p->id ?>" class="btn btn-sm btn-info">Edit</a>
                                <a href="?page=csq-products&action=delete&id=<?= $p->id ?>"
                                   class="btn btn-sm btn-danger"
                                   onclick="return confirm('Delete this product?');">Delete</a>
                            </div>
                        </div>
                    <?php endforeach; ?>
                    <?php if (empty($products)): ?>
                        <p>No products found. Add one using the form.</p>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>
    <?php
}

function csq_handle_product_form() {
    global $wpdb;
    $table = $wpdb->prefix . 'skin_products';

    if (!wp_verify_nonce($_POST['csq_nonce'], 'csq_product_nonce')) {
        wp_die('Security check failed');
    }

    $data = [
        'product_name' => sanitize_text_field($_POST['product_name']),
        'image_url' => esc_url_raw($_POST['image_url']),
        'product_details' => sanitize_textarea_field($_POST['product_details']),
        'product_link' => esc_url_raw($_POST['product_link']),
        'count_threshold' => absint($_POST['count_threshold']),
        'product_order' => absint($_POST['product_order'])
    ];

    if ($_POST['product_id']) {
        $wpdb->update($table, $data, ['id' => absint($_POST['product_id'])]);
    } else {
        $wpdb->insert($table, $data);
    }
}

function csq_delete_product($id) {
    global $wpdb;
    $wpdb->delete($wpdb->prefix . 'skin_products', ['id' => absint($id)]);
}
