<?php
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

if (!class_exists('AddRewriteRules')):
class AddRewriteRules{

    private $rule     = null;
    private $query    = null;
    private $callback = null;

    function __construct($rule, $query, $callback){
        $this->rule     = $rule;
        $this->query    = $query;
        $this->callback = $callback;
        add_filter('query_vars', array(&$this, 'query_vars'));
        add_filter('rewrite_rules_array', array(&$this, 'rewrite_rules_array'));
        add_action('init', array(&$this, 'init'));
        add_action('wp', array(&$this, 'wp'));
    }

    public function init()
    {
        global $wp_rewrite;
        $rules = $wp_rewrite->wp_rewrite_rules();
        if (!isset($rules[$this->rule])) {
            $wp_rewrite->flush_rules();
        }
    }

    public function rewrite_rules_array($rules)
    {
        global $wp_rewrite;
        $new_rules[$this->rule] = $wp_rewrite->index . '?'.$this->query.'=1';
        $rules = array_merge($new_rules, $rules);
        return $rules;
    }

    public function query_vars($vars)
    {
        $vars[] = $this->query;
        return $vars;
    }
    
    public function wp()
    {
        if (get_query_var($this->query)) {
            call_user_func($this->callback);
        }
    }
}
endif;

?>
