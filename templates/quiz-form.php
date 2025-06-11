<?php
$questions = csq_get_questions();
$answers   = csq_get_answers();
?>

<!-- Skincare Hero Section -->
<div class="csq-skin-hero">
  <div class="csq-hero-content">
    <div class="csq-hero-text">
      <h1 class="csq-hero-title">Discover Your Perfect Skincare Routine</h1>
      <p class="csq-hero-subtitle">Answer a few questions to get personalized recommendations</p>
    </div>
    <div class="csq-hero-visual">
      <div class="csq-skin-illustration"></div>
    </div>
    <button id="csq-start-quiz" class="csq-hero-cta">Begin Your Skin Journey</button>
  </div>
</div>

<!-- Personalized Quiz Overlay -->
<div class="csq-skin-overlay">
  <div class="csq-skin-modal">
    <div class="csq-modal-header">
      <div class="csq-modal-decor">
        <div class="csq-leaf-decor csq-leaf-1"></div>
        <div class="csq-leaf-decor csq-leaf-2"></div>
        <div class="csq-leaf-decor csq-leaf-3"></div>
      </div>
      <h2 class="csq-modal-title">Your Personalized Skin Analysis</h2>
    </div>

    <div class="csq-modal-body">
      <div class="csq-modal-step">
        <div class="csq-step-number">1</div>
        <div class="csq-step-text">Answer questions about your skin type and concerns</div>
      </div>
      <div class="csq-modal-step">
        <div class="csq-step-number">2</div>
        <div class="csq-step-text">Our algorithm analyzes your unique skin profile</div>
      </div>
      <div class="csq-modal-step">
        <div class="csq-step-number">3</div>
        <div class="csq-step-text">Receive your custom skincare regimen</div>
      </div>
    </div>

    <button id="csq-instruction-start" class="csq-modal-start">
      Start Analysis
      <svg width="18" height="14" viewBox="0 0 18 14" fill="none" xmlns="http://www.w3.org/2000/svg">
        <path d="M1 7H17M17 7L11.5 1.5M17 7L11.5 12.5" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/>
      </svg>
    </button>
  </div>
</div>

