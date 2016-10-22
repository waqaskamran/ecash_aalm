<?php

/**
 * @author Russell Lee <russell.lee@sellingsource.com>
 * @package ECashUI.Attributes
 */
class ECashUI_Pages_Attributes_Save extends ECashUI_Modules_Admin_Page
{
	/**
	 * Processes the given request.
	 *
	 * @param Site_Request $request
	 * @return Site_IResponse
	 */
	public function processRequest(Site_Request $request)
	{
		if (empty($request['attributes']) || !is_array($request['attributes']))
		{
			return new Site_Response_Json(
				array(
					'success' => TRUE,
				)
			);
		}

		$flag = $request['flag'];
		$application_id = $request['application_id'];

		$application = new ECash_Application($application_id);

		// Generic flag remove/set processing.
		foreach ($request['attributes'] as $new)
		{
			$added = FALSE;
			$removed = FALSE;

			$level = empty($new['level']) ? NULL : $new['level'];
			$type = empty($new['type']) ? NULL : $new['type'];
			$value = empty($new['value']) ? NULL : $new['value'];
			$misc = empty($new['misc']) ? NULL : $new['misc'];
			$fields = empty($new['fields']) ? NULL : $new['fields'];

			if (empty($new['checked']))
			{
				$removed = $application->getAttributes()->remove($flag, $type, $value, $level);
			}
			else
			{
				$added = $application->getAttributes()->set($flag, $type, $value, $level, $misc);
			}

			// Flag specific processing.
			if ($flag === 'do_not_contact' && ($removed || $added))
			{
				$comment_prefix = 'DNC ';
				if ($removed)
				{
					$comment_prefix .= 'Removed ';
				}
				$comment_prefix .= '(' . $fields . '): ';

				if ($request['contact_comments'])
				{
					$application->getComments()->add($comment_prefix . $request['contact_comments']);
				}

				if ($request['submit_button'] === 'DoNotContactApplication' && !empty($request['comments']))
				{
					$application->getComments()->add($comment_prefix . $request['comments']);
				}

				if ($removed
					&& !$request['contact_comments']
					&& (
						$request['submit_button'] !== 'DoNotContactApplication'
						|| empty($request['comments'])
					))
				{
					$application->getComments()->add($comment_prefix . 'removed');
				}
			}

			if ($flag === 'login_lock' && ($removed || $added))
			{
				$lock = ECash::getFactory()->getModel('ApplicationLoginLock');
				$loaded = $lock->loadBy(array('application_id' => $application_id));
				if ($loaded)
				{
					$lock->counter = 0;
					$lock->save();
				}
			}
		}

		return new Site_Response_Json(
			array(
				'success' => TRUE,
			)
		);
	}
}

?>
