/* Toggle visibility for every password field on the page. */
(function () {
    'use strict';

    function setupPasswordToggle(input) {
        if (input.dataset.passwordToggle === 'ready') {
            return;
        }

        input.dataset.passwordToggle = 'ready';

        var wrapper = document.createElement('div');
        wrapper.className = 'input-group';

        var parent = input.parentNode;
        parent.insertBefore(wrapper, input);
        wrapper.appendChild(input);

        var button = document.createElement('button');
        button.type = 'button';
        button.className = 'btn btn-outline-secondary';
        button.setAttribute('aria-label', 'Show password');
        button.innerHTML = '<i class="bi bi-eye" aria-hidden="true"></i>';

        button.addEventListener('click', function () {
            if (input.type === 'password') {
                input.type = 'text';
                button.innerHTML = '<i class="bi bi-eye-slash" aria-hidden="true"></i>';
                button.setAttribute('aria-label', 'Hide password');
            } else {
                input.type = 'password';
                button.innerHTML = '<i class="bi bi-eye" aria-hidden="true"></i>';
                button.setAttribute('aria-label', 'Show password');
            }
        });

        wrapper.appendChild(button);
    }

    function init() {
        document.querySelectorAll('input[type="password"]').forEach(setupPasswordToggle);
    }

    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', init);
    } else {
        init();
    }
})();