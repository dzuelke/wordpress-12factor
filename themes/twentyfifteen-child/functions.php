<?php

function theme_enqueue_styles() {
	$parent_style_name = 'twentyfifteen';
	
	wp_enqueue_style($parent_style_name, get_template_directory_uri().'/style.css');
	wp_enqueue_style(
		'twentyfifteen-child',
		get_stylesheet_directory_uri() . '/style.css',
		array($parent_style_name)
	);
}

add_action( 'wp_enqueue_scripts', 'theme_enqueue_styles' );
