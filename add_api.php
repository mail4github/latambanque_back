<?php
require_once(DIR_WS_CLASSES.'send_mail.class.php');

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
		$id_account = '20'.$user_obj->userid;
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
			echo generate_answer(1, '', ['address' => $result_address, 'id_account' => $id_account]);
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