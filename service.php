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
		$page = $request->input->data->page ?? 1;
		$friendsCount = $request->person->getFriendsCount();
		$pages = ceil($friendsCount / 50);

		$friends = $request->person->getFriends(false, $page);

		foreach ($friends as &$friend) {
			$user = Database::queryFirst("SELECT id, username, gender, avatar, avatarColor, online, is_influencer FROM person WHERE id='{$friend}'");
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
			'friendsCount' => $friendsCount,
			'page' => $page,
			'pages' => $pages,
			'title' => 'Amigos',
			'isInfluencer' => $isInfluencer
		];

		$response->setLayout('amigos.ejs');
		$response->setTemplate('main.ejs', $content);
	}

	/**
	 * user friends list
	 *
	 * @param Request $request
	 * @param Response $response
	 * @throws Alert
	 * @author ricardo
	 */
	public function _waiting(Request $request, Response $response)
	{
		$isInfluencer = $request->person->isInfluencer;
		$page = $request->input->data->page ?? 1;
		$waitingCount = $request->person->getFriendRequestsCount();
		$pages = ceil($waitingCount / 50);

		$waiting = [];
		if (!$isInfluencer) {
			$waiting = $request->person->getFriendRequests(false, $page);

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

		$content = [
			'waiting' => $waiting,
			'waitingCount' => $waitingCount,
			'page' => $page,
			'pages' => $pages,
			'title' => 'Solicitudes',
			'isInfluencer' => $isInfluencer
		];

		$response->setLayout('amigos.ejs');
		$response->setTemplate('waiting.ejs', $content);
	}

	/**
	 * user friends list
	 *
	 * @param Request $request
	 * @param Response $response
	 * @throws Alert
	 * @throws \Apretaste\Alert
	 * @author ricardo
	 */
	public function _blocked(Request $request, Response $response)
	{
		$isInfluencer = $request->person->isInfluencer;
		$page = $request->input->data->page ?? 1;
		$blockedCount = $request->person->getPeopleBlockedCount();
		$pages = ceil($blockedCount / 50);

		$blocked = $request->person->getPeopleBlocked(true, $page, 50, false);

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
			'blocked' => $blocked,
			'blockedCount' => $blockedCount,
			'page' => $page,
			'pages' => $pages,
			'title' => 'Bloqueados',
			'isInfluencer' => $isInfluencer
		];

		$response->setLayout('amigos.ejs');
		$response->setTemplate('blocked.ejs', $content);
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
	 * @param Request $request
	 * @param Response $response
	 * @throws Alert
	 * @throws \Apretaste\Alert
	 */
	public function _busqueda(Request $request, Response $response) {
		$response->setTemplate('searchForm.ejs', [
			'username' => '',
			'email' => '',
			'cellphone' => '',
			'fullName' => '',
			'gender' => '',
			'ageFrom' => 0,
			'ageTo' => 0,
			'province' => '',
			'sexual_orientation' => '',
			'religion' => ''
		]);
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
		$limit  = 20;
		$offset = 0;
		$where = '';

		$username = Database::escape($request->input->data->username ?? '');
		$email = Database::escape($request->input->data->email ?? '');
		$cellphone = Database::escape($request->input->data->cellphone ?? '');
		$gender = Database::escape($request->input->data->gender ?? '');
		$ageFrom = (int) Database::escape($request->input->data->ageFrom ?? '');
		$ageTo = (int) Database::escape($request->input->data->ageTo ?? '');
		$province = Database::escape($request->input->data->profince ?? '');
		$sexual_orientation = Database::escape($request->input->data->sexual_orientation ?? '');
		$religion = Database::escape($request->input->data->religion ?? '');

		$chips = [$username, $email, $cellphone, $gender, $province, $sexual_orientation, $religion];
		if (!empty($ageFrom)) $chips[] = 'de '.$ageFrom.' a '.$ageTo;

		$chips = array_filter($chips, function($value){
			if (empty($value)) return false;
			return true;
		});

		$results = Database::query("SELECT * FROM (SELECT 
										person.id, person.active, person.online, person.last_access,
										IF(person.username like '%$username%', 1, 0) AS match_username,
										IF(email like '%$email%', 1 ,0) AS match_email,
										IF(cellphone like '%$cellphone%', 1, 0) AS match_cellphone,
										IF(gender = '$gender', 1, 0) AS match_gender,
										IF(province = '$province', 1, 0) AS match_province,
										IF(sexual_orientation = '$sexual_orientation', 1, 0) AS match_sexual,
										IF(religion = '$religion', 1,0) AS match_religion,
										IF(year_of_birth IS NULL OR IFNULL(YEAR(NOW())-year_of_birth,0) >= $ageFrom, 1, 0) AS match_age_from,
										IF(year_of_birth IS NULL OR IFNULL(YEAR(NOW())-year_of_birth,0) <= $ageTo, 1, 0) AS match_age_to,
										B.user1 IS NOT NULL as friend 
									FROM person 
    								LEFT JOIN person_relation_friend B ON (person.id = B.user1 AND B.user2 = {$request->person->id}) 
    								           OR (person.id = B.user2 AND B.user1 = {$request->person->id})
									LEFT JOIN person_relation_blocked K ON (person.id = K.user1 AND K.user2 = {$request->person->id}) 
    								           OR  (person.id = K.user2 AND K.user1 = {$request->person->id})
									WHERE K.user1 IS NULL) subq WHERE TRUE "
									. (empty($username) ? '' : " AND match_username = 1 ")
									. (empty($email) ? '' : " AND match_email = 1 ")
									. (empty($cellphone) ? '' : " AND match_cellphone = 1")
									. (empty($gender) ? '' : " AND match_gender = 1 ")
									. (empty($sexual_orientation) ? '' : " AND match_sexual = 1 ")
									. (empty($province) ? '' : " AND match_province = 1 ")
									. (empty($religion) ? '' : " AND match_religion = 1 ")
									. (empty($ageFrom) ? '' : " AND match_age_from = 1 ")
									. (empty($ageTo) ? '' : " AND match_age_to = 1 ")
									. " ORDER BY match_username + match_email + match_cellphone + match_gender 
										+ match_sexual + match_province + match_religion + match_age_from + match_age_to DESC, active DESC, online DESC, last_access DESC  
									LIMIT $offset, $limit ");

		$newResults = [];
		foreach ($results as $item) {
			$person = Person::find($item->id);
			$newResults[] = (object) [
				'id' => $person->id,
				'username' => $person->username,
				'fullName' => $person->fullName,
				'avatar' => $person->avatar,
				'avatarColor' => $person->avatarColor,
				'experience' => $person->experience,
				'friend' => (int) $item->friend == 1
			];
		}

		$response->setTemplate('searchResults.ejs', [
			'results' => $newResults,
			'chips' => $chips
		]);
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
