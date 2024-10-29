<?php
class promo_supsysticViewBup extends viewBup {
	public function displayAdminFooter() {
		$this->assign('mainLink', $this->getModule()->getMainLink());
		parent::display('adminFooter');
	}	
	public function showAdminSendStatNote() {
		parent::display('adminSendStatNote');
	}	
	public function getOverviewTabContent() {
		frameBup::_()->addScript('jquery.slimscroll', BUP_JS_PATH. 'jquery.slimscroll.js');
		frameBup::_()->addScript('admin.overview', $this->getModule()->getModPath(). 'js/admin.overview.js');
		frameBup::_()->addStyle('admin.overview', $this->getModule()->getModPath(). 'css/admin.overview.css');
		$this->assign('mainLink', $this->getModule()->getMainLink());
		$this->assign('faqList', $this->getFaqList());
		$this->assign('serverSettings', $this->getServerSettings());
		$this->assign('news', $this->getNewsContent());
		$this->assign('contactFields', $this->getModule()->getContactFormFields());
		return parent::getContent('overviewTabContent');
	}
	public function getFaqList() {
		return array(
			__('How to get PRO version of plugin for FREE?', BUP_LANG_CODE) => sprintf(__('You have an incredible opportunity to get PRO version for free. Make Translation of plugin! It will be amazing if you take advantage of this offer! More info you can find here <a target="_blank" href="%s">"Get PRO version of any plugin for FREE"</a>', BUP_LANG_CODE), $this->getModule()->getMainLink()),
			__('Translation', BUP_LANG_CODE) => sprintf(__('All available languages are provided with the Supsystic Google Maps plugin. If your language isn\'t available, your plugin will be in English by default.<br /><b>Available Translations: English</b><br />Translate or update a translation Backup WordPress plugin in your language and get a Premium license for FREE. <a target="_blank" href="%s">Contact us</a>.', BUP_LANG_CODE), $this->getModule()->getMainLink(). '#contact'),
		);
	}
	public function getNewsContent() {
		// TODO: Finish this
		$getData = wp_remote_get('http://supsystic.com/news/main.html');
		$content = '';
		if($getData 
			&& is_array($getData) 
			&& isset($getData['response']) 
			&& isset($getData['response']['code']) 
			&& $getData['response']['code'] == 200
			&& isset($getData['body'])
			&& !empty($getData['body'])
		) {
			$content = $getData['body'];
		} else {
			$content = sprintf(__('There were some problem while trying to retrive our news, but you can always check all list <a target="_blank" href="%s">here</a>.', BUP_LANG_CODE), frameBup::_()->getModule('promo_supsystic')->getMainLink());
		}
		return $content;
	}
	public function getServerSettings() {
		global $wpdb;
		return array(
			'Operating System' => array('value' => PHP_OS),
            'PHP Version' => array('value' => PHP_VERSION),
            'Server Software' => array('value' => $_SERVER['SERVER_SOFTWARE']),
			'MySQL' => array('value' =>  $wpdb->db_version()),
            'PHP Allow URL Fopen' => array('value' => ini_get('allow_url_fopen') ? __('Yes', BUP_LANG_CODE) : __('No', BUP_LANG_CODE)),
            'PHP Memory Limit' => array('value' => ini_get('memory_limit')),
            'PHP Max Post Size' => array('value' => ini_get('post_max_size')),
            'PHP Max Upload Filesize' => array('value' => ini_get('upload_max_filesize')),
            'PHP Max Script Execute Time' => array('value' => ini_get('max_execution_time')),
            'PHP EXIF Support' => array('value' => extension_loaded('exif') ? __('Yes', BUP_LANG_CODE) : __('No', BUP_LANG_CODE)),
            'PHP EXIF Version' => array('value' => phpversion('exif')),
            'PHP XML Support' => array('value' => extension_loaded('libxml') ? __('Yes', BUP_LANG_CODE) : __('No', BUP_LANG_CODE), 'error' => !extension_loaded('libxml')),
            'PHP CURL Support' => array('value' => extension_loaded('curl') ? __('Yes', BUP_LANG_CODE) : __('No', BUP_LANG_CODE), 'error' => !extension_loaded('curl')),
		);
	}
    public function showReviewAdminNotice() {
        if (is_admin() && frameBup::_()->isPluginAdminPage()) {
            $showReviewBlock = get_option('bupShowReviewBlockV2'); // v2 because was v1 and it don't using now
            $bupShowReviewBlockTimestamp = get_option('bupShowReviewBlockTimestampV2');
            $sendStatAfterSevenDays = get_option('sendStatAfterSevenDays');

            if ($showReviewBlock === false) {
                add_option('bupShowReviewBlockV2', 'yes');
                add_option('sendStatAfterSevenDays', 'yes');
                add_option('bupShowReviewBlockTimestampV2', time());
            } elseif ($showReviewBlock === 'yes' && time() > ($bupShowReviewBlockTimestamp + 86400 * 7)) {
                if ($sendStatAfterSevenDays === 'yes') {
                    $this->getModel()->sendUsageStat(array('code' => 'seven_days_passed', 'visits' => 1,));
                    update_option('sendStatAfterSevenDays', 'no');
                }
                echo parent::getContent('reviewNotice');
            }
        }
    }
	public function showFeaturedPluginsPage() {
		frameBup::_()->getModule('templates')->loadBootstrapSimple();
		frameBup::_()->addStyle('admin.featured-plugins', $this->getModule()->getModPath(). 'css/admin.featured-plugins.css');
		frameBup::_()->getModule('templates')->loadGoogleFont('Montserrat');
		$siteUrl = 'https://supsystic.com/';
		$pluginsUrl = $siteUrl. 'plugins/';
		$uploadsUrl = $siteUrl. 'wp-content/uploads/';
		$downloadsUrl = 'https://downloads.wordpress.org/plugin/';
		$imgUrl = frameBup::_()->getModule('promo_supsystic')->getModPath(). 'img/';
		$promoCampaign = 'backup';
		$this->assign('pluginsList', array(
			array('label' => __('Popup Plugin', BUP_LANG_CODE), 'url' => $pluginsUrl. 'popup-plugin/', 'img' => $uploadsUrl. '2016/07/Popup_256.png', 'desc' => __('The Best WordPress PopUp option plugin to help you gain more subscribers, social followers or advertisement. Responsive pop-ups with friendly options.', BUP_LANG_CODE), 'download' => $downloadsUrl. 'popup-by-supsystic.zip'),
			array('label' => __('Slider Plugin', BUP_LANG_CODE), 'url' => $pluginsUrl. 'slider/', 'img' => $uploadsUrl. '2016/07/Slider_256.png', 'desc' => __('Creating slideshows with Slider plugin is fast and easy. Simply select images from your WordPress Media Library, Flickr, Instagram or Facebook, set slide captions, links and SEO fields all from one page.', BUP_LANG_CODE), 'download' => $downloadsUrl. 'slider-by-supsystic.zip'),
			array('label' => __('Photo Gallery Plugin', BUP_LANG_CODE), 'url' => $pluginsUrl. 'photo-gallery/', 'img' => $uploadsUrl. '2016/07/Gallery_256.png', 'desc' => __('Photo Gallery Plugin with a great number of layouts will help you to create quality respectable portfolios and image galleries.', BUP_LANG_CODE), 'download' => $downloadsUrl. 'gallery-by-supsystic.zip'),
			array('label' => __('Data Tables Generator', BUP_LANG_CODE), 'url' => $pluginsUrl. 'data-tables-generator-plugin/', 'img' => $uploadsUrl. '2016/07/Data_Tables_256.png', 'desc' => __('Create and manage beautiful data tables with custom design. No HTML knowledge is required.', BUP_LANG_CODE), 'download' => $downloadsUrl. 'data-tables-generator-by-supsystic.zip'),
			array('label' => __('Social Share Buttons', BUP_LANG_CODE), 'url' => $pluginsUrl. 'social-share-plugin/', 'img' => $uploadsUrl. '2016/07/Social_Buttons_256.png', 'desc' => __('Social share buttons to increase social traffic and popularity. Social sharing to Facebook, Twitter and other social networks.', BUP_LANG_CODE), 'download' => $downloadsUrl. 'social-share-buttons-by-supsystic.zip'),
			array('label' => __('Live Chat Plugin', BUP_LANG_CODE), 'url' => $pluginsUrl. 'live-chat/', 'img' => $uploadsUrl. '2016/07/Live_Chat_256.png', 'desc' => __('Be closer to your visitors and customers with Live Chat Support by Supsystic. Help you visitors, support them in real-time with exceptional Live Chat WordPress plugin by Supsystic.', BUP_LANG_CODE), 'download' => $downloadsUrl. 'live-chat-by-supsystic.zip'),
			array('label' => __('Pricing Table', BUP_LANG_CODE), 'url' => $pluginsUrl. 'pricing-table/', 'img' => $uploadsUrl. '2016/07/Pricing_Table_256.png', 'desc' => __('It’s never been so easy to create and manage pricing and comparison tables with table builder. Any element of the table can be customise with mouse click.', BUP_LANG_CODE), 'download' => $downloadsUrl. 'pricing-table-by-supsystic.zip'),
			array('label' => __('Coming Soon Plugin', BUP_LANG_CODE), 'url' => $pluginsUrl. 'coming-soon-plugin/', 'img' => $uploadsUrl. '2016/07/Coming_Soon_256.png', 'desc' => __('Coming soon page with drag-and-drop builder or under construction | maintenance mode to notify visitors and collects emails.', BUP_LANG_CODE), 'download' => $downloadsUrl. 'coming-soon-by-supsystic.zip'),
			array('label' => __('Backup Plugin', BUP_LANG_CODE), 'url' => $pluginsUrl. 'backup-plugin/', 'img' => $uploadsUrl. '2016/07/Backup_256.png', 'desc' => __('Backup and Restore WordPress Plugin by Supsystic provides quick and unhitched DropBox, FTP, Amazon S3, Google Drive backup for your WordPress website.', BUP_LANG_CODE), 'download' => $downloadsUrl. 'backup-by-supsystic.zip'),
			array('label' => __('Google Maps Easy', BUP_LANG_CODE), 'url' => $pluginsUrl. 'google-maps-plugin/', 'img' => $uploadsUrl. '2016/07/Google_Maps_256.png', 'desc' => __('Display custom Google Maps. Set markers and locations with text, images, categories and links. Customize google map in a simple and intuitive way.', BUP_LANG_CODE), 'download' => $downloadsUrl. 'google-maps-easy.zip'),
			array('label' => __('Digital Publication Plugin', BUP_LANG_CODE), 'url' => $pluginsUrl. 'digital-publication-plugin/', 'img' => $uploadsUrl. '2016/07/Digital_Publication_256.png', 'desc' => __('Digital Publication WordPress Plugin by Supsystic for Magazines, Catalogs, Portfolios. Convert images, posts, PDF to the page flip book.', BUP_LANG_CODE), 'download' => $downloadsUrl. 'digital-publications-by-supsystic.zip'),
			array('label' => __('Contact Form Plugin', BUP_LANG_CODE), 'url' => $pluginsUrl. 'contact-form-plugin/', 'img' => $uploadsUrl. '2016/07/Contact_Form_256.png', 'desc' => __('One of the best plugin for creating Contact Forms on your WordPress site. Changeable fonts, backgrounds, an option for adding fields etc.', BUP_LANG_CODE), 'download' => $downloadsUrl. 'contact-form-by-supsystic.zip'),
			array('label' => __('Newsletter Plugin', BUP_LANG_CODE), 'url' => $pluginsUrl. 'newsletter-plugin/', 'img' => $uploadsUrl. '2016/08/icon-256x256.png', 'desc' => __('Supsystic Newsletter plugin for automatic mailing of your letters. You will have no need to control it or send them manually. No coding, hard skills or long hours of customizing are required.', BUP_LANG_CODE), 'download' => $downloadsUrl. 'newsletter-by-supsystic.zip'),
			array('label' => __('Membership by Supsystic', BUP_LANG_CODE), 'url' => $pluginsUrl. 'membership-plugin/', 'img' => $uploadsUrl. '2016/09/256.png', 'desc' => __('Create online membership community with custom user profiles, roles, FrontEnd registration and login. Members Directory, activity, groups, messages.', BUP_LANG_CODE), 'download' => $downloadsUrl. 'membership-by-supsystic.zip'),
			array('label' => __('Kinsta Hosting', BUP_LANG_CODE), 'url' => 'https://kinsta.com?kaid=MNRQQASUYJRT', 'external' => true, 'img' => $imgUrl. 'kinsta_banner.png', 'desc' => __('If you want to host a business site or a blog, Kinsta managed WordPress hosting is the best place to stop on. Without any hesitation, we can say Kinsta is incredible when it comes to uptime and speed.', BUP_LANG_CODE)),
		));
		foreach($this->pluginsList as $i => $p) {
			if(empty($p['external'])) {
				$this->pluginsList[ $i ]['url'] = $this->pluginsList[ $i ]['url']. '?utm_source=plugin&utm_medium=featured_plugins&utm_campaign='. $promoCampaign;
			}
		}
		$this->assign('bundleUrl', $siteUrl. 'product/plugins-bundle/'. '?utm_source=plugin&utm_medium=featured_plugins&utm_campaign='. $promoCampaign);
		return parent::getContent('featuredPlugins');
	}
	public function getDiscountMsg($buyLink = '#') {
		$this->assign('bundlePageLink', '//supsystic.com/all-plugins/');
		$this->assign('buyLink', $buyLink);
		parent::display('discountMsg');
	}
}