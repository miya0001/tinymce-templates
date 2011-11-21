<?php
/*
Plugin Name: TinyMCE Templates
Plugin URI: http://wpist.me/wp/tinymce-templates/
Description: TinyMCE Templates plugin will enable to use HTML template on WordPress Visual Editor.
Author: Takayuki Miyauchi
Version: 2.4.0
Author URI: http://wpist.me/
Domain Path: /languages
Text Domain: tinymce_templates
*/

/*
Copyright (c) 2010 Takayuki Miyauchi (THETA NETWORKS Co,.Ltd).

Permission is hereby granted, free of charge, to any person obtaining a copy
of this software and associated documentation files (the "Software"), to deal
in the Software without restriction, including without limitation the rights
to use, copy, modify, merge, publish, distribute, sublicense, and/or sell
copies of the Software, and to permit persons to whom the Software is
furnished to do so, subject to the following conditions:

The above copyright notice and this permission notice shall be included in
all copies or substantial portions of the Software.

THE SOFTWARE IS PROVIDED "AS IS", WITHOUT WARRANTY OF ANY KIND, EXPRESS OR
IMPLIED, INCLUDING BUT NOT LIMITED TO THE WARRANTIES OF MERCHANTABILITY,
FITNESS FOR A PARTICULAR PURPOSE AND NONINFRINGEMENT. IN NO EVENT SHALL THE
AUTHORS OR COPYRIGHT HOLDERS BE LIABLE FOR ANY CLAIM, DAMAGES OR OTHER
LIABILITY, WHETHER IN AN ACTION OF CONTRACT, TORT OR OTHERWISE, ARISING FROM,
OUT OF OR IN CONNECTION WITH THE SOFTWARE OR THE USE OR OTHER DEALINGS IN
THE SOFTWARE.
*/

require_once(dirname(__FILE__).'/includes/mceplugins.class.php');

new tinymceTemplates();

