<?php
/**
* @package CTARCPlugin
*/
/*
Plugin Name: CTA Banner
Plugin URI: https://github.com/lilitakahashi/ctarc-plugin
Description: Banner plugin
Version: 1.0.0
Author: Lili Takahashi
Author URI: http://lilitakahashi.com
License: GPLv2 or later
Text Domain: ctarc-plugin
*/

/*
This program is free software; you can redistribute it and/or
modify it under the terms of the GNU General Public License
as published by the Free Software Foundation; either version 2
of the License, or (at your option) any later version.

This program is distributed in the hope that it will be useful,
but WITHOUT ANY WARRANTY; without even the implied warranty of
MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
GNU General Public License for more details.

You should have received a copy of the GNU General Public License
along with this program; if not, write to the Free Software
Foundation, Inc., 51 Franklin Street, Fifth Floor, Boston, MA  02110-1301, USA.

Copyright 2005-2015 Automattic, Inc.
*/

// Make sure we don't expose any info if called directly
if ( !function_exists( 'add_action' ) ) {
	echo 'Hi there!  I\'m just a plugin, not much I can do when called directly.';
	exit;
}

/**
 * Post type
 */
function wpt_ctas_post_type() {
	$labels = array(
		'name'               => __( 'CTA\'s' ),
		'singular_name'      => __( 'CTA' ),
		'add_new'            => __( 'Adicionar novo CTA' ),
		'add_new_item'       => __( 'Adicionar novo CTA' ),
		'edit_item'          => __( 'Editar CTA' ),
		'new_item'           => __( 'Adicionar novo CTA' ),
		'view_item'          => __( 'Ver CTA' ),
		'search_items'       => __( 'Buscar CTA' ),
		'not_found'          => __( 'Nenhum CTA\'s encontrado' ),
		'not_found_in_trash' => __( 'Nenhum CTA\'s encontrado na lixeira' )
	);
	$supports = array(
		'title',
		'thumbnail',
		'revisions',
	);
	$args = array(
		'labels'               => $labels,
		'supports'             => $supports,
		'public'               => true,
		'capability_type'      => 'post',
		'rewrite'              => array( 'slug' => 'ctas' ),
		'has_archive'          => true,
		'menu_position'        => 30,
		'menu_icon'            => 'dashicons-admin-comments',
		'register_meta_box_cb' => 'wpt_add_cta_metaboxes',
	);
	register_post_type( 'ctas', $args );
}
add_action( 'init', 'wpt_ctas_post_type' );

/**
 * Meta box
 */
function wpt_add_cta_metaboxes() {
	add_meta_box(
		'wpt_ctas_link',
		'URL',
		'wpt_ctas_link',
		'ctas',
		'normal',
		'default'
	);
}

/**
 * HTML meta box
 */
function wpt_ctas_link() {
	global $post;
	// Nonce field to validate form request came from current site
	wp_nonce_field( basename( __FILE__ ), 'cta_fields' );
	// Get the link data if it's already been entered
	$link = get_post_meta( $post->ID, 'link', true );
	// Output the field
	echo '<input type="text" name="link" value="' . esc_textarea( $link )  . '" class="widefat"><p class="description">Link de para onde o CTA deve redirecionar o usuário. Informe a url completa, ex: https://blog.com.br/meu-ebook</p>';
}

/**
 * Save meta box
 */
function wpt_save_ctas_meta( $post_id, $post ) {
	// Return if the user doesn't have edit permissions.
	if ( ! current_user_can( 'edit_post', $post_id ) ) {
		return $post_id;
	}
	// Verify this came from the our screen and with proper authorization,
	// because save_post can be triggered at other times.
	if ( ! isset( $_POST['link'] ) || ! wp_verify_nonce( $_POST['cta_fields'], basename(__FILE__) ) ) {
		return $post_id;
	}
	// Now that we're authenticated, time to save the data.
	// This sanitizes the data from the field and saves it into an array $ctas_meta.
	$ctas_meta['link'] = esc_textarea( $_POST['link'] );
	// Cycle through the $ctas_meta array.
	// Note, in this example we just have one item, but this is helpful if you have multiple.
	foreach ( $ctas_meta as $key => $value ) :
		// Don't store custom data twice
		if ( 'revision' === $post->post_type ) {
			return;
		}
		if ( get_post_meta( $post_id, $key, false ) ) {
			// If the custom field already has a value, update it.
			update_post_meta( $post_id, $key, $value );
		} else {
			// If the custom field doesn't have a value, add it.
			add_post_meta( $post_id, $key, $value);
		}
		if ( ! $value ) {
			// Delete the meta key if there's no value
			delete_post_meta( $post_id, $key );
		}
	endforeach;
}
add_action( 'save_post', 'wpt_save_ctas_meta', 1, 2 );

/**
 * Shortcode HTML
 */
add_theme_support('post-thumbnails');
add_image_size('img_banner', 960, 300, True);

function cta_banner_function($atts) 
{
    global $post;

    $atts   = shortcode_atts( [
        'id'  => '',
	], $atts );
	
	$post_id = $atts['id'];

	$banner = wp_get_attachment_image_src( get_post_thumbnail_id( $post_id ), array(960, 300) );
	$url = get_post_meta( $post_id, 'link', TRUE );
	
    
    $content = '<div class="banner_wrapper">';
    $content .= '<a href="'. $url .'" target="_blank">';
    $content .= '<img src="' . $banner[0] . '" class="foo">';
    $content .= '</a>';
    $content .= '</div>';

	return $content;
}
add_shortcode( 'cta_banner', 'cta_banner_function' );


function rewrite_ctas_flush() {
    wpt_ctas_post_type();
    flush_rewrite_rules();
}
register_activation_hook( __FILE__, 'rewrite_ctas_flush' );

/**
 * Add custom column to Post listing page
 */
add_filter( 'manage_ctas_posts_columns', 'set_custom_edit_ctas_columns' );
function set_custom_edit_ctas_columns( $columns ) {
    
    $columns['cta_shortcode'] 	= __( 'Shortcode', 'ctarc-plugin' );	   

    //$columns = ctas_add_array( $columns, $new_columns, 1, true );

    return $columns;
}

/**
 * Add custom column data to Post listing page
 */
add_action( 'manage_ctas_posts_custom_column' , 'custom_ctas_column', 10, 2 );
function custom_ctas_column( $column, $post_id ) {

    //$prefix = CTAS_META_PREFIX; // Taking metabox prefix

    switch ($column) {
        case 'cta_shortcode':			
            $shortcode_string = '';
            $shortcode_string .= '[cta_banner id="'.$post_id.'"] ';				
            echo $shortcode_string;
            break;
        
    }
}

/**
 * Enqueue the style
 */
function banner_style() {
	wp_register_style('banner-style',  plugin_dir_url( __FILE__ ) .'css/banner.css');
    wp_enqueue_style('banner-style');
}
add_action( 'wp_enqueue_scripts', 'banner_style' );