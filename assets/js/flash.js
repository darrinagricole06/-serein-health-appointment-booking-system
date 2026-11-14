/* Auto-dismiss flash messages after 6 seconds. */
(function () {
    'use strict';

    function removeEmptyFlashContainer(alert) {
        const container = alert.closest('.container.mt-3');
        if (container && !container.querySelector('.alert')) {
            container.remove();
        }
    }

    function dismissFlashMessages() {
        document.querySelectorAll('.alert-dismissible').forEach(function (alert) {
            const container = alert.closest('.container.mt-3');

            window.setTimeout(function () {
                if (window.bootstrap && window.bootstrap.Alert) {
                    window.bootstrap.Alert.getOrCreateInstance(alert).close();
                    window.setTimeout(function () {
                        if (container && !container.querySelector('.alert')) {
                            container.remove();
                        }
                    }, 300);
                } else {
                    alert.remove();
                    if (container && !container.querySelector('.alert')) {
                        container.remove();
                    }
                }
            }, 6000);
        });
    }

    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', dismissFlashMessages);
    } else {
        dismissFlashMessages();
    }
})();
