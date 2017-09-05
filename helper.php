<?php
/**
 * PagelistNG helper component.
 * 
 * A pagelist is made of pages. The data of each page fills a record.
 * The records a formed to a table or list etc...
 * One record is one table row or list item etc...
 * 
 * A record consists of record fields.
 * The minimum record field is usually the page id.
 * 
 * @license    GPL 2 (http://www.gnu.org/licenses/gpl.html)
 * @author     LarsDW223
 */

// must be run within Dokuwiki
if (!defined('DOKU_INC')) die();

require_once DOKU_INC.'lib/plugins/pagelistng/view/AbstractView.php';
require_once DOKU_INC.'lib/plugins/pagelistng/view/Simplelist.php';
require_once DOKU_INC.'lib/plugins/pagelistng/view/Table.php';
require_once DOKU_INC.'lib/plugins/pagelistng/view/UBoard.php';

class helper_plugin_pagelistng extends DokuWiki_Plugin {

    protected $view     = NULL; // view mode, e.g.:  'table', 'list', 'simplelist'
    protected $viewinst = NULL; // View Object (base class AbstractView)
    protected $columns  = array('page', 'date', 'user', 'desc', 'diff');
    protected $ccolumns = NULL;
    protected $options  = array();
    protected $plugins  = array();

    /**
     * Constructor gets default preferences.
     *
     * These can be overriden by plugins using this class.
     */
    function __construct() {
        $view = NULL;
        $viewinst = NULL;

        if ($this->ccolumns == NULL) {
            $this->ccolumns = $this->parse_columns_config($this->getConf('columns'));
        }
    }

    /**
     * This function sets the view and creates
     * the corresponding view instance.
     */
    function setView($name, $overwrite=false) {
        if ($this->viewinst && !$overwrite) {
            // View is already set.
            return;
        }
        $name = strtolower($name);
        switch ($name) {
            case 'uboard':
                $this->view = 'UBoard';
                $this->viewinst = new UBoard();
                break;
            case 'list':
                // ToDo
                msg('Pagelistng: unknown view: '.$name);
                break;
            case 'simplelist':
                $this->view = 'Simplelist';
                $this->viewinst = new Simplelist();
                break;
            case 'table':
                $this->view = 'Table';
                $this->viewinst = new Table();
                break;
            default:
                // ToDo: throw exception?
                msg('Pagelistng: unknown view: '.$name);
                break;
        }
        if ($this->viewinst) {
            $this->setSortParams($this->getConf('sort'), $this->getConf('rsort'));
        }
    }

    /**
     * Return the set view mode.
     */
    function getView() {
        return $this->view;
    }

    /**
     * This function sets the view and creates
     * the corresponding view instance.
     */
    function getViewInstance() {
        return $this->viewinst;
    }

    /**
     * Overrides standard values for style, showheader and show(column) settings
     */
    function setFlags($flags) {
        if (!is_array($flags)) return false;

        foreach ($flags as $flag) {
            switch ($flag) {
                case 'default':
                    $this->setView($this->getConf('view'), true);
                    break;
                case 'table':
                    $this->setView('Table', true);
                    break;
                case 'list':
                    $this->setView('List', true);
                    break;
                case 'simplelist':
                    $this->setView('Simplelist', true);
                    break;
                case 'uboard':
                    $this->setView('UBoard', true);
                    break;
                case 'header':
                    $this->options['showheader'] = true;
                    break;
                case 'noheader':
                    $this->options['showheader'] = false;
                    break;
                case 'firsthl':
                    $this->options['showfirsthl'] = true;
                    break;
                case 'nofirsthl':
                    $this->options['showfirsthl'] = false;
                    break;
                case 'sort':
                    $this->setSortParams(true, false);
                    break;
                case 'rsort':
                    $this->setSortParams(false, true);
                    break;
                case 'nosort':
                    $this->setSortParams(false, false);
                    break;
                case 'showdiff':
                    $flag = 'diff';
                    break;
                default:
                    if (strpos($flag, 'title=') === 0) {
                        $title = substr($flag, 6);
                        $title = trim($title);
                        $this->options['title'] = $title;
                    }
                    break;
            }

            if (substr($flag, 0, 2) == 'no') {
                $value = false;
                $flag  = substr($flag, 2);
            } else {
                $value = true;
            }

            //if (in_array($flag, $this->ccolumns)) $this->column[$flag] = $value;
        }
        return true;
    }

