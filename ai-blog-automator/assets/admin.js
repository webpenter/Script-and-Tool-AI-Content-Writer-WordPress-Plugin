(function () {
  var form = document.getElementById('bluteem-aba-generate-form');

  if (!form) {
    return;
  }

  form.addEventListener('submit', function () {
    var button = document.getElementById('bluteem-aba-generate-now');

    if (!button || button.disabled) {
      return;
    }

    var generatingLabel =
      window.bluteemAbaAdmin && window.bluteemAbaAdmin.generatingLabel
        ? window.bluteemAbaAdmin.generatingLabel
        : 'Generating...';

    button.disabled = true;
    button.setAttribute('aria-busy', 'true');
    button.dataset.originalText = button.value;
    button.value = generatingLabel;
    button.classList.add('bluteem-aba-is-generating');

    if (!form.querySelector('.bluteem-aba-generate-spinner')) {
      var spinner = document.createElement('span');
      spinner.className = 'spinner is-active bluteem-aba-generate-spinner';
      spinner.setAttribute('aria-hidden', 'true');
      button.insertAdjacentElement('afterend', spinner);
    }
  });
})();
