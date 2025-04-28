<?php
function csq_products_page() {
    global $wpdb;
    $table = $wpdb->prefix . 'skin_products';

    // Handle form submissions
    if ($_SERVER['REQUEST_METHOD'] === 'POST') {
        csq_handle_product_form();
    }

    // Handle deletions
    if (isset($_GET['action']) && $_GET['action'] === 'delete' && isset($_GET['id'])) {
        csq_delete_product($_GET['id']);
    }

    $products = $wpdb->get_results("SELECT * FROM $table ORDER BY product_order ASC");
    $editing = isset($_GET['action']) && $_GET['action'] === 'edit' ? $wpdb->get_row($wpdb->prepare("SELECT * FROM $table WHERE id = %d", $_GET['id'])) : null;

    ?>
    <div class="wrap csq-admin">
        <h1 class="csq-admin-title">Manage Products</h1>

        <div class="card csq-card">
            <h2 class="csq-card-title"><?= $editing ? 'Edit Product' : 'Add New Product' ?></h2>
            <form method="POST">
                <?php wp_nonce_field('csq_product_nonce', 'csq_nonce'); ?>
                <input type="hidden" name="product_id" value="<?= $editing->id ?? 0 ?>">

                <div class="row">
                    <div class="col-md-6">
                        <div class="form-group">
                            <label>Product Name</label>
                            <input type="text" name="product_name" class="form-control"
                                   value="<?= $editing->product_name ?? '' ?>" required>
                        </div>

                        <div class="form-group">
                            <label>Image URL</label>
                            <input type="url" name="image_url" class="form-control"
                                   value="<?= $editing->image_url ?? '' ?>">
                        </div>
                    </div>

                    <div class="col-md-6">
                        <div class="form-group">
                            <label>Product Link</label>
                            <input type="url" name="product_link" class="form-control"
                                   value="<?= $editing->product_link ?? '' ?>">
                        </div>

                        <div class="row">
                            <div class="col-6">
                                <div class="form-group">
                                    <label>Threshold</label>
                                    <input type="number" name="count_threshold" class="form-control"
                                           value="<?= $editing->count_threshold ?? 1 ?>" min="1" required>
                                </div>
                            </div>
                            <div class="col-6">
                                <div class="form-group">
                                    <label>Order</label>
                                    <input type="number" name="product_order" class="form-control"
                                           value="<?= $editing->product_order ?? 0 ?>">
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="form-group">
                    <label>Product Details</label>
                    <textarea name="product_details" class="form-control"
                              rows="4"><?= $editing->product_details ?? '' ?></textarea>
                </div>

                <button type="submit" class="btn btn-primary">Save Product</button>
            </form>
        </div>

        <div class="card csq-card mt-4">
            <h2 class="csq-card-title">All Products</h2>
            <table class="table csq-table">
                <thead>
                    <tr>
                        <th>Name</th>
                        <th>Threshold</th>
                        <th>Order</th>
                        <th>Actions</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($products as $product) : ?>
                    <tr>
                        <td><?= esc_html($product->product_name) ?></td>
                        <td><?= $product->count_threshold ?></td>
                        <td><?= $product->product_order ?></td>
                        <td>
                            <a href="?page=csq-products&action=edit&id=<?= $product->id ?>"
                               class="btn btn-sm btn-primary">Edit</a>
                            <a href="?page=csq-products&action=delete&id=<?= $product->id ?>"
                               class="btn btn-sm btn-danger"
                               onclick="return confirm('Delete this product?')">Delete</a>
                        </td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
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
