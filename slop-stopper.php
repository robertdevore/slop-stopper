<?php

/**
 * The plugin bootstrap file
 *
 * @link              https://robertdevore.com
 * @since             1.0.0
 * @package           Stats_For_WordPress
 *
 * @wordpress-plugin
 *
 * Plugin Name: Slop Stopper
 * Description: Checks content submitted by non-admin users for AI-generated phrases and flags posts for admin review.
 * Plugin URI:  https://github.com/robertdevore/slop-stopper/
 * Version:     1.0.0
 * Author:      Robert DeVore
 * Author URI:  https://robertdevore.com/
 * License:     GPL-2.0+
 * License URI: http://www.gnu.org/licenses/gpl-2.0.txt
 * Text Domain: slop-stopper
 * Domain Path: /languages
 * Update URI:  https://github.com/robertdevore/slop-stopper/
 */

defined( 'ABSPATH' ) || exit;

define( 'SLOP_STOPPER_PLUGIN_VERSION', '1.0.0' );
define( 'SLOP_STOPPER_PLUGIN_DIR', plugin_dir_url( __FILE__ ) );

require 'vendor/plugin-update-checker/plugin-update-checker.php';
use YahnisElsts\PluginUpdateChecker\v5\PucFactory;

$myUpdateChecker = PucFactory::buildUpdateChecker(
    'https://github.com/robertdevore/slop-stopper/',
    __FILE__,
    'slop-stopper'
);

// Set the branch that contains the stable release.
$myUpdateChecker->setBranch( 'main' );

// Check if Composer's autoloader is already registered globally.
if ( ! class_exists( 'RobertDevore\WPComCheck\WPComPluginHandler' ) ) {
    require_once __DIR__ . '/vendor/autoload.php';
}

use RobertDevore\WPComCheck\WPComPluginHandler;

new WPComPluginHandler( plugin_basename( __FILE__ ), 'https://robertdevore.com/why-this-plugin-doesnt-support-wordpress-com-hosting/' );

/**
 * Load plugin text domain for localization.
 *
 * @since  1.0.0
 * @return void
 */
function slop_stopper_load_textdomain() {
    load_plugin_textdomain( 'slop-stopper', false, dirname( plugin_basename( __FILE__ ) ) . '/languages' );
}
add_action( 'plugins_loaded', 'slop_stopper_load_textdomain' );

class Slop_Stopper {

    public function __construct() {
        add_action( 'admin_menu', [ $this, 'add_settings_page' ] );
        add_action( 'save_post', [ $this, 'check_content' ], 10, 3 );
        add_action( 'add_meta_boxes', [ $this, 'add_metabox' ] );
        add_action( 'save_post', [ $this, 'save_metabox' ] );
        add_action( 'admin_notices', [ $this, 'display_admin_notice' ] );
    }

    /**
     * Adds the Slop Stopper settings page to the WordPress admin menu.
     * 
     * @since  1.0.0
     * @return void
     */
    public function add_settings_page() {
        add_menu_page(
            esc_attr__( 'Slop Stopper', 'slop-stopper' ),
            esc_attr__( 'Slop Stopper', 'slop-stopper' ),
            'manage_options',
            'slop-stopper',
            [ $this, 'render_settings_page' ],
            'dashicons-dismiss',
            80
        );
    }

    /**
     * Renders the Slop Stopper settings page displaying flagged posts.
     * 
     * @since  1.0.0
     * @return void
     */
    public function render_settings_page() {
        ?>
        <div class="wrap">
            <h1>
                <strong><?php esc_html_e( 'Slop Stopper', 'slop-stopper' ); ?></strong> - <?php esc_html_e( 'Flagged Posts', 'slop-stopper' ); ?>
            </h1>
            <?php
            $args = [
                'post_type'      => 'any',
                'meta_key'       => '_slop_stopper_flagged',
                'meta_value'     => 1,
                'posts_per_page' => -1,
            ];
            $flagged_posts = new WP_Query( $args );

            if ( $flagged_posts->have_posts() ) {
                $html  = '<table class="widefat striped">';
                $html .= '<thead><tr>';
                $html .= '<th>' . esc_html__( 'Title', 'slop-stopper' ) . '</th>';
                $html .= '<th>' . esc_html__( 'Author', 'slop-stopper' ) . '</th>';
                $html .= '<th>' . esc_html__( 'Date', 'slop-stopper' ) . '</th>';
                $html .= '<th>' . esc_html__( 'Flagged Phrase', 'slop-stopper' ) . '</th>';
                $html .= '</tr></thead><tbody>';

                $phrases = $this->get_phrases();

                while ( $flagged_posts->have_posts() ) {
                    $flagged_posts->the_post();
                    $content       = get_the_content();
                    $found_phrases = [];
                
                    foreach ( $phrases as $phrase ) {
                        if ( stripos( $content, $phrase ) !== false ) {
                            $found_phrases[] = $phrase;
                        }
                    }
                
                    $html .= '<tr>';
                    $html .= '<td><a href="' . esc_url( get_edit_post_link() ) . '">' . esc_html( get_the_title() ) . '</a></td>';
                    $html .= '<td>' . esc_html( get_the_author() ) . '</td>';
                    $html .= '<td>' . esc_html( get_the_date() ) . '</td>';
                    $html .= '<td>' . esc_html( implode( ', ', $found_phrases ) ) . '</td>';
                    $html .= '</tr>';
                }                

                $html .= '</tbody></table>';

                echo wp_kses_post( $html );
                wp_reset_postdata();
            } else {
                echo '<p>' . esc_html__( 'No flagged posts found.', 'slop-stopper' ) . '</p>';
            }
            ?>
        </div>
        <?php
    }

