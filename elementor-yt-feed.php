<?php
/**
 * Plugin Name: Elementor YouTube Feed & Importer
 * Description: Fetches YouTube videos, auto-categorizes them based on title (Title | Category), and creates an Elementor Widget with a Hero + Carousel layout.
 * Version: 2.2.0
 * Author: D Kandekore
 * Text Domain: el-yt-feed
 */

if ( ! defined( 'ABSPATH' ) ) exit; // Exit if accessed directly

/**
 * Main Plugin Class
 */
class Elementor_YT_Feed_Plugin {

    public function __construct() {
        // 1. Register CPT and Taxonomy
        add_action( 'init', array( $this, 'register_cpt_and_tax' ) );

        // 2. Admin Settings & Fetching
        add_action( 'admin_menu', array( $this, 'add_admin_menu' ) );
        add_action( 'admin_post_syi_fetch_videos', array( $this, 'handle_fetch_videos' ) );

        // 3. Register Elementor Widget
        add_action( 'elementor/widgets/register', array( $this, 'register_elementor_widget' ) );

        // 4. Enqueue Styles/Scripts for the widget
        add_action( 'elementor/frontend/after_enqueue_styles', array( $this, 'enqueue_widget_assets' ) );

        // 5. Cron: Auto-sync videos
        add_action( 'syi_cron_fetch_videos', array( $this, 'cron_fetch_videos' ) );
        add_filter( 'cron_schedules', array( $this, 'add_cron_schedules' ) );
    }

    /**
     * Add custom cron intervals
     */
    public function add_cron_schedules( $schedules ) {
        $schedules['every_15_minutes'] = array(
            'interval' => 900,
            'display'  => __( 'Every 15 Minutes' ),
        );
        $schedules['every_30_minutes'] = array(
            'interval' => 1800,
            'display'  => __( 'Every 30 Minutes' ),
        );
        return $schedules;
    }

    /**
     * Schedule the cron event on plugin activation
     */
    public static function activate() {
        if ( ! wp_next_scheduled( 'syi_cron_fetch_videos' ) ) {
            wp_schedule_event( time(), 'every_15_minutes', 'syi_cron_fetch_videos' );
        }
    }

    /**
     * Clear the cron event on plugin deactivation
     */
    public static function deactivate() {
        wp_clear_scheduled_hook( 'syi_cron_fetch_videos' );
    }

    /**
     * Cron callback: Fetch videos without auth checks
     */
    public function cron_fetch_videos() {
        $api_key    = get_option( 'syi_api_key' );
        $channel_id = get_option( 'syi_channel_id' );

        if ( ! $api_key || ! $channel_id ) return;

        $this->fetch_and_import_videos( $api_key, $channel_id );
        update_option( 'syi_last_cron_sync', time() );
    }

    /**
     * Register Custom Post Type and Taxonomy
     */
    public function register_cpt_and_tax() {
        // Taxonomy: Video Categories
        register_taxonomy( 'video_category', 'yt_video', array(
            'labels' => array(
                'name' => 'Video Categories',
                'singular_name' => 'Video Category',
            ),
            'hierarchical' => true,
            'show_ui' => true,
            'show_admin_column' => true,
            'query_var' => true,
            'rewrite' => array( 'slug' => 'video-category' ),
        ));

        // CPT: YouTube Videos
        register_post_type( 'yt_video', array(
            'labels' => array(
                'name' => 'YouTube Videos',
                'singular_name' => 'Video',
            ),
            'public' => true,
            'has_archive' => true,
            'menu_icon' => 'dashicons-video-alt3',
            'supports' => array( 'title', 'editor', 'thumbnail', 'custom-fields' ),
        ));
    }

    /**
     * Admin Menu
     */
    public function add_admin_menu() {
        add_submenu_page( 'edit.php?post_type=yt_video', 'YT Feed Settings', 'Settings', 'manage_options', 'yt-feed-settings', array( $this, 'settings_page' ) );
    }

