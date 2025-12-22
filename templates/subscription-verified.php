<?php
/**
 * Subscription verified success page.
 *
 * Author: Tobalt — https://tobalt.lt
 */

defined( 'ABSPATH' ) || exit;
?>
<!DOCTYPE html>
<html <?php language_attributes(); ?>>
<head>
    <meta charset="<?php bloginfo( 'charset' ); ?>">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title><?php esc_html_e( 'Subscription Verified', 'tobalt-city-alerts' ); ?> - <?php bloginfo( 'name' ); ?></title>
    <?php wp_head(); ?>
    <style>
        body {
            font-family: -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, sans-serif;
            background: #f0f0f1;
            margin: 0;
            padding: 20px;
            display: flex;
            align-items: center;
            justify-content: center;
            min-height: 100vh;
            box-sizing: border-box;
        }
        .tobalt-message-box {
            max-width: 450px;
            background: #fff;
            border-radius: 8px;
            box-shadow: 0 2px 4px rgba(0,0,0,0.1);
            padding: 40px;
            text-align: center;
        }
        .tobalt-message-box svg {
            color: #46b450;
            margin-bottom: 20px;
        }
        .tobalt-message-box h1 {
            margin: 0 0 15px;
            font-size: 24px;
            color: #1d2327;
        }
        .tobalt-message-box p {
            margin: 0 0 25px;
            color: #646970;
            line-height: 1.6;
        }
        .tobalt-message-box a {
            color: #0073aa;
            text-decoration: none;
        }
        .tobalt-message-box a:hover {
            text-decoration: underline;
        }
    </style>
</head>
<body>
    <div class="tobalt-message-box">
        <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="currentColor" width="64" height="64">
            <path d="M12 2C6.48 2 2 6.48 2 12s4.48 10 10 10 10-4.48 10-10S17.52 2 12 2zm-2 15l-5-5 1.41-1.41L10 14.17l7.59-7.59L19 8l-9 9z"/>
        </svg>
        <h1><?php esc_html_e( 'Subscription Verified!', 'tobalt-city-alerts' ); ?></h1>
        <p><?php esc_html_e( 'Your email subscription has been confirmed. You will now receive alerts based on your preferences.', 'tobalt-city-alerts' ); ?></p>
        <a href="<?php echo esc_url( home_url( '/' ) ); ?>"><?php esc_html_e( '← Return to site', 'tobalt-city-alerts' ); ?></a>
    </div>
    <?php wp_footer(); ?>
</body>
</html>
