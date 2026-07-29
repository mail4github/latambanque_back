<?php
if ( !empty($_GET['custom_api']) ) {

    // >>>>>>>>>>>>>>>>>>>>>	
    // Initiate $user_obj
    // >>>>>>>>>>>>>>>>>>>>>
    
    $user_obj = new User();
    $user_obj->userid = 0;
    if (!empty($_POST['bot_userid'])) {
        $user_obj->userid = (int)$_POST['bot_userid'];
    }
    else
    if (!empty($_POST['userid'])) {
        $user_obj->userid = (int)$_POST['userid'];
    }
    $user_obj->email = tep_sanitize_string($_POST['email']);

    if ( !empty($_POST['token']) && $user_obj->read_data(false) ) {
        $user_obj->token_valid = check_token($user_obj);
    }
    
    /**
     * Get crypto address to deposit funds.
     *
     * @Route("/api/custom_api/", name="get_crypto_addr", methods={"POST"})
     *
     * @param custom_command            (required)  get_crypto_addr
	 * @param currency					(required)	'btc' | 'usdt'
     *
     * @return Json string
     */
    if ( @$_POST['custom_command'] == 'get_crypto_addr' ) {

        // Getting address from DB first
        $make_post_request = false;
		$res = $user_obj->get_request_to_add_funds(
			0, 
			$_POST['currency'], 
			'', // note
			$make_post_request,
			ADD_FUNDS_PREFIX, // transaction_prefix
			'none', //pay_email 
			$_POST['currency'],
			$_POST['currency'], 
			'', // invoice_suffix
			1, // return_post_request_as_text
			1, // force_to_use_pay_email
			'', //ip
			$_SERVER['REMOTE_ADDR'], 
			1, // return_data_as_array
			0, // generate_new_address
		);
		if ( $res && !empty($res['pay_email']) ) {
			tep_db_perform(TABLE_USERS, [
					'paypalemail' => $res['pay_email'],
					'payoutoption' => tep_sanitize_string($_POST['currency'], 45),
				], 
				'update', 'userid = "'.$user_obj->userid.'"'
			);
			echo generate_answer(1, '', ['address' => $res['pay_email']]);
        	exit;
		}

        // if there is no saved address then get address from third party source
        $currency = new Currency();
        $currency->read_data($_POST['currency']);
        $result_address = '';
        $result_error = '';
        // Getting a PHP code from DB. This code is receiving address from a third party API
        $eval_code = $currency->blocks_explorer;
        $currency_code = strtoupper($_POST['currency']);
        $debug_info = '';

        if (!empty($eval_code)) {
            eval($eval_code);
        }

        if (!empty($result_address)) {
            $currency = tep_sanitize_string($_POST['currency'], 10);
            $invoiceNmb = INVOICE_PREFIX.ADD_FUNDS_PREFIX.USERID_PREFIX.$user_obj->userid.WEBSITE_NUMBER_PREFIX.WEBSITE_NUMBER.WEBSITE_NUMBER_SUFFIX;
            $invoiceNmb = tep_sanitize_string($invoiceNmb.'_'.date("ymdHis").rand(1, 99), 64);
            tep_db_perform(TABLE_PAY_ATTEMPTS, [
                'userid' => $user_obj->userid,
                'invoice' => $invoiceNmb,
                'ip' => '',
                '_created' => 'now()',
                'country' => '',
                'total' => 0,
                'received' => 0,
                'note' => '',
                'pay_email' => $result_address,
                'priv_key' => 'none',
                'payment_method' => $currency,
                'currency' => strtoupper($currency),
                'total_in_currency' => 0,
                'serviceid' => 0,
                'siteid' => '',
            ]);
			echo generate_answer(1, '', ['address' => $result_address]);
        	exit;
        }
        if (!empty($result_error)) {
            echo generate_answer(0, $result_error);
        }
        else {
            echo generate_answer(0, 'Error: no answer from API');
        }
        exit;
    }
}
?>