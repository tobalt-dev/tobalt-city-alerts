/**
 * CityAlerts Admin JavaScript
 * Author: Tobalt — https://tobalt.lt
 */

(function() {
    'use strict';

    // Select all checkbox
    const selectAll = document.getElementById('cb-select-all');
    if (selectAll) {
        selectAll.addEventListener('change', function() {
            const checkboxes = document.querySelectorAll('input[name="email_ids[]"]');
            checkboxes.forEach(cb => cb.checked = this.checked);
        });
    }
})();
