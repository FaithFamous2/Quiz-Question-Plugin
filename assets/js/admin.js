jQuery(document).ready(function($) {
    // Initialize Select2
    $('.select2').select2({
        width: '100%',
        placeholder: 'Select products...',
        allowClear: true
    });

    // Initialize DataTables
    $('.csq-table').DataTable({
        paging: true,
        pageLength: 10,
        lengthChange: false,
        searching: true,
        ordering: true,
        info: true,
        autoWidth: false,
        language: {
            search: '',
            searchPlaceholder: 'Search...',
            paginate: {
                previous: '&laquo;',
                next: '&raquo;'
            },
            info: 'Showing _START_ to _END_ of _TOTAL_ entries'
        },
        dom: '<"top"f>rt<"bottom"ip><"clear">'
    });

    // Response Details Modal - Use event delegation
    $(document).on('click', '.view-details', function() {
        const data = $(this).data('response');
        const modal = $('#responseDetailsModal');
        const content = $('#responseDetailsContent');

        // Properly escape and handle empty values
        const safeData = {
            fullname: data.fullname || 'N/A',
            email: data.email || 'N/A',
            gender: data.gender || 'N/A',
            created_at: data.created_at ? new Date(data.created_at).toLocaleString() : 'N/A',
            ip_address: data.ip_address || 'N/A',
            user_agent: data.user_agent || 'N/A',
            responses: data.responses || {},
            product_votes: data.product_votes || {}
        };

        content.html(`
        <div class="response-details">
          <div class="csq-detail-section">
            <h4>User Information</h4>
            <div class="csq-detail-grid">
              <div><label>Name:</label><p>${data.fullname || 'N/A'}</p></div>
              <div><label>Email:</label><p>${data.email}</p></div>
              <div><label>Gender:</label><p>${data.gender || 'N/A'}</p></div>
              <div><label>Date:</label><p>${new Date(data.created_at).toLocaleString()}</p></div>
            </div>
          </div>

          <div class="csq-detail-section">
            <h4>Technical Information</h4>
            <div class="csq-detail-grid">
              <div><label>IP Address:</label><p>${data.ip_address || 'N/A'}</p></div>
              <div><label>User Agent:</label><p>${data.user_agent || 'N/A'}</p></div>
            </div>
          </div>

          <div class="csq-detail-section">
            <h4>Quiz Answers</h4>
            <div class="csq-answer-grid">
              ${Object.entries(data.responses).map(([qid, aid]) => `
                <div class="csq-answer-card">
                  <div class="csq-answer-header">
                    <span>Question ${qid.replace('question_', '')}</span>
                  </div>
                  <div class="csq-answer-body">
                    Answer ID: ${aid}
                  </div>
                </div>
              `).join('')}
            </div>
          </div>

          <div class="csq-detail-section">
            <h4>Product Recommendations</h4>
            <div class="csq-product-grid">
              ${Object.entries(data.product_votes).map(([pid, votes]) => `
                <div class="csq-product-card">
                  <div class="csq-product-header">
                    <span>Product ID: ${pid}</span>
                    <span class="csq-vote-count">${votes} votes</span>
                  </div>
                  <div class="csq-progress-bar">
                    <div class="csq-progress" style="width: ${(votes / 10) * 100}%"></div>
                  </div>
                </div>
              `).join('')}
            </div>
          </div>
        </div>
      `);

      modal.addClass('active');
    });

    // Close modal
    $('.csq-modal-close, .csq-modal').on('click', function(e) {
      if (e.target === this) {
        $('#responseDetailsModal').removeClass('active');
      }
    });

    // Delete confirmation
    $('.csq-table').on('click', '.btn-danger', function(e) {
      if (!confirm('Are you sure you want to delete this item?')) {
        e.preventDefault();
      }
    });

    // Toggle form sections
    $('[data-toggle="form-section"]').on('click', function() {
      const target = $(this).data('target');
      $(target).slideToggle();
      $(this).find('i').toggleClass('fa-plus fa-minus');
    });
  });
