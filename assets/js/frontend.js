document.addEventListener('DOMContentLoaded', () => {
  class SkinCareQuiz {
    constructor() {
      this.dom = {
        hero:        document.querySelector('.csq-hero'),
        startBtn:    document.getElementById('csq-start-quiz'),
        instruction: document.querySelector('.csq-instruction-overlay'),
        instStart:   document.getElementById('csq-instruction-start'),
        container:   document.querySelector('.csq-quiz-container'),
        form:        document.getElementById('csq-quiz-form'),
        steps:       Array.from(document.querySelectorAll('.csq-step')),
        progress:    document.querySelector('.csq-progress-bar'),
        progressInfo: document.querySelector('.csq-progress-info'),
        prevBtn:     document.querySelector('.csq-prev-btn'),
        nextBtn:     document.querySelector('.csq-next-btn'),
        loading:     document.querySelector('.csq-loading-state'),
        results:     document.querySelector('.csq-results-content'),
        grid:        document.getElementById('csq-products-grid'),
        overlay:     document.getElementById('csq-champagne-overlay'),
        restart:     document.getElementById('csq-restart-btn'),
      };
      this.current   = 0;
      this.total     = this.dom.steps.length;
      this.sessionId = null;
      this.bind();
    }

    bind() {
      this.dom.startBtn.addEventListener('click', () =>
        this.dom.instruction.classList.add('active')
      );
      this.dom.instStart.addEventListener('click', () => {
        this.dom.instruction.classList.remove('active');
        setTimeout(() => this.showQuiz(), 200);
      });

      this.dom.prevBtn.addEventListener('click', () => {
        if (this.current > 1) this.goto(this.current - 1);
      });

      this.dom.nextBtn.addEventListener('click', () => {
        // Step 0: validate email/gender
        if (this.current === 0) {
          if (!this.dom.form.email.checkValidity() || !this.dom.form.gender.value) {
            alert('Please enter your email and select a gender.');
            return;
          }
          this.saveContact().then(() => this.goto(1));
          return;
        }

        // Last question -> Submit
        if (this.current === this.total - 2) {
          this.submit();
          return;
        }

        // Intermediate question -> ensure one answer selected
        const inputs = this.dom.steps[this.current].querySelectorAll('input[type="radio"]');
        if (![...inputs].some(i => i.checked)) {
          alert('Please choose an option before continuing.');
          return;
        }

        // Otherwise just advance
        this.goto(this.current + 1);
      });

      this.dom.form.addEventListener('submit', e => e.preventDefault());

      // Answer card click highlighting
      document.querySelectorAll('.csq-answer-card').forEach(card =>
        card.addEventListener('click', () => {
          card.querySelector('input').checked = true;
          card.classList.add('selected');
          card.parentNode
            .querySelectorAll('.csq-answer-card')
            .forEach(sib => { if (sib !== card) sib.classList.remove('selected'); });
        })
      );

      this.dom.restart.addEventListener('click', () => window.location.reload());
    }

    showQuiz() {
      this.dom.hero.style.transform      = 'translateX(-100%)';
      this.dom.hero.style.opacity        = '0';
      this.dom.container.style.transform = 'translateX(0)';
      this.dom.container.style.opacity   = '1';
      this.goto(0);
    }

    goto(idx) {
      if (idx < 0 || idx > this.total - 1) return;
      this.dom.steps[this.current].classList.remove('active');
      this.current = idx;
      this.dom.steps[this.current].classList.add('active');
      this.updateNav();
      this.updateProgress();
    }

    updateNav() {
      // hide back on step 0 & 1
      this.dom.prevBtn.style.visibility = this.current > 1 ? 'visible' : 'hidden';
      // hide next on results
      this.dom.nextBtn.style.visibility = this.current === this.total - 1 ? 'hidden' : 'visible';
      // change text
      this.dom.nextBtn.querySelector('span').textContent =
        this.current === this.total - 2 ? 'Submit' : 'Continue';
    }

    updateProgress() {
      const pct = this.current / (this.total - 1) * 100;
      this.dom.progress.style.width = pct + '%';
      this.dom.progressInfo.textContent = `Step ${this.current + 1} of ${this.total}`;
    }

    async saveContact() {
      const fm = new FormData(this.dom.form);
      const params = new URLSearchParams({
        action:   'csq_save_contact',
        security: csqData.nonce,
        email:    fm.get('email'),
        gender:   fm.get('gender')
      });
      const res  = await fetch(csqData.ajaxurl, {
        method: 'POST',
        headers:{ 'Content-Type':'application/x-www-form-urlencoded' },
        body:   params.toString()
      });
      const json = await res.json();
      if (json.success) this.sessionId = json.data.session_id;
    }

    async submit() {
      this.goto(this.total - 1);
      this.dom.loading.classList.add('active');
      this.dom.results.style.display = 'none';

      const fm = new FormData(this.dom.form);
      const params = new URLSearchParams();
      params.append('action',   'csq_process_quiz');
      params.append('security', csqData.nonce);
      params.append('email',    fm.get('email'));
      params.append('gender',   fm.get('gender'));
      if (this.sessionId) params.append('session_id', this.sessionId);
      fm.forEach((v,k) => {
        if (k.startsWith('question_')) params.append('answers[]', v);
      });

      try {
        const resp = await fetch(csqData.ajaxurl, {
          method: 'POST',
          headers:{ 'Content-Type':'application/x-www-form-urlencoded' },
          body:   params.toString()
        });
        const json = await resp.json();
        if (!json.success) throw new Error(json.data || 'Server error');
        // Stop spinner & show results
        this.dom.loading.classList.remove('active');
        this.dom.results.style.display = '';
        // Render & animate
        this.renderResults(json.data.products);
      } catch (err) {
        alert('Quiz failed: ' + err.message);
        this.dom.loading.classList.remove('active');
        this.dom.results.style.display = '';
      }
    }

    renderResults(products) {
      if (!products.length) {
        this.dom.grid.innerHTML = '<p>No matches found. Try again!</p>';
      } else {
        this.dom.grid.innerHTML = products.slice(0,4).map(p => `
          <div class="csq-product-card">
            <img src="${p.image}" alt="${p.name}">
            <h3>${p.name}</h3>
            <p>${p.details}</p>
            <a href="${p.link}" target="_blank">Buy Now →</a>
          </div>
        `).join('');
      }
      // trigger the shine animation
      this.dom.overlay.classList.add('animate-shine');
      // hide nav buttons
      this.dom.prevBtn.style.visibility = 'hidden';
      this.dom.nextBtn.style.visibility = 'hidden';
    }
  }

  new SkinCareQuiz();
});
