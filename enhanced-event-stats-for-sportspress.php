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
 * @package enhanced-event-stats-for-sportspress
 * @category Core
 * @author savvasha
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
//add_filter( 'sportspress_player_settings', 'fnlstats_add_settings' );

add_action( 'wp_enqueue_scripts', 'eessp_css_styles_scripts' );

/**
 * Add css styles and scripts to player pages only.
 *
 * @return void
 */
function eessp_css_styles_scripts() {
	wp_register_style( 'eessp-styles', EESSP_PLUGIN_URL . 'assets/css/eessp_style.css' );
	if ( get_post_type( get_the_ID() ) == 'sp_event' ) {
		wp_enqueue_style( 'eessp-styles' );
	}
}

/**
 * Add templates to event layout.
 *
 * @return array
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
 * Output eessp form guide template.
 *
 * @access public
 * @return void
 */
function eessp_output_form_guide() {
	// Get timelines format option
	$format = get_option( 'eessp_event_showing_format', 'list' );
	$event_id = get_the_ID();
	$teams = get_post_meta( $event_id,'sp_team' );
	$leagues = sp_get_leagues( $event_id );
	$seasons = sp_get_seasons( $event_id );
	$event_date = get_the_date( 'Y-m-d', $event_id );
	
	foreach ( $teams as $team ) {
		if ( 'list' === $format ) {
			sp_get_template( 'eessp-event-list.php', array(
				'title' 			 => get_the_title( $team ),
				'show_title' 		 => true,
				'team' 				 => $team,
				'leagues' 			 => $leagues,
				'seasons' 			 => $seasons,
				'status' 			 => 'publish',
				'current_event_date' => get_the_date( 'Y-m-d', $event_id ),
				'title_format' 		 => 'homeaway',
				'time_format' 		 => 'separate',
				'columns' 			 => apply_filters( 'eessp_form_guide_columns', array( 'event', 'time', 'results' ) ),
				'order' 			 => 'DESC',
				'hide_if_empty' 	 => true,
			), '', EESSP_PLUGIN_DIR . 'templates/' );
		}else{
			sp_get_template( 'event-blocks.php', array(
				'title' => get_the_title( $team ),
				'show_title' => true,
				'team' => $team,
				'league' => reset( $leagues ),
				'season' => reset( $seasons ),
				'status' => 'publish',
				'date_before' => $event_date, //Not working...
				'order' => 'DESC',
				'hide_if_empty' => true,
			) );
		}
	}
}
