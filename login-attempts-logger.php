<?php
/**
 * Plugin Name: Login Attempts Logger
 * Description: Logs failed WordPress login attempts and displays them in the admin area.
 * Version: 1.0
 * Author: Your Name
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

global $lal_db_table;
$lal_db_table = $GLOBALS['wpdb']->prefix . 'login_attempts_logger';

/**
 * Create DB table on activation
 */
function lal_create_table() {
    global $wpdb, $lal_db_table;

    $charset_collate = $wpdb->get_charset_collate();

    $sql = "CREATE TABLE $lal_db_table (
        id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
        username VARCHAR(60) NOT NULL,
        ip_address VARCHAR(45) NOT NULL,
        attempted_at DATETIME NOT NULL,
        PRIMARY KEY (id)
    ) $charset_collate;";

    require_once ABSPATH . 'wp-admin/includes/upgrade.php';
    dbDelta( $sql );
}
register_activation_hook( __FILE__, 'lal_create_table' );

/**
 * Log failed login attempts
 */
function lal_log_failed_login( $username ) {
    global $wpdb, $lal_db_table;

    $ip = ! empty( $_SERVER['REMOTE_ADDR'] )
        ? sanitize_text_field( $_SERVER['REMOTE_ADDR'] )
        : 'unknown';

    $wpdb->insert(
        $lal_db_table,
        array(
            'username'     => sanitize_text_field( $username ),
            'ip_address'   => $ip,
            'attempted_at' => current_time( 'mysql' ),
        ),
        array( '%s', '%s', '%s' )
    );
}
add_action( 'wp_login_failed', 'lal_log_failed_login' );

/**
 * Admin menu
 */
function lal_register_admin_page() {
    add_menu_page(
        'Login Attempts',
        'Login Attempts',
        'manage_options',
        'login-attempts-logger',
        'lal_admin_page',
        'dashicons-shield',
        75
    );
}
add_action( 'admin_menu', 'lal_register_admin_page' );

/**
 * Admin page HTML
 */
function lal_admin_page() {
    if ( ! current_user_can( 'manage_options' ) ) {
        return;
    }

    global $wpdb, $lal_db_table;
    $attempts = $wpdb->get_results(
        "SELECT * FROM $lal_db_table ORDER BY attempted_at DESC LIMIT 50"
    );
    ?>
    <div class="wrap">
        <h1>Login Attempts</h1>
        <p>Recent failed login attempts.</p>

        <table class="widefat striped">
            <thead>
                <tr>
                    <th>Username</th>
                    <th>IP Address</th>
                    <th>Date</th>
                </tr>
            </thead>
            <tbody>
                <?php if ( ! empty( $attempts ) ) : ?>
                    <?php foreach ( $attempts as $attempt ) : ?>
                        <tr>
                            <td><?php echo esc_html( $attempt->username ); ?></td>
                            <td><?php echo esc_html( $attempt->ip_address ); ?></td>
                            <td><?php echo esc_html( $attempt->attempted_at ); ?></td>
                        </tr>
                    <?php endforeach; ?>
                <?php else : ?>
                    <tr>
                        <td colspan="3">No failed login attempts logged yet.</td>
                    </tr>
                <?php endif; ?>
            </tbody>
        </table>
    </div>
    <?php
}
