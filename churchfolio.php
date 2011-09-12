<?php
/*
Plugin Name: Church Folio
Plugin URI: http://churchfolio.com
Description: Sermon management for Wordpress
Version: 0.01
Author: Brian Fegter
Author URI: http://coderrr.com
License: GPL2
*/

class ChurchFolio {

    protected $plugin_path;
    protected $plugin_url;
    
    function __construct(){

        $this->plugin_path = dirname(__FILE__);   
        $this->plugin_url = WP_PLUGIN_URL . '/churchfolio';
        
        add_action('init', array($this, 'register_assets'));
        add_action('admin_menu', array($this, 'register_meta_box'));
        add_action('wp_ajax_get_sermon', array($this, 'get_sermon'));
        add_action('wp_ajax_nopriv_get_sermon', array($this, 'get_sermon'));
    }
    
    function register_assets(){
        
        $this->nonce = wp_create_nonce('churchfolio');
        
        wp_enqueue_style('church-folio-css', $this->plugin_url . '/css/core.css');
        wp_enqueue_script('church-folio-js', $this->plugin_url . '/js/functions.js', array('jquery'));
    }
    
    function register_meta_box(){
        add_meta_box('church-folio', __('Church Folio Options', 'churchfolio'), array($this, 'render_meta_box'), 'sermon', 'normal', 'high');
    }
    
    
    function render_sermon_meta_box(){
        
    }
    
    function save_sermon_meta(){
        
    }
    
    
    function set_theme($style = 'default'){
        $this->theme = $theme;
    }
    
    function get_sermon($sermon_id = null){
        //check for sermon id from post, function, or 
    }
    
    function get_sermons_list($sermons_per_page = 25, $page = 0){
        
        $args = array(
            'post_type' => 'post',
            'posts_per_page' => $show_sermons,
            'paged' => $page
        );
        $args = apply_filters('get_sermons_list', $args);
        $sermons = query_posts($args);
        return $sermons;
    }

    function render_template($name){
        require_once($this->plugin_path . '/view/' . $name . '.php');
    }
    
    function render_player(){
        $this->render_template('header');
        $this->sermons = $this->get_sermons_list();
        $this->render_template('playlist');
        $this->render_template('footer');
    }
    
    function get_the_player_link(){
        //JS popup
    }
    
}

function churchfolio(){
    $churchfolio = new ChurchFolio();
    return $churchfolio;
}