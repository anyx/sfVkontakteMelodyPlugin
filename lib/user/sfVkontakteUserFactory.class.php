<?php
/**
 * sfMelody extension for Vkontakte OAuth2
 * 
 * @author Aleksandr Klimenkov
 * @since 24.07.2011
 */

/**
 * User factory for vkontakte
 */
class sfVkontakteUserFactory extends sfMelodyUserFactory {

	/**
	 * Init user from site
	 * 
	 * @param boolean $save
	 * @return sfGuardUser 
	 */
	protected function createUser($save = false) {
		
		$user = new sfGuardUser();

		$config = $this->getConfig();

		$modified = false;

		$last_call = null;
		$last_result = null;

		$service_config = $this->getService()->getConfig();


		foreach ($config as $field => $field_config) {
			list($call, $call_parameters, $path, $prefix, $suffix) = $this->explodeConfig($field_config);

			if (!is_null($call)) {
				if ($last_call == $call) {
					$result = $last_result;
				} else {

					$result = $this->getService()->get($call, $call_parameters);
					$last_result = $result;
					$last_call = $call;
				}

				$result = $this->getService()->fromPath($result, $path);

				if ($result) {
					$result = $prefix . $result . $suffix;
					$method = 'set' . sfInflector::classify($field);

					if (is_callable(array($user, $method))) {
						$user->$method($result);
						$modified = true;
					}
				}
			}
		}

		if ($save) {
			$user->save();
		}

		return $user;
	}
}