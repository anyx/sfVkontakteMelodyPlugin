<?php

/**
 * sfMelody extension for Vkontakte OAuth2
 * 
 * @author Aleksandr Klimenkov
 * @since 24.07.2011
 */

/**
 * sfMelody extension for Vkontakte
 */
class sfVkontakteMelody extends sfMelody2 {

	/**
	 * Init Vkontakte melody
	 * 
	 * @param array $config 
	 */
	protected function initialize($config) {

		$this->setRequestAuthUrl('http://api.vk.com/oauth/authorize');
		$this->setAccessTokenUrl('https://api.vkontakte.ru/oauth/access_token');

		$this->setNamespaces(array(
			'default' => 'http://api.vk.com/',
			'api' => 'https://api.vkontakte.ru/method',
		));

		if (isset($config['scope'])) {
			$this->setAuthParameter('scope', $config['scope']);
		}

		$this->prepareProfileCall();
	}

	/**
	 * Prepare before call profile data
	 */
	protected function prepareProfileCall() {

		$this->ns('api');

		if ($this->getToken() == null) {
			return false;
		}

		$this->setCallParameter('uid', $this->getToken()->getParam('user_id'));
		$this->setAlias('me', 'getProfiles');
		$config = $this->getConfig();
		$this->setCallParameter('fields', $config['call_fields']);
	}

	/**
	 * @return sfVkontakteUserFactory
	 */
	public function &getUserFactory() {

		if (empty($this->user_factory)) {
			$config = $this->getConfig();
			$user_config = isset($config['user']) ? $config['user'] : array();

			$this->user_factory = new sfVkontakteUserFactory($this, $user_config);
		}

		return $this->user_factory;
	}

	/**
	 * (non-PHPdoc)
	 * @see plugins/sfDoctrineOAuthPlugin/lib/sfOAuth::getAccessToken()
	 */
	public function getAccessToken($verifier, $parameters = array()) {

		$this->ns('default');

		$url = $this->getAccessTokenUrl();

		$this->setAccessParameter('client_id', $this->getKey());
		$this->setAccessParameter('client_secret', $this->getSecret());
		$this->setAccessParameter('redirect_uri', $this->getCallback());
		$this->setAccessParameter('code', $verifier);

		$this->addAccessParameters($parameters);

		$params = $this->call($url, $this->getAccessParameters(), 'GET');

		$params = json_decode($params, true);

		$access_token = isset($params['access_token']) ? $params['access_token'] : null;

		if (is_null($access_token)) {
			$error = sprintf('{OAuth} access token failed - %s returns %s', $this->getName(), print_r($params, true));
			sfContext::getInstance()->getLogger()->err($error);
		} else {
			$message = sprintf('{OAuth} %s return %s', $this->getName(), print_r($params, true));
			sfContext::getInstance()->getLogger()->info($message);
		}

		$token = new Token();
		$token->setTokenKey($access_token);
		$token->setName($this->getName());
		$token->setStatus(Token::STATUS_ACCESS);
		$token->setOAuthVersion($this->getVersion());

		unset($params['access_token']);

		if (count($params) > 0) {
			$token->setParams($params);
		}

		$this->setExpire($token);

		$this->setToken($token);

		// get identifier maybe need the access token
		$token->setIdentifier($this->getIdentifier());

		$this->setToken($token);

		$this->prepareProfileCall();

		return $token;
	}
}