    /**
     * Checks post content for flagged phrases and prevents non-admin users from publishing flagged content.
     *
     * @param int     $post_id The ID of the post being saved.
     * @param WP_Post $post    The post object.
     * @param bool    $update  Whether this is an existing post being updated or not.
     * 
     * @since  1.0.0
     * @return void
     */
    public function check_content( $post_id, $post, $update ) {
        if ( wp_is_post_revision( $post_id ) || wp_is_post_autosave( $post_id ) || ( defined( 'DOING_AUTOSAVE' ) && DOING_AUTOSAVE ) ) {
            return;
        }

        $user = wp_get_current_user();
        if ( in_array( 'administrator', (array) $user->roles ) ) {
            return;
        }

        $content = $post->post_content;
        $phrases = $this->get_phrases();

        foreach ( $phrases as $phrase ) {
            if ( stripos( $content, $phrase ) !== false ) {
                update_post_meta( $post_id, '_slop_stopper_flagged', 1 );
                wp_die( esc_html__( 'Your content contains flagged AI-generated phrases and requires admin review. Your post has been saved as a draft.', 'slop-stopper' ), esc_html__( 'Content Review Required', 'slop-stopper' ), [ 'back_link' => true ] );
            }
        }

        delete_post_meta( $post_id, '_slop_stopper_flagged' );
    }

    /**
     * Displays an admin notice if a post contains flagged content.
     * 
     * @since  1.0.0
     * @return void
     */
    public function display_admin_notice() {
        if ( isset( $_GET['slop_stopper_notice'] ) ) {
            echo '<div class="notice notice-error"><p>' . esc_html__( 'Your content contains flagged AI-generated phrases and requires admin review. Post status set to draft.', 'slop-stopper' ) . '</p></div>';
        }
    }

    /**
     * Retrieves the list of phrases that trigger content flagging.
     *
     * @since  1.0.0
     * @return array The list of flagged phrases.
     */
    public function get_phrases() {
        $phrases = [
            esc_attr__( 'digital landscape', 'slop-stopper' ),
            esc_attr__( 'transformative journey', 'slop-stopper' ),
            esc_attr__( 'innovative solutions', 'slop-stopper' ),
            esc_attr__( 'delve', 'slop-stopper' ),
            esc_attr__( 'tapestry', 'slop-stopper' ),
            esc_attr__( 'embark', 'slop-stopper' ),
        ];
        return apply_filters( 'slop_stopper_phrases', $phrases );
    }

    /**
     * Adds a meta box to posts for manually flagging AI-generated content.
     * 
     * @since  1.0.0
     * @return void
     */
    public function add_metabox() {
        add_meta_box(
            'slop_stopper_metabox',
            esc_html__( 'SLOP', 'slop-stopper' ),
            [ $this, 'render_metabox' ],
            'post',
            'side',
            'high'
        );
    }

    /**
     * Renders the meta box UI for manually flagging AI-generated content.
     *
     * @param WP_Post $post The post object.
     * 
     * @since  1.0.0
     * @return void
     */
    public function render_metabox( $post ) {
        wp_nonce_field( 'slop_stopper_nonce_action', 'slop_stopper_nonce' );
        $flagged = get_post_meta( $post->ID, '_slop_stopper_flagged', true );
        ?>
        <label>
            <input type="checkbox" name="slop_stopper_flagged" value="1" <?php checked( $flagged, 1 ); ?> />
            <?php esc_html_e( 'Flag content as AI-generated', 'slop-stopper' ); ?>
        </label>
        <?php
    }

    /**
     * Saves the meta box data for manually flagged AI-generated content.
     *
     * @param int $post_id The ID of the post being saved.
     * 
     * @since  1.0.0
     * @return void
     */
    public function save_metabox( $post_id ) {
        if ( ! isset( $_POST['slop_stopper_nonce'] ) || ! wp_verify_nonce( $_POST['slop_stopper_nonce'], 'slop_stopper_nonce_action' ) ) {
            return;
        }

        if ( isset( $_POST['slop_stopper_flagged'] ) ) {
            update_post_meta( $post_id, '_slop_stopper_flagged', 1 );
        } else {
            delete_post_meta( $post_id, '_slop_stopper_flagged' );
        }
    }
}

new Slop_Stopper();