<!-- Skin Analysis Container -->
<div class="csq-skin-container">
  <!-- Progress Indicator -->
  <div class="csq-skin-progress">
    <div class="csq-progress-track">
      <div class="csq-progress-bar"></div>
    </div>
    <div class="csq-progress-info">
      <span class="csq-current-step">1</span>/<span class="csq-total-steps"><?php echo count($questions) + 1; ?></span>
    </div>
  </div>

  <form id="csq-quiz-form" class="csq-skin-form">
    <!-- Step 0: Personal Information -->
    <div class="csq-skin-step active">
      <div class="csq-skin-card">
        <div class="csq-card-header">
          <div class="csq-card-icon">
            <svg width="24" height="24" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg">
              <path d="M20 21V19C20 17.9391 19.5786 16.9217 18.8284 16.1716C18.0783 15.4214 17.0609 15 16 15H8C6.93913 15 5.92172 15.4214 5.17157 16.1716C4.42143 16.9217 4 17.9391 4 19V21" stroke="#5C7E6E" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/>
              <path d="M12 11C14.2091 11 16 9.20914 16 7C16 4.79086 14.2091 3 12 3C9.79086 3 8 4.79086 8 7C8 9.20914 9.79086 11 12 11Z" stroke="#5C7E6E" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/>
            </svg>
          </div>
          <h3 class="csq-card-title">Personalize Your Experience</h3>
          <p class="csq-card-subtitle">We'll use this to tailor your results</p>
        </div>

        <div class="csq-form-group">
          <label class="csq-input-label">Your Email</label>
          <div class="csq-input-with-icon">
            <svg width="20" height="16" viewBox="0 0 20 16" fill="none" xmlns="http://www.w3.org/2000/svg">
              <path d="M18 3L10 9L2 3M3 1H17C17.5304 1 18.0391 1.21071 18.4142 1.58579C18.7893 1.96086 19 2.46957 19 3V13C19 13.5304 18.7893 14.0391 18.4142 14.4142C18.0391 14.7893 17.5304 15 17 15H3C2.46957 15 1.96086 14.7893 1.58579 14.4142C1.21071 14.0391 1 13.5304 1 13V3C1 2.46957 1.21071 1.96086 1.58579 1.58579C1.96086 1.21071 2.46957 1 3 1Z" stroke="#5C7E6E" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/>
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

        <div class="csq-form-group">
          <label class="csq-input-label">Skin Profile</label>
          <div class="csq-gender-select">
            <label class="csq-gender-option">
              <input type="radio" name="gender" value="Female" required>
              <div class="csq-gender-content">
                <svg width="24" height="24" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg">
                  <path d="M12 15C15.3137 15 18 12.3137 18 9C18 5.68629 15.3137 3 12 3C8.68629 3 6 5.68629 6 9C6 12.3137 8.68629 15 12 15Z" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/>
                  <path d="M3 21V19C3 17.9391 3.42143 16.9217 4.17157 16.1716C4.92172 15.4214 5.93913 15 7 15H17C18.0609 15 19.0783 15.4214 19.8284 16.1716C20.5786 16.9217 21 17.9391 21 19V21" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/>
                </svg>
                <span>Female</span>
              </div>
            </label>

            <label class="csq-gender-option">
              <input type="radio" name="gender" value="Male" required>
              <div class="csq-gender-content">
                <svg width="24" height="24" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg">
                  <path d="M10 15C12.7614 15 15 12.7614 15 10C15 7.23858 12.7614 5 10 5C7.23858 5 5 7.23858 5 10C5 12.7614 7.23858 15 10 15Z" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/>
                  <path d="M2 21V19C2 17.9391 2.42143 16.9217 3.17157 16.1716C3.92172 15.4214 4.93913 15 6 15H14C15.0609 15 16.0783 15.4214 16.8284 16.1716C17.5786 16.9217 18 17.9391 18 19V21" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/>
                  <path d="M21 3L15 9" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/>
                  <path d="M15 3H21V9" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/>
                </svg>
                <span>Male</span>
              </div>
            </label>

            <label class="csq-gender-option">
              <input type="radio" name="gender" value="Other" required>
              <div class="csq-gender-content">
                <svg width="24" height="24" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg">
                  <path d="M12 15C15.3137 15 18 12.3137 18 9C18 5.68629 15.3137 3 12 3C8.68629 3 6 5.68629 6 9C6 12.3137 8.68629 15 12 15Z" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/>
                  <path d="M3 21V19C3 17.9391 3.42143 16.9217 4.17157 16.1716C4.92172 15.4214 5.93913 15 7 15H17C18.0609 15 19.0783 15.4214 19.8284 16.1716C20.5786 16.9217 21 17.9391 21 19V21" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/>
                  <path d="M9 9H15" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/>
                  <path d="M9 12H15" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/>
                </svg>
                <span>Other</span>
              </div>
            </label>
          </div>
        </div>
      </div>
    </div>

    <!-- Steps 1…N: Questions -->
    <?php foreach ($questions as $i => $q) : ?>
      <div class="csq-skin-step">
        <div class="csq-skin-card">
          <div class="csq-card-header">
            <div class="csq-card-icon">
              <svg width="24" height="24" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg">
                <path d="M12 22C17.5228 22 22 17.5228 22 12C22 6.47715 17.5228 2 12 2C6.47715 2 2 6.47715 2 12C2 17.5228 6.47715 22 12 22Z" stroke="#5C7E6E" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/>
                <path d="M9.09 9C9.3251 8.33167 9.78915 7.76811 10.4 7.40913C11.0108 7.05016 11.7289 6.91894 12.4272 7.03871C13.1255 7.15848 13.7588 7.52152 14.2151 8.06353C14.6713 8.60553 14.9211 9.29152 14.92 10C14.92 12 11.92 13 11.92 13" stroke="#5C7E6E" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/>
                <path d="M12 17H12.01" stroke="#5C7E6E" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/>
              </svg>
            </div>
            <h3 class="csq-card-title">Skin Analysis: Step <?php echo $i + 1; ?></h3>
            <p class="csq-card-subtitle"><?php echo esc_html($q->question_text); ?></p>
          </div>

          <?php if (!empty($q->image_url)) : ?>
            <div class="csq-skin-media">
              <img
                src="<?php echo esc_url($q->image_url); ?>"
                alt="<?php echo esc_attr($q->question_text); ?>"
                class="csq-skin-image"
              >
            </div>
          <?php endif; ?>

          <div class="csq-answer-grid">
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
                    <div class="csq-answer-media">
                      <img
                        src="<?php echo esc_url($a->image_url); ?>"
                        alt="<?php echo esc_attr($a->answer_text); ?>"
                      >
                    </div>
                  <?php endif; ?>
                  <span class="csq-answer-text"><?php echo esc_html($a->answer_text); ?></span>
                </div>
              </label>
            <?php endforeach; ?>
          </div>
        </div>
      </div>
    <?php endforeach; ?>

    <!-- Results Step -->
    <div class="csq-skin-step">
      <div class="csq-results-container">
        <div class="csq-loading-state">
          <div class="csq-skin-spinner">
            <div class="csq-spinner-dot"></div>
            <div class="csq-spinner-dot"></div>
            <div class="csq-spinner-dot"></div>
          </div>
          <p class="csq-loading-text">Analyzing your skin profile...</p>
        </div>

        <div class="csq-results-content" id="csq-results-content">
          <div class="csq-results-header">
            <div class="csq-results-decor">
              <div class="csq-results-leaf csq-leaf-1"></div>
              <div class="csq-results-leaf csq-leaf-2"></div>
            </div>
            <h2 class="csq-results-title">Your Personalized Skincare Routine</h2>
            <p class="csq-results-subtitle">Formulated specifically for your skin needs</p>
          </div>

          <div class="csq-products-grid" id="csq-products-grid"></div>

          <div class="csq-results-footer">
            <p class="csq-results-note">Based on your analysis, we recommend starting with these products</p>
            <button type="button" id="csq-restart-btn" class="csq-restart-btn">
              <svg width="20" height="20" viewBox="0 0 20 20" fill="none" xmlns="http://www.w3.org/2000/svg">
                <path d="M17.5 10C17.5 14.1421 14.1421 17.5 10 17.5C5.85786 17.5 2.5 14.1421 2.5 10C2.5 5.85786 5.85786 2.5 10 2.5C12.3206 2.5 14.4213 3.55357 15.8579 5.2381M17.5 3.33333V7.5H13.3333" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/>
              </svg>
              Retake Analysis
            </button>
          </div>
        </div>
      </div>
    </div>
  </form>

  <!-- Navigation Controls -->
  <div class="csq-skin-controls">
    <button class="csq-nav-btn csq-prev-btn">
      <svg width="16" height="16" viewBox="0 0 16 16" fill="none" xmlns="http://www.w3.org/2000/svg">
        <path d="M10 12L6 8L10 4" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/>
      </svg>
      Previous
    </button>
    <button class="csq-nav-btn csq-next-btn">
      Continue
      <svg width="16" height="16" viewBox="0 0 16 16" fill="none" xmlns="http://www.w3.org/2000/svg">
        <path d="M6 4L10 8L6 12" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/>
      </svg>
    </button>
  </div>
</div>
