<?php

if (!class_exists('WP_AddRewriteRules')):
class WP_AddRewriteRules{
    private $rule     = null;
    private $query    = null;
    private $callback = null;

    function __construct($rule, $query, $callback)
    {
        $this->rule     = $rule;
        $this->query    = $query;
        $this->callback = $callback;
        add_filter('query_vars', array(&$this, 'query_vars'));
        add_action(
            'generate_rewrite_rules',
            array(&$this, 'generate_rewrite_rules')
        );
        add_action('wp', array(&$this, 'wp'));
    }

    public function generate_rewrite_rules($wp_rewrite)
    {
        $new_rules[$this->rule] = $wp_rewrite->index . '?' . (
            strpos($this->query, '=') === FALSE
            ? $this->query . '=1'
            : $this->query
        );
        $wp_rewrite->rules = $new_rules + $wp_rewrite->rules;
    }

    private function parse_query($query)
    {
        $query = explode('&', $query);
        $query = explode(
            '=',
            is_array($query) && isset($query[0]) ? $query[0] : $query
        );
        return (is_array($query) && isset($query[0]) ? $query[0] : $query);
    }

    public function query_vars($vars)
    {
        $vars[] = $this->parse_query($this->query);
        return $vars;
    }

    public function wp()
    {
        if (get_query_var($this->parse_query($this->query))) {
            call_user_func($this->callback);
        }
    }
}
endif;

// eol