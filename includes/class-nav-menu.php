<?php
/**
 * Nav Menu integration - adds alerts icon to WordPress menus.
 *
 * Author: Tobalt — https://tobalt.lt
 */

namespace Tobalt\CityAlerts;

defined( 'ABSPATH' ) || exit;

class Nav_Menu {

    public function __construct() {
        add_filter( 'wp_nav_menu_items', [ $this, 'add_alerts_to_menu' ], 10, 2 );
        add_action( 'wp_footer', [ $this, 'add_menu_trigger_script' ], 99 );
    }

    /**
     * Add alerts icon to nav menu.
     */
    public function add_alerts_to_menu( $items, $args ) {
        $settings      = get_option( 'tobalt_city_alerts_settings', [] );
        $injection_mode = $settings['injection_mode'] ?? 'auto';
        $menu_location = $settings['menu_location'] ?? '';

        // Only add when menu mode is selected
        if ( 'menu' !== $injection_mode ) {
            return $items;
        }

        // Only add to specified menu location
        $theme_location = is_object( $args ) ? ( $args->theme_location ?? '' ) : '';
        if ( empty( $menu_location ) || $menu_location !== $theme_location ) {
            return $items;
        }

        $icon_color = esc_attr( $settings['icon_color'] ?? '#0073aa' );

        $tooltip_text = __( 'Avarijos ir planiniai darbai', 'tobalt-city-alerts' );

        $alert_item = sprintf(
            '<li class="menu-item tobalt-alerts-menu-item">
                <a href="#" class="tobalt-alerts-menu-trigger" aria-label="%s" onclick="event.preventDefault(); window.tobaltOpenAlertsPanel && window.tobaltOpenAlertsPanel();">
                    <svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="%s" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                        <path d="M18 8A6 6 0 0 0 6 8c0 7-3 9-3 9h18s-3-2-3-9"></path>
                        <path d="M13.73 21a2 2 0 0 1-3.46 0"></path>
                    </svg>
                    <span class="tobalt-alerts-badge" style="display:none;"></span>
                    <span class="tobalt-menu-tooltip">%s</span>
                </a>
            </li>',
            esc_attr__( 'City Alerts', 'tobalt-city-alerts' ),
            $icon_color,
            esc_html( $tooltip_text )
        );

        return $items . $alert_item;
    }

    /**
     * Add script to connect menu trigger to Alpine panel.
     */
    public function add_menu_trigger_script() {
        $settings       = get_option( 'tobalt_city_alerts_settings', [] );
        $injection_mode = $settings['injection_mode'] ?? 'auto';

        if ( 'menu' !== $injection_mode ) {
            return;
        }
        ?>
        <style>
        .tobalt-alerts-menu-item { position: relative !important; }
        .tobalt-alerts-menu-trigger { position: relative !important; display: inline-flex !important; align-items: center !important; }
        .tobalt-menu-tooltip {
            position: absolute !important;
            bottom: 100% !important;
            left: 50% !important;
            transform: translateX(-50%) !important;
            background: #333 !important;
            color: #fff !important;
            padding: 6px 12px !important;
            border-radius: 4px !important;
            font-size: 12px !important;
            font-weight: 400 !important;
            white-space: nowrap !important;
            opacity: 0 !important;
            visibility: hidden !important;
            transition: opacity 0.3s, visibility 0.3s !important;
            z-index: 99999 !important;
            pointer-events: none !important;
            margin-bottom: 8px !important;
            line-height: 1.4 !important;
        }
        .tobalt-menu-tooltip::after {
            content: '' !important;
            position: absolute !important;
            top: 100% !important;
            left: 50% !important;
            transform: translateX(-50%) !important;
            border: 6px solid transparent !important;
            border-top-color: #333 !important;
        }
        .tobalt-menu-tooltip.is-visible,
        .tobalt-alerts-menu-trigger:hover .tobalt-menu-tooltip {
            opacity: 1 !important;
            visibility: visible !important;
        }
        </style>
        <script>
        document.addEventListener('DOMContentLoaded', function() {
            // Expose function to open panel from menu
            window.tobaltOpenAlertsPanel = function() {
                var panel = document.querySelector('[x-data*="tobaltCityAlertsPanel"]');
                if (panel && panel._x_dataStack) {
                    panel._x_dataStack[0].toggle();
                }
            };

            // Show tooltip on page load, then hide after 3 seconds
            var tooltip = document.querySelector('.tobalt-menu-tooltip');
            if (tooltip) {
                setTimeout(function() {
                    tooltip.classList.add('is-visible');
                }, 500);
                setTimeout(function() {
                    tooltip.classList.remove('is-visible');
                }, 4000);
            }
        });
        </script>
        <?php
    }
}
