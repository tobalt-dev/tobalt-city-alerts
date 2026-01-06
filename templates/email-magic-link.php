<?php
/**
 * Magic link email template.
 *
 * Author: Tobalt — https://tobalt.lt
 *
 * @var string $submit_url  The submission URL with token
 * @var int    $expiry_min  Token expiry in minutes
 */

defined( 'ABSPATH' ) || exit;
?>
<!DOCTYPE html>
<html>
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
</head>
<body style="margin:0;padding:0;font-family:-apple-system,BlinkMacSystemFont,'Segoe UI',Roboto,Oxygen-Sans,Ubuntu,Cantarell,'Helvetica Neue',sans-serif;background:#f0f0f1;">
    <table role="presentation" style="width:100%;border-collapse:collapse;">
        <tr>
            <td align="center" style="padding:40px 20px;">
                <table role="presentation" style="width:100%;max-width:500px;border-collapse:collapse;background:#fff;border-radius:8px;box-shadow:0 2px 4px rgba(0,0,0,0.1);">
                    <tr>
                        <td style="padding:30px;text-align:center;">
                            <h1 style="margin:0 0 20px;color:#1d2327;font-size:24px;">
                                <?php esc_html_e( 'Your Alert Submission Link', 'tobalt-city-alerts' ); ?>
                            </h1>

                            <p style="margin:0 0 25px;color:#646970;font-size:15px;line-height:1.6;">
                                <?php esc_html_e( 'Click the button below to submit a new alert. This link is valid for one use only.', 'tobalt-city-alerts' ); ?>
                            </p>

                            <a href="<?php echo esc_url( $submit_url ); ?>" style="display:inline-block;padding:14px 28px;background:#0073aa;color:#fff;text-decoration:none;border-radius:4px;font-size:16px;font-weight:600;">
                                <?php esc_html_e( 'Submit Alert', 'tobalt-city-alerts' ); ?>
                            </a>

                            <p style="margin:25px 0 0;color:#a7aaad;font-size:13px;">
                                <?php
                                printf(
                                    /* translators: %d: minutes */
                                    esc_html__( 'This link expires in %d minutes.', 'tobalt-city-alerts' ),
                                    $expiry_min
                                );
                                ?>
                            </p>
                        </td>
                    </tr>
                    <tr>
                        <td style="padding:20px 30px;background:#f9f9f9;border-top:1px solid #eee;border-radius:0 0 8px 8px;">
                            <p style="margin:0;color:#a7aaad;font-size:12px;text-align:center;">
                                <?php esc_html_e( "If you didn't request this link, you can safely ignore this email.", 'tobalt-city-alerts' ); ?>
                            </p>
                        </td>
                    </tr>
                </table>

                <p style="margin:20px 0 0;color:#a7aaad;font-size:12px;">
                    <?php echo esc_html( get_bloginfo( 'name' ) ); ?>
                </p>
            </td>
        </tr>
    </table>
</body>
</html>
