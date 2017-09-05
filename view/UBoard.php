<?php

// must be run within Dokuwiki
if (!defined('DOKU_INC')) die();

require_once DOKU_INC.'lib/plugins/pagelistng/view/AbstractView.php';

/**
 * Class UBoard.
 *
 * The class creates an uncategorized board view with a title.
 * Each page is a card on the board with a card title and content.
 * The content can be given by multiple plugins.
 * The card is a div and each part of the card is also a div:
 * <div class="pagelist uboard">
 *     <div class="card">
 *         <div class="title">...</div>
 *         <div class="content">
 *             <div class="pluginA">...</div>
 *             <div class="pluginB">...</div>
 *             <div class="pluginC">...</div>
 *         </div>
 *     </div>
 * </div>
 */
class UBoard extends AbstractView {

    protected $title = NULL;
    protected $cardtitle = NULL;
    protected $content = NULL; // multi-dimensional array of columns,
                               // see 'addContent()'.

    /**
     * AbstractView constructor.
     *
     * @param string $viewname the name of this action
     */
    public function __construct($viewname = '') {
        parent::__construct($viewname);
        $this->title = NULL;
        $this->cardtitle = array();
        $this->content = array();
    }

    /**
     * Set the title of the board.
     * 
     */
    public function setTitle ($title) {
        $this->title = $title;
    }

    /**
     * Set a plugin for giving the title of each card.
     * 
     * @param $name       The name of the column
     * @param $thcallback Callback for returning table header content
     * @param $tdcallback Callback for returning table cell content
     * @param $position   Position of the column or NULL
     * @param $options    Options for the column, will be passed to callbacks
     */
    public function setCardTitle ($name, $plugin, $options) {
        $plugin = $this->loadPlugin($plugin);
        if ($plugin == NULL) {
            return false;
        }
        $callback = $plugin->getPagelistNGCallable('UBoard', 'cardtitle', $name);
        if (empty($callback[0]) || !is_string($callback[0]) || empty($callback[1])) {
            return false;
        }
        $callable = $this->getCallable($callback[0], $callback[1]);

        if (!is_callable($callable)) {
            return false;
        }

        $this->cardtitle = array('name' => $name,
                                 'plugin' => $callback[0],
                                 'callback' => $callable,
                                 'options' => $options);
        return true;
    }

    /**
     * Add a content provider (plugin) to the board.
     * 
     * @param $name       The name of the column
     * @param $thcallback Callback for returning table header content
     * @param $tdcallback Callback for returning table cell content
     * @param $position   Position of the column or NULL
     * @param $options    Options for the column, will be passed to callbacks
     */
    public function addContent ($name, $plugin, $options) {
        $plugin = $this->loadPlugin($plugin);
        if ($plugin == NULL) {
            return false;
        }
        $callback = $plugin->getPagelistNGCallable('UBoard', 'content', $name);
        if (empty($callback[0]) || !is_string($callback[0]) || empty($callback[1])) {
            return false;
        }
        $callable = $this->getCallable($callback[0], $callback[1]);

        if (!is_callable($callable)) {
            return false;
        }

        $this->content[] = array('name' => $name,
                                 'plugin' => $callback[0],
                                 'callback' => $callable,
                                 'options' => $options);
        return true;
    }

    /**
     * Sets the list header.
     * 
     * @param mixed $options Specific to the view
     */
    public function startList($options=NULL) {
        // Save meaningful options for later use
        $this->options = $options;

        $options['class'] .= ' pagelist uboard';
        parent::startList($options);
        if (!empty($this->title)) {
            $this->doc .= DOKU_TAB.'<div><p>'.$this->title.'</p></div>'.DOKU_LF;
        }
    }

    /**
     * Finish/close the list.
     */
    public function finishList() {
        // Sort pages first
        $this->sortPages();

        // Surround all cards with an own div for styling/positioning
        $this->doc .= DOKU_TAB.'<div class="cards">';

        // Generate list items
        foreach ($this->pages as $page) {
            $id = $page['id'];

            // Open card
            $this->doc .= DOKU_TAB.'<div class="card">'.DOKU_LF;

            // Generate title div
            $class = 'title';
            if ($this->cardtitle['callback']) {
                $content = $this->cardtitle['callback']($page, $class, $this->cardtitle['options']);
            }
            $this->doc .= DOKU_TAB.DOKU_TAB.'<div class="'.$class.'">'.$content.'</div>'.DOKU_LF;

            // Open content div
            $this->doc .= DOKU_TAB.DOKU_TAB.'<div class="content">'.DOKU_LF;

            foreach ($this->content as $part) {
                $class = $part['name'];
                $content = $part['callback']($page, $part['name'], $class, $part['options']);
                $this->doc .= DOKU_TAB.DOKU_TAB.DOKU_TAB.'<div class="'.$class.'">'.$content.'</div>'.DOKU_LF;
            }

            // Close content div
            $this->doc .= DOKU_TAB.DOKU_TAB.'</div>'.DOKU_LF;

            // Close card
            $this->doc .= DOKU_TAB.'</div>'.DOKU_LF;
        }

        // Close cards div
        $this->doc .= DOKU_TAB.'</div>'.DOKU_LF;

        // Finish/close the list
        parent::finishList();

        $result = $this->doc;

        // reset defaults
        $this->__construct();

        return $result;
    }
}
