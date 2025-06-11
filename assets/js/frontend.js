document.addEventListener('DOMContentLoaded', () => {
  class SkinCareQuiz {
    constructor() {
      // Cache all needed DOM elements
      this.dom = {
        form:        document.getElementById('csq-quiz-form'),
        startBtn:    document.getElementById('csq-start-quiz'),
        overlay:     document.querySelector('.csq-skin-overlay'),
        instStart:   document.getElementById('csq-instruction-start'),
        container:   document.querySelector('.csq-skin-container'),
        steps:       Array.from(document.querySelectorAll('.csq-skin-step')),
        prevBtn:     document.querySelector('.csq-prev-btn'),
        nextBtn:     document.querySelector('.csq-next-btn'),
        loading:     document.querySelector('.csq-loading-state'),
        results:     document.querySelector('.csq-results-content'),
        grid:        document.getElementById('csq-products-grid'),
        restart:     document.getElementById('csq-restart-btn'),
        progressBar: document.querySelector('.csq-progress-bar'),
        currentStep: document.querySelector('.csq-current-step'),
        totalSteps:  document.querySelector('.csq-total-steps'),
      };

      this.current = 0;
      this.total   = this.dom.steps.length;
      this.sessionId = null;

      // Prevent any native form submission
      this.dom.form.addEventListener('submit', e => e.preventDefault());

      this.init();
      this.bindEvents();
    }

    init() {
      // Display total steps (questions + result)
      this.dom.totalSteps.textContent = this.total;
    }

    bindEvents() {
      // Hero → overlay
      this.dom.startBtn.addEventListener('click', () =>
        this.dom.overlay.classList.add('active')
      );

      // Overlay → first step
      this.dom.instStart.addEventListener('click', () => {
        this.dom.overlay.classList.remove('active');
        this.goto(0);
        this.dom.container.classList.add('active');
      });

      // Prev / Next navigation
      this.dom.prevBtn.addEventListener('click', () => {
        if (this.current > 0) this.goto(this.current - 1);
      });
      this.dom.nextBtn.addEventListener('click', () => this.handleNext());

      // Answer / gender selection highlighting (optional)
      document.querySelectorAll('.csq-answer-card, .csq-gender-option').forEach(el => {
        el.addEventListener('click', () => {
          const parent = el.parentNode;
          Array.from(parent.children).forEach(sib => sib.classList.remove('selected'));
          el.classList.add('selected');
          const input = el.querySelector('input');
          if (input) input.checked = true;
        });
      });

      // Retake button: reset UI w/o reload
      this.dom.restart.addEventListener('click', () => this.resetQuiz());
    }

    handleNext() {
      // Step 0: save email + gender
      if (this.current === 0) {
        if (!this.validateStep0()) return;
        this.saveContact().then(() => this.goto(1));
        return;
      }
      // Before results: submit quiz
      if (this.current === this.total - 2) {
        return this.submitQuiz();
      }
      // Validate question answer
      if (!this.validateCurrentStep()) {
        return alert('Please select an answer.');
      }
      this.goto(this.current + 1);
    }

    validateStep0() {
      const email = this.dom.form.querySelector('input[name="email"]');
      const gender = this.dom.form.querySelector('input[name="gender"]:checked');
      if (!email.checkValidity()) { email.reportValidity(); return false; }
      if (!gender) { alert('Please select your skin profile'); return false; }
      return true;
    }

    validateCurrentStep() {
      const step = this.dom.steps[this.current];
      return !!step.querySelector('input[type="radio"]:checked');
    }

    goto(index) {
      // Bounds check
      if (index < 0 || index >= this.total) return;

      // Hide old, show new
      this.dom.steps[this.current].classList.remove('active');
      this.current = index;
      this.dom.steps[this.current].classList.add('active');

      // Update progress
      this.dom.currentStep.textContent = this.current + 1;
      const pct = (this.current / (this.total - 1)) * 100;
      this.dom.progressBar.style.width = `${pct}%`;

      // Nav button text & visibility
      this.dom.prevBtn.disabled = this.current === 0;
      if (this.current === this.total - 2) {
        this.dom.nextBtn.innerHTML = 'Get Results &rarr;';
      } else {
        this.dom.nextBtn.innerHTML = 'Continue &rarr;';
      }
      // Hide on final “results” step
      const isLast = this.current === this.total - 1;
      this.dom.prevBtn.style.display = isLast ? 'none' : '';
      this.dom.nextBtn.style.display = isLast ? 'none' : '';
    }

    async saveContact() {
      const fm = new FormData(this.dom.form);
      const params = new URLSearchParams({
        action:   'csq_save_contact',
        security: csqData.nonce,
        email:    fm.get('email'),
        gender:   fm.get('gender'),
      });
      const res  = await fetch(csqData.ajaxurl, {
        method: 'POST',
        headers: {'Content-Type':'application/x-www-form-urlencoded'},
        body:    params.toString()
      });
      const json = await res.json();
      if (json.success) this.sessionId = json.data.session_id;
      else throw new Error('Failed to save contact');
    }

    async submitQuiz() {
      // Show loading spinner
      this.goto(this.total - 1);
      this.dom.loading.style.display = 'block';
      this.dom.results.style.display = 'none';

      // Prepare payload
      const fm = new FormData(this.dom.form);
      const params = new URLSearchParams();
      params.append('action',   'csq_process_quiz');
      params.append('security', csqData.nonce);
      params.append('email',    fm.get('email'));
      params.append('gender',   fm.get('gender'));
      if (this.sessionId) params.append('session_id', this.sessionId);
      document
        .querySelectorAll('input[type="radio"]:checked')
        .forEach(i => params.append('answers[]', i.value));

      // Fire AJAX
      const res  = await fetch(csqData.ajaxurl, {
        method:'POST',
        headers:{'Content-Type':'application/x-www-form-urlencoded'},
        body: params.toString()
      });
      const json = await res.json();

      // Swap spinner for product grid
      this.dom.loading.style.display = 'none';
      this.dom.results.style.display = 'block';
      this.renderResults(json.success ? json.data.products : []);
    }

    renderResults(products) {
      if (!products || products.length === 0) {
        this.dom.grid.innerHTML = `
          <div class="csq-no-products">
            <h3>No Matches Found</h3>
            <p>Try adjusting your answers and retake the quiz.</p>
          </div>`;
        return;
      }
      // Build cards
      this.dom.grid.innerHTML = products.map(p => `
        <div class="csq-product-card">
          <img src="${p.image}" alt="${p.name}">
          <h4>${p.name}</h4>
          <p>${p.details}</p>
          <a href="${p.link}" target="_blank">View Product &rarr;</a>
        </div>
      `).join('');
    }

    resetQuiz() {
      // Clear form & UI
      this.dom.form.reset();
      document.querySelectorAll('.selected').forEach(el => el.classList.remove('selected'));
      this.sessionId = null;
      this.goto(0);
      this.dom.results.style.display = 'none';
      this.dom.loading.style.display = 'none';
    }
  }

  new SkinCareQuiz();
});
