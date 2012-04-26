<?php
	
	require_once(TOOLKIT . '/class.administrationpage.php');
	
	Class ContentExtensionSitemapMap extends AdministrationPage{
		
		const SITEMAP_LEVELS = 3;
		public $_pages = array();
		
		private $type_index = null;
		private $type_primary = null;
		private $type_utility = null;
		private $type_exclude = null;
		
		function view(){
			
			// fetch all pages
			$pages = PageManager::fetch();
			
			// build container DIV
			$sitemap = new XMLElement('div', null, array('class' => 'sitemap'));
			
			// add headings
			$sitemap->appendChild(new XMLElement('h1', 'Sitemap <span>' . Symphony::Configuration()->get('sitename', 'general') . '</span>'));
			$sitemap->appendChild(new XMLElement('h2', 'Site Map, ' . date('d F Y', time())));
			
			// build container ULs
			$primary = new XMLElement('ul', null, array('id' => 'primaryNav'));
			$utilities = new XMLElement('ul', null, array('id' => 'utilityNav'));
			
			// get values from config: remove spaces, remove any trailing commas and split into an array
			$this->type_index = explode(',', trim(preg_replace('/ /', '', Symphony::Configuration()->get('index_type', 'sitemap')), ','));
			$this->type_primary = explode(',', trim(preg_replace('/ /', '', Symphony::Configuration()->get('primary_type', 'sitemap')), ','));
			$this->type_utility = explode(',', trim(preg_replace('/ /', '', Symphony::Configuration()->get('utilities_type', 'sitemap')), ','));
			$this->type_exclude = explode(',', trim(preg_replace('/ /', '', Symphony::Configuration()->get('exclude_type', 'sitemap')), ','));
			
			// supplement list of pages with additional meta data
			foreach($pages as $page) {
				
				$page['url'] = '/' . implode('/', Administration::instance()->resolvePagePath($page['id']));
				$page['edit-url'] = Administration::instance()->getCurrentPageURL() . 'edit/' . $page['id'] . '/';
				
				if (count(array_intersect($page['type'], $this->type_exclude)) > 0) continue;
				
				$page['is_home'] = (count(array_intersect($page['type'], $this->type_index))) ? true : false;				
				$page['is_primary'] = (count(array_intersect($page['type'], $this->type_primary)) > 0) ? true : false;
				$page['is_utility'] = (count(array_intersect($page['type'], $this->type_utility)) > 0) ? true : false;
				
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
			$html->setDTD('<!DOCTYPE html>');
			
			$head = new XMLElement('head');
			$head->appendChild(new XMLElement('meta', null, array(
				'charset' => 'utf-8'
			)));
			$head->appendChild(new XMLElement('title', 'Site Map — ' . Symphony::Configuration()->get('sitename', 'general')));
			$head->appendChild(new XMLElement('link', null, array(
				'rel' => 'stylesheet',
				'type' => 'text/css',
				'media' => 'print, screen',
				'href' => URL . '/extensions/sitemap/assets/sitemap.map.css'
			)));
			$head->appendChild(new XMLElement('link', null, array(
				'rel' => 'stylesheet',
				'type' => 'text/css',
				'media' => 'print, screen',
				'href' => URL . '/extensions/sitemap/assets/slickmap/slickmap.css'
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
			
			$types = (is_array($page['type'])) ? implode(' ', $page['type']) : NULL;
			
			// concatenate URL with params
			$meta = new XMLElement(
				'span',
				($root==true) ? URL : $page['url'] . (($page['params'] != '') ? '/:' . implode('/:',explode('/',$page['params'])) . '' : ''),
				array('class' => 'meta')
			);
			
			$title = $page['title'];
			if ($page['is_home'] && $page['is_primary']) $title .= ' (home)';
			
			$link = new XMLElement(
				'a',
				$title . $meta->generate(),
				array('href' => ($root==true) ? URL : $page['url'] . '/')
			);
			
			$page_element = new XMLElement('li', $link->generate());
			
			$class = $types;
			if ($root && $page['is_home'] && $page['is_primary']) $class .= ' placeholder';
			
			if ($root == true) $page_element->setAttribute('id', 'home');
			if ($types != '') $page_element->setAttribute('class', $class);

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