    public function settings_page() {
        if ( isset( $_POST['syi_save_settings'] ) ) {
            check_admin_referer( 'syi_settings_verify' );
            update_option( 'syi_api_key', sanitize_text_field( $_POST['syi_api_key'] ) );
            update_option( 'syi_channel_id', sanitize_text_field( $_POST['syi_channel_id'] ) );
            echo '<div class="notice notice-success"><p>Settings Saved.</p></div>';
        }
        $api_key = get_option( 'syi_api_key' );
        $channel_id = get_option( 'syi_channel_id' );
        ?>
        <div class="wrap">
            <h1>YouTube Feed Settings</h1>
            <form method="post" action="">
                <?php wp_nonce_field( 'syi_settings_verify' ); ?>
                <table class="form-table">
                    <tr><th>API Key</th><td><input type="text" name="syi_api_key" value="<?php echo esc_attr( $api_key ); ?>" class="regular-text"></td></tr>
                    <tr><th>Channel ID</th><td><input type="text" name="syi_channel_id" value="<?php echo esc_attr( $channel_id ); ?>" class="regular-text"></td></tr>
                </table>
                <input type="submit" name="syi_save_settings" class="button button-primary" value="Save Settings">
            </form>
            <hr>
            <h3>Sync Content</h3>
            <form method="post" action="<?php echo admin_url('admin-post.php'); ?>">
                <input type="hidden" name="action" value="syi_fetch_videos">
                <?php wp_nonce_field( 'syi_fetch_verify' ); ?>
                <input type="submit" class="button button-secondary" value="Fetch Latest Videos">
            </form>
            <hr>
            <h3>Auto-Sync Status</h3>
            <?php
            $next = wp_next_scheduled( 'syi_cron_fetch_videos' );
            if ( $next ) {
                echo '<p style="color:green;"><strong>&#10003; Auto-sync is active.</strong> Next run: ' . get_date_from_gmt( date( 'Y-m-d H:i:s', $next ), 'j M Y, H:i:s' ) . '</p>';
            } else {
                echo '<p style="color:red;"><strong>&#10007; Auto-sync is NOT scheduled.</strong> Try deactivating and reactivating the plugin.</p>';
            }
            $last = get_option( 'syi_last_cron_sync' );
            if ( $last ) {
                echo '<p>Last auto-sync: ' . get_date_from_gmt( date( 'Y-m-d H:i:s', $last ), 'j M Y, H:i:s' ) . '</p>';
            }
            ?>
        </div>
        <?php
    }

    /**
     * Manual Fetch (from admin form)
     */
    public function handle_fetch_videos() {
        if ( ! current_user_can( 'manage_options' ) ) wp_die( 'Unauthorized' );
        check_admin_referer( 'syi_fetch_verify' );

        $api_key    = get_option( 'syi_api_key' );
        $channel_id = get_option( 'syi_channel_id' );

        if ( ! $api_key || ! $channel_id ) wp_die( 'Missing API Key or Channel ID.' );

        $imported_count = $this->fetch_and_import_videos( $api_key, $channel_id );

        wp_redirect( admin_url( 'edit.php?post_type=yt_video&page=yt-feed-settings&imported=' . $imported_count ) );
        exit;
    }