    /**
     * This function adds the common options of the PagelistNG plugin
     * itself. Thses are defined in the plugin configuration.
     */
    public function setPagelistNGOptions(&$options) {
        $options['showheader']  = $this->getConf('showheader');
        $options['showfirsthl'] = $this->getConf('showfirsthl');
        $options['showdesc']    = $this->getConf('showdesc');
    }

    protected function loadPlugin($plugin) {
        if ($this->plugins[$plugin] == NULL) {
            if (plugin_isdisabled($plugin)) {
                return NULL;
            }
            $this->plugins[$plugin] = plugin_load('helper', $plugin);
        }
        return $this->plugins[$plugin];
    }


    /**
     * This function adds the common columns known to the
     * PagelistNG plugin itself.
     *
     * These can be overriden by plugins using this class. The view
     * must already have been set.
     */
    public function addPagelistNGColumns() {
        if (!$this->viewinst) {
            // Set view first.
            msg('Pagelistng: no view set!');
            return;
        }
        if ($this->view != 'Table') {
            // Not applicable for other views
            msg('Pagelistng: call to "' . __FUNCTION__ . '" only valid for view "Table"!');
            return;
        }
        $options = array();
        $this->setPagelistNGOptions($options);
        foreach ($this->ccolumns as $pluginname => $columns) {
            $plugin = $this->loadPlugin($pluginname);
            if ($plugin && is_callable(array($plugin, 'getPagelistNGCallable'))) {
                foreach ($columns as $column) {
                    $this->viewinst->addColumn ($column,
                                                $pluginname,
                                                $pluginname,
                                                $pluginname,
                                                NULL,
                                                $options);
                }
            }
        }
    }

    /**
     * This function adds the common content for cards known to the
     * PagelistNG plugin itself.
     */
    public function addPagelistNGCardContent() {
        if (!$this->viewinst) {
            // Set view first.
            msg('Pagelistng: no view set!');
            return;
        }
        if ($this->view != 'UBoard') {
            // Not applicable for other views
            msg('Pagelistng: call to "' . __FUNCTION__ . '" only valid for view "UBoard"!');
            return;
        }
        $options = array();
        $this->setPagelistNGOptions($options);
        $this->viewinst->setTitle($this->options['title']);
        $this->viewinst->setCardTitle('page', 'pagelistng', $options);

        foreach ($this->ccolumns as $pluginname => $columns) {
            $plugin = $this->loadPlugin($pluginname);
            if ($plugin && is_callable(array($plugin, 'getPagelistNGCallable'))) {
                foreach ($columns as $column) {
                    if (!($pluginname == 'pagelistng' && $column == 'page')) {
                        $this->viewinst->addContent ($column,
                                                     $pluginname,
                                                     $options);
                    }
                }
            }
        }
    }

    function getMethods() {
        $result = array();
        $result[] = array(
                'name'   => 'startList',
                'desc'   => 'prepares the start for the page list',
                );
        $result[] = array(
                'name'   => 'addPage',
                'desc'   => 'adds a page to the page list',
                'params' => array("page attributes, 'id' required, others optional" => 'array'),
                );
        $result[] = array(
                'name'   => 'finishList',
                'desc'   => 'returns the XHTML output',
                'return' => array('xhtml' => 'string'),
                );
        return $result;
    }

    /**
     * Sets the list header.
     * 
     * @param mixed $options Specific to the view
     */
    public function startList($options=NULL) {
        if (!$this->viewinst) {
            // Set view first.
            return;
        }
        $this->viewinst->startList($options);
    }

    /**
     * Add a page.
     * 
     * This means directly generate the XHTML code for the page
     * or safe the page and generate all XHTML code in 'finishList'
     * (depending on the view)
     */
    public function addPage($page) {
        if (!$this->viewinst) {
            // Set view first.
            return;
        }
        $this->viewinst->addPage($page);
    }

    /**
     * Finish/close the list.
     */
    public function finishList() {
        if (!$this->viewinst) {
            // Set view first.
            return;
        }
        $result = $this->viewinst->finishList($page);
        $this->__construct();
        return $result;
    }

