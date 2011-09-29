<?php

/*
Plugin Name: Menu Item Image Substitution
Plugin URI: http://www.josephburnett.com
Description: Replaces menu items with png images from the media library
Version: 1.0
Author: Joseph Burnett
Author URI: http://www.josephburnett.com
Liscense: whatever
*/

class MenuItemImageSubstitution
{
    // Singleton instance of the class
    private static $instance;

    // List of menu ids in the current menu
    private static $menu_ids;
            
    // Hash of media library png files by title
    private static $media_library_images;

    // Private constructor to prevent instantiation
    private function __construct() {

        // Initialize the private members
        self::$media_library_images = array();
        self::$menu_ids = array();

        // Find all png images in the library
        $png_images = get_posts( array(
            'post_type'        => 'attachment',
            'post_mime_type'   => 'image/png',
            'numberposts' => 1000,
        ));

        // Hash the image meta data by title
        foreach ($png_images as $image) {
            setup_postdata($image);
            $image_meta = wp_get_attachment_image_src($image->ID, 'full');
            if ($image_meta && preg_match( '/([\-\w]+)\.png$/', $image_meta[0])) {
                $key = $image->post_title;
                $image_meta_hash = array();
                $image_meta_hash['url'] = $image_meta[0];
                $image_meta_hash['width'] = $image_meta[1];
                $image_meta_hash['height'] = $image_meta[2];
                self::$media_library_images[$key] = $image_meta_hash;
            }
        }
    }

    // Initialize the class if necessary
    private static function init() {
        if (!isset(self::$instance)) {
            $c = __CLASS__;
            self::$instance = new $c;
        }
    }

    // Record a nav menu item id
    public static function nav_menu_item_id( $id, $item, $args ) {

        self::init();

        // Record the menu ids
        array_push(self::$menu_ids, $id);

        return $id;

    }

    // Generate css to do image substitution for the menu ids 
    // collected by nav_menu_id() which have corresponding
    // png images in the media library
    public static function wp_nav_menu( $nav_menu, $args ) {

        self::init();

        // Css for each image/menu-id pair
        $css = '<style>';
        foreach (self::$menu_ids as $id) {
            if (self::$media_library_images[$id]) {

                $png = self::$media_library_images[$id];
                $url = $png['url'];
                $width = $png['width'];
                $height = $png['height'];

                $css .= '#' . $id . ' a { ';
                $css .= 'display: block; ';
                $css .= 'padding: ' . $height . 'px 0 0 0; ';
                $css .= 'background-image: url("' . $url . '"); ';
                $css .= 'overflow: hidden; ';
                $css .= 'height: 0; ';
                $css .= 'width: ' . $width . 'px; } ';

                // Use an image with the suffix "_hover" for :hover
                if (self::$media_library_images[$id.'_hover']) {
                    $hover_image = self::$media_library_images[$id.'_hover'];
                    $css .= '#' . $id . ' a:hover { ';
                    $css .= 'background-image: url("' . $hover_image['url'] . '"); } ';
                }
            }
        }
        $css .= '</style>';

        // Append the css style at the end of the menu and start over
        $nav_menu .= $css;       
        self::$menu_ids = array();

        return $nav_menu;
    }
}

// Register the nav_menu_item_id and wp_nav_menu hooks
add_action('nav_menu_item_id', array('MenuItemImageSubstitution', 'nav_menu_item_id'));
add_action('wp_nav_menu', array('MenuItemImageSubstitution', 'wp_nav_menu')); 

?>