class tinymceTemplates {

private $db_version  = '2';
private $post_type   = 'tinymcetemplates';
private $meta_param  = '_tinymcetemplates-share';
private $table       = 'mce_template';
private $base_url;
private $translators = array(
    'Takayuki Miyauchi' => array(
        'lang' => 'Japanese',
        'url'  => 'http://wpist.me/',
    ),
    'Andrea Bersi' => array(
        'lang' => 'Italian',
        'url'  => 'http://www.andreabersi.com/',
    ),
    'Tobias Bergius' => array(
        'lang' => 'Swedish',
        'url'  => '',
    ),
    'Martin Lettner' => array(
        'lang' => 'German',
        'url'  => 'http://www.martinlettner.info/',
    ),
    'David Bravo' => array(
        'lang' => 'Spanish',
        'url'  => 'http://www.dimensionmultimedia.com/',
    ),
    'Frank Groeneveld' => array(
        'lang' => 'Dutch',
        'url'  => 'http://ivaldi.nl/',
    ),
    'HAROUY Jean-Michel' => array(
        'lang' => 'French',
        'url'  => 'http://www.laposte.net/',
    ),
);

function __construct()
{
    $this->base_url = WP_PLUGIN_URL.'/'.dirname(plugin_basename(__FILE__));
    register_activation_hook(__FILE__, array(&$this, 'activation'));
    add_action('plugins_loaded', array(&$this, 'plugins_loaded'));
    add_action('save_post', array(&$this, 'save_post'));
    add_filter('mce_css', array(&$this, 'mce_css'));
    add_action('admin_head', array(&$this, 'admin_head'));
    add_action('wp_ajax_tinymce_templates', array(&$this, 'wp_ajax'));
}

public function activation()
{
    if (get_option("tinymce_templates_db_version") == $this->db_version) {
        return;
    }

    global $wpdb;
    update_option("tinymce_templates_db_version", $this->db_version);
    $sql = $wpdb->prepare('show tables like %s', $wpdb->prefix.$this->table);
    if ($wpdb->get_var($sql)) {
        $sql = "select * from ".mysql_real_escape_string($wpdb->prefix.$this->table);
        $res = $wpdb->get_results($sql);
        foreach ($res as $tpl) {
            $post = array();
            $post['post_title']   = $tpl->name;
            $post['post_content'] = $tpl->html;
            $post['post_excerpt'] = $tpl->desc;
            $post['post_author']  = $tpl->author;
            $post['post_date']    = $tpl->modified;
            $post['post_type']    = $this->post_type;
            $post['post_status']    = 'publish';
            $id = wp_insert_post($post);
            if ($id) {
                update_post_meta($id, $this->meta_param, $tpl->share);
            }
        }
        $sql = 'drop table '.$wpdb->prefix.$this->table;
        $wpdb->query($sql);
    }
}

public function plugins_loaded()
{
    load_plugin_textdomain(
        'tinymce_templates',
        false,
        dirname(plugin_basename(__FILE__)).'/languages'
    );
    $this->addCustomPostType();
}

public function mce_css($css)
{
    $files   = preg_split("/,/", $css);
    $files[] = $this->base_url.'/editor.css';
    $files   = array_map('trim', $files);
    return join(",", $files);
}

public function admin_head(){
    $plugin = $this->base_url.'/mce_plugins/plugins/template/editor_plugin.js';

    $url    = admin_url('admin-ajax.php');
    $url    = add_query_arg('action', 'tinymce_templates', $url);
    $url    = add_query_arg('action', 'tinymce_templates', $url);
    $nonce  = wp_create_nonce("tinymce_templates");
    $url    = add_query_arg('nonce', $nonce, $url);

    $inits['template_external_list_url'] = $url;
    $inits['template_popup_width']       = 600;
    $inits['template_popup_height']      = 500;

    new tinymcePlugins(
        'template',
        $plugin,
        array(&$this, 'addButton'),
        $inits
    );

    if (get_post_type() === $this->post_type) {
        if (get_option("tinymce_templates_db_version") != $this->db_version) {
            $this->activation();
        }
        global $hook_suffix;
        if ($hook_suffix === 'post.php' || $hook_suffix === 'post-new.php') {
            remove_meta_box('slugdiv', $this->post_type, 'normal');
            if (get_option("tinymce_templates_db_version") != $this->db_version) {
                $this->activation();
            }
            echo '<style>#visibility{display:none;}</style>';
        } elseif ($hook_suffix === 'edit.php') {
            add_filter("display_post_states", array(&$this, "display_post_states"));
        }
    }
}

public function display_post_states($stat)
{
    global $post;
    $share = get_post_meta($post->ID, $this->meta_param, true);
    if ($share) {
        $stat[] = __('Shared', 'tinymce_templates');
    }
    return $stat;
}

public function save_post($id)
{
    if (defined('DOING_AUTOSAVE') && DOING_AUTOSAVE)
        return $id;

    if (isset($_POST['action']) && $_POST['action'] == 'inline-save')
        return $id;

    $p = get_post($id);
    if ($p->post_type === $this->post_type) {
        if (isset($_POST[$this->meta_param]) && $_POST[$this->meta_param]) {
            update_post_meta($id, $this->meta_param, 1);
        } else {
            delete_post_meta($id, $this->meta_param);
        }
    }
}

public function addButton($buttons = array())
{
    array_unshift($buttons, '|');
    array_unshift($buttons, 'template');
    return $buttons;
}

private function addCustomPostType()
{
    $args = array(
        'label' => __('Templates', 'tinymce_templates'),
        'labels' => array(
            'singular_name' => __('Templates', 'tinymce_templates'),
            'add_new_item' => __('Add New Template', 'tinymce_templates'),
            'edit_item' => __('Edit Template', 'tinymce_templates'),
            'add_new' => __('Add New', 'tinymce_templates'),
            'new_item' => __('New Template', 'tinymce_templates'),
            'view_item' => __('View Template', 'tinymce_templates'),
            'not_found' => __('No templatess found.', 'tinymce_templates'),
            'not_found_in_trash' => __(
                'No templates found in Trash.',
                'tinymce_templates'
            ),
            'search_items' => __('Search Templates', 'tinymce_templates'),
        ),
        'public' => false,
        'publicly_queryable' => false,
        'exclude_from_search' => true,
        'show_ui' => true,
        'capability_type' => 'post',
        'hierarchical' => false,
        'menu_position' => 100,
        'rewrite' => false,
        'show_in_nav_menus' => false,
        'register_meta_box_cb' => array(&$this, 'addMetaBox'),
        'supports' => array(
            'title',
            'editor',
            'excerpt',
            'revisions',
            'author',
        )
    );

    register_post_type($this->post_type, $args);
}

public function addMetaBox()
{
    add_meta_box(
        'tinymce_templates-share',
        __('Share', 'tinymce_templates'),
        array(&$this, 'sharedMetaBox'),
        $this->post_type,
        'side',
        'low'
    );

    add_meta_box(
        'tinymce_templates-translators',
        __('Translators', 'tinymce_templates'),
        array(&$this, 'translatorsMetaBox'),
        $this->post_type,
        'side',
        'low'
    );

    add_meta_box(
        'tinymce_templates-donate',
        __('Donate', 'tinymce_templates'),
        array(&$this, 'donateMetaBox'),
        $this->post_type,
        'side',
        'low'
    );
}

public function donateMetaBox($post, $box)
{
    echo '<p>';
    echo '<a href="https://www.paypal.com/cgi-bin/webscr?cmd=_s-xclick&hosted_button_id=CU8N3N2Q9DA8U">';
    echo '<img src="'.$this->base_url.'/img/paypal.png">';
    echo '</a>';
    echo '</p>';
    echo '<p>'.__('It is hard to continue development and support for WordPress plugins without contributions from users like you.', 'tinymce_templates').'</p>';
}

public function translatorsMetaBox($post, $box)
{
    echo '<ul>';
    foreach ($this->translators as $u => $p) {
        if ($p['url']) {
            printf(
                '<li><a href="%s">%s</a> (%s)</li>',
                esc_attr($p['url']),
                esc_html($u),
                esc_html($p['lang'])
            );
        } else {
            printf(
                '<li>%s (%s)</li>',
                esc_html($u),
                esc_html($p['lang'])
            );
        }
    }
    echo '</ul>';
}

public function sharedMetaBox($post, $box)
{
    $share = get_post_meta($post->ID, $this->meta_param, true);
    echo '<select name="'.$this->meta_param.'">';
    echo '<option value="0">'.__('Private', 'tinymce_templates').'</option>';
    if ($share) {
        echo '<option value="1" selected="selected">'.__('Shared', 'tinymce_templates').'</option>';
    } else {
        echo '<option value="1">'.__('Shared', 'tinymce_templates').'</option>';
    }
    echo '</select>';
}

public function wp_ajax(){
    nocache_headers();
    if (!wp_verify_nonce($_GET['nonce'], 'tinymce_templates')) {
        return;
    }
    $u = wp_get_current_user();
    header( 'Content-Type: application/x-javascript; charset=UTF-8' );
    if (isset($_GET['template_id']) && intval($_GET['template_id'])) {
        $p = get_post($_GET['template_id']);
        if ($p->post_status === 'publish') {
            if ($u->ID === $p->post_author) {
                echo apply_filters(
                    "tinymce_templates",
                    wpautop($p->post_content),
                    stripslashes($p->post_content)
                );
            } else {
                $share = get_post_meta($p->ID, $this->meta_param, true);
                if ($share) {
                    echo apply_filters(
                        "tinymce_templates",
                        wpautop($p->post_content),
                        stripslashes($p->post_content)
                    );
                }
            }
        }
        exit;
    }
    $p = array(
        'post_status' => 'publish',
        'post_type'   => $this->post_type,
        'orderby'     => 'date',
        'order'       => 'DESC',
        'numberposts' => -1,
    );
    $posts = get_posts($p);

    $url    = admin_url('admin-ajax.php');
    $url = add_query_arg('action', 'tinymce_templates', $url);
    $url = add_query_arg('action', 'tinymce_templates', $url);
    $nonce = wp_create_nonce("tinymce_templates");
    $url = add_query_arg('nonce', $nonce, $url);

    $arr = array();
    foreach ($posts as $p) {
        if ($u->ID !== $p->post_author) {
            $share = get_post_meta($p->ID, $this->meta_param, true);
            if (!$share) {
                continue;
            }
        }
        $ID = intval($p->ID);
        $name = esc_html($p->post_title);
        $desc = esc_html($p->post_excerpt);
        $url  = add_query_arg('template_id', $ID, $url);
        $arr[] = array($name, $url, $desc);
    }
    echo 'var tinyMCETemplateList = '.json_encode($arr);
    exit;
}

} // end class tinymceTemplates


// eof
