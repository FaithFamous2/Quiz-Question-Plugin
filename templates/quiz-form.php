<?php
/**
 * Quiz Form Template
 */
if ( ! isset( $questions ) || ! is_array( $questions ) ) {
    $questions = [];
}
if ( ! isset( $answers ) || ! is_array( $answers ) ) {
    $answers = [];
}
?>
<!-- Hero -->
<div class="csq-hero">
  <div class="csq-hero-content">
    <h1 class="csq-hero-title">Get Started with Your Skincare Quiz</h1>
    <button id="csq-start-quiz" class="csq-hero-cta">Take Your Skincare Quiz</button>
  </div>
</div>

<!-- Instruction Overlay -->
<div class="csq-instruction-overlay">
  <div class="csq-instruction-modal">
    <h2 class="csq-modal-title">How It Works</h2>
    <p class="csq-modal-text">
      Answer a few quick questions<br>
      and we’ll recommend up to 4 products.<br>
      No page reloads—instant results!
    </p>
    <button id="csq-instruction-start" class="csq-modal-start">Start Quiz</button>
  </div>
</div>

<!-- Quiz Container -->
<div class="csq-quiz-container">
  <div class="csq-progress-track">
    <div class="csq-progress-bar"></div>
  </div>
  <div class="csq-progress-info"></div>

  <form id="csq-quiz-form">
    <!-- Step 0: Email & Gender -->
    <div class="csq-step">
      <div class="csq-question-card">
        <h2 class="csq-question-header">Tell Us About You</h2>
        <div class="csq-form-grid">
          <input
            type="email"
            name="email"
            class="csq-form-input"
            placeholder="Your Email"
            required
          >
          <select
            name="gender"
            class="csq-form-select"
            required
          >
            <option value="">Select Gender</option>
            <option value="Female">Female</option>
            <option value="Male">Male</option>
            <option value="Other">Other</option>
          </select>
        </div>
      </div>
    </div>

    <!-- Dynamic Quiz Questions (Steps 1…N) -->
    <?php foreach ( $questions as $i => $q ) : ?>
      <div class="csq-step">
        <div class="csq-question-card">
          <?php if ( ! empty( $q->image_url ) ) : ?>
            <div class="csq-media-container">
              <img
                src="<?php echo esc_url( $q->image_url ); ?>"
                alt="<?php echo esc_attr( $q->question_text ); ?>"
                class="csq-question-media"
              >
            </div>
          <?php endif; ?>
          <div class="csq-question-body">
            <h3 class="csq-question-title">Question <?php echo $i + 1; ?>:</h3>
            <p class="csq-question-text"><?php echo esc_html( $q->question_text ); ?></p>
            <div class="csq-answer-grid">
              <?php foreach ( $answers[ $q->id ] ?? [] as $a ) : ?>
                <label class="csq-answer-card">
                  <input
                    type="radio"
                    name="question_<?php echo esc_attr( $q->id ); ?>"
                    value="<?php echo esc_attr( $a->id ); ?>"
                    required
                  >
                  <span><?php echo esc_html( $a->answer_text ); ?></span>
                  <?php if ( ! empty( $a->image_url ) ) : ?>
                    <img
                      src="<?php echo esc_url( $a->image_url ); ?>"
                      alt="<?php echo esc_attr( $a->answer_text ); ?>"
                      class="csq-answer-media"
                    >
                  <?php endif; ?>
                </label>
              <?php endforeach; ?>
            </div>
          </div>
        </div>
      </div>
    <?php endforeach; ?>

    <!-- Results Step -->
    <div class="csq-step">
      <div class="csq-results-container">
        <div class="csq-loading-state">
          <div class="csq-loading-spinner"></div>
          <p>Analyzing your responses…</p>
        </div>
        <div class="csq-results-content" id="csq-results-content">
          <h2>Your Personalized Routine</h2>
          <div class="csq-products-grid" id="csq-products-grid"></div>
          <div class="csq-champagne-overlay" id="csq-champagne-overlay"></div>
          <button type="button" id="csq-restart-btn" class="csq-restart-btn">
            Restart Quiz
          </button>
        </div>
      </div>
    </div>
  </form>

  <!-- Navigation -->
  <div class="csq-quiz-controls">
    <button class="csq-nav-btn csq-prev-btn" disabled>← Back</button>
    <button class="csq-nav-btn csq-next-btn"><span>Continue</span></button>
  </div>
</div>
