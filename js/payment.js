/* ================================================================
   IMMO-LOCATION — Credit Card interactive simulation (3D Flip)
   ================================================================ */

document.addEventListener('DOMContentLoaded', () => {
  const card = document.querySelector('.credit-card');
  const numberInput = document.getElementById('card_number');
  const nameInput = document.getElementById('card_name');
  const expiryInput = document.getElementById('card_expiry');
  const cvvInput = document.getElementById('card_cvv');
  const paymentForm = document.getElementById('payment-form');

  if (!paymentForm) return;

  // Real-time card number masking and typing
  numberInput.addEventListener('input', (e) => {
    let value = e.target.value.replace(/\D/g, '');
    value = value.substring(0, 16); // max 16 chars
    
    // Group in sets of 4
    const formatted = value.match(/.{1,4}/g)?.join(' ') || '';
    e.target.value = formatted;

    const display = document.querySelector('.card-number-display');
    if (display) {
      display.textContent = formatted || '•••• •••• •••• ••••';
    }
  });

  // Real-time cardholder name typing
  nameInput.addEventListener('input', (e) => {
    const value = e.target.value;
    const display = document.querySelector('.card-holder-name');
    if (display) {
      display.textContent = value.toUpperCase() || 'VOTRE NOM COMPLET';
    }
  });

  // Real-time expiry typing
  expiryInput.addEventListener('input', (e) => {
    let value = e.target.value.replace(/\D/g, '');
    if (value.length > 2) {
      value = value.substring(0, 2) + '/' + value.substring(2, 4);
    }
    e.target.value = value;

    const display = document.querySelector('.card-expiry-val');
    if (display) {
      display.textContent = value || 'MM/AA';
    }
  });

  // CVV focus flips the card
  cvvInput.addEventListener('focus', () => {
    card.classList.add('flipped');
  });

  cvvInput.addEventListener('blur', () => {
    card.classList.remove('flipped');
  });

  // Real-time CVV typing
  cvvInput.addEventListener('input', (e) => {
    let value = e.target.value.replace(/\D/g, '');
    value = value.substring(0, 3);
    e.target.value = value;

    const display = document.querySelector('.card-cvv-display');
    if (display) {
      display.textContent = value || '•••';
    }
  });

  // Credit card Luhn Algorithm validation
  function validateLuhn(cardNumber) {
    const cleanNumber = cardNumber.replace(/\s/g, '');
    if (!/^\d{16}$/.test(cleanNumber)) return false;

    let sum = 0;
    let shouldDouble = false;
    for (let i = cleanNumber.length - 1; i >= 0; i--) {
      let digit = parseInt(cleanNumber.charAt(i));

      if (shouldDouble) {
        if ((digit *= 2) > 9) digit -= 9;
      }

      sum += digit;
      shouldDouble = !shouldDouble;
    }

    return (sum % 10) === 0;
  }

  // Handle payment form submission with premium loading flow
  paymentForm.addEventListener('submit', (e) => {
    e.preventDefault();

    const numberVal = numberInput.value;
    const expiryVal = expiryInput.value;
    const cvvVal = cvvInput.value;

    // Validation
    if (!validateLuhn(numberVal)) {
      window.showToast("Le numéro de carte bancaire saisi est invalide (Luhn check échoué).", "error", "Erreur de paiement");
      numberInput.classList.add('error');
      return;
    } else {
      numberInput.classList.remove('error');
    }

    if (!/^\d{2}\/\d{2}$/.test(expiryVal)) {
      window.showToast("La date d'expiration doit être au format MM/AA.", "error", "Erreur de paiement");
      expiryInput.classList.add('error');
      return;
    } else {
      expiryInput.classList.remove('error');
    }

    if (cvvVal.length < 3) {
      window.showToast("Le code CVV doit contenir 3 chiffres.", "error", "Erreur de paiement");
      cvvInput.classList.add('error');
      return;
    } else {
      cvvInput.classList.remove('error');
    }

    // Submit animation loader simulation
    const payBtn = document.getElementById('pay-submit-btn');
    payBtn.classList.add('loading');
    payBtn.disabled = true;

    window.showToast("Connexion à la passerelle bancaire...", "info", "Paiement en cours", 2000);

    setTimeout(() => {
      window.showToast("Authentification 3D-Secure...", "warning", "Paiement en cours", 2500);

      setTimeout(() => {
        window.showToast("Finalisation de la transaction...", "success", "Paiement en cours", 1500);

        setTimeout(() => {
          // Submit the actual HTML form
          paymentForm.submit();
        }, 1500);

      }, 2500);

    }, 2000);
  });
});
