<?php
class promo_supsysticControllerBup extends controllerBup {
	public function bupSendInfo(){
		$res = new responseBup();
		if($this->getModel()->welcomePageSaveInfo(reqBup::get('post'))) {
			$res->addMessage(__('Information was saved. Thank you!', BUP_LANG_CODE));
		} else {
			$res->pushError($this->getModel()->getErrors());
		}
		$originalPage = reqBup::getVar('original_page');
		//$return = $this->getModule()->decodeSlug(str_replace('return=', '', $originalPage));
		$return = admin_url( strpos($originalPage, '?') ? $originalPage : 'admin.php?page='. $originalPage);
		// Start usage in any case
        redirectBup($return);
		return $res->ajaxExec();
	}

	public function getPermissions() {
		return array(
			BUP_USERLEVELS => array(
				BUP_ADMIN => array('bupSendInfo', 'sendContact')
			),
		);
	}

    public function getPromoScheduleAction() {
        return $this->render('schedulePromo');
    }

    public function getPromoMigrationAction() {
        return $this->render('migrationPromo');
    }

    public function sendStatistic(){
        $res = new responseBup();
        $req = reqBup::get('post');
        $statisticCode = !empty($req['statisticCode']) ? $req['statisticCode'] : null;
        if($statisticCode) {
            $this->getModel()->sendUsageStat(array('code' => $statisticCode, 'visits' => 1,));
            if($statisticCode === 'maybe_later_leave_feedback' || $statisticCode === 'leaved_feedback')
                update_option('bupShowReviewBlockV2', 'no');
        } else {
            $res->addError('unexpectedError');
        }
        return $res->ajaxExec();
    }

    /**
     *
     * @param  string $template
     * @param  array  $data
     * @return string
     */
    public function render($template, $data = array()) {
        return $this->getView()->getContent($template, $data);
    }
	
	public function sendContact() {
		$res = new responseBup();
		$time = time();
		$prevSendTime = (int) get_option(BUP_CODE. '_last__time_contact_send');
		if($prevSendTime && ($time - $prevSendTime) < 5 * 60) {	// Only one message per five minutes
			$res->pushError(__('Please don\'t send contact requests so often - wait for response for your previous requests.'));
			$res->ajaxExec();
		}
        $data = reqBup::get('post');
        $fields = $this->getModule()->getContactFormFields();
		foreach($fields as $fName => $fData) {
			$validate = isset($fData['validate']) ? $fData['validate'] : false;
			$data[ $fName ] = isset($data[ $fName ]) ? trim($data[ $fName ]) : '';
			if($validate) {
				$error = '';
				foreach($validate as $v) {
					if(!empty($error))
						break;
					switch($v) {
						case 'notEmpty':
							if(empty($data[ $fName ])) {
								$error = $fData['html'] == 'selectbox' ? __('Please select %s', BUP_LANG_CODE) : __('Please enter %s', BUP_LANG_CODE);
								$error = sprintf($error, $fData['label']);
							}
							break;
						case 'email':
							if(!is_email($data[ $fName ])) 
								$error = __('Please enter valid email address', BUP_LANG_CODE);
							break;
					}
					if(!empty($error)) {
						$res->pushError($error, $fName);
					}
				}
			}
		}
		if(!$res->error()) {
			$msg = 'Message from: '. get_bloginfo('name').', Host: '. $_SERVER['HTTP_HOST']. '<br />';
			$msg .= 'Plugin: '. BUP_WP_PLUGIN_NAME. '<br />';
			foreach($fields as $fName => $fData) {
				if(in_array($fName, array('name', 'email', 'subject'))) continue;
				if($fName == 'category')
					$data[ $fName ] = $fData['options'][ $data[ $fName ] ];
                $msg .= '<b>'. $fData['label']. '</b>: '. nl2br($data[ $fName ]). '<br />';
            }
			if(frameBup::_()->getModule('mail')->send('support@supsystic.zendesk.com', $data['subject'], $msg, $data['name'], $data['email'])) {
				update_option(BUP_CODE. '_last__time_contact_send', $time);
			} else {
				$res->pushError( frameBup::_()->getModule('mail')->getMailErrors() );
			}
			
		}
        $res->ajaxExec();
	}
}