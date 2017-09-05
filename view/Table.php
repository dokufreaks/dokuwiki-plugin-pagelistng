<?php

// must be run within Dokuwiki
if (!defined('DOKU_INC')) die();

require_once DOKU_INC.'lib/plugins/pagelistng/view/AbstractView.php';

/**
 * Class Table.
 *
 * The table creates a table with multiple columns.
 * Other plugins can add thier own columns to the table.
 */
class Table extends AbstractView {

    protected $index = 0;
    protected $columns = array();   // multi-dimensional array of columns,
                                    // see 'addColumn()'.

    /**
     * AbstractView constructor.
     *
     * @param string $viewname the name of this action
     */
    public function __construct($viewname = '') {
        parent::__construct($viewname);
        $this->index = 0;
        $this->columns = array();
    }

    /**
     * Add a column.
     * 
     * @param $name       The name of the column
     * @param $thcallback Callback for returning table header content
     * @param $tdcallback Callback for returning table cell content
     * @param $position   Position of the column or NULL
     * @param $options    Options for the column, will be passed to callbacks
     */
    public function addColumn ($name, $thplugin, $trplugin=NULL, $tdplugin, $position=NULL, $options) {
        $thplugin = $this->loadPlugin($thplugin);
        if ($thplugin == NULL) {
            return false;
        }
        $tdplugin = $this->loadPlugin($tdplugin);
        if ($tdplugin == NULL) {
            return false;
        }
        $thcallback = $thplugin->getPagelistNGCallable('Table', 'th', $name);
        if (empty($thcallback[0]) || !is_string($thcallback[0]) || empty($thcallback[1])) {
            return false;
        }
        $tdcallback = $tdplugin->getPagelistNGCallable('Table', 'td', $name);
        if (empty($tdcallback[0]) || !is_string($tdcallback[0]) || empty($tdcallback[1])) {
            return false;
        }
        $thcallable = $this->getCallable($thcallback[0], $thcallback[1]);
        $tdcallable = $this->getCallable($tdcallback[0], $tdcallback[1]);

        // The trcallback is optional and my be NULL
        $trplugin = $this->loadPlugin($trplugin);
        if ($trplugin !== NULL) {
            $trcallback = $trplugin->getPagelistNGCallable('Table', 'tr', $name);
            $trcallable = $this->getCallable($trcallback[0], $trcallback[1]);
        } else {
            $trcallable = NULL;
        }
        if (!is_callable($thcallable) || !is_callable($tdcallable)) {
            return false;
        }
        if ($trcallback !== NULL && !is_callable($trcallable)) {
            return false;
        }

        if ($position === NULL) {
            if ($this->index == 0) {
                $position = 1;
            } else {
                $position = $this->columns[$this->index-1]['position'] + 1;
            }
        } else {
            if ($position < 1) {
                $position = 1;
            } else if ($position > $this->index + 1) {
                $position = $this->index + 1;
            }
            for ($index = 0 ; $index < count($this->columns) ; $index++) {
                if ($this->columns[$index]['position'] >= $position) {
                    $this->columns[$index]['position']++;
                }
            }
        }
        $this->index++;
        $this->columns[] = array('name' => $name,
                                 'thplugin' => $thcallback[0],
                                 'thcallback' => $thcallable,
                                 'trplugin' => $trcallback[0],
                                 'trcallback' => $trcallable,
                                 'tdplugin' => $tdcallback[0],
                                 'tdcallback' => $tdcallable,
                                 'position' => $position,
                                 'options' => $options);
        return true;
    }

