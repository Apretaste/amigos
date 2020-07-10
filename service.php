<?php

use Apretaste\Challenges;
use Apretaste\Chats;
use Apretaste\Level;
use Apretaste\Notifications;
use Apretaste\Person;
use Apretaste\Request;
use Apretaste\Response;
use Framework\Core;
use Framework\Alert;
use Framework\Database;
use Framework\Images;
use Framework\Utils;

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
		$friends = $request->person->getFriends();

		foreach ($friends as &$friend) {
			$user = Database::queryFirst("SELECT id, username, gender, avatar, avatarColor, online FROM person WHERE id='{$friend}' LIMIT 1");
			$friend = $user;

			// get the person's avatar
			$friend->avatar = $friend->avatar ?? ($friend->gender === 'F' ? 'chica' : 'hombre');

			// get the person's avatar color
			$friend->avatarColor = $friend->avatarColor ?? 'verde';
		}

		$response->setLayout('amigos.ejs');
		$response->setTemplate('main.ejs', ['friends' => $friends, 'title' => 'Amigos']);
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
		$results = [];
		if ($username) {
			$username = Database::escape(str_replace('@', '', $username));
			// only this few columns are needed
			$exactUser = Database::queryCache("SELECT id, username, gender, avatar, avatarColor, online FROM person WHERE username='$username' LIMIT 1");
			if (!empty($exactUser)) {
				$results[] = $exactUser[0];
			} else {
				$results = Database::queryCache("SELECT id, username, gender, avatar, avatarColor, online FROM person WHERE username LIKE('%$username%') LIMIT 10");
			}

			foreach ($results as $result) {
				// get the person's avatar
				$result->avatar = $result->avatar ?? ($result->gender === 'F' ? 'chica' : 'hombre');

				// get the person's avatar color
				$result->avatarColor = $result->avatarColor ?? 'verde';

				$result->relation = 'none';

				// TODO optimize to query only once to know the state
				if ($request->person->isFriendOf($result->id)) $result->relation = 'friend';
				else {
					$waitingRelation = $request->person->getWaitingRelation($result->id);
					if ($waitingRelation) {
						$result->relation = $waitingRelation->user1 == $request->person->id ? 'waiting' : 'waitingForMe';
					}
				}
			}
		}

		$response->setCache('hour');
		$response->setLayout('amigos.ejs');
		$response->setTemplate('search.ejs', ['results' => $results, 'search' => $username]);
	}

	/**
	 *
	 *
	 * @param Request $request
	 * @param Response $response
	 * @throws Alert
	 * @author ricardo@apretaste.org
	 */
	public function _esperando(Request $request, Response $response)
	{
		$waiting = $request->person->getFriendRequests();

		foreach ($waiting as &$result) {
			$user = Database::queryFirst("SELECT id, username, gender, avatar, avatarColor, online FROM person WHERE id='{$result->id}' LIMIT 1");
			$result = (object)array_merge((array)$user, (array)$result);

			// get the person's avatar
			$result->avatar = $result->avatar ?? ($result->gender === 'F' ? 'chica' : 'hombre');

			// get the person's avatar color
			$result->avatarColor = $result->avatarColor ?? 'verde';
		}

		// send data to the view
		$response->setLayout('amigos.ejs');
		$response->setTemplate('waiting.ejs', ['waiting' => $waiting, 'title' => 'Esperando']);
	}

	/**
	 *
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
}