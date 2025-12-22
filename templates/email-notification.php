<?php
/**
 * Alert notification email template.
 *
 * Author: Tobalt — https://tobalt.lt
 *
 * @var WP_Post $alert           The alert post
 * @var string  $severity        Alert severity
 * @var string  $starts_at       Start datetime
 * @var string  $ends_at         End datetime
 * @var array   $category_names  Category names
 * @var string  $unsubscribe_url Unsubscribe URL
 */

defined( 'ABSPATH' ) || exit;

$severity_colors = [
    'low'      => '#4caf50',
    'medium'   => '#ff9800',
    'high'     => '#f44336',
    'critical' => '#9c27b0',
];

$severity_labels = [
    'low'      => __( 'Low', 'tobalt-city-alerts' ),
    'medium'   => __( 'Medium', 'tobalt-city-alerts' ),
    'high'     => __( 'High', 'tobalt-city-alerts' ),
    'critical' => __( 'Critical', 'tobalt-city-alerts' ),
];

$severity_color = $severity_colors[ $severity ] ?? '#ff9800';
$severity_label = $severity_labels[ $severity ] ?? __( 'Medium', 'tobalt-city-alerts' );

$formatted_starts = $starts_at ? date_i18n( get_option( 'date_format' ) . ' ' . get_option( 'time_format' ), strtotime( $starts_at ) ) : '';
$formatted_ends   = $ends_at ? date_i18n( get_option( 'date_format' ) . ' ' . get_option( 'time_format' ), strtotime( $ends_at ) ) : '';
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
                <table role="presentation" style="width:100%;max-width:600px;border-collapse:collapse;background:#fff;border-radius:8px;box-shadow:0 2px 4px rgba(0,0,0,0.1);">
                    <!-- Header with severity -->
                    <tr>
                        <td style="padding:0;">
                            <div style="background:<?php echo esc_attr( $severity_color ); ?>;padding:15px 30px;border-radius:8px 8px 0 0;">
                                <span style="color:#fff;font-size:13px;font-weight:600;text-transform:uppercase;letter-spacing:0.5px;">
                                    <?php echo esc_html( $severity_label ); ?> <?php esc_html_e( 'Alert', 'tobalt-city-alerts' ); ?>
                                </span>
                            </div>
                        </td>
                    </tr>

                    <!-- Content -->
                    <tr>
                        <td style="padding:30px;">
                            <h1 style="margin:0 0 20px;color:#1d2327;font-size:22px;line-height:1.3;">
                                <?php echo esc_html( $alert->post_title ); ?>
                            </h1>

                            <div style="margin:0 0 25px;color:#50575e;font-size:15px;line-height:1.6;">
                                <?php echo wp_kses_post( wpautop( $alert->post_content ) ); ?>
                            </div>

                            <!-- Meta info -->
                            <table role="presentation" style="width:100%;border-collapse:collapse;margin-bottom:25px;">
                                <?php if ( $formatted_starts ) : ?>
                                <tr>
                                    <td style="padding:8px 0;color:#646970;font-size:14px;width:100px;vertical-align:top;">
                                        <?php esc_html_e( 'Starts:', 'tobalt-city-alerts' ); ?>
                                    </td>
                                    <td style="padding:8px 0;color:#1d2327;font-size:14px;">
                                        <?php echo esc_html( $formatted_starts ); ?>
                                    </td>
                                </tr>
                                <?php endif; ?>

                                <?php if ( $formatted_ends ) : ?>
                                <tr>
                                    <td style="padding:8px 0;color:#646970;font-size:14px;width:100px;vertical-align:top;">
                                        <?php esc_html_e( 'Ends:', 'tobalt-city-alerts' ); ?>
                                    </td>
                                    <td style="padding:8px 0;color:#1d2327;font-size:14px;">
                                        <?php echo esc_html( $formatted_ends ); ?>
                                    </td>
                                </tr>
                                <?php endif; ?>

                                <?php if ( ! empty( $category_names ) ) : ?>
                                <tr>
                                    <td style="padding:8px 0;color:#646970;font-size:14px;width:100px;vertical-align:top;">
                                        <?php esc_html_e( 'Category:', 'tobalt-city-alerts' ); ?>
                                    </td>
                                    <td style="padding:8px 0;color:#1d2327;font-size:14px;">
                                        <?php echo esc_html( implode( ', ', $category_names ) ); ?>
                                    </td>
                                </tr>
                                <?php endif; ?>
                            </table>

                            <!-- View more link -->
                            <a href="<?php echo esc_url( home_url( '/' ) ); ?>" style="display:inline-block;padding:12px 24px;background:#0073aa;color:#fff;text-decoration:none;border-radius:4px;font-size:14px;font-weight:600;">
                                <?php esc_html_e( 'View All Alerts', 'tobalt-city-alerts' ); ?>
                            </a>
                        </td>
                    </tr>

                    <!-- Footer -->
                    <tr>
                        <td style="padding:20px 30px;background:#f9f9f9;border-top:1px solid #eee;border-radius:0 0 8px 8px;">
                            <p style="margin:0;color:#a7aaad;font-size:12px;text-align:center;">
                                <?php esc_html_e( 'You received this email because you subscribed to alerts.', 'tobalt-city-alerts' ); ?>
                                <br>
                                <a href="<?php echo esc_url( $unsubscribe_url ); ?>" style="color:#a7aaad;">
                                    <?php esc_html_e( 'Unsubscribe', 'tobalt-city-alerts' ); ?>
                                </a>
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