    /**
     * Overwrite an already added column.
     * The column to overwrite is identified by its name and the plugin name
     * which was given in thcallback[0] on calling 'addColumn()'.
     * 
     * @param $name            The name of the column
     * @param $plugin          The name of the plugin (taken from thcallback)
     * @param $thcallback|NULL Callback for returning table header content
     * @param $tdcallback|NULL Callback for returning table cell content
     * @param $position|NULL   Position of the column or NULL
     * @param $options|NULL    Options for the column, will be passed to callbacks
     */
    public function overwriteColumn ($name, $plugin, $thplugin=NULL, $trplugin=NULL, $tdplugin=NULL, $position=NULL, $options=NULL) {
        // Search the Column
        for ($index = 0 ; $index < count($this->columns) ; $index++) {
            if ($this->columns[$index]['name'] == $name
                && $this->columns[$index]['thplugin'] == $plugin) {
                break;
            }
        }
        if ($index >= count($this->columns)) {
            // Column was not found
            return false;
        }

        $thcallable = NULL;
        $thplugin = $this->loadPlugin($thplugin);
        if ($thplugin != NULL) {
            $thcallback = $thplugin->getPagelistNGCallable('Table', 'th', $name);
            if ($thcallback[0] != NULL && is_string($thcallback[0]) && is_string($thcallback[1])) {
                $thplugin = $thcallback[0];
                $thcallable = $this->getCallable($thcallback[0], $thcallback[1]);
            }
        }
        if (!is_callable($thcallable)) {
            // Keep existing value
            $thplugin = $this->columns[$index]['thplugin'];
            $thcallable = $this->columns[$index]['thcallback'];
        }
        $trcallable = NULL;
        $trplugin = $this->loadPlugin($trplugin);
        if ($trplugin != NULL) {
            $trcallback = $trplugin->getPagelistNGCallable('Table', 'tr', $name);
            if ($trcallback[0] != NULL && is_string($trcallback[0]) && is_string($trcallback[1])) {
                $trplugin = $trcallback[0];
                $trcallable = $this->getCallable($trcallback[0], $trcallback[1]);
            }
        }
        if (!is_callable($trcallable)) {
            // Keep existing value
            $trplugin = $this->columns[$index]['trplugin'];
            $trcallable = $this->columns[$index]['trcallback'];
        }
        $tdcallable = NULL;
        $tdplugin = $this->loadPlugin($tdplugin);
        if ($tdplugin != NULL) {
            $tdcallback = $tdplugin->getPagelistNGCallable('Table', 'td', $name);
            if ($tdcallback[0] != NULL && is_string($tdcallback[0]) && is_string($tdcallback[1])) {
                $tdplugin = $tdcallback[0];
                $tdcallable = $this->getCallable($tdcallback[0], $tdcallback[1]);
            }
        }
        if (!is_callable($tdcallable)) {
            // Keep existing value
            $tdplugin = $this->columns[$index]['tdplugin'];
            $tdcallable = $this->columns[$index]['tdcallback'];
        }

        if ($position === NULL) {
            // Keep existing value
            $position = $this->columns[$index]['position'];
        } else {
            if ($position < 1) {
                $position = 1;
            } else if ($position > $this->index + 1) {
                $position = $this->index + 1;
            }
            foreach ($this->columns as $column) {
                if ($column['position'] >= $position) {
                    $column['position']++;
                }
            }
        }
        if ($position === NULL) {
            // Keep existing value
            $options = $this->columns[$index]['options'];
        }
        $this->columns[$index] = array('name' => $name,
                                      'thplugin' => $thplugin,
                                      'thcallback' => $thcallable,
                                      'trplugin' => $trplugin,
                                      'trcallback' => $trcallable,
                                      'tdplugin' => $tdplugin,
                                      'tdcallback' => $tdcallable,
                                      'position' => $position,
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

        $options['class'] .= ' pagelist table';
        parent::startList($options);
        $this->doc .= '<table>';
        if (!empty($options['title'])) {
            $this->doc .= '<caption>'.$options['title'].'</caption>';
        }

        // Sort columns by position first
        usort($this->columns, array($this, 'comparePosition'));

        $this->createHeaderRow();
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
            foreach ($this->columns as $column) {
                if ($column['trcallback'] !== NULL) {
                    $column['trcallback']($page, $column['name'], $class, $column['options']);
                }
            }
            if(!empty($class)) {
                $class = ' class="' . $class . '"';
            }
            $this->doc .= DOKU_TAB.'<tr'.$class.'>'.DOKU_LF.DOKU_TAB.DOKU_TAB;

            foreach ($this->columns as $column) {
                $class = $column['name'];
                $content = $column['tdcallback']($page, $column['name'], $class, $column['options']);
                $this->doc .= '<td class="'.$class.'">'.$content.'</td>';
            }

            $this->doc .= DOKU_LF.DOKU_TAB.'</tr>'.DOKU_LF;
        }

        // Finish/close the list (table)
        $this->doc .= '</table>' . DOKU_LF;
        parent::finishList();

        $result = $this->doc;

        // reset defaults
        $this->__construct();

        return $result;
    }

    /**
     * Compare function for column position
     */
    public function comparePosition($a, $b) {
        $valuea = $a['position'];
        $valueb = $b['position'];
        if ($valuea < $valueb) {
            return -1;
        }
        if ($valuea > $valueb) {
            return 1;
        }
        return 0;
    }

    /**
     * The function creates the header row.
     * For this the 'thcallback' is called in this way:
     * $thcallback ($columnname, $class);
     */
    protected function createHeaderRow() {
        if ($this->options['showheader']) {
            $this->doc .= DOKU_TAB.'<tr>'.DOKU_LF.DOKU_TAB.DOKU_TAB;

            foreach ($this->columns as $column) {
                $class = $column['name'];
                $header = $column['thcallback']($column['name'], $class, $column['options']);
                $this->doc .= '<th class="'.$class.'">'.$header.'</th>';
            }

            $this->doc .= DOKU_LF.DOKU_TAB.'</tr>'.DOKU_LF;
        }
    }
}
