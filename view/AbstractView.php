<?php

/**
 * Class AbstractView.
 *
 * Base class for all pagelistng views.
 */
abstract class AbstractView {

    protected $pages      = NULL;  // array of pages
    protected $sort       = false; // alphabetical sort
    protected $rsort      = false; // reverse alphabetical sort
    protected $sortkey    = 'id';  // what is the sort key?
    protected $sortmethod = 'id';  // what is the sort key?
    protected $doc        = '';    // the final output XHTML string
    protected $options    = NULL;  // the final output XHTML string
    protected $plugins    = array();

    /** @var string holds the name of the view */
    protected $viewname;

    /**
     * AbstractView constructor.
     *
     * @param string $viewname the name of this action
     */
    public function __construct($viewname = '') {
        if($viewname !== '') {
            $this->viewname = $viewname;
        } else {
            // http://stackoverflow.com/a/27457689/172068
            $this->viewname = strtolower(substr(strrchr(get_class($this), '\\'), 1));
        }
        $this->doc = '';
        $this->sort = false;
        $this->rsort = false;
        $this->sortkey = 'id';
        $this->pages = array();
        $this->options = array();
    }

    /**
     * Sets the list header.
     * 
     * @param mixed $options Specific to the view
     */
    function startList($options=NULL) {
        $this->doc = '<div class="'.$options['class'].'">'.DOKU_LF;
    }

    /**
     * Add a page.
     * 
     */
    public function addPage($page) {
        if (!$page['id']) {
            return false;
        }
        $this->pages [] = $page;
        return true;
    }

    /**
     * Finish/close the list.
     */
    public function finishList() {
        $this->doc .= '</div>'.DOKU_LF;
    }

    /**
     * Helper function for loading a plugin.
     */
    protected function loadPlugin($plugin) {
        if (empty($plugin)) {
            return NULL;
        }
        if ($this->plugins[$plugin] == NULL) {
            if (plugin_isdisabled($plugin)) {
                return NULL;
            }
            $loaded = plugin_load('helper', $plugin);
            $this->plugins[$plugin] = $loaded;
        } else {
            $loaded = $this->plugins[$plugin];
        }
        return $loaded;
    }

    /**
     * Helper function for creating a callable
     * based on a plugin and method name.
     */
    protected function getCallable($plugin, $method) {
        $loaded = $this->loadPlugin($plugin);
        if ($loaded == NULL) {
            return NULL;
        }
        if (empty($method)) {
            return NULL;
        }
        return array($loaded, $method);
    }

    /**
     * Compare function for key page ID
     */
    public function comparePageKeyID($a, $b) {
        return strcmp(noNS($a[$this->sortkey]), noNS($b[$this->sortkey]));
    }

    /**
     * Compare function for key page ID (Reverse sort)
     */
    public function comparePageKeyIDR($a, $b) {
        $result = strcmp(noNS($a[$this->sortkey]), noNS($b[$this->sortkey]));
        return ($result * -1);
    }

    /**
     * Compare function for simple string key
     */
    public function comparePageKeyString($a, $b) {
        return strcmp($a[$this->sortkey], $b[$this->sortkey]);
    }

    /**
     * Compare function for simple string key (Reverse sort)
     */
    public function comparePageKeyStringR($a, $b) {
        $result = strcmp($a[$this->sortkey], $b[$this->sortkey]);
        return ($result * -1);
    }

    /**
     * The function sorts the pages according to the actually set
     * sorting parameters.
     */
    public function sortPages() {
        switch ($this->sortkey) {
            case 'id':
                $function = 'comparePageKeyID';
                break;
            default:
                $function = 'comparePageKeyString';
                break;
        }
        if ($this->sort) {
            usort($this->pages, array($this, $function));
        } else if ($this->rsort) {
            usort($this->pages, array($this, $function.'R'));
        }
    }

    /**
     * The function sets the sorting parameters.
     */
    public function setSortParams($sort, $rsort, $key='id', $method='stringcmp') {
        $this->sort = $sort;
        $this->rsort = $rsort;
        if ($this->sort || $this->rsort) {
            $this->sortkey = $key;
            $this->sortmethod = $method;
        }
    }

    /**
     * Returns the name of this view.
     *
     * This is usually the lowercased class name.
     *
     * @return string
     */
    public function getViewName() {
        return $this->viewname;
    }
}
