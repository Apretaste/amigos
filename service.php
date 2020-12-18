<?php

use Apretaste\Challenges;
use Apretaste\Person;
use Apretaste\Request;
use Apretaste\Response;
use Framework\Alert;
use Framework\Database;

class Service
{
	/**
	 * user friends list
	 *
	 * @param Request $request
	 * @param Response $response
	 * @throws Alert
	 * @author ricardo
	 */
	public function _main(Request $request, Response $response)
	{
		$isCreator = $request->person->isContentCreator;

		$friends = $request->person->getFriends();

		foreach ($friends as &$friend) {
			$user = Database::queryFirst("SELECT id, username, gender, avatar, avatarColor, online FROM person WHERE id='{$friend}' LIMIT 1");
			if (empty($user)) {
				continue;
			}

			$friend = $user;

			// get the person's avatar
			$friend->avatar = $friend->avatar ?? ($friend->gender === 'F' ? 'chica' : 'hombre');

			// get the person's avatar color
			$friend->avatarColor = $friend->avatarColor ?? 'verde';
		}

		usort($friends, function ($a, $b) {
			return strcmp($a->username, $b->username);
		});

		$waiting = [];
		if (!$isCreator) {
			$waiting = $request->person->getFriendRequests();

			foreach ($waiting as &$result) {
				$user = Database::queryFirst("SELECT id, username, gender, avatar, avatarColor, online FROM person WHERE id='{$result->id}' LIMIT 1");
				$result = (object)array_merge((array)$user, (array)$result);

				// get the person's avatar
				$result->avatar = $result->avatar ?? ($result->gender === 'F' ? 'chica' : 'hombre');

				// get the person's avatar color
				$result->avatarColor = $result->avatarColor ?? 'verde';
			}

			usort($waiting, function ($a, $b) {
				return strcmp($a->username, $b->username);
			});
		}

		$blocked = $request->person->getPeopleBlocked();

		foreach ($blocked as &$result) {
			$user = Database::queryFirst("SELECT id, username, gender, avatar, avatarColor, online FROM person WHERE id='{$result->id}' LIMIT 1");
			$result = (object)array_merge((array)$user, (array)$result);

			// get the person's avatar
			$result->avatar = $result->avatar ?? ($result->gender === 'F' ? 'chica' : 'hombre');

			// get the person's avatar color
			$result->avatarColor = $result->avatarColor ?? 'verde';
		}

		usort($blocked, function ($a, $b) {
			return strcmp($a->username, $b->username);
		});

		$content = [
			'friends' => $friends,
			'waiting' => $waiting,
			'blocked' => $blocked,
		];

		$template = $isCreator ? 'main-cdc.ejs' : 'main.ejs';

		$response->setTemplate($template, $content);
	}

	/**
	 * Search friends by username
	 *
	 * @param Request $request
	 * @param Response $response
	 * @throws Alert
	 * @author ricardo
	 */
	public function _buscar(Request $request, Response $response)
	{
		$username = $request->input->data->username ?? false;
		$user = Person::find($username);
		if ($user) {
			$request->person->requestFriend($user->id);
			$content = [
				"header" => 'Solicitud enviada',
				"text" => "Has enviado una solicitud de amistad a @{$user->username}",
				'icon' => "person_add",
				'btn' => ['command' => 'amigos', 'caption' => 'Ver amigos']
			];
		} else {
			$username = str_replace('@', '', $username);
			$content = [
				"header" => 'Lo sentimos',
				"text" => "El usuario @$username no fue encontrado.",
				'icon' => "sentiment_very_dissatisfied",
				'btn' => ['command' => 'amigos', 'caption' => 'Ver amigos']
			];
		}

		$response->setCache('hour');
		$response->setTemplate('message.ejs', $content);
	}

	/**
	 * Invite a friend to connect
	 *
	 * @param Request $request
	 * @param Response $response
	 * @throws Alert
	 * @author ricardo@apretaste.org
	 */
	public function _agregar(Request $request, Response $response)
	{
		$userId = $request->input->data->id ?? false;
		if ($userId) {

			// check previous interactions
			$interactions = Person::getInteractions($request->person->id, $userId, null, false);
			if (empty($interactions)) {
				Challenges::complete('request-friend', $request->person->id);
			}

			$request->person->requestFriend($userId);
		}
	}

	/**
	 *
	 *
	 * @param Request $request
	 * @param Response $response
	 * @throws Alert
	 * @author ricardo@apretaste.org
	 */
	public function _eliminar(Request $request, Response $response)
	{
		$userId = $request->input->data->id ?? false;
		if ($userId) {
			$request->person->deleteFriend($userId);
		}
	}

	/**
	 *
	 *
	 * @param Request $request
	 * @param Response $response
	 * @throws Alert
	 * @author ricardo@apretaste.org
	 */
	public function _rechazar(Request $request, Response $response)
	{
		$userId = $request->input->data->id ?? false;
		if ($userId) {
			$request->person->deniedFriend($userId);
		}
	}

	/**
	 *
	 *
	 * @param Request $request
	 * @param Response $response
	 * @throws Alert
	 * @author ricardo@apretaste.org
	 */
	public function _bloquear(Request $request, Response $response)
	{
		$userId = $request->input->data->id ?? false;
		if ($userId) {
			$request->person->blockPerson($userId);
		}
	}

	/**
	 *
	 *
	 * @param Request $request
	 * @param Response $response
	 * @throws Alert
	 * @author ricardo@apretaste.org
	 */
	public function _desbloquear(Request $request, Response $response)
	{
		$userId = $request->input->data->id ?? false;
		if ($userId) {
			$request->person->unblockPerson($userId);
		}
	}
}
