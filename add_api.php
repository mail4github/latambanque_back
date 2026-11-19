<?php
require_once(DIR_WS_CLASSES.'send_mail.class.php');

global $ranks;
$ranks = array(
	array('name' => 'newbie',    'sell_shares' => 1, 'cashout' => 39,  'min_cashout' => 1, 'max_cashout' => 1000000, 'feedbacks' => 0, 'expert_job' => 0, 'arbitr' => 0, 'upgrade' => 00.00, 'ransom' => 1, 'referrals_off' => 0, 'traffic_off' => 0, 'PTC_bonus' => 0, 'withdrawal_fee' => WITHDRAWAL_FEE * 6, 'max_number_of_jobs' => 5, 'buy_from_balance' => 0, 'can_buy_shares' => 1, 'pay_for_clicks' => 1),
	array('name' => 'amateur',   'sell_shares' => 1, 'cashout' => 5,   'min_cashout' => 1, 'max_cashout' => 1000000, 'feedbacks' => 1, 'expert_job' => 0, 'arbitr' => 0, 'upgrade' => 5.99, 'ransom' => 1, 'referrals_off' => 0, 'traffic_off' => 0, 'PTC_bonus' => 0, 'withdrawal_fee' => WITHDRAWAL_FEE * 5, 'max_number_of_jobs' => 10, 'buy_from_balance' => 0, 'can_buy_shares' => 1, 'pay_for_clicks' => 1),
	array('name' => 'investor',  'sell_shares' => 1, 'cashout' => 3,   'min_cashout' => 1, 'max_cashout' => 1000000, 'feedbacks' => 1, 'expert_job' => 0, 'arbitr' => 0, 'upgrade' => 9.99, 'ransom' => 1, 'referrals_off' => 0.05, 'traffic_off' => 0.05, 'PTC_bonus' => 0, 'withdrawal_fee' => WITHDRAWAL_FEE * 4, 'max_number_of_jobs' => 15, 'buy_from_balance' => 1, 'can_buy_shares' => 1, 'pay_for_clicks' => 1),
	array('name' => 'broker',    'sell_shares' => 1, 'cashout' => 1,   'min_cashout' => 1, 'max_cashout' => 1000000, 'feedbacks' => 1, 'expert_job' => 1, 'arbitr' => 0, 'upgrade' => 19.99, 'ransom' => 1, 'referrals_off' => 0.10, 'traffic_off' => 0.10, 'PTC_bonus' => 0, 'withdrawal_fee' => WITHDRAWAL_FEE * 3, 'max_number_of_jobs' => 20, 'buy_from_balance' => 1, 'can_buy_shares' => 1, 'pay_for_clicks' => 0),
	array('name' => 'manager',   'sell_shares' => 1, 'cashout' => 1,   'min_cashout' => 1, 'max_cashout' => 1000000, 'feedbacks' => 1, 'expert_job' => 1, 'arbitr' => 1, 'upgrade' => 29.99, 'ransom' => 1, 'referrals_off' => 0.15, 'traffic_off' => 0.20, 'PTC_bonus' => 0, 'withdrawal_fee' => WITHDRAWAL_FEE * 2, 'max_number_of_jobs' => 25, 'buy_from_balance' => 1, 'can_buy_shares' => 1, 'pay_for_clicks' => 0),
	array('name' => 'master',    'sell_shares' => 1, 'cashout' => 0.1, 'min_cashout' => 1, 'max_cashout' => 1000000, 'feedbacks' => 1, 'expert_job' => 1, 'arbitr' => 1, 'upgrade' => 39.99, 'ransom' => 1, 'referrals_off' => 0.20, 'traffic_off' => 0.30, 'PTC_bonus' => 0, 'withdrawal_fee' => WITHDRAWAL_FEE, 'max_number_of_jobs' => 30, 'buy_from_balance' => 1, 'can_buy_shares' => 1, 'pay_for_clicks' => 0),
);

