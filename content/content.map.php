<?php
	
	require_once(TOOLKIT . '/class.administrationpage.php');
	
	Class ContentExtensionSitemapMap extends AdministrationPage{
		
		const SITEMAP_LEVELS = 3;
		public $_pages = array();
		
		function view(){
			
			// fetch all pages
			$pages = $this->_Parent->Database->fetch("SELECT p.* FROM `tbl_pages` AS p ORDER BY p.sortorder ASC");
			
			// build container DIV
			$sitemap = new XMLElement('div', null, array('class' => 'sitemap'));
			
			// add headings
			$sitemap->appendChild(new XMLElement('h1', $this->_Parent->Configuration->get('sitename', 'general')));
			$sitemap->appendChild(new XMLElement('h2', 'Site Map, ' . date('d F Y', time())));
			
			// build container ULs
			$primary = new XMLElement('ul', null, array('id' => 'primaryNav'));
			$utilities = new XMLElement('ul', null, array('id' => 'utilityNav'));
			
			// get values from config: remove spaces, remove any trailing commas and split into an array
			$type_primary = explode(',', trim(preg_replace('/ /', '', $this->_Parent->Configuration->get('primary_type', 'sitemap')), ','));
			$type_utility = explode(',', trim(preg_replace('/ /', '', $this->_Parent->Configuration->get('utilities_type', 'sitemap')), ','));
			$type_exclude = explode(',', trim(preg_replace('/ /', '', $this->_Parent->Configuration->get('exclude_type', 'sitemap')), ','));
			
			// supplement list of pages with additional meta data
			foreach($pages as $page) {
				$page_types = $this->_Parent->Database->fetchCol('type', "SELECT `type` FROM `tbl_pages_types` WHERE page_id = '".$page['id']."' ORDER BY `type` ASC");
				
				$page['url'] = '/' . $this->_Parent->resolvePagePath($page['id']);
				$page['edit-url'] = $this->_Parent->getCurrentPageURL() . 'edit/' . $page['id'] . '/';
				$page['types'] = $page_types;
				
				if (count(array_intersect($page['types'], $type_exclude)) > 0) continue;
				
				$page['is_home'] = (is_null($page['parent']) && in_array('index', $page['types'])) ? true : false;
				$page['is_primary'] = ($page['is_home'] == false && count(array_intersect($page['types'], $type_primary)) > 0) ? true : false;
				$page['is_utility'] = (count(array_intersect($page['types'], $type_utility)) > 0) ? true : false;
				
				$this->_pages[] = $page;
			}
			
			// append the Home page first
			foreach($this->_pages as $page) {
				if ($page['is_home'] == true) $this->appendPage($primary, $page, 1, true, false);
			}
			
			// append top level pages
			$primary_pages = 0;
			foreach($this->_pages as $page) {
				if ($page['is_primary'] == true) {
					$primary_pages++;
					$this->appendPage($primary, $page);
				}
			}
			
			// sitemap provides styles for up to 10 top level pages
			if ($primary_pages > 0 && $primary_pages < 11) {
				$primary->setAttribute('class', 'col' . $primary_pages);
			}
			
			// append utilities (global) pages
			foreach($this->_pages as $page) {
				if ($page['is_utility'] == true) {
					$this->appendPage($utilities, $page, 1, false, false);
				}
			}
			
			if ($utilities->getNumberOfChildren() > 0) $sitemap->appendChild($utilities);
			$sitemap->appendChild($primary);
			
			// build a vanilla HTML document			
			$html = new XMLElement('html');
			$html->setDTD('<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Strict//EN" "http://www.w3.org/TR/xhtml1/DTD/xhtml1-strict.dtd">');
			$html->setAttribute('lang', __LANG__);
			
			$head = new XMLElement('head');
			$head->appendChild(new XMLElement('meta', null, array(
				'http-equiv' => 'Content-Type',
				'context' => 'text/html; charset=utf-8'
			)));
			$head->appendChild(new XMLElement('title', 'Site Map — ' . $this->_Parent->Configuration->get('sitename', 'general')));
			$head->appendChild(new XMLElement('link', null, array(
				'rel' => 'stylesheet',
				'type' => 'text/css',
				'media' => 'print, screen',
				'href' => URL . '/extensions/sitemap/assets/slickmap-custom.css'
			)));
			$head->appendChild(new XMLElement('link', null, array(
				'rel' => 'stylesheet',
				'type' => 'text/css',
				'media' => 'print, screen',
				'href' => URL . '/extensions/sitemap/assets/slickmap/sitemap.css'
			)));
			
			$html->appendChild($head);
			
			$body = new XMLElement('body');
			$body->appendChild($sitemap);		
			
			$html->appendChild($body);
			
			header('content-type: text/html');
			echo $html->generate(true);
			
			die;

		}
		
		private function appendPage(&$wrapper, $page, $level=1, $root=false, $add_children=true) {
			
			$types = (is_array($page['types'])) ? implode(' ', $page['types']) : NULL;
			
			// concatenate URL with params
			$meta = new XMLElement(
				'span',
				($root==true) ? URL : $page['url'] . (($page['params'] != '') ? '/{' . implode('}/{',explode('/',$page['params'])) . '}/' : ''),
				array('class' => 'meta')
			);
			
			$link = new XMLElement(
				'a',
				$page['title'] . $meta->generate(),
				array('href' => ($root==true) ? URL : $page['url'] . '/')
			);
			
			$page_element = new XMLElement('li', $link->generate());
			
			if ($root == true) $page_element->setAttribute('id', 'home');
			if ($types != '') $page_element->setAttribute('class', $types);
			
			$ul = null;
			
			// append direct child pages (recursive)
			foreach($this->_pages as $sub_page) { 
				if ($sub_page['parent'] == $page['id']) {
					if (is_null($ul)) {
						$level++;
						if ($level > self::SITEMAP_LEVELS) continue;
						$ul = new XMLElement('ul');
					}
					$this->appendPage($ul, $sub_page, $level);
				}
			}
			
			// only append UL to list if has child pages
			if ($add_children==true && !is_null($ul)) {
				$page_element->appendChild($ul);
			}
			
			$wrapper->appendChild($page_element);
		}
		
	}