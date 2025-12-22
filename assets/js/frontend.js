/**
 * CityAlerts Frontend JavaScript
 * Author: Tobalt — https://tobalt.lt
 */

document.addEventListener('alpine:init', () => {
    Alpine.data('tobaltCityAlertsPanel', () => ({
        open: false,
        loading: false,
        alerts: [],
        alertCount: 0,
        currentDate: null,
        minDate: null,
        maxDate: null,
        categories: [],
        selectedCategory: null,

        // From localized data
        apiUrl: window.tobaltCityAlerts?.apiUrl || '/wp-json/tobalt/v1/',
        settings: window.tobaltCityAlerts?.settings || {},
        labels: window.tobaltCityAlerts?.labels || {},
        severityColors: window.tobaltCityAlerts?.severityColors || {},

        get canGoPrev() {
            if (!this.currentDate || !this.minDate) return false;
            return this.currentDate > this.minDate;
        },

        get canGoNext() {
            if (!this.currentDate || !this.maxDate) return false;
            return this.currentDate < this.maxDate;
        },

        async init() {
            // Set date range
            const today = new Date();
            today.setHours(0, 0, 0, 0);

            const dateRange = this.settings.dateRange || 7;
            const maxDate = new Date(today);
            maxDate.setDate(maxDate.getDate() + dateRange);

            this.minDate = this.formatDateISO(today);
            this.maxDate = this.formatDateISO(maxDate);
            this.currentDate = this.minDate;

            // Load categories
            await this.loadCategories();

            // Load initial alerts count
            this.loadAlertCount();
        },

        toggle() {
            this.open = !this.open;
            if (this.open) {
                this.loadAlerts();
                document.body.style.overflow = 'hidden';
            } else {
                document.body.style.overflow = '';
            }
        },

        close() {
            this.open = false;
            document.body.style.overflow = '';
        },

        prevDay() {
            if (!this.canGoPrev) return;

            const date = new Date(this.currentDate);
            date.setDate(date.getDate() - 1);
            this.currentDate = this.formatDateISO(date);
            this.alerts = []; // Clear immediately
            this.loadAlerts();
        },

        nextDay() {
            if (!this.canGoNext) return;

            const date = new Date(this.currentDate);
            date.setDate(date.getDate() + 1);
            this.currentDate = this.formatDateISO(date);
            this.alerts = []; // Clear immediately
            this.loadAlerts();
        },

        filterCategory(categoryId) {
            this.selectedCategory = categoryId;
            this.alerts = []; // Clear immediately
            this.loadAlerts();
        },

        async loadAlerts() {
            this.loading = true;

            try {
                const url = new URL(this.apiUrl + 'alerts', window.location.origin);
                url.searchParams.set('date', this.currentDate);

                if (this.selectedCategory) {
                    url.searchParams.set('category', this.selectedCategory);
                }

                const res = await fetch(url.toString());
                const data = await res.json();

                this.alerts = data.alerts || [];
            } catch (e) {
                console.error('Failed to load alerts', e);
                this.alerts = [];
            }

            this.loading = false;
        },

        async loadAlertCount() {
            try {
                const url = new URL(this.apiUrl + 'alerts', window.location.origin);
                url.searchParams.set('from', this.minDate);
                url.searchParams.set('to', this.maxDate);

                const res = await fetch(url.toString());
                const data = await res.json();

                this.alertCount = data.total || 0;
            } catch (e) {
                console.error('Failed to load alert count', e);
            }
        },

        async loadCategories() {
            try {
                const res = await fetch(this.apiUrl + 'categories');
                const data = await res.json();
                this.categories = data || [];
            } catch (e) {
                console.error('Failed to load categories', e);
                this.categories = [];
            }
        },

        formatDate(dateStr) {
            if (!dateStr) return '';

            // Lithuanian month names (genitive case)
            const ltMonths = [
                'sausio', 'vasario', 'kovo', 'balandžio', 'gegužės', 'birželio',
                'liepos', 'rugpjūčio', 'rugsėjo', 'spalio', 'lapkričio', 'gruodžio'
            ];

            // Lithuanian weekday names
            const ltWeekdays = [
                'sekmadienis', 'pirmadienis', 'antradienis', 'trečiadienis',
                'ketvirtadienis', 'penktadienis', 'šeštadienis'
            ];

            const date = new Date(dateStr + 'T00:00:00');
            const today = new Date();
            today.setHours(0, 0, 0, 0);

            const tomorrow = new Date(today);
            tomorrow.setDate(tomorrow.getDate() + 1);

            // Check if today
            if (date.getTime() === today.getTime()) {
                return this.labels?.today || 'Šiandien';
            }

            // Check if tomorrow
            if (date.getTime() === tomorrow.getTime()) {
                return this.labels?.tomorrow || 'Rytoj';
            }

            // Format: "Gruodžio 14 d., sekmadienis"
            const month = ltMonths[date.getMonth()];
            const day = date.getDate();
            const weekday = ltWeekdays[date.getDay()];

            return `${month.charAt(0).toUpperCase() + month.slice(1)} ${day} d., ${weekday}`;
        },

        formatDateISO(date) {
            const year = date.getFullYear();
            const month = String(date.getMonth() + 1).padStart(2, '0');
            const day = String(date.getDate()).padStart(2, '0');
            return `${year}-${month}-${day}`;
        },

        formatDateTime(dateStr, timeStr) {
            if (!dateStr) return '';

            const date = new Date(dateStr + 'T00:00:00');
            const year = date.getFullYear();
            const month = String(date.getMonth() + 1).padStart(2, '0');
            const day = String(date.getDate()).padStart(2, '0');

            // Lithuanian format: YYYY-MM-DD
            let formatted = `${year}-${month}-${day}`;

            if (timeStr) {
                formatted += ' ' + timeStr;
            }

            return formatted;
        }
    }));
});
