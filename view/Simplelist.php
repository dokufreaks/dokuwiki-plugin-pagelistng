<?php

// must be run within Dokuwiki
if (!defined('DOKU_INC')) die();

require_once DOKU_INC.'lib/plugins/pagelistng/view/AbstractView.php';

/**
 * Class Simplelist.
 *
 * The simplelist creates a simple <ul> list which
 * contains a list item per page.
 */
class Simplelist extends AbstractView {

    /**
     * AbstractView constructor.
     *
     * @param string $viewname the name of this action
     */
    public function __construct($viewname = '') {
        parent::__construct($viewname);
    }

    /**
     * Sets the list header.
     * 
     * @param mixed $options Specific to the view
     */
    public function startList($options=NULL) {
        $options['class'] .= ' pagelist simplelist';
        parent::startList($options);
        if (!empty($options['title'])) {
            $this->doc .= '<p>'.$options['title'].'</p>';
        }
        $this->doc .= DOKU_LF.'<ul>'.DOKU_LF;
    }

    /**
     * Finish/close the list.
     */
    public function finishList() {
        // Sort pages first
        $this->sortPages();

        // Generate list items
        foreach ($this->pages as $page) {
            $id = $page['id'];

            $class = '';
            $this->doc .= DOKU_TAB . '<li>';
            if (page_exists($id)) {
                $class = 'wikilink1';
            } else {
                $class = 'wikilink2';
            }
            
            if (!$page['title']) {
                $page['title'] = str_replace('_', ' ', noNS($id));
            }
            $title = hsc($page['title']);
            
            $content = '<a href="'.wl($id).'" class="'.$class.'" title="'.$id.'">'.$title.'</a>';

            $this->doc .= $content;
            $this->doc .= '</li>'.DOKU_LF;
        }

        // Finish/close the list
        $this->doc .= '</ul>' . DOKU_LF;
        parent::finishList();

        $result = $this->doc;

        // reset defaults
        $this->__construct();

        return $result;
    }
}
