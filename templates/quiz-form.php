<?php
$questions = csq_get_questions();
$answers   = csq_get_answers();
$total_steps = count($questions) + 2;
?>

<div class="csq-skin-analysis-system">
  <!-- Hero Section with Animation -->
  <div class="csq-skin-hero">
    <div class="csq-hero-content">
      <div class="csq-hero-text animate__animated animate__fadeInLeft">
        <h1 class="csq-hero-title">Discover Your Perfect Skincare Routine</h1>
        <p class="csq-hero-subtitle">Take our 2-minute skin analysis to get personalized recommendations</p>
      </div>
      <div class="csq-hero-visual animate__animated animate__fadeInRight">
        <div class="csq-skin-illustration">
          <div class="csq-skin-layer csq-skin-base"></div>
          <div class="csq-skin-layer csq-skin-texture"></div>
          <div class="csq-skin-layer csq-skin-glow"></div>
        </div>
      </div>
      <button id="csq-start-quiz" class="csq-hero-cta animate__animated animate__pulse animate__infinite">
        <svg width="24" height="24" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg">
          <path d="M14 2H6C5.46957 2 4.96086 2.21071 4.58579 2.58579C4.21071 2.96086 4 3.46957 4 4V20C4 20.5304 4.21071 21.0391 4.58579 21.4142C4.96086 21.7893 5.46957 22 6 22H18C18.5304 22 19.0391 21.7893 19.4142 21.4142C19.7893 21.0391 20 20.5304 20 20V8L14 2Z" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/>
          <path d="M14 2V8H20" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/>
          <path d="M16 13H8" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/>
          <path d="M16 17H8" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/>
          <path d="M10 9H9H8" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/>
        </svg>
        Begin Skin Analysis
      </button>
    </div>
  </div>

  <!-- Analysis System -->
  <div class="csq-analysis-system" style="display: none;">
    <!-- Progress Visualization -->
    <div class="csq-analysis-progress">
      <div class="csq-progress-visual">
        <div class="csq-progress-circle">
          <svg class="csq-progress-ring" width="120" height="120">
            <circle class="csq-progress-ring-circle" stroke="#e0e6ed" stroke-width="6" fill="transparent" r="52" cx="60" cy="60"/>
            <circle class="csq-progress-ring-active" stroke="#c76f3a" stroke-width="6" stroke-dasharray="326.56" stroke-dashoffset="326.56" fill="transparent" r="52" cx="60" cy="60" transform="rotate(-90 60 60)"/>
          </svg>
          <div class="csq-progress-text">
            <span class="csq-current-step">1</span>
            <span class="csq-progress-divider">/</span>
            <span class="csq-total-steps"><?php echo $total_steps; ?></span>
          </div>
        </div>
        <div class="csq-progress-labels">
          <div class="csq-progress-label csq-active">Personal Info</div>
          <?php for ($i = 1; $i <= count($questions); $i++): ?>
          <div class="csq-progress-label">Question <?php echo $i; ?></div>
          <?php endfor; ?>
          <div class="csq-progress-label">Results</div>
        </div>
      </div>
    </div>

    <!-- Quiz Container -->
    <div class="csq-analysis-container">
      <form id="csq-quiz-form" class="csq-skin-form">
        <!-- Step 1: Personal Information -->
        <div class="csq-analysis-step active" data-step="1">
          <div class="csq-analysis-card animate__animated animate__fadeIn">
            <div class="csq-card-header">
              <div class="csq-card-icon">
                <svg width="32" height="32" viewBox="0 0 32 32" fill="none" xmlns="http://www.w3.org/2000/svg">
                  <path d="M16 20C20.4183 20 24 16.4183 24 12C24 7.58172 20.4183 4 16 4C11.5817 4 8 7.58172 8 12C8 16.4183 11.5817 20 16 20Z" stroke="#5C7E6E" stroke-width="2"/>
                  <path d="M4 28C4 23.5817 7.58172 20 12 20H20C24.4183 20 28 23.5817 28 28" stroke="#5C7E6E" stroke-width="2" stroke-linecap="round"/>
                </svg>
              </div>
              <h3 class="csq-card-title">Personalize Your Experience</h3>
              <p class="csq-card-subtitle">We'll use this information to customize your skin analysis</p>
            </div>

            <div class="csq-form-grid">
              <div class="csq-form-group">
                <label class="csq-input-label">Full Name</label>
                <div class="csq-input-with-icon">
                  <svg width="20" height="20" viewBox="0 0 20 20" fill="none" xmlns="http://www.w3.org/2000/svg">
                    <path d="M10 10C12.7614 10 15 7.76142 15 5C15 2.23858 12.7614 0 10 0C7.23858 0 5 2.23858 5 5C5 7.76142 7.23858 10 10 10Z" stroke="#7F8C8D" stroke-width="1.5"/>
                    <path d="M0 20C0 15.5817 3.58172 12 8 12H12C16.4183 12 20 15.5817 20 20" stroke="#7F8C8D" stroke-width="1.5" stroke-linecap="round"/>
                  </svg>
                  <input
                    type="text"
                    name="fullname"
                    class="csq-form-input"
                    placeholder="Your full name"
                    required
                  >
                </div>
              </div>

              <div class="csq-form-group">
                <label class="csq-input-label">Email Address</label>
                <div class="csq-input-with-icon">
                  <svg width="20" height="16" viewBox="0 0 20 16" fill="none" xmlns="http://www.w3.org/2000/svg">
                    <path d="M18 3L10 9L2 3M3 1H17C17.5304 1 18.0391 1.21071 18.4142 1.58579C18.7893 1.96086 19 2.46957 19 3V13C19 13.5304 18.7893 14.0391 18.4142 14.4142C18.0391 14.7893 17.5304 15 17 15H3C2.46957 15 1.96086 14.7893 1.58579 14.4142C1.21071 14.0391 1 13.5304 1 13V3C1 2.46957 1.21071 1.96086 1.58579 1.58579C1.96086 1.21071 2.46957 1 3 1Z" stroke="#7F8C8D" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round"/>
                  </svg>
                  <input
                    type="email"
                    name="email"
                    class="csq-form-input"
                    placeholder="name@example.com"
                    required
                  >
                </div>
              </div>
            </div>

            <div class="csq-form-group">
              <label class="csq-input-label">Skin Profile</label>
              <div class="csq-gender-select">
                <label class="csq-gender-option">
                  <input type="radio" name="gender" value="Female" required>
                  <div class="csq-gender-content">
                    <div class="csq-gender-icon">
                      <svg width="28" height="28" viewBox="0 0 28 28" fill="none" xmlns="http://www.w3.org/2000/svg">
                        <path d="M14 17C17.866 17 21 13.866 21 10C21 6.13401 17.866 3 14 3C10.134 3 7 6.13401 7 10C7 13.866 10.134 17 14 17Z" stroke="currentColor" stroke-width="1.5"/>
                        <path d="M3 25V22C3 20.3431 4.34315 19 6 19H22C23.6569 19 25 20.3431 25 22V25" stroke="currentColor" stroke-width="1.5" stroke-linecap="round"/>
                      </svg>
                    </div>
                    <span>Female</span>
                  </div>
                </label>

                <label class="csq-gender-option">
                  <input type="radio" name="gender" value="Male" required>
                  <div class="csq-gender-content">
                    <div class="csq-gender-icon">
                      <svg width="28" height="28" viewBox="0 0 28 28" fill="none" xmlns="http://www.w3.org/2000/svg">
                        <path d="M14 15C16.7614 15 19 12.7614 19 10C19 7.23858 16.7614 5 14 5C11.2386 5 9 7.23858 9 10C9 12.7614 11.2386 15 14 15Z" stroke="currentColor" stroke-width="1.5"/>
                        <path d="M5 23V21C5 19.3431 6.34315 18 8 18H20C21.6569 18 23 19.3431 23 21V23" stroke="currentColor" stroke-width="1.5" stroke-linecap="round"/>
                        <path d="M23 5L18 10" stroke="currentColor" stroke-width="1.5" stroke-linecap="round"/>
                        <path d="M18 5H23V10" stroke="currentColor" stroke-width="1.5" stroke-linecap="round"/>
                      </svg>
                    </div>
                    <span>Male</span>
                  </div>
                </label>

                <label class="csq-gender-option">
                  <input type="radio" name="gender" value="Other" required>
                  <div class="csq-gender-content">
                    <div class="csq-gender-icon">
                      <svg width="28" height="28" viewBox="0 0 28 28" fill="none" xmlns="http://www.w3.org/2000/svg">
                        <path d="M14 17C17.866 17 21 13.866 21 10C21 6.13401 17.866 3 14 3C10.134 3 7 6.13401 7 10C7 13.866 10.134 17 14 17Z" stroke="currentColor" stroke-width="1.5"/>
                        <path d="M3 25V22C3 20.3431 4.34315 19 6 19H22C23.6569 19 25 20.3431 25 22V25" stroke="currentColor" stroke-width="1.5" stroke-linecap="round"/>
                        <path d="M10 10H18" stroke="currentColor" stroke-width="1.5" stroke-linecap="round"/>
                        <path d="M10 14H18" stroke="currentColor" stroke-width="1.5" stroke-linecap="round"/>
                      </svg>
                    </div>
                    <span>Other</span>
                  </div>
                </label>
              </div>
            </div>
          </div>
        </div>

        <!-- Questions -->
        <?php foreach ($questions as $i => $q) : ?>
        <div class="csq-analysis-step" data-step="<?php echo $i + 2; ?>">
          <div class="csq-analysis-card animate__animated animate__fadeIn">
            <div class="csq-question-layout">
              <?php if (!empty($q->image_url)) : ?>
              <div class="csq-question-visual">
                <div class="csq-visual-container">
                  <img src="<?php echo esc_url($q->image_url); ?>" alt="<?php echo esc_attr($q->question_text); ?>" class="csq-skin-image">
                  <div class="csq-visual-overlay"></div>
                  <div class="csq-question-counter">Question <?php echo $i + 1; ?> of <?php echo count($questions); ?></div>
                </div>
              </div>
              <?php endif; ?>

              <div class="csq-question-content">
                <div class="csq-card-header">
                  <div class="csq-card-icon">
                    <svg width="32" height="32" viewBox="0 0 32 32" fill="none" xmlns="http://www.w3.org/2000/svg">
                      <path d="M16 28C22.6274 28 28 22.6274 28 16C28 9.37258 22.6274 4 16 4C9.37258 4 4 9.37258 4 16C4 22.6274 9.37258 28 16 28Z" stroke="#5C7E6E" stroke-width="2"/>
                      <path d="M11.4545 11.4545H16V20.3636" stroke="#5C7E6E" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/>
                      <path d="M20.5455 20.3636H11.4545" stroke="#5C7E6E" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/>
                    </svg>
                  </div>
                  <h3 class="csq-card-title">Skin Analysis</h3>
                  <p class="csq-card-subtitle"><?php echo esc_html($q->question_text); ?></p>
                </div>

                <div class="csq-answer-options">
                  <?php foreach ($answers[$q->id] ?? [] as $a) : ?>
                  <label class="csq-answer-card">
                    <input
                      type="radio"
                      name="question_<?php echo esc_attr($q->id); ?>"
                      value="<?php echo esc_attr($a->id); ?>"
                      required
                    >
                    <div class="csq-answer-content">
                      <?php if (!empty($a->image_url)) : ?>
                      <div class="csq-answer-visual">
                        <img src="<?php echo esc_url($a->image_url); ?>" alt="<?php echo esc_attr($a->answer_text); ?>">
                      </div>
                      <?php endif; ?>
                      <span class="csq-answer-text"><?php echo esc_html($a->answer_text); ?></span>
                    </div>
                  </label>
                  <?php endforeach; ?>
                </div>
              </div>
            </div>
          </div>
        </div>
        <?php endforeach; ?>

        <!-- Results Step -->
        <div class="csq-analysis-step" data-step="<?php echo $total_steps + 2; ?>">
          <div class="csq-results-container">
            <div class="csq-analysis-loading">
              <div class="csq-skin-spinner">
                <div class="csq-spinner-layer">
                  <div class="csq-spinner-fill"></div>
                </div>
                <div class="csq-spinner-core"></div>
              </div>
              <div class="csq-loading-text">
                <p class="csq-loading-title">Analyzing Your Skin Profile</p>
                <p class="csq-loading-subtitle">Creating your personalized routine</p>
              </div>
            </div>

            <div class="csq-results-content">
              <div class="csq-results-header">
                <div class="csq-results-icon">
                  <svg width="48" height="48" viewBox="0 0 48 48" fill="none" xmlns="http://www.w3.org/2000/svg">
                    <path d="M24 44C35.0457 44 44 35.0457 44 24C44 12.9543 35.0457 4 24 4C12.9543 4 4 12.9543 4 24C4 35.0457 12.9543 44 24 44Z" stroke="#5C7E6E" stroke-width="2"/>
                    <path d="M16 24L22 30L32 18" stroke="#5C7E6E" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/>
                  </svg>
                </div>
                <h2 class="csq-results-title">Your Personalized Skincare Routine</h2>
                <p class="csq-results-subtitle">Formulated specifically for your skin needs</p>
              </div>

              <div class="csq-skin-profile">
                <div class="csq-profile-card">
                  <div class="csq-profile-header">
                    <h3>Skin Analysis Summary</h3>
                    <div class="csq-profile-score">
                      <span id="csq-match-percentage">87%</span> Match
                    </div>
                  </div>
                  <div class="csq-profile-attributes">
                    <div class="csq-attribute">
                      <span class="csq-attribute-label">Skin Type:</span>
                      <span class="csq-attribute-value" id="csq-skin-type">Combination</span>
                    </div>
                    <div class="csq-attribute">
                      <span class="csq-attribute-label">Main Concerns:</span>
                      <span class="csq-attribute-value" id="csq-main-concerns">Aging, Uneven Texture</span>
                    </div>
                    <div class="csq-attribute">
                      <span class="csq-attribute-label">Sensitivity:</span>
                      <span class="csq-attribute-value" id="csq-sensitivity">Moderate</span>
                    </div>
                  </div>
                </div>
              </div>

              <div class="csq-products-section">
                <h3 class="csq-section-title">Recommended Products</h3>
                <p class="csq-section-subtitle">Based on your skin analysis</p>

                <div class="csq-products-grid" id="csq-products-grid">
                  <!-- Products will be dynamically inserted here -->
                </div>
              </div>

              <div class="csq-results-actions">
                <button type="button" id="csq-email-results" class="csq-action-btn">
                  <svg width="20" height="20" viewBox="0 0 20 20" fill="none" xmlns="http://www.w3.org/2000/svg">
                    <path d="M18 3H2C0.89543 3 0 3.89543 0 5V15C0 16.1046 0.89543 17 2 17H18C19.1046 17 20 16.1046 20 15V5C20 3.89543 19.1046 3 18 3Z" stroke="currentColor" stroke-width="1.5"/>
                    <path d="M0 5L10 10L20 5" stroke="currentColor" stroke-width="1.5" stroke-linecap="round"/>
                  </svg>
                  Email My Results
                </button>
                <button type="button" id="csq-restart-btn" class="csq-action-btn csq-alt">
                  <svg width="20" height="20" viewBox="0 0 20 20" fill="none" xmlns="http://www.w3.org/2000/svg">
                    <path d="M17.5 10C17.5 14.1421 14.1421 17.5 10 17.5C5.85786 17.5 2.5 14.1421 2.5 10C2.5 5.85786 5.85786 2.5 10 2.5C12.3206 2.5 14.4213 3.55357 15.8579 5.2381M17.5 3.33333V7.5H13.3333" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round"/>
                  </svg>
                  Retake Analysis
                </button>
              </div>
            </div>
          </div>
        </div>
      </form>
    </div>
  </div>

  <!-- Navigation Controls -->
  <div class="csq-analysis-controls">
    <button class="csq-nav-btn csq-prev-btn">
      <svg width="20" height="20" viewBox="0 0 20 20" fill="none" xmlns="http://www.w3.org/2000/svg">
        <path d="M12.5 15L7.5 10L12.5 5" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round"/>
      </svg>
      Previous
    </button>
    <div class="csq-step-indicator">
      <span class="csq-current-indicator">1</span> of <span class="csq-total-indicator"><?php echo $total_steps; ?></span>
    </div>
    <button class="csq-nav-btn csq-next-btn">
      Continue
      <svg width="20" height="20" viewBox="0 0 20 20" fill="none" xmlns="http://www.w3.org/2000/svg">
        <path d="M7.5 5L12.5 10L7.5 15" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round"/>
      </svg>
    </button>
  </div>

  <!-- Email Results Modal -->
  <div class="csq-modal" id="csq-email-modal">
    <div class="csq-modal-overlay"></div>
    <div class="csq-modal-content animate__animated animate__zoomIn">
      <button class="csq-modal-close">
        <svg width="24" height="24" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg">
          <path d="M18 6L6 18M6 6L18 18" stroke="#2f3e26" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/>
        </svg>
      </button>
      <div class="csq-modal-icon">
        <svg width="64" height="64" viewBox="0 0 64 64" fill="none" xmlns="http://www.w3.org/2000/svg">
          <path d="M52 16L32 36L12 16M10 14H54C56.2091 14 58 15.7909 58 18V46C58 48.2091 56.2091 50 54 50H10C7.79086 50 6 48.2091 6 46V18C6 15.7909 7.79086 14 10 14Z" stroke="#c76f3a" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/>
        </svg>
      </div>
      <h3 class="csq-modal-title">Email Your Results</h3>
      <p class="csq-modal-text">Enter your email to receive your personalized skincare recommendations</p>
      <form id="csq-email-form">
        <div class="csq-form-group">
          <input type="email" id="csq-email-input" class="csq-form-input" placeholder="name@example.com" required>
        </div>
        <button type="submit" class="csq-modal-btn">Send Results</button>
      </form>
    </div>
  </div>
</div>
