<?php
/*
Plugin Name: TinyMCE Templates
Plugin URI: http://firegoby.theta.ne.jp/wp/tinymce_templates
Description: Manage & Add Tiny MCE template.
Author: Takayuki Miyauchi
Version: 2.1.0
Author URI: http://firegoby.theta.ne.jp/
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

require_once(dirname(__FILE__).'/includes/class-addrewriterules.php');
require_once(dirname(__FILE__).'/includes/mceplugins.class.php');

define('TINYMCE_TEMPLATES_DOMAIN', 'tinymce_templates');

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
        'url'  => 'http://twitter.com/#!/miya0001',
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
    register_activation_hook(__FILE__, array(&$this, 'activation'));
    $this->base_url = WP_PLUGIN_URL.'/'.dirname(plugin_basename(__FILE__));
    add_action('plugins_loaded', array(&$this, 'plugins_loaded'));
    add_action('admin_menu', array(&$this, 'admin_menu'));
    add_action('save_post', array(&$this, 'save_post'));
    add_filter('mce_css', array(&$this, 'mce_css'));
    add_action('admin_head', array(&$this, 'admin_head'));
    new WP_AddRewriteRules(
        'wp-admin/mce_templates.js$',
        'mce_templates',
        array(&$this, 'get_templates')
    );
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
    // do  flush rewrite rules
    flush_rewrite_rules();
}

public function plugins_loaded()
{
    load_plugin_textdomain(
        TINYMCE_TEMPLATES_DOMAIN,
        false,
        dirname(plugin_basename(__FILE__)).'/languages'
    );
    $this->addCustomPostType();
}

public function mce_css($css)
{
    $files = preg_split("/,/", $css);
    $files[] = $this->base_url.'/editor.css';
    $files = array_map('trim', $files);
    return join(",", $files);
}

public function admin_head(){
    $plugin = $this->base_url.'/mce_plugins/plugins/template/editor_plugin.js';
    $lang   = dirname(__FILE__).'/mce_plugins/plugins/template/langs/langs.php';
    $url    = home_url();
    $list_url = add_query_arg('mce_templates', 1, home_url('/'));
    $inits['template_external_list_url'] = $list_url;
    new mcePlugins(
        'template',
        $plugin,
        $lang,
        array(&$this, 'addButton'),
        $inits
    );
    if (get_post_type() === $this->post_type) {
        global $hook_suffix;
        if ($hook_suffix === 'post.php' || $hook_suffix === 'post-new.php') {
            if (get_option("tinymce_templates_db_version") != $this->db_version) {
                $this->activation();
            }
            echo '<style>#visibility{display:none;}</style>';
        }
    }
}

public function admin_menu()
{
    remove_meta_box('slugdiv', $this->post_type, 'normal');
}

public function save_post($id)
{
    if (defined('DOING_AUTOSAVE') && DOING_AUTOSAVE)
        return $id;

    if (isset($_POST['action']) && $_POST['action'] == 'inline-save')
        return $id;

    $p = get_post($id);
    if ($p->post_type === $this->post_type) {
        if (isset($_POST[$this->meta_param])) {
            update_post_meta($id, $this->meta_param, 1);
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
        'label' => __('Templates', TINYMCE_TEMPLATES_DOMAIN),
        'labels' => array(
            'singular_name' => __('Templates', TINYMCE_TEMPLATES_DOMAIN),
            'add_new_item' => __('Add New Template', TINYMCE_TEMPLATES_DOMAIN),
            'edit_item' => __('Edit Template', TINYMCE_TEMPLATES_DOMAIN),
            'add_new' => __('Add New', TINYMCE_TEMPLATES_DOMAIN),
            'new_item' => __('New Template', TINYMCE_TEMPLATES_DOMAIN),
            'view_item' => __('View Template', TINYMCE_TEMPLATES_DOMAIN),
            'not_found' => __('No templatess found.', TINYMCE_TEMPLATES_DOMAIN),
            'not_found_in_trash' => __(
                'No templates found in Trash.',
                TINYMCE_TEMPLATES_DOMAIN
            ),
            'search_items' => __('Search Templates', TINYMCE_TEMPLATES_DOMAIN),
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
        __('Share', TINYMCE_TEMPLATES_DOMAIN),
        array(&$this, 'sharedMetaBox'),
        $this->post_type,
        'side',
        'low'
    );
    add_meta_box(
        'tinymce_templates-translators',
        __('Translators', TINYMCE_TEMPLATES_DOMAIN),
        array(&$this, 'translatorsMetaBox'),
        $this->post_type,
        'side',
        'low'
    );
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
    echo '<p>';
    echo '<a href="https://www.paypal.com/cgi-bin/webscr?cmd=_s-xclick&hosted_button_id=CU8N3N2Q9DA8U">';
    echo '<img src="'.$this->base_url.'/paypal.png">';
    echo '</a>';
    echo '</p>';
}

public function sharedMetaBox($post, $box)
{
    $share = get_post_meta($post->ID, $this->meta_param, true);
    echo '<select name="'.$this->meta_param.'">';
    echo '<option value="0">'.__('Private', TINYMCE_TEMPLATES_DOMAIN).'</option>';
    if ($share) {
        echo '<option value="1" selected="selected">'.__('Shared', TINYMCE_TEMPLATES_DOMAIN).'</option>';
    } else {
        echo '<option value="1">'.__('Shared', TINYMCE_TEMPLATES_DOMAIN).'</option>';
    }
    echo '</select>';
}

public function get_templates(){
    if (is_user_logged_in() && get_query_var('mce_templates')) {
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
        );
        $posts = get_posts($p);
        echo 'var tinyMCETemplateList = [';
        $arr = array();
        $list_url = add_query_arg('mce_templates', 1, home_url('/'));
        foreach ($posts as $p) {
            if ($u->ID !== $p->post_author) {
                $share = get_post_meta($p->ID, $this->meta_param, true);
                if (!$share) {
                    continue;
                }
            }
            $ID = esc_html($p->ID);
            $name = esc_html($p->post_title);
            $desc = esc_html($p->post_excerpt);
            $url  = add_query_arg('template_id', $ID, $list_url);
            $arr[] = "[\"{$name}\", \"{$url}\", \"{$desc}\"]";
        }
        echo join(',', $arr);
        echo ']';
        exit;
    }
}

} // end class tinymceTemplates


// eof
