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
        
        add_action('init', array($this, 'register_global_assets'));
        add_action('admin_init', array($this, 'register_admin_assets'));
        if(!is_admin()) add_action('init', array($this, 'register_frontend_assets'));
        add_action('after_setup_theme', array($this, 'check_theme_support'));
        add_filter('gettext', array($this, 'replace_core_text'));
        add_filter('ngettext', array($this, 'replace_core_text'));
        
        add_action('admin_menu', array($this, 'register_meta_box'));
        add_action('save_post', array($this, 'save_meta'));
        add_action('wp_ajax_get_sermon', array($this, 'get_sermon'));
        add_action('wp_ajax_nopriv_get_sermon', array($this, 'get_sermon'));
    }
    
    function register_global_assets(){
        
        $sermon_labels = array(
            'name' => _x('Sermons', 'post type general name'),
            'singular_name' => _x('Sermon', 'post type singular name'),
            'add_new' => _x('Add New Sermon', 'sermon'),
            'add_new_item' => __('Add New Sermon'),
            'edit_item' => __('Edit Sermon'),
            'new_item' => __('New Sermon'),
            'all_items' => __('All Sermons'),
            'view_item' => __('View Sermon'),
            'search_items' => __('Search Sermons'),
            'not_found' =>  __('No Sermons found'),
            'not_found_in_trash' => __('No Sermons found in Trash'), 
            'parent_item_colon' => '',
            'menu_name' => 'Sermons'
        );
        
        $sermon_args = array(
            'labels' => $sermon_labels,
            'public' => true,
            'publicly_queryable' => true,
            'show_ui' => true, 
            'show_in_menu' => true, 
            'query_var' => true,
            'rewrite' => true,
            'capability_type' => 'post',
            'has_archive' => true, 
            'hierarchical' => false,
            'menu_position' => null,
            'supports' => array('title', 'thumbnail', 'editor')
        ); 
        register_post_type('sermon', $sermon_args);
        
        $series_labels = array(
            'name' => _x('Series', 'taxonomy general name'),
            'singular_name' => _x('Series', 'taxonomy singular name'),
            'search_items' =>  __('Search Series'),
            'all_items' => __('All Series'),
            'parent_item' => __('Parent Series'),
            'parent_item_colon' => __('Parent Series:'),
            'edit_item' => __('Edit Series'), 
            'update_item' => __('Update Series'),
            'add_new_item' => __('Add New Series'),
            'new_item_name' => __('New Series Name'),
            'menu_name' => __('Series'),
        ); 	
        
        register_taxonomy('series', array('sermon'), array(
            'hierarchical' => true,
            'labels' => $series_labels,
            'show_ui' => true,
            'query_var' => true,
            'rewrite' => array('slug' => 'series'),
        ));
        
        $speakers_labels = array(
            'name' => _x('Speakers', 'taxonomy general name'),
            'singular_name' => _x('Speakers', 'taxonomy singular name'),
            'search_items' =>  __('Search Speakers'),
            'all_items' => __('All Speakers'),
            'parent_item' => __('Parent Speakers'),
            'parent_item_colon' => __('Parent Speakers:'),
            'edit_item' => __('Edit Speakers'), 
            'update_item' => __('Update Speakers'),
            'add_new_item' => __('Add New Speakers'),
            'new_item_name' => __('New Speakers Name'),
            'menu_name' => __('Speakers'),
        ); 	
        
        register_taxonomy('speakers', array('sermon'), array(
            'hierarchical' => true,
            'labels' => $speakers_labels,
            'show_ui' => true,
            'query_var' => true,
            'rewrite' => array('slug' => 'speakers'),
        ));
        
        $topics_labels = array(
            'name' => _x( 'Topics', 'taxonomy general name' ),
            'singular_name' => _x( 'Topic', 'taxonomy singular name' ),
            'search_items' =>  __( 'Search Topics' ),
            'popular_items' => __( 'Popular Topics' ),
            'all_items' => __( 'All Topics' ),
            'parent_item' => null,
            'parent_item_colon' => null,
            'edit_item' => __( 'Edit Topic' ), 
            'update_item' => __( 'Update Topic' ),
            'add_new_item' => __( 'Add New Topic' ),
            'new_item_name' => __( 'New Topic Name' ),
            'separate_items_with_commas' => __( 'Separate sermon topics with commas' ),
            'add_or_remove_items' => __( 'Add or remove sermon topics' ),
            'choose_from_most_used' => __( 'Choose from the most used sermon topics' ),
            'menu_name' => __( 'Topics' ),
        ); 
        
        register_taxonomy('topics', array('sermon'), array(
            'hierarchical' => false,
            'labels' => $topics_labels,
            'show_ui' => true,
            'query_var' => true,
            'rewrite' => array('slug' => 'topics'),
        ));
        
        add_image_size('sermon', 720, 480, true);

        $this->nonce = wp_create_nonce('churchfolio');
    }
    
    function register_admin_assets(){
        
        wp_enqueue_style('church-folio-admin-css', $this->plugin_url . '/css/admin-core.css');
        wp_enqueue_script('church-folio-admin-js', $this->plugin_url . '/js/admin-functions.js', array('jquery'));
        
    }
    
    function register_frontend_assets(){
        
        wp_enqueue_style('church-folio-css', $this->plugin_url . '/css/core.css');
        wp_enqueue_script('church-folio-js', $this->plugin_url . '/js/functions.js', array('jquery'));
        
    }
    
    function check_theme_support(){
        
        if(!current_theme_supports('post-thumbnails'))
            add_theme_support('post-thumbnails');
        
    }
    
    function replace_core_text($text){
        
        global $post;
        if('sermon' === $post->post_type)
            $text = str_ireplace('Featured Image', 'Sermon Graphic', $text);
            
        return $text;
    }
    
    
    function register_meta_box(){
        
        add_meta_box('church-folio', __('Sermon Options', 'churchfolio'), array($this, 'render_meta_box'), 'sermon', 'normal', 'high');
        
    }
    
    
    function render_meta_box(){
        
        global $post;
        
        
        $meta = get_post_meta($post->ID, '_sermon', true);
        
        $options = array(
            'description' => array(
                'name' => __('Description', 'churchfolio'),
                'description' => __('Enter a brief description of the sermon', 'churchfolio'),
                'form_type' => 'textarea'
            ),
            'scripture' => array(
                'name' => __('Scripture', 'churchfolio'),
                'description' => __('Enter the sermon Scripture reference', 'churchfolio'),
                'form_type' => 'text'
            ),
            'audio' => array(
                'name' => __('Audio URL', 'churchfolio'),
                'description' => __('Upload/insert MP3 audio URL', 'churchfolio'),
                'form_type' => 'text_upload',
                'upload_type' => 'audio',
                'upload_text' => __('Upload MP3', 'churchfolio')
            ),
            'video' => array(
                'name' => __('Video URL', 'churchfolio'),
                'description' => __('Enter a Vimeo or Youtube URL', 'churchfolio'),
                'form_type' => 'text'
            ),
            'download' => array(
                'name' => __('Download URL', 'churchfolio'),
                'description' => __('Upload/insert a URL for the downloadable sermon file', 'churchfolio'),
                'form_type' => 'text_upload'
            ),
            'purchase' => array(
                'name' => __('Purchase URL', 'churchfolio'),
                'description' => __('Enter the URL where a user can purchase this sermon', 'churchfolio'),
                'form_type' => 'text'
            )
        );
        
        $count = count($options);
        
        echo "<div id='churchfolio'>";
        
        foreach($options as $id => $option){
            
            $count--;
            
            if($count === 0) $option_class = 'last';
            
            echo "<div class='churchfolio-option $option_class'>";
            echo "<label for='$id'>{$option['name']}</label>";
            
            if('text' === $option['form_type'])
                echo "<input type='text' id='$id' name='sermon[$id]' value='{$meta[$id]}' />";
            
            elseif('text_upload' === $option['form_type']){
                echo "<input type='text' id='$id' name='sermon[$id]' value='{$meta[$id]}' />";
                
                $button_text = $option['upload_text'] ? $option['upload_text'] : __('Upload', 'churchfolio');
                echo "<button type='button' id='$id' value='{$option['upload_type']}'>$button_text</button>";
                
                echo "
                    <script type='text/javascript'>
                        jQuery(document).ready(function($){
    
                            $('#churchfolio button#$id').click(function() {
                                formfield = '$id';
                                console.log(formfield);
                                tb_show('', 'media-upload.php?&amp;TB_iframe=true');
                                return false;
                            });";
                            
                            if('audio' === $option['upload_type']){
                                echo "
                                    window.send_to_editor = function(html) {
                                        imgURL = jQuery('img',html).attr('src');
                                        $('#churchfolio #$id').val(imageURL);
                                        tb_remove();
                                    }
                                ";
                            }
                            
                        echo "});
                    </script>
                ";
            }
            
            elseif('textarea' === $option['form_type'])
                echo "<textarea id='$id' name='sermon[$id]' rows='10'>{$meta[$id]}</textarea>";
            
            if($option['description'])
                echo "<kbd>{$option['description']}</kbd>";
            
            echo "<br class='clear'>";
            echo "</div>";
        }
        
        echo "</div>";
        
    }
    
    function save_meta(){
        
        if(isset($_REQUEST['sermon'])){
            update_post_meta($_REQUEST['post_ID'], '_sermon', $_REQUEST['sermon']);
        }
        
    }
    
    
    function set_theme($style = 'default'){
        $this->theme = $theme;
    }
    
    function get_sermon($sermon_id = null){
        
        if(!$sermon_id && $_POST['sermon_id']){
            $sermon_id = $_POST['sermon_id'];
        }
        elseif(!$sermon_id){
            global $post;
            $sermon_id = $post->ID;
        }
        else{ return; }
        
        $sermon_entry = get_post($sermon_id);
        $sermon_meta = get_post_meta($sermon_id, 'sermon');
        
        $sermon = array(
            'title' => $sermon_entry->post_title,
            'description' => $sermon_entry->post_content,
            'thumb' => get_the_post_thumbnail($sermon_id, 'sermon'),
            'speaker' => $sermon_meta['speaker'],
            'audio' => $sermon_meta['audio'],
            'video' => $sermon_meta['video'],
            'download' => $sermon_meta['download'],
            'purchase' => $sermon_meta['purchase'],
            'scripture' => $sermon_meta['scripture'],
        );
        
        $sermon = json_encode($sermon);
        
        return $sermon;
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

churchfolio();