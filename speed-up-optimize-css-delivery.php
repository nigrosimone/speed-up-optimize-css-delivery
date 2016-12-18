<?php
/*
 Plugin Name: Speed Up - Optimize CSS Delivery
 Plugin URI: http://wordpress.org/plugins/speed-up-optimize-css-delivery/
 Description: This plugin load the stylesheets asynchronously and improve page load times.
 Version: 1.0.1
 Author: Simone Nigro
 Author URI: https://profiles.wordpress.org/nigrosimone
 License: GPLv2 or later
 License URI: http://www.gnu.org/licenses/gpl-2.0.html
*/

if ( !defined('ABSPATH') ) exit;

class SpeedUp_OptimizeCSSDelivery {
    
    const HANDLE  = 'speed-up-optimize-css-delivery';
    
    // @see /wp-content/plugins/speed-up-optimize-css-delivery/js/loadCSS.js for unminified version
    const LOADCSS = '/* loadCSS: load a CSS file asynchronously. [c]2016 @scottjehl, Filament Group, Inc. Licensed MIT  CSS rel=preload polyfill. Depends on loadCSS function. [c]2016 @scottjehl, Filament Group, Inc. Licensed MIT  */(function(a){var b=function(b,h,n){function d(a){if(f.body)return a();setTimeout(function(){d(a)})}function k(){c.addEventListener&&c.removeEventListener("load",k);c.media=n||"all"}var f=a.document,c=f.createElement("link"),g;if(h)g=h;else{var e=(f.body||f.getElementsByTagName("head")[0]).childNodes;g=e[e.length-1]}var m=f.styleSheets;c.rel="stylesheet";c.href=b;c.media="only x";d(function(){g.parentNode.insertBefore(c,h?g:g.nextSibling)});var l=function(a){for(var b=c.href,d=m.length;d--;)if(m[d].href===b)return a();setTimeout(function(){l(a)})};c.addEventListener&&c.addEventListener("load",k);c.onloadcssdefined=l;l(k);return c};"undefined"!==typeof exports?exports.loadCSS=b:a.loadCSS=b})("undefined"!==typeof global?global:this);(function(a){if(a.loadCSS){var b=loadCSS.relpreload={};b.support=function(){try{return a.document.createElement("link").relList.supports("preload")}catch(h){return!1}};b.poly=function(){for(var b=a.document.getElementsByTagName("link"),e=0;e<b.length;e++){var d=b[e];"preload"===d.rel&&"style"===d.getAttribute("as")&&(a.loadCSS(d.href,d),d.rel=null)}};if(!b.support()){b.poly();var e=a.setInterval(b.poly,300);a.addEventListener&&a.addEventListener("load",function(){a.clearInterval(e)});a.attachEvent&&a.attachEvent("onload",function(){a.clearInterval(e)})}}})(this);';
    
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
            add_filter('style_loader_tag', array($this, 'style_loader_tag'), 9999, 3);
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
        
        return '<link id="'.$handle.'" rel="preload" href="'.$href.'" as="style" media="'.$media.'" onload="this.rel=\'stylesheet\'" type="text/css"><noscript><link id="'.$handle.'" rel="stylesheet" href="'.$href.'" media="'.$media.'" type="text/css"></noscript>'."\n";
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