    /**
     * Set sorting params.
     */
    public function setSortParams($sort, $rsort, $key='id', $method='stringcmp') {
        if (!$this->viewinst) {
            // Set view first.
            return;
        }
        return $this->viewinst->setSortParams($sort, $rsort, $key, $method);
    }

    public function getPagelistNGCallable ($view, $type, $name) {
        if ($type == 'th') {
            return array('pagelistng', 'columnGetTitle');
        } else if ($type == 'tr') {
            return array('pagelistng', 'rowSetClass');
        } else {
            switch ($name) {
                case 'page':
                    if ($view == 'UBoard') {
                        return array('pagelistng', 'cellPageGetContentUBoard');
                    }
                    return array('pagelistng', 'cellPageGetContent');
                case 'date':
                    return array('pagelistng', 'cellDateGetContent');
                case 'user':
                    return array('pagelistng', 'cellUserGetContent');
                case 'desc':
                    return array('pagelistng', 'cellDescGetContent');
                case 'diff':
                    return array('pagelistng', 'cellDiffGetContent');
            }
        }
    }

    /**
     * The function returns the column header text for column $name.
     * 
     * @param $name  Name of the column
     * @param $class CSS class
     */
    public function columnGetTitle($name, &$class, $options) {
        return hsc($this->getLang($name));
    }

    /**
     * The function modifies the class for the table row.
     * 
     * @param $page  Current page for which the content shall be generated
     * @param $name  Name of the column
     * @param $class CSS class
     */
    public function rowSetClass($page, $name, &$class, $options) {
        if ($name == 'page') {
            if (!isset($page['draft'])) {
                $page['draft'] = ($this->_getMeta($page, 'type') == 'draft');
            }
            if ($page['draft']) $class .= 'draft ';
            if ($page['class']) $class .= $this->page['class'];
        }
    }

    public function cellPageGetContentUBoard($page, $name, &$class, $options) {
        $result = $this->cellPageGetContent($page, $name, $class, $options);
        $class = 'cardpage';
        return $result;
    }

    /**
     * The function returns the cell content for column 'page'.
     * 
     * @param $page  Current page for which the content shall be generated
     * @param $name  Name of the column
     * @param $class CSS class
     */
    public function cellPageGetContent($page, $name, &$class, $options) {
        $id = $page['id'];

        // check for page existence
        if (!isset($page['exists'])) {
            if (!isset($page['file'])) $page['file'] = wikiFN($id);
            $page['exists'] = @file_exists($page['file']);
        }
        if ($page['exists']) {
            $class = 'wikilink1';
        } else {
            $class = 'wikilink2';
        }

        // handle image and text titles
        if ($page['titleimage']) {
            $title = '<img src="'.ml($page['titleimage']).'" class="media"';
            if ($page['title']) $title .= ' title="'.hsc($page['title']).'"'.
                ' alt="'.hsc($page['title']).'"';
            $title .= ' />';
        } else {
            if($this->showfirsthl) {
                $page['title'] = $this->_getMeta($page, 'title');
            }

            if (!$page['title']) $page['title'] = str_replace('_', ' ', noNS($id));
            $title = hsc($page['title']);
        }

        // prepare results
        $content = '<a href="'.wl($id).($page['section'] ? '#'.$page['section'] : '').
                   '" class="'.$class.'" title="'.$id.'">'.$title.'</a>';
        $class = 'page';

        return $content;
    }

    /**
     * The function returns the cell content for column 'date'.
     * 
     * @param $page  Current page for which the content shall be generated
     * @param $name  Name of the column
     * @param $class CSS class
     */
    public function cellDateGetContent($page, $name, &$class, $options) {
        global $conf;

        if($options['date'] == 2) {
            $page['date'] = $this->_getMeta($page, array('date', 'modified'));
        } elseif(!$page['date'] && $page['exists']) {
            $page['date'] = $this->_getMeta($page, array('date', 'created'));
        }

        $class = 'date';
        if ((!$page['date']) || (!$page['exists'])) {
            return '';
        } else {
            return dformat($page['date'], $conf['dformat']);
        }
    }

