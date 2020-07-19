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

		$waiting = $request->person->getFriendRequests();

		foreach ($waiting as &$result) {
			$user = Database::queryFirst("SELECT id, username, gender, avatar, avatarColor, online FROM person WHERE id='{$result->id}' LIMIT 1");
			$result = (object)array_merge((array)$user, (array)$result);

			// get the person's avatar
			$result->avatar = $result->avatar ?? ($result->gender === 'F' ? 'chica' : 'hombre');

			// get the person's avatar color
			$result->avatarColor = $result->avatarColor ?? 'verde';
		}

		$content = ['friends' => $friends, 'waiting' => $waiting];

		$response->setTemplate('main.ejs', $content);
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