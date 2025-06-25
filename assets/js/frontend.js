jQuery(function($) {
  // Initialize quiz
  function initSkinQuiz() {
      const quizSystem = $('.csq-skin-analysis-system');
      let currentStep = 1;
      let totalSteps = $('.csq-analysis-step').length;
      const questionCount = totalSteps - 2;
      let sessionID = null;
      let answers = {};
      let userEmail = '';
      let userData = {};

      // Hide navigation initially
      $('.csq-analysis-controls').hide();

      // Update UI elements
      updateProgress();
      updateStepIndicator();

      // Event listeners
      $('#csq-start-quiz').on('click', startQuiz);
      $('.csq-nav-btn.csq-next-btn').on('click', nextStep);
      $('.csq-nav-btn.csq-prev-btn').on('click', prevStep);
      $('#csq-email-results').on('click', emailResults);
      $('#csq-restart-btn').on('click', restartQuiz);
      $('.csq-modal-close, .csq-modal-overlay').on('click', closeModal);
      $('#csq-email-form').on('submit', submitEmailForm);

      // Start the quiz
      function startQuiz() {
          $('.csq-skin-hero').fadeOut(500, function() {
              $('.csq-analysis-system').fadeIn(800);
              $('.csq-analysis-controls').show();
          });
      }

      // Navigation functions
      function nextStep() {
          const currentStepEl = $(`.csq-analysis-step[data-step="${currentStep}"]`);
          const nextStep = currentStep + 1;

          // Validate current step
          if (!validateStep(currentStep)) return;

          // Save contact info if it's the first step
          if (currentStep === 1) {
              saveContactInfo(nextStepCallback); // Pass callback function
              return; // Exit early - will proceed via callback
          }

          // Hide current step
          currentStepEl.removeClass('active').addClass('animate__fadeOutLeft');

          // Show next step
          setTimeout(() => {
              currentStepEl.hide();
              $(`.csq-analysis-step[data-step="${nextStep}"]`)
                  .addClass('active animate__fadeInRight')
                  .show();

              // Update current step
              currentStep = nextStep;

              // Update UI
              updateProgress();
              updateStepIndicator();

              // Submit on last step
              if (currentStep === totalSteps) {
                  setTimeout(submitQuiz, 1500);
              }
          }, 300);
      }

      // Callback function after saving contact info
      function nextStepCallback() {
          const currentStepEl = $(`.csq-analysis-step[data-step="1"]`);
          const nextStep = 2;

          // Hide current step
          currentStepEl.removeClass('active').addClass('animate__fadeOutLeft');

          setTimeout(() => {
              currentStepEl.hide();
              $(`.csq-analysis-step[data-step="${nextStep}"]`)
                  .addClass('active animate__fadeInRight')
                  .show();

              // Update current step
              currentStep = nextStep;

              // Update UI
              updateProgress();
              updateStepIndicator();
          }, 300);
      }

      function prevStep() {
          if (currentStep <= 1) return;
          if (currentStep === 2) return; // Prevent going back to personal info

          const currentStepEl = $(`.csq-analysis-step[data-step="${currentStep}"]`);
          const prevStep = currentStep - 1;

          // Hide current step
          currentStepEl.removeClass('active').addClass('animate__fadeOutRight');

          setTimeout(() => {
              currentStepEl.hide();
              $(`.csq-analysis-step[data-step="${prevStep}"]`)
                  .addClass('active animate__fadeInLeft')
                  .show();

              // Update current step
              currentStep = prevStep;

              // Update UI
              updateProgress();
              updateStepIndicator();
          }, 300);
      }

      // Save contact information
      function saveContactInfo(callback) {
          const fullname = $('input[name="fullname"]').val().trim();
          const email = $('input[name="email"]').val().trim();
          const gender = $('input[name="gender"]:checked').val();

          // Basic validation
          if (!fullname || !email || !gender) {
              alert('Please fill in all required fields');
              return false;
          }

          // Enhanced email validation
          const emailRegex = /^[^\s@]+@[^\s@]+\.[^\s@]+$/;
          if (!emailRegex.test(email)) {
              alert('Please enter a valid email address');
              return false;
          }

          // Store email for later use
          userEmail = email;
          userData = { fullname, email, gender };

          // Show loading indicator
          const nextBtn = $('.csq-next-btn');
          const originalText = nextBtn.html();
          nextBtn.html('<span class="csq-spinner"></span> Saving...').prop('disabled', true);

          // AJAX request
          $.ajax({
              url: csqData.ajaxurl,
              type: 'POST',
              data: {
                  action: 'csq_save_contact',
                  security: csqData.nonce,
                  fullname: fullname,
                  email: email,
                  gender: gender
              },
              success: function(response) {
                  nextBtn.html(originalText).prop('disabled', false);

                  if (response.success) {
                      sessionID = response.data.session_id;
                      console.log('Contact info saved with session ID:', sessionID);

                      // Execute callback to advance to next step
                      if (typeof callback === 'function') {
                          callback();
                      }
                  } else {
                      alert('Error saving your information: ' + response.data);
                  }
              },
              error: function(xhr) {
                  nextBtn.html(originalText).prop('disabled', false);
                  console.error('Error saving contact info:', xhr.responseText);
                  alert('An error occurred while saving your information. Please try again.');
              }
          });
      }

      // Submit quiz
      function submitQuiz() {
          // Collect all answers
          const answers = {};
          $('input[type="radio"]:checked').each(function() {
              const name = $(this).attr('name');
              // Only include question answers, skip personal info
              if (name.startsWith('question_')) {
                  const value = $(this).val();
                  answers[name] = value;
              }
          });

          if (Object.keys(answers).length === 0) {
              alert('Please answer all questions before submitting');
              return;
          }

          if (!sessionID) {
              alert('Session information missing. Please restart the quiz.');
              return;
          }

          // Show loading state
          $('.csq-analysis-loading').show();
          $('.csq-results-content').hide();

          // AJAX request
          $.ajax({
              url: csqData.ajaxurl,
              type: 'POST',
              data: {
                  action: 'csq_process_quiz',
                  security: csqData.nonce,
                  session_id: sessionID,
                  answers: answers
              },
              success: function(response) {
                  if (response.success) {
                      // Add user data to response
                      displayResults(response.data);
                  } else {
                      console.error('Error processing quiz:', response.data);
                      alert('Error processing your quiz: ' + response.data);
                  }
              },
              error: function(xhr) {
                  console.error('Error processing quiz:', xhr.responseText);
                  alert('Error processing your quiz. Please try again.');
              },
              complete: function() {
                  // Hide loading, show results
                  setTimeout(function() {
                      $('.csq-analysis-loading').hide();
                      $('.csq-results-content').fadeIn(800);
                  }, 1000);
              }
          });
      }

      // Display results
      function displayResults(data) {
          const productsGrid = $('#csq-products-grid');
          productsGrid.empty();

          // Update profile information
          if (data.user) {
              $('#csq-skin-type').text(data.user.gender + ' Skin');
          }

          // Calculate match percentage
          // let maxVotes = 0;
          // if (data.total_votes && Object.keys(data.total_votes).length > 0) {
          //     maxVotes = Math.max(...Object.values(data.total_votes));
          // }

          // // const matchPercentage = Math.round((maxVotes / Object.keys(data.answers || {}).length) * 100);
          // const matchPercentage = questionCount > 0
          // ? Math.round((maxVotes / questionCount) * 100)
          // : 0;
          // $('#csq-match-percentage').text(matchPercentage);

           // Show how many products matched
       const matchCount = Array.isArray(data.products) ? data.products.length : 0;
        $('#csq-match-percentage').text(matchCount);


          if (data.products && data.products.length > 0) {
              // Find max votes for percentage calculation
              let maxProductVotes = 0;
              data.products.forEach(product => {
                  if (product.votes > maxProductVotes) maxProductVotes = product.votes;
              });

              data.products.forEach(product => {
                  const percentage = maxProductVotes > 0
                      ? Math.round((product.votes / maxProductVotes) * 100)
                      : 0;

                  productsGrid.append(`
                      <div class="csq-product-card">
                          <div class="csq-product-image">
                              <img src="${product.image}" alt="${product.name}">
                              <div class="csq-product-overlay">
                                  <h3 class="csq-product-name">${product.name}</h3>
                                  <div class="csq-product-type">Recommended Product</div>
                              </div>
                          </div>
                          <div class="csq-product-details">
                              <p class="csq-product-description">${product.details}</p>

                              <div class="csq-product-match">
                                  <div class="csq-match-bar" style="width: ${percentage}%"></div>
                                  <div class="csq-match-text">${percentage}% match with your skin profile</div>
                              </div>

                              <a href="${product.link}" class="csq-product-link">
                                  View Product Details
                                  <svg width="16" height="16" viewBox="0 0 16 16" fill="none" xmlns="http://www.w3.org/2000/svg">
                                      <path d="M4 12L12 4M12 4H6M12 4V10" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round"/>
                                  </svg>
                              </a>
                          </div>
                      </div>
                  `);
              });
          } else {
              productsGrid.append('<p class="csq-no-products">No products match your skin profile at this time.</p>');
          }
      }

      // Email results modal
      function emailResults() {
          $('#csq-email-input').val(userEmail);
          $('#csq-email-modal').fadeIn();
      }

      function closeModal() {
          $('#csq-email-modal').fadeOut();
      }

      function submitEmailForm(e) {
          e.preventDefault();
          const email = $('#csq-email-input').val().trim();

          if (!email) {
              alert('Please enter your email address');
              return;
          }

          // AJAX request to send email
          $.ajax({
              url: csqData.ajaxurl,
              type: 'POST',
              data: {
                  action: 'csq_email_results',
                  security: csqData.nonce,
                  session_id: sessionID,
                  email: email
              },
              success: function(response) {
                  if (response.success) {
                      alert('Your results have been sent to ' + email);
                      closeModal();
                  } else {
                      alert('Error sending email: ' + response.data);
                  }
              },
              error: function(xhr) {
                  console.error('Error sending email:', xhr.responseText);
                  alert('An error occurred while sending your results. Please try again.');
              }
          });
      }

      // Restart quiz
      function restartQuiz() {
          // Reset form and UI
          $('#csq-quiz-form')[0].reset();
          $('.csq-analysis-step').removeClass('active').hide();
          $('.csq-analysis-step:first').addClass('active').show();

          // Reset variables
          currentStep = 1;
          answers = {};
          sessionID = null;
          userEmail = '';
          userData = {};

          // Update UI
          updateProgress();
          updateStepIndicator();

          // Hide results, show first step
          $('.csq-results-content').hide();
          $('.csq-analysis-loading').show();

          // Hide analysis system, show hero
          $('.csq-analysis-system').hide();
          $('.csq-skin-hero').show();
      }

      // Validate current step
      function validateStep(step) {
          if (step === 1) {
              // Validate personal info
              const fullname = $('input[name="fullname"]').val().trim();
              const email = $('input[name="email"]').val().trim();
              const gender = $('input[name="gender"]:checked').val();

              if (!fullname || !email || !gender) {
                  alert('Please fill in all required fields');
                  return false;
              }

              // Enhanced email validation
              const emailRegex = /^[^\s@]+@[^\s@]+\.[^\s@]+$/;
              if (!emailRegex.test(email)) {
                  alert('Please enter a valid email address');
                  return false;
              }
          } else {
              // Validate question answers
              const questionId = $(`.csq-analysis-step[data-step="${step}"] input[type="radio"]`).attr('name');
              if (!questionId || !$(`input[name="${questionId}"]:checked`).val()) {
                  alert('Please select an answer');
                  return false;
              }
          }
          return true;
      }

      // Update progress visualization
      function updateProgress() {
          const percent = (currentStep / totalSteps) * 100;
          const dashoffset = 326.56 * (1 - (currentStep / totalSteps));

          // Update progress ring
          $('.csq-progress-ring-active').css('stroke-dashoffset', dashoffset);

          // Update current step
          $('.csq-current-step').text(currentStep);

          // Update labels
          $('.csq-progress-label').removeClass('csq-active');
          $(`.csq-progress-label:nth-child(${currentStep})`).addClass('csq-active');

          // Update navigation button visibility
          if (currentStep === 1 || currentStep === 2) {
              $('.csq-prev-btn').hide();
          } else {
              $('.csq-prev-btn').show();
          }
      }

      // Update step indicator
      function updateStepIndicator() {
          $('.csq-current-indicator').text(currentStep);
          $('.csq-total-indicator').text(totalSteps);
      }
  }

  // Initialize when document is ready
  $(document).ready(function() {
      // Add Google Fonts
      $('head').append('<link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800&display=swap" rel="stylesheet">');

      // Add Animate.css
      $('head').append('<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/animate.css/4.1.1/animate.min.css">');

      // Initialize the quiz
      initSkinQuiz();
  });
});
