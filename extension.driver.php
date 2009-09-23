<?php

	Class extension_Sitemap extends Extension{
	
		public function about(){
			return array('name' => 'Sitemap',
						 'version' => '1.0',
						 'release-date' => '2009-10-02',
						 'author' => array('name' => 'Nick Dunn',
										   'website' => 'http://nick-dunn.co.uk',
										   'email' => 'nick@nick-dunn.co.uk')
				 		);
		}
		
		public function fetchNavigation() {
			return array(
				array(
					'location' => 'Blueprints',
					'name'	=> 'Site Map',
					'link'	=> '/map/',
				),
			);
		}
		
		public function getSubscribedDelegates() {
			return array(
				array(
					'page' => '/system/preferences/',
					'delegate' => 'AddCustomPreferenceFieldsets',
					'callback' => '__appendPreferences'
				),
			);
		}
		
		public function install() {
			
			if (!$this->_Parent->Configuration->get('primary_type', 'sitemap')) {
				$this->_Parent->Configuration->set('primary_type', 'primary', 'sitemap');
				$this->_Parent->Configuration->set('utilities_type', 'global', 'sitemap');
				$this->_Parent->Configuration->set('exclude_type', 'hidden, XML, 403, 404', 'sitemap');
			}
			return $this->_Parent->saveConfig();
		}
		
		public function uninstall() {
			
			$this->_Parent->Configuration->remove('sitemap');
			return $this->_Parent->saveConfig();
		}
		
		public function __appendPreferences($context) {
			
			$group = new XMLElement('fieldset');
			$group->setAttribute('class', 'settings');
			$group->appendChild(new XMLElement('legend', 'Sitemap'));

			$label = Widget::Label('Primary page types (top level)');
			$label->appendChild(
				Widget::Input(
					'settings[sitemap][primary_type]',
					General::Sanitize($this->_Parent->Configuration->get('primary_type', 'sitemap'))
				)
			);
			$group->appendChild($label);
			
			$label = Widget::Label('Utility page types (global navigation)');
			$label->appendChild(
				Widget::Input(
					'settings[sitemap][utilities_type]',
					General::Sanitize($this->_Parent->Configuration->get('utilities_type', 'sitemap'))
				)
			);
			$group->appendChild($label);
			
			$label = Widget::Label('Excluded page types');
			$label->appendChild(
				Widget::Input(
					'settings[sitemap][exclude_type]',
					General::Sanitize($this->_Parent->Configuration->get('exclude_type', 'sitemap'))
				)
			);
			$group->appendChild($label);

			$context['wrapper']->appendChild($group);
		}
		
	}

?>