<?php
/**
 * Metadata for configuration manager plugin
 * Additions for the Pagelist Plugin
 *
 * @author    Esther Brunner <wikidesign@gmail.com>
 */

$meta['view']       = array('multichoice',
                            '_choices' => array('table', 'list', 'simplelist'));
$meta['showheader'] = array('onoff');
$meta['sort']       = array('onoff');
$meta['rsort']      = array('onoff');
$meta['showdesc']   = array('numeric');
$meta['columns']    = array('text');

//Setup VIM: ex: et ts=2 :
