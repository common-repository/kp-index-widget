<?php
/**
* Plugin Name: KP Index Widget
* Plugin URI: https://swissmediatools.ch/web/extensions-wordpress/kp-index-widget/
* Description: Affichage de l'indice KP dans un widget
* Version: 1.01
* Author: Swiss Media Tools
* Author URI: https://swissmediatools.ch/
* Text Domain: kp-index-smt
* Domain Path: /languages
*/

    if ( ! defined( 'ABSPATH' ) ) exit;

    //  define( 'WP_DEBUG', true );

    $kpindexsmt_pluginpath = plugin_dir_path( __FILE__ );
    $kpindexsmt_pluginwebpath = plugin_dir_url( __FILE__);
    $kpindexsmt_pluginalias = 'kpindexsmt';

    $warningcolors=array('#2a356a','#f5ea51','#f8c945','f39a38','#ed3323','#b92619');

    //  Scripts KPINDEX
    include_once($kpindexsmt_pluginpath.'kp-index-class.php');

    function kpindex_load_style() {
        global $kpindexsmt_pluginwebpath;
        wp_register_style( 'kpindex_style', $kpindexsmt_pluginwebpath . 'css/kp-index-smt.css' );
        wp_enqueue_style('kpindex_style');
    }

    add_action('wp_enqueue_scripts', 'kpindex_load_style');

    //  Widget
    function kpindexsmt_widget() {	register_widget( 'kpindexsmt_widget_class' ); }
    add_action( 'widgets_init', 'kpindexsmt_widget' );


?>