    /**
     * Shared Fetch & Import Logic with Auto-Categorization
     * Used by both manual sync and cron.
     */
    public function fetch_and_import_videos( $api_key, $channel_id ) {
        $url = "https://www.googleapis.com/youtube/v3/search?order=date&part=snippet&channelId={$channel_id}&maxResults=15&type=video&key={$api_key}";
        $response = wp_remote_get( $url, array(
            'headers' => array(
                'Referer' => home_url(),
            ),
        ) );

        if ( is_wp_error( $response ) ) return 0;

        $body = wp_remote_retrieve_body( $response );
        $data = json_decode( $body );
        if ( ! isset( $data->items ) ) return 0;

        require_once( ABSPATH . 'wp-admin/includes/media.php' );
        require_once( ABSPATH . 'wp-admin/includes/file.php' );
        require_once( ABSPATH . 'wp-admin/includes/image.php' );

        $imported_count = 0;

        foreach ( $data->items as $item ) {
            $video_id = $item->id->videoId;

            // Check existence
            $existing = new WP_Query( array(
                'post_type'      => 'yt_video',
                'meta_key'       => 'syi_video_id',
                'meta_value'     => $video_id,
                'posts_per_page' => 1
            ));

            if ( ! $existing->have_posts() ) {
                $full_title = sanitize_text_field( $item->snippet->title );

                // --- Logic: Split Category from Title (supports | or @ as delimiter) ---
                $cat_name = 'Uncategorised';
                if ( strpos( $full_title, '|' ) !== false ) {
                    $title_parts = explode( '|', $full_title );
                    $clean_title = trim( $title_parts[0] );
                    $cat_name = trim( end( $title_parts ) );
                } elseif ( strpos( $full_title, '@' ) !== false ) {
                    $title_parts = explode( '@', $full_title );
                    $clean_title = trim( $title_parts[0] );
                    $cat_name = trim( end( $title_parts ) );
                } else {
                    $clean_title = $full_title;
                }

                // Prepare Content
                $description = sanitize_textarea_field( $item->snippet->description );
                $date = date( 'Y-m-d H:i:s', strtotime( $item->snippet->publishedAt ) );
                $embed_code = "\n<figure class=\"wp-block-embed is-type-video is-provider-youtube wp-block-embed-youtube wp-embed-aspect-16-9 wp-has-aspect-ratio\"><div class=\"wp-block-embed__wrapper\">\nhttps://www.youtube.com/watch?v={$video_id}\n</div></figure>";

                // Insert Post
                $post_id = wp_insert_post( array(
                    'post_title'    => $clean_title,
                    'post_content'  => $description . "\n\n" . $embed_code,
                    'post_status'   => 'publish',
                    'post_type'     => 'yt_video',
                    'post_date'     => $date,
                    'meta_input'    => array( 'syi_video_id' => $video_id )
                ));

                if ( $post_id ) {
                    // Set Category
                    $term = term_exists( $cat_name, 'video_category' );
                    if ( ! $term ) {
                        $term = wp_insert_term( $cat_name, 'video_category' );
                    }
                    if ( ! is_wp_error( $term ) ) {
                        $term_id = is_array( $term ) ? $term['term_id'] : $term;
                        wp_set_object_terms( $post_id, (int)$term_id, 'video_category' );
                    }

                    // Download Thumbnail
                    $thumb_url = $item->snippet->thumbnails->high->url;
                    $image_id = media_sideload_image( $thumb_url, $post_id, $clean_title, 'id' );
                    if ( ! is_wp_error( $image_id ) ) set_post_thumbnail( $post_id, $image_id );

                    $imported_count++;
                }
            }
        }

        return $imported_count;
    }

    /**
     * Register Elementor Widget
     */
    public function register_elementor_widget( $widgets_manager ) {
        require_once( __DIR__ . '/class-elementor-yt-widget.php' );
        $widgets_manager->register( new \Elementor_YT_Feed_Widget() );
    }

    /**
     * Enqueue CSS for the Widget
     */
    public function enqueue_widget_assets() {
        wp_enqueue_style( 'yt-feed-css', plugin_dir_url( __FILE__ ) . 'yt-feed.css' );
    }
}

// Activation & Deactivation hooks
register_activation_hook( __FILE__, array( 'Elementor_YT_Feed_Plugin', 'activate' ) );
register_deactivation_hook( __FILE__, array( 'Elementor_YT_Feed_Plugin', 'deactivate' ) );

// Initialize
new Elementor_YT_Feed_Plugin();