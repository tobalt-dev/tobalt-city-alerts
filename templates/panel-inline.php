<?php
/**
 * Inline panel template (for shortcode with inline attribute).
 *
 * Author: Tobalt — https://tobalt.lt
 */

defined( 'ABSPATH' ) || exit;
?>
<div
    x-data="tobaltCityAlertsPanel()"
    x-init="init(); open = true; loadAlerts();"
    class="tobalt-city-alerts-inline"
>
    <!-- Date Navigation -->
    <div class="tobalt-date-nav">
        <button @click="prevDay()" :disabled="!canGoPrev" class="tobalt-nav-btn">
            <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="currentColor" width="20" height="20">
                <path d="M15.41 7.41L14 6l-6 6 6 6 1.41-1.41L10.83 12z"/>
            </svg>
        </button>
        <span class="tobalt-current-date" x-text="formatDate(currentDate)"></span>
        <button @click="nextDay()" :disabled="!canGoNext" class="tobalt-nav-btn">
            <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="currentColor" width="20" height="20">
                <path d="M10 6L8.59 7.41 13.17 12l-4.58 4.59L10 18l6-6z"/>
            </svg>
        </button>
    </div>

    <!-- Category Filter -->
    <div x-show="categories.length > 0" class="tobalt-category-filter">
        <button
            @click="filterCategory(null)"
            class="tobalt-filter-btn"
            :class="{ 'is-active': !selectedCategory }"
        ><?php esc_html_e( 'All', 'tobalt-city-alerts' ); ?></button>
        <template x-for="cat in categories" :key="cat.id">
            <button
                @click="filterCategory(cat.id)"
                class="tobalt-filter-btn"
                :class="{ 'is-active': selectedCategory === cat.id }"
                x-text="cat.name"
            ></button>
        </template>
    </div>

    <!-- Alerts List -->
    <div class="tobalt-alerts-list">
        <!-- Loading -->
        <div x-show="loading" class="tobalt-loading">
            <div class="tobalt-spinner"></div>
            <span x-text="labels.loading"></span>
        </div>

        <!-- No Alerts -->
        <div x-show="!loading && alerts.length === 0" class="tobalt-no-alerts">
            <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="currentColor" width="48" height="48">
                <path d="M12 2C6.48 2 2 6.48 2 12s4.48 10 10 10 10-4.48 10-10S17.52 2 12 2zm-2 15l-5-5 1.41-1.41L10 14.17l7.59-7.59L19 8l-9 9z"/>
            </svg>
            <p x-text="labels.noAlerts"></p>
        </div>

        <!-- Alert Items -->
        <template x-for="alert in alerts" :key="alert.id">
            <div class="tobalt-alert-item" :class="{ 'is-pinned': alert.pinned }">
                <!-- Pinned Badge -->
                <span x-show="alert.pinned" class="tobalt-pinned-badge">
                    <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="currentColor" width="14" height="14">
                        <path d="M16 12V4h1V2H7v2h1v8l-2 2v2h5.2v6h1.6v-6H18v-2l-2-2z"/>
                    </svg>
                    <span x-text="labels.pinned"></span>
                </span>

                <!-- Severity Badge -->
                <span
                    x-show="alert.severity"
                    class="tobalt-severity-badge"
                    :style="{ backgroundColor: severityColors[alert.severity] }"
                    x-text="alert.severity"
                ></span>

                <!-- Alert Content -->
                <h3 class="tobalt-alert-title" x-text="alert.title"></h3>

                <div class="tobalt-alert-meta">
                    <!-- Start date/time -->
                    <span x-show="alert.date" class="tobalt-alert-date">
                        <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="currentColor" width="14" height="14">
                            <path d="M19 3h-1V1h-2v2H8V1H6v2H5c-1.11 0-1.99.9-1.99 2L3 19c0 1.1.89 2 2 2h14c1.1 0 2-.9 2-2V5c0-1.1-.9-2-2-2zm0 16H5V8h14v11zM9 10H7v2h2v-2zm4 0h-2v2h2v-2zm4 0h-2v2h2v-2zm-8 4H7v2h2v-2zm4 0h-2v2h2v-2zm4 0h-2v2h2v-2z"/>
                        </svg>
                        <span><strong>Pradžia:</strong> <span x-text="formatDateTime(alert.date, alert.time)"></span></span>
                    </span>
                    <!-- End date -->
                    <span x-show="alert.end_date" class="tobalt-alert-end-date">
                        <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="currentColor" width="14" height="14">
                            <path d="M11.99 2C6.47 2 2 6.48 2 12s4.47 10 9.99 10C17.52 22 22 17.52 22 12S17.52 2 11.99 2zM12 20c-4.42 0-8-3.58-8-8s3.58-8 8-8 8 3.58 8 8-3.58 8-8 8zm.5-13H11v6l5.25 3.15.75-1.23-4.5-2.67z"/>
                        </svg>
                        <span><strong>Pabaiga:</strong> <span x-text="formatDateTime(alert.end_date, alert.end_time)"></span></span>
                    </span>
                    <!-- Location -->
                    <span x-show="alert.location" class="tobalt-alert-location">
                        <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="currentColor" width="14" height="14">
                            <path d="M12 2C8.13 2 5 5.13 5 9c0 5.25 7 13 7 13s7-7.75 7-13c0-3.87-3.13-7-7-7zm0 9.5c-1.38 0-2.5-1.12-2.5-2.5s1.12-2.5 2.5-2.5 2.5 1.12 2.5 2.5-1.12 2.5-2.5 2.5z"/>
                        </svg>
                        <span x-text="alert.location"></span>
                    </span>
                </div>

                <div class="tobalt-alert-description" x-html="alert.description"></div>

                <!-- Categories -->
                <div x-show="alert.categories.length > 0" class="tobalt-alert-categories">
                    <template x-for="cat in alert.categories" :key="cat.id">
                        <span class="tobalt-category-tag" x-text="cat.name"></span>
                    </template>
                </div>
            </div>
        </template>
    </div>
</div>
