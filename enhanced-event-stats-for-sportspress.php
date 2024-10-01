<?php
/**
 * Plugin Name: Enhanced Event Stats for SportsPress
 * Description: Show extra stats and performances of the participating teams of an event.
 * Version: 1.0
 * Author: Savvas
 * Author URI: https://profiles.wordpress.org/savvasha/
 * Requires at least: 5.3
 * Requires PHP: 7.4
 * License: GPL v2 or later
 * License URI: https://www.gnu.org/licenses/gpl.html
 *
 * @package EnhancedEventStatsForSportsPress
 */

// Exit if accessed directly.
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

// Define constants.
if ( ! defined( 'EESSP_PLUGIN_BASE' ) ) {
	define( 'EESSP_PLUGIN_BASE', plugin_basename( __FILE__ ) );
}
if ( ! defined( 'EESSP_PLUGIN_DIR' ) ) {
	define( 'EESSP_PLUGIN_DIR', plugin_dir_path( __FILE__ ) );
}
if ( ! defined( 'EESSP_PLUGIN_URL' ) ) {
	define( 'EESSP_PLUGIN_URL', plugin_dir_url( __FILE__ ) );
}

// Hooks.
add_filter( 'sportspress_event_templates', 'eessp_event_templates' );
add_action( 'wp_enqueue_scripts', 'eessp_css_front_styles_scripts' );
add_action( 'admin_enqueue_scripts', 'eessp_css_admin_styles_scripts' );
add_filter( 'sportspress_event_settings', 'eessp_add_settings' );

/**
 * Add CSS styles and scripts to frontend event pages only.
 *
 * @return void
 */
function eessp_css_front_styles_scripts() {
	if ( 'sp_event' === get_post_type( get_the_ID() ) ) {
		wp_register_style( 'eessp-styles', EESSP_PLUGIN_URL . 'assets/css/eessp_style.css' );
		wp_enqueue_style( 'eessp-styles' );
	}
}

/**
 * Add CSS styles and scripts to backend.
 *
 * @return void
 */
function eessp_css_admin_styles_scripts() {
	wp_enqueue_script( 'eessp-admin-js', EESSP_PLUGIN_URL . 'assets/js/eessp_admin.js', array( 'jquery' ), '1.0', true );
}

/**
 * Add settings for the Enhanced Event Stats.
 *
 * @param array $settings Existing SportsPress event settings.
 * @return array Modified event settings.
 */
function eessp_add_settings( $settings ) {
	// List of the available form guide template layouts
	$eessp_form_guide_templates = apply_filters( 'eessp_form_guide_templates', 
									array(
										'list' => 'List', 
										'blocks' => 'Blocks', 
										'premierleague' => 'Premier League (PRO only)', 
										'premierleaguealt' => 'Premier League Alternative (PRO only)', 
									) );
	
	// Merge SportsPress Event Settings with additional Event Summary options.
	$settings = array_merge(
		$settings,
		array(
			array(
				'title' => __( 'Enhanced Event Stats', 'eessp' ),
				'type'  => 'title',
				'id'    => 'eessp_enhanced_events_options',
			),
		),
		apply_filters(
			'eessp_enhanced_events_options',
			array(
				array(
					'title'   => __( 'Form Guide Layout', 'eessp' ),
					'id'      => 'eessp_form_guide_layout',
					'type'    => 'select',
					'options' => $eessp_form_guide_templates,
					'custom_attributes' => array(
						'premierleague' => 'disabled',    // Add custom attribute for the specific option
						'premierleaguealt' => 'disabled', // Add custom attribute for the specific option
					),
				),
				array(
					'title'             => esc_attr__( 'Limit', 'sportspress' ),
					'id'                => 'eessp_form_guide_rows',
					'class'             => 'small-text',
					'default'           => '3',
					'desc'              => esc_attr__( 'events', 'sportspress' ),
					'type'              => 'number',
					'custom_attributes' => array(
						'min'  => 1,
						'step' => 1,
					),
				),
			)
		),
		array(
			array(
				'type' => 'sectionend',
				'id'   => 'eessp_enhanced_events_options',
			),
		)
	);
	return $settings;
}

/**
 * Add templates to the event layout.
 *
 * @param array $templates Array of existing templates.
 * @return array Modified array of templates.
 */
function eessp_event_templates( $templates = array() ) {
	$templates['eessp_form_guide'] = array(
		'title'   => __( 'Form Guide', 'eessp' ),
		'option'  => 'sportspress_event_show_eessp_form_guide',
		'action'  => 'eessp_output_form_guide',
		'default' => 'yes',
	);

	return $templates;
}

/**
 * Output the EESSP form guide template.
 *
 * @return void
 */
function eessp_output_form_guide() {
	$format    = get_option( 'eessp_form_guide_layout', 'blocks' );
	$event_id  = get_the_ID();
	$teams     = get_post_meta( $event_id, 'sp_team' );
	$leagues   = sp_get_leagues( $event_id );
	$seasons   = sp_get_seasons( $event_id );
	$form_guides = array();

	foreach ( $teams as $team ) {
		ob_start();

		switch ( $format ) {
			case 'blocks':
				sp_get_template(
					'eessp-event-blocks.php',
					array(
						'title'             => get_the_title( $team ),
						'show_title'        => true,
						'team'              => $team,
						'leagues'           => $leagues,
						'seasons'           => $seasons,
						'status'            => 'publish',
						'current_event_date'=> get_the_date( 'Y-m-d', $event_id ),
						'order'             => 'DESC',
						'hide_if_empty'     => true,
					),
					'',
					EESSP_PLUGIN_DIR . 'templates/'
				);
				break;

			case 'list':
				sp_get_template(
					'eessp-event-list.php',
					array(
						'title'             => get_the_title( $team ),
						'show_title'        => true,
						'team'              => $team,
						'leagues'           => $leagues,
						'seasons'           => $seasons,
						'status'            => 'publish',
						'current_event_date'=> get_the_date( 'Y-m-d', $event_id ),
						'title_format'      => 'homeaway',
						'time_format'       => 'separate',
						'columns'           => apply_filters( 'eessp_form_guide_columns', array( 'event', 'time', 'results' ) ),
						'order'             => 'DESC',
						'hide_if_empty'     => true,
					),
					'',
					EESSP_PLUGIN_DIR . 'templates/'
				);
				break;

			default:
				do_action( 'eessp_form_guide_cases', $event_id, $team, $format );
				break;
		}

		$form_guides[] = ob_get_clean();
	}

	// Get default allowed HTML tags from wp_kses_post().
	$allowed_html = wp_kses_allowed_html( 'post' );

	// Add custom allowed tags and attributes.
	$allowed_html['a']['itemprop']    = true;
	$allowed_html['meta']['itemprop'] = true;
	$allowed_html['date']             = array();
	$allowed_html['td']['data-label'] = true;
	$allowed_html['td']['itemprop']   = true;
	$allowed_html['td']['itemscope']  = true;
	$allowed_html['td']['itemtype']   = true;
	$allowed_html['div']['itemprop']  = true;
	$allowed_html['div']['itemscope'] = true;
	$allowed_html['div']['itemtype']  = true;

	if ( 2 === count( $teams ) ) {
		echo '<div class="sp-widget-align-left">';
		echo wp_kses( $form_guides[0], $allowed_html );
		echo '</div>';

		echo '<div class="sp-widget-align-right">';
		echo wp_kses( $form_guides[1], $allowed_html );
		echo '</div>';
	} else {
		foreach ( $form_guides as $form_guide ) {
			echo wp_kses( $form_guide, $allowed_html );
		}
	}
}