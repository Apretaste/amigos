<?php

use Apretaste\Person;
use Apretaste\Request;
use Apretaste\Response;
use Apretaste\Tutorial;
use Apretaste\Challenges;
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
		$isInfluencer = $request->person->isInfluencer;

		$friends = $request->person->getFriends();

		foreach ($friends as &$friend) {
			$user = Database::queryFirst("SELECT id, username, gender, avatar, avatarColor, online, is_influencer FROM person WHERE id='{$friend}' LIMIT 1");
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
		if (!$isInfluencer) {
			$waiting = $request->person->getFriendRequests();

			foreach ($waiting as $key => &$result) {
				$user = Database::queryFirst("SELECT id, username, gender, avatar, avatarColor, online FROM person WHERE id='{$result->id}' LIMIT 1");
				if (!$user) {
					unset($waiting[$key]);
					continue;
				};

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
			$user = Database::queryFirst("SELECT id, username, gender, avatar, avatarColor, online FROM person WHERE id={$result} LIMIT 1");
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

		$template = $isInfluencer ? 'main-influencer.ejs' : 'main.ejs';
		$response->setCache('hour');
		$response->setTemplate($template, $content);
	}

	/**
	 * user friends list
	 *
	 * @param Request $request
	 * @param Response $response
	 * @throws Alert
	 * @author ricardo
	 */
	public function _listar(Request $request, Response $response)
	{
		$userId = $request->input->data->id ?? false;
		$searched = Person::find($userId);

		if (!$searched) {
			$content = [
				"header" => 'Lo sentimos',
				"text" => "El usuario no fue encontrado.",
				'icon' => "sentiment_very_dissatisfied",
				'btn' => ['command' => 'amigos', 'caption' => 'Ver amigos']
			];

			$response->setCache('hour');
			$response->setTemplate('message.ejs', $content);
		}

		$friends = $searched->getFriends();

		foreach ($friends as &$friend) {
			$user = Database::queryFirst("SELECT id, username, gender, avatar, avatarColor, online, is_influencer FROM person WHERE id='{$friend}' LIMIT 1");
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

		$content = [
			'friends' => $friends,
			'isInfluencer' => $searched->isInfluencer
		];

		$response->setTemplate('listar.ejs', $content);
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
		// get the friend from username
		$username = $request->input->data->username ?? false;
		$user = Person::find($username);

		// if username exist ...
		if ($user) {
			// send request
			$request->person->requestFriend($user->id);

			// complete tutorial
			if ($user->username == 'apretin') {
				Tutorial::complete($request->person->id, 'add_apretin');
			}

			// prepare response
			$content = [
				"header" => 'Solicitud enviada',
				"text" => "Has enviado una solicitud de amistad a @{$user->username}",
				'icon' => "person_add",
				'btn' => ['command' => 'amigos', 'caption' => 'Ver amigos']
			];
		} // if username do not exist ...
		else {
			// prepare response
			$username = str_replace('@', '', $username);
			$content = [
				"header" => 'Lo sentimos',
				"text" => "El usuario @$username no fue encontrado.",
				'icon' => "sentiment_very_dissatisfied",
				'btn' => ['command' => 'amigos', 'caption' => 'Ver amigos']
			];
		}

		// send data to the view
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
		// get the user to add
		$userId = $request->input->data->id ?? false;

		if ($userId) {
			// check previous interactions
			$interactions = Person::getInteractions($request->person->id, $userId, null, false);

			// if no previous friend request, complete challenge
			if (empty($interactions)) {
				Challenges::complete('request-friend', $request->person->id);
			}

			// complete tutorial
			Tutorial::complete($request->person->id, 'find_friends');

			// create the friend request
			$request->person->requestFriend($userId);
		}
	}

	/**
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
		$username = $request->input->data->username;
		if ($userId) {
			$request->person->unblockPerson($userId);
		}

		// prepare response
		$content = [
			"header" => 'Usuario desbloqueado',
			"text" => "Has desbloqueado a @$username",
			'icon' => "lock_open",
			'btn' => ['command' => 'amigos', 'caption' => 'Ver amigos']
		];

		// send data to the view
		$response->setCache('hour');
		$response->setTemplate('message.ejs', $content);
	}
}
