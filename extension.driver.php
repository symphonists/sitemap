<?php

	Class extension_Sitemap extends Extension{
	
		public function about(){
			return array('name' => 'Sitemap',
						 'version' => '1.0.2',
						 'release-date' => '2010-08-30',
						 'author' => array('name' => 'Nick Dunn',
										   'website' => 'http://nick-dunn.co.uk')
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
			if (!Symphony::Configuration()->get('primary_type', 'sitemap')) {
				Symphony::Configuration()->set('index_type', 'index', 'sitemap');
				Symphony::Configuration()->set('primary_type', 'primary', 'sitemap');
				Symphony::Configuration()->set('utilities_type', 'global', 'sitemap');
				Symphony::Configuration()->set('exclude_type', 'hidden, XML, 403, 404', 'sitemap');
			}
			return Administration::instance()->saveConfig();
		}
		
		public function uninstall() {
			Symphony::Configuration()->remove('sitemap');
			return Administration::instance()->saveConfig();
		}
		
		public function __appendPreferences($context) {
			
			$group = new XMLElement('fieldset');
			$group->setAttribute('class', 'settings');
			$group->appendChild(new XMLElement('legend', 'Sitemap'));

			$label = Widget::Label('Home page type');
			$label->appendChild(
				Widget::Input(
					'settings[sitemap][index_type]',
					General::Sanitize(Symphony::Configuration()->get('index_type', 'sitemap'))
				)
			);
			$group->appendChild($label);
			
			$label = Widget::Label('Primary page types (top level)');
			$label->appendChild(
				Widget::Input(
					'settings[sitemap][primary_type]',
					General::Sanitize(Symphony::Configuration()->get('primary_type', 'sitemap'))
				)
			);
			$group->appendChild($label);
			
			$label = Widget::Label('Utility page types (global navigation)');
			$label->appendChild(
				Widget::Input(
					'settings[sitemap][utilities_type]',
					General::Sanitize(Symphony::Configuration()->get('utilities_type', 'sitemap'))
				)
			);
			$group->appendChild($label);
			
			$label = Widget::Label('Excluded page types');
			$label->appendChild(
				Widget::Input(
					'settings[sitemap][exclude_type]',
					General::Sanitize(Symphony::Configuration()->get('exclude_type', 'sitemap'))
				)
			);
			$group->appendChild($label);

			$context['wrapper']->appendChild($group);
		}
		
	}

?>