<?php
/*
 Plugin Name: Speed Up - Optimize CSS Delivery
 Plugin URI: http://wordpress.org/plugins/speed-up-optimize-css-delivery/
 Description: This plugin load the stylesheets asynchronously and improve page load times.
 Version: 1.0.3
 Author: Simone Nigro
 Author URI: https://profiles.wordpress.org/nigrosimone
 License: GPLv2 or later
 License URI: http://www.gnu.org/licenses/gpl-2.0.html
*/

if ( !defined('ABSPATH') ) exit;

class SpeedUp_OptimizeCSSDelivery {
    
    const HANDLE  = 'speed-up-optimize-css-delivery';
    
    // @see /wp-content/plugins/speed-up-optimize-css-delivery/js/loadCSS.js for unminified version
    const LOADCSS = '/* loadCSS. [c]2018 Filament Group, Inc. MIT License */ !function(e){"use strict";var t=function(t,n,o){var a,l=e.document,r=l.createElement("link");if(n)a=n;else{var d=(l.body||l.getElementsByTagName("head")[0]).childNodes;a=d[d.length-1]}var i=l.styleSheets;r.rel="stylesheet",r.href=t,r.media="only x",function e(t){if(l.body)return t();setTimeout(function(){e(t)})}(function(){a.parentNode.insertBefore(r,n?a:a.nextSibling)});var s=function(e){for(var t=r.href,n=i.length;n--;)if(i[n].href===t)return e();setTimeout(function(){s(e)})};function u(){r.addEventListener&&r.removeEventListener("load",u),r.media=o||"all"}return r.addEventListener&&r.addEventListener("load",u),r.onloadcssdefined=s,s(u),r};"undefined"!=typeof exports?exports.loadCSS=t:e.loadCSS=t}("undefined"!=typeof global?global:this),function(e){"use strict";e.loadCSS||(e.loadCSS=function(){});var t=loadCSS.relpreload={};if(t.support=function(){var t;try{t=e.document.createElement("link").relList.supports("preload")}catch(e){t=!1}return function(){return t}}(),t.bindMediaToggle=function(e){var t=e.media||"all";function n(){e.media=t}e.addEventListener?e.addEventListener("load",n):e.attachEvent&&e.attachEvent("onload",n),setTimeout(function(){e.rel="stylesheet",e.media="only x"}),setTimeout(n,3e3)},t.poly=function(){if(!t.support())for(var n=e.document.getElementsByTagName("link"),o=0;o<n.length;o++){var a=n[o];"preload"!==a.rel||"style"!==a.getAttribute("as")||a.getAttribute("data-loadcss")||(a.setAttribute("data-loadcss",!0),t.bindMediaToggle(a))}},!t.support()){t.poly();var n=e.setInterval(t.poly,500);e.addEventListener?e.addEventListener("load",function(){t.poly(),e.clearInterval(n)}):e.attachEvent&&e.attachEvent("onload",function(){t.poly(),e.clearInterval(n)})}"undefined"!=typeof exports?exports.loadCSS=loadCSS:e.loadCSS=loadCSS}("undefined"!=typeof global?global:this);';
    
    /**
     * Instance of the object.
     *
     * @since  1.0.0
     * @static
     * @access public
     * @var null|object
     */
    public static $instance = null;
    
    /**
     * Access the single instance of this class.
     *
     * @since  1.0.0
     * @return SpeedUp_OptimizeCSSDelivery
     */
    public static function get_instance() {
        if ( null === self::$instance ) {
            self::$instance = new self();
        }
        return self::$instance;
    }
    
    /**
     * Constructor
     *
     * @since  1.0.0
     * @return SpeedUp_OptimizeCSSDelivery
     */
    private function __construct(){

        if( !is_admin() ){

        	add_filter('style_loader_tag', array($this, 'style_loader_tag'), PHP_INT_MAX, 3);
            add_action('wp_head', array($this, 'print_inline_script'));
        }
    }
    
    /**
     * Wordpress style loader tag.
     * 
     * @since 1.0.0
     * @param string $html
     * @param string $handle
     * @param string $href
     * @return string
     */
    public function style_loader_tag($html, $handle, $href){
    	
        // check if current handle is excluded
        if( apply_filters(self::HANDLE, $handle) === true ){
            return $html;
        }
        
        // default media-attribute is "all"
        $media = 'all';
        
        // try to catch media-attribute in the html tag
        if( preg_match('/media=\'(.*)\'/', $html, $match) ){
        
            // extract media-attribute
            if( isset($match[1]) && !empty($match[1]) ){
                $media = $match[1];
            }
        }
        
        return '<link id="'.$handle.'" rel="preload" href="'.$href.'" as="style" media="'.$media.'" onload="this.onload=null;this.rel=\'stylesheet\'" type="text/css"><noscript><link id="'.$handle.'" rel="stylesheet" href="'.$href.'" media="'.$media.'" type="text/css"></noscript>'."\n";
    }
    
    /**
     * Print inline loadCSS script.
     * 
     * @since 1.0.0
     * @return void
     */
    public function print_inline_script(){
        echo '<script id="'.self::HANDLE.'" type="text/javascript">'.self::LOADCSS.'</script>';
    }
}

// Init
SpeedUp_OptimizeCSSDelivery::get_instance();