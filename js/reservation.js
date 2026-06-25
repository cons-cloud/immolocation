/* ================================================================
   IMMO-LOCATION — Tunnel de Réservation Multi-Étapes JS
   ================================================================ */

document.addEventListener('DOMContentLoaded', () => {
  const wizardForm = document.getElementById('checkout-wizard-form');
  if (!wizardForm) return;

  const steps = Array.from(document.querySelectorAll('.wizard-step'));
  const progressNodes = Array.from(document.querySelectorAll('.step-node'));
  const progressBar = document.querySelector('.steps-progress-bar');
  let currentStepIdx = 0;

  function updateSteps() {
    steps.forEach((step, idx) => {
      step.classList.toggle('active', idx === currentStepIdx);
    });

    progressNodes.forEach((node, idx) => {
      node.classList.toggle('active', idx === currentStepIdx);
      node.classList.toggle('completed', idx < currentStepIdx);
    });

    const progressPercent = (currentStepIdx / (steps.length - 1)) * 100;
    if (progressBar) {
      progressBar.style.width = `${progressPercent}%`;
    }

    // Scroll to top of form
    window.scrollTo({ top: 180, behavior: 'smooth' });
  }

  // Handle next button click
  document.querySelectorAll('.btn-next-step').forEach(btn => {
    btn.addEventListener('click', () => {
      // Validate current step fields
      const currentStepEl = steps[currentStepIdx];
      let isValid = true;
      
      currentStepEl.querySelectorAll('[required]').forEach(field => {
        const errorMsg = field.parentElement.querySelector('.form-error');
        if (!field.value.trim()) {
          field.classList.add('error');
          if (errorMsg) errorMsg.style.display = 'flex';
          isValid = false;
        } else {
          field.classList.remove('error');
          if (errorMsg) errorMsg.style.display = 'none';
        }
      });

      if (!isValid) {
        window.showToast("Veuillez remplir tous les champs obligatoires pour continuer.", "warning", "Champs requis");
        return;
      }

      if (currentStepIdx < steps.length - 1) {
        currentStepIdx++;
        updateSteps();
      }
    });
  });

  // Handle prev button click
  document.querySelectorAll('.btn-prev-step').forEach(btn => {
    btn.addEventListener('click', () => {
      if (currentStepIdx > 0) {
        currentStepIdx--;
        updateSteps();
      }
    });
  });

  // Initial update
  updateSteps();
});