if ( !empty($_GET['custom_api']) ) {
	/**
	 * Payment callback
	 *
	 * @Route("/api/custom_api/", name="payment_callback", methods={"POST"})
	 *
	 * @param custom_command            (required)  payment_callback
	 * @param id_account				(required)
	 * @param amount					(required)
	 * @param coin						(required)
	 * @param txId						(required)
	 * @param address					(required)
	 * @param mint						(required)
	 * @return Json string
	 */
	if ( @$_POST['custom_command'] == 'payment_callback' ) {
		WriteToLogFile("GET: ".json_encode($_GET).', POST: '.json_encode($_POST), 'payment_callback');
		
		if (empty($_POST['id_account']) || empty($_POST['address']) || empty($_POST['txId']) || empty($_POST['coin']) || empty($_POST['amount'])) {
			echo generate_answer(0, 'Error: empty id_account or amount or coin or txId or address or mint');
			exit;
		}

		$user = new User();
		$user->userid = intval($_POST['id_account']);
		if ( !$user->read_data(false) ) {
			echo generate_answer(0, 'Error: no such user');
			exit;
		}

		tep_db_begin_transaction();
		$res = $user->reward(
			floatval($_POST['amount']), 
			tep_sanitize_string('from payment_callback.php, mint: '.$_POST['mint']), 
			tep_sanitize_string($_POST['coin']), 
			'AF', 
			0, // hold_reward_for_days
			0, // parent_transactionid
			tep_sanitize_string($_POST['txId']),
			tep_sanitize_string($_POST['address'])
		);

		if (!$res) {
			echo generate_answer(0, 'This transaction processed already');
			exit;
		}

		$ticket = new ticket();
		$ticket->open_ticket("&#10071; user has been rewarded", "Manager: ".$manager_user->userid." rewarded user ".$user->userid." for ".$_POST['amount']." ".tep_sanitize_string($_POST['coin']), MANAGER_ID, GENERAL_AFF_MANAGER_USERID);

		tep_db_finish_transaction();

		echo generate_answer(1);
		exit;
	}
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
	 * Get crypto address to deposit funds or the fiat currency account number.
	 *
	 * @Route("/api/custom_api/", name="get_account_number", methods={"POST"})
	 *
	 * @param custom_command            (required)  get_account_number
	 * @param currency					(required)	'btc' | 'usdt'
	 * @param create_if_not_exists		            1 | 0
	 * @return Json string
	 */
	if ( @$_POST['custom_command'] == 'get_account_number' ) {

		if ( empty($_POST['currency']) ) {
			echo generate_answer(0, 'currency is empty' , '', 'ERROR_EMPTY_CURRENCY');
			exit;
		}
		$id_account = '20'.$user_obj->userid;

		$currency = new Currency();
		if ( !$currency->read_data($_POST['currency']) ) {
			echo generate_answer(0, 'no such currency' , '', 'ERROR_WRONG_CURRENCY');
			exit;
		}

		$bank_data = '';

		// Getting address from DB first
		$result_address = '';
		$pay_attempt_arr = tep_db_perform_one_row('SELECT * FROM '.TABLE_PAY_ATTEMPTS.' WHERE userid = "'.$user_obj->userid.'" AND UPPER(currency) = "'.strtoupper(tep_sanitize_string($_POST['currency'], 10)).'" ORDER BY created DESC LIMIT 1');
		
		if ( !empty($pay_attempt_arr) ) {
			$result_address = $pay_attempt_arr['pay_email'];
			$bank_data = $pay_attempt_arr['note'];
			tep_db_perform(TABLE_USERS, [
					'paypalemail' => $result_address,
					'payoutoption' => tep_sanitize_string($_POST['currency'], 45),
				], 
				'update', 'userid = "'.$user_obj->userid.'"'
			);
		}
		else {
			// if there is no saved address then get address from third party source
			$result_address = '';
			$result_error = '';
			// Getting a PHP code from DB. This code is receiving address from a third party API
			$eval_code = $currency->blocks_explorer;
			$currency_code = strtoupper($_POST['currency']);
			$debug_info = '';

			if (!empty($eval_code)) {
				eval($eval_code);
			}

			if ( empty($result_address) && intval($_POST['create_if_not_exists']) ) {
				$generate_new_account_number = true;
				if (!empty($eval_code)) {
					global $result_address;
					eval($eval_code);
				}
			}

			if ( !empty($result_address) ) {
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
					'payment_method' => strtolower(tep_sanitize_string($_POST['currency'], 10)),
					'currency' => strtoupper(tep_sanitize_string($_POST['currency'], 10)),
					'total_in_currency' => 0,
					'serviceid' => 0,
					'siteid' => '',
				]);
			}
		}
		echo generate_answer(1, '', [
			'address' => $result_address,
			'id_account' => $id_account,
			'bank_data' => $bank_data
		]);
		exit;
	}
	else
	/**
	 * Set user account bank data
	 *
	 * @Route("/api/custom_api/", name="admin_update_user_account", methods={"POST"})
	 *
	 * @param custom_command            (required)  admin_update_user_account
	 *
	 * @return Json string
	 */
	if ( @$_POST['custom_command'] == 'admin_update_user_account' ) {

		check_credentials(PERMISSION_MANAGER, $user_obj);

		if ( empty($_POST['currency']) ) {
			echo generate_answer(0, 'currency is empty' , '', 'ERROR_EMPTY_CURRENCY');
			exit;
		}

		if ( empty($_POST['acc_number']) ) {
			echo generate_answer(0, 'acc_number is empty' , '', 'ERROR_EMPTY_ACC_NUMBER');
			exit;
		}

		// Getting bank data from DB
		$pay_attempt_arr = tep_db_perform_one_row('SELECT * FROM '.TABLE_PAY_ATTEMPTS.' WHERE userid = "'.$user_obj->userid.'" AND UPPER(currency) = "'.strtoupper(tep_sanitize_string($_POST['currency'], 10)).'" ORDER BY created DESC LIMIT 1');
		if ( !empty($pay_attempt_arr) ) {
			
			tep_db_perform(TABLE_PAY_ATTEMPTS, [
					'pay_email' => tep_sanitize_string($_POST['acc_number']),
					'note' => tep_sanitize_string($_POST['bank_data'], replace_quotes: false),
				], 
				'update', 
				'attemptid = "'.$pay_attempt_arr['attemptid'].'"'
			);
		}
		else {
			echo generate_answer(0, 'currency not found' , '', 'ERROR_CURRENCY_NOT_FOUND');
			exit;
		}

		echo generate_answer(1);
		exit;
	}
	else
	/**
	 * Send notification to user.
	 *
	 * @Route("/api/custom_api/", name="send_notification", methods={"POST"})
	 *
	 * @param custom_command            (required)  send_notification
	 * @param for_userid                (required)	str
	 * @param subject					(required)	str
	 * @param message					(required)	str
	 *
	 * @return Json string
	 */
	if ( @$_POST['custom_command'] == 'send_notification' ) {

		check_credentials(PERMISSION_MANAGER, $user_obj);

		if ( empty($_POST['for_userid']) ) {
			echo generate_answer(0, 'for_userid is empty' , '', 'ERROR_EMPTY_FOR_USERID');
			exit;
		}
		if ( empty($_POST['subject']) ) {
			echo generate_answer(0, 'subject is empty' , '', 'ERROR_EMPTY_SUBJECT');
			exit;
		}
		if ( empty($_POST['message']) ) {
			echo generate_answer(0, 'message is empty' , '', 'ERROR_EMPTY_MESSAGE');
			exit;
		}

		$mail = new send_mail();
		$mail->save_email_to_db(
			intval($_POST['for_userid']), 
			tep_sanitize_string($_POST['subject']), // subject
			tep_sanitize_string($_POST['message']), // html_body
			$user_obj->userid // sender_userid
		);

		echo generate_answer(1, '', []);
		exit;
	}
	else
	/**
	 * Get list of chats by admin
	 *
	 * @Route("/api/custom_api/", name="admin_get_chats_list", methods={"POST"})
	 *
	 * @param custom_command            (required)  admin_get_chats_list
	 *
	 * @return Json string
	 */
	if ( @$_POST['custom_command'] == 'admin_get_chats_list' ) {

		check_credentials(PERMISSION_MANAGER, $user_obj);

		$total_unread = 0;

		$q = '
		SELECT 
			recepient AS userId,
			`name`,
			docNumber,
			SUM(total_messages) AS total,
			0 AS unread,
			lastText,
			IF(LOCATE(",", sender_permissions) OR LOCATE("MNG", sender_permissions), "support", "user") AS lastFrom,
			UNIX_TIMESTAMP(created) AS lastUnixDate,
			DATE_FORMAT(created, "%Y-%m-%dT%H:%i:%s.000Z") AS lastDate
		FROM (
			SELECT *,
				GROUP_CONCAT( permissionid SEPARATOR ",") AS sender_permissions
			FROM (
				SELECT 
					tb2.userid AS sender,
					tb2.wall_userid AS recepient,
					tb2.text AS lastText,
					tb2.created,
					tb2.total_messages,
					CONCAT(users.firstname, " ", users.lastname) AS name,
					users.email AS docNumber,
					permissionid
				FROM (
					SELECT 
						userid,
						wall_userid,
						text,
						created,
						tb1.total_messages
					FROM '.TABLE_FORUM_TOPICS.' AS forum_topics
					INNER JOIN (
						SELECT 
							userid AS tb_userid,
							MAX(created) AS latest,
							COUNT(*) AS total_messages,
                        	wall_userid AS tb_wall_userid
						FROM '.TABLE_FORUM_TOPICS.' 
						GROUP BY 
							wall_userid
					) AS tb1
					ON forum_topics.created = tb1.latest AND forum_topics.wall_userid = tb1.tb_wall_userid
					ORDER BY created DESC
				) AS tb2 
				LEFT JOIN '.TABLE_USERS.' AS users ON users.userid = tb2.wall_userid
				LEFT JOIN '.TABLE_USERS_PERMISSIONS.' AS users_permissions ON users_permissions.userid = tb2.userid
			) AS tb5
			GROUP BY sender, recepient
		) AS tb3
		GROUP BY recepient 
		ORDER BY IF(LOCATE(",", sender_permissions) OR LOCATE("MNG", sender_permissions), DATE_SUB(created, INTERVAL 1 YEAR), created) DESC
		';
		$res_arr = array();
		if ( $r = tep_db_query($q) ) {
			while ( $row = tep_db_fetch_array($r) ) {
				$res_arr[] = $row;
				if ( intval($row['unread']) ) {
					$total_unread++;
				}
			}
		}
		
		echo generate_answer(1, '', ['chats' => $res_arr, 'totalUnread' => $total_unread]);
		exit;
	}
	
}
?>