    /**
     * The function returns the cell content for column 'user'.
     * 
     * @param $page  Current page for which the content shall be generated
     * @param $name  Name of the column
     * @param $class CSS class
     */
    public function cellUserGetContent($page, $name, &$class, $options) {
        if (!array_key_exists('user', $page)) {
            if ($options['user'] == 2) {
                $users = $this->_getMeta($page, 'contributor');
                if (is_array($users)) $page['user'] = join(', ', $users);
            } else {
                $page['user'] = $this->_getMeta($page, 'creator');
            }
        }

        $class = 'user';
        return hsc($page['user']);
    }

    /**
     * The function returns the cell content for column 'desc'.
     * Description = (truncated) auto abstract if not set otherwise.
     * 
     * @param $page  Current page for which the content shall be generated
     * @param $name  Name of the column
     * @param $class CSS class
     */
    public function cellDescGetContent($page, $name, &$class, $options) {
        if (array_key_exists('desc', $page)) {
            $desc = $page['desc'];
        } elseif (strlen($page['description']) > 0) {
            // This condition will become true, when a page-description is given
            // inside the syntax-block
            $desc = $page['description'];
        } else {
            $desc = $this->_getMeta($page, array('description', 'abstract'));
        }
        
        $max = $options['showdesc'];
        if (($max > 1) && (utf8_strlen($desc) > $max)) {
            $desc = utf8_substr($desc, 0, $max).'…';
        }

        $class = 'desc';
        return hsc($desc);
    }

    /**
     * The function returns the cell content for column 'diff'.
     * This includes the diff icon / link to the diff page.
     * 
     * @param $page  Current page for which the content shall be generated
     * @param $name  Name of the column
     * @param $class CSS class
     */
    function cellDiffGetContent($page, $name, &$class, $options) {
        $id = $page['id'];

        // check for page existence
        if (!isset($page['exists'])) {
            if (!isset($page['file'])) {
                $page['file'] = wikiFN($id);
            }
            $page['exists'] = @file_exists($page['file']);
        }

        // produce output
        $url_params = array();
        $url_params ['do'] = 'diff';
        $content = '<a href="'.wl($id, $url_params).($page['section'] ? '#'.$page['section'] : '').'" class="diff_link">
<img src="/lib/images/diff.png" width="15" height="11" title="'.hsc($this->getLang('diff_title')).'" alt="'.hsc($this->getLang('diff_alt')).'"/>
</a>';

        $class = 'diff_link';
        return $content;
    }

    /**
     * Get default value for an unset element
     */
    function _getMeta($page, $key) {
        if (!$page['exists']) {
            return false;
        }
        if (!isset($page['meta'])) {
            $page['meta'] = p_get_metadata($page['id'], '', METADATA_RENDER_USING_CACHE);
        }
        if (is_array($key)) {
            return $page['meta'][$key[0]][$key[1]];
        }
        else {
            return $page['meta'][$key];
        }
    }

    /**
     * Internal function for parsing the columns config.
     *
     * @param string $options Comma separated list of key-value pairs,
     *                        e.g. plugin1="columnA", plugin2="columnB, columnA"
     * @return array|null     Array of plugin-columns relation $array['key'] = array();
     *                        E.g. plugin1="columnA" results in array['plugin1'] = array('columnA')
     *                        E.g. plugin2="columnB, columnA" results in array['plugin2'] = array('columnB', 'columnA')
     */
    protected function parse_columns_config ($options) {
        $result = array();
        preg_match_all('/(\w+(?:="[^"]*"))|(\w+[^=,\]])(?:,*)/', $options, $matches, PREG_SET_ORDER);
        foreach ($matches as $match) {
            $equal_sign = strpos($match [0], '=');
            if ($equal_sign !== false) {
                $key = substr($match[0], 0, $equal_sign);
                $value = substr($match[0], $equal_sign+1);
                $value = trim($value, '"');
                if (strlen($value) > 0) {
                    $columns = explode(',', $value);
                    for ($index = 0 ; $index < count($columns) ; $index++) {
                        $columns[$index] = trim($columns[$index]);
                    }
                    $columns = array_unique($columns);
                    $result [$key] = $columns;
                }
            }
        }

        return $result;
    }
}
// vim:ts=4:sw=4:et: 
