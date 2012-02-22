<?php
/**
 * Description of LangSelector
 *
 * @author jon
 */
class My_Controller_Plugin_LangSelector extends Zend_Controller_Plugin_Abstract
{
	public function preDispatch(Zend_Controller_Request_Abstract $request)
	{
		/*
		$lang = $request->getParam('lang','');

		if ($lang !== 'en' && $lang !== 'fr')
			$request->setParam('lang','en');

		$lang = $request->getParam('lang');
		if ($lang == 'en')
			$locale = 'en_CA';
		else
			$locale = 'fr_CA';

		
		$zl->setLocale($locale);
		Zend_Registry::set('Zend_Locale', $zl);

		$translate = new Zend_Translate('csv', APPLICATION_PATH . '/configs/lang/'. $lang . '.csv' , $lang);
		*/
		
		$translator = new Zend_Translate( array(
				'adapter' => 'csv',
				'content' => APPLICATION_PATH.'/language/en/kc.csv',
				'locale'  => 'en'
		));
		
		$locale = new Zend_Locale();
		$language = $locale->getLanguage();
		
		$translator->addTranslation(
				array(
						'content' => APPLICATION_PATH."/language/$language/kc.csv",
						'locale'  => $language,
						'route'   => array($language => 'en')
				)
		);
		$translator->setLocale($language);
		
		$front = Zend_Controller_Front::getInstance();
		$bootstrap = $front->getParam('bootstrap');
		$view = $bootstrap->getResource('view');
		$view->translator = $translator;
	}

}