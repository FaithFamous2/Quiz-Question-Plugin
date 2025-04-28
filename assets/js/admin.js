jQuery(document).ready(function($) {
    // Initialize Select2 for multi-select fields
    $('.select2').select2({
        width: '100%',
        placeholder: 'Select products...',
        allowClear: true
    });

    // Response Details Modal
    $('.view-details').on('click', function() {
        const data = JSON.parse($(this).data('response'));
        const modal = $('#responseDetailsModal');
        const content = $('#responseDetailsContent');

        // Clear previous content
        content.html('');

        // Build response details
        const detailsHtml = `
            <div class="row">
                <div class="col-md-6">
                    <h5>Question Responses</h5>
                    <ul class="list-group">
                        ${Object.entries(data.responses).map(([qid, aid]) => `
                            <li class="list-group-item">
                                <strong>Question ${qid}:</strong> Answer ${aid}
                            </li>
                        `).join('')}
                    </ul>
                </div>
                <div class="col-md-6">
                    <h5>Product Votes</h5>
                    <ul class="list-group">
                        ${Object.entries(data.product_votes).map(([pid, votes]) => `
                            <li class="list-group-item d-flex justify-content-between align-items-center">
                                Product ${pid}
                                <span class="badge badge-primary badge-pill">${votes}</span>
                            </li>
                        `).join('')}
                    </ul>
                </div>
            </div>
        `;

        content.html(detailsHtml);
        modal.modal('show');
    });

    // Delete Confirmation
    $('.csq-table').on('click', '.btn-danger', function(e) {
        if (!confirm('Are you sure you want to delete this item?')) {
            e.preventDefault();
        }
    });

    // Table Sorting and Search
    $('.csq-table').DataTable({
        paging: false,
        info: false,
        autoWidth: false,
        language: {
            search: '_INPUT_',
            searchPlaceholder: 'Search...'
        },
        columnDefs: [{
            orderable: false,
            targets: 'no-sort'
        }]
    });

    // Toggle Form Visibility
    $('[data-toggle="form-section"]').on('click', function() {
        const target = $(this).data('target');
        $(target).slideToggle();
        $(this).find('i').toggleClass('fa-plus fa-minus');
    });
});
