<?php

use Apretaste\Config;
use Apretaste\Core;
use Apretaste\Email;
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
		$response->setCache('year');
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
		$limit  = 50;
		$offset = 0;

		$username = trim(Database::escape($request->input->data->username ?? ''));
		$fullname = trim(Database::escape($request->input->data->fullname ?? ''));
		$email = trim(Database::escape($request->input->data->email ?? ''));
		$cellphone = trim(Database::escape($request->input->data->cellphone ?? ''));
		$gender = Database::escape($request->input->data->gender ?? '');
		$ageFrom = (int) Database::escape($request->input->data->ageFrom ?? '');
		$ageTo = (int) Database::escape($request->input->data->ageTo ?? '');
		$province = Database::escape($request->input->data->profince ?? '');
		$sexual_orientation = Database::escape($request->input->data->sexual_orientation ?? '');
		$religion = Database::escape($request->input->data->religion ?? '');

		$chips = [$username, $email, $cellphone, $fullname,
			Core::$gender[$gender] ?? '', $province,
			Core::$sexualOrientation[$sexual_orientation] ?? '',
			Core::$religions[$religion] ?? ''];

		$username = str_replace('@','', $username);

		if ($ageFrom>0 && $ageTo>0) $chips[] = $ageFrom.' a '.$ageTo. ' años';
		elseif($ageFrom==0 && $ageTo>0) $chips[] = ' menores de '.$ageTo. ' años';
		elseif($ageFrom > 0 && $ageTo==0) $chips[] = ' mayores de '.$ageFrom. ' años';

		$chips = array_filter($chips, function($value){
			if (empty($value)) return false;
			return true;
		});

		$fullname = strtolower(trim($fullname));

		while(strpos($fullname,'  ')!==false) {
			$fullname = str_replace('  ',' ', $fullname);
		}

		$words = explode(' ', $fullname);
		$i = 0;
		$xwords = [];
		$t = count($words);
		foreach($words as $word) {
			$i++;
			$xwords[] = " IF(concat(first_name,' ', middle_name, ' ',last_name,' ',mother_name) LIKE '%$word%', ".($t + 1 - $i).",0)  as w$i ";
		}

		$wordsSQL = implode(', ', $xwords);

		$wordsSum = '0';
		for($j=1; $j<=$i; $j++) $wordsSum .= "+w$j";

		$results = Database::query("SELECT * FROM (SELECT 
										person.id, person.active, person.online, person.last_access, person.gender,
										". (empty($username) ? '0 as match_username,' : "IF(person.username ='$username', 1, 0) AS match_username,")."
                      					".($i < 1 ? "": $wordsSQL.",")."
										". (empty($email) ? '0 as match_email,' : "IF(email = '$email', 1 ,0) AS match_email,")."
										". (empty($cellphone) ? '0 as match_cellphone,' : "IF(cellphone = '$cellphone', 1, 0) AS match_cellphone,")."
										". (empty($gender) ? '0 as match_gender,' : "IF(gender = '$gender', 1, 0) AS match_gender,")."
										". (empty($province) ? '0 as match_province,' : "IF(province = '$province', 1, 0) AS match_province,")."
										". (empty($sexual_orientation) ? '0 as match_sexual,' : "IF(sexual_orientation = '$sexual_orientation', 1, 0) AS match_sexual,")."
										". (empty($religion) ? '0 as match_religion,' : "IF(religion = '$religion', 1,0) AS match_religion,")."
										". (empty($ageFrom) ? '0 as match_age_from,' : "IF(year_of_birth IS NULL OR IFNULL(YEAR(NOW())-year_of_birth,0) >= $ageFrom, 1, 0) AS match_age_from,")."
										". (empty($ageTo) ? '0 as match_age_to,' : "IF(year_of_birth IS NULL OR IFNULL(YEAR(NOW())-year_of_birth,0) <= $ageTo, 1, 0) AS match_age_to,")."
										B.user1 IS NOT NULL as friend,
                      					W.user1 IS NOT NULL as waiting
									FROM person 
    								LEFT JOIN person_relation_friend B ON (person.id = B.user1 AND B.user2 = {$request->person->id}) 
										   OR (person.id = B.user2 AND B.user1 = {$request->person->id})
									LEFT JOIN apretaste.person_relation_waiting W ON (person.id = W.user1 AND W.user2 = {$request->person->id}) 
										   OR (person.id = W.user2 AND W.user1 = {$request->person->id})
									LEFT JOIN person_relation_blocked K ON (person.id = K.user1 AND K.user2 = {$request->person->id}) 
    								           OR  (person.id = K.user2 AND K.user1 = {$request->person->id})
									WHERE K.user1 IS NULL) subq WHERE TRUE "
									. (empty($username) ? '' : " AND match_username = 1 ")
									. (empty($fullname) ? '' : " AND ($wordsSum) > 0 ")
									. (empty($email) ? '' : " AND match_email = 1 ")
									. (empty($cellphone) ? '' : " AND match_cellphone = 1")
									. (empty($gender) ? '' : " AND match_gender = 1 ")
									. (empty($sexual_orientation) ? '' : " AND match_sexual = 1 ")
									. (empty($province) ? '' : " AND match_province = 1 ")
									. (empty($religion) ? '' : " AND match_religion = 1 ")
									. (empty($ageFrom) ? '' : " AND match_age_from = 1 ")
									. (empty($ageTo) ? '' : " AND match_age_to = 1 ")
									. " ORDER BY match_username + match_email + match_cellphone + match_gender 
										+ match_sexual + match_province + match_religion + match_age_from + match_age_to 
										+ $wordsSum
										DESC, active DESC, online DESC, last_access DESC  
									LIMIT $offset, $limit ");

		if (count($results) < 1) {
			return $response->setTemplate('message.ejs',  [
				"header" => 'Sin resultados',
				"text" => "No se encontraron resultados para los criterios de búsqueda proporcionados",
				'icon' => "sentiment_very_dissatisfied",
				'btn' => [
					'command' => 'AMIGOS BUSQUEDA',
					'caption' => 'Buscar'
				]
			]);
		}

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
				'friend' => (int) $item->friend == 1,
				'gender' => $person->gender,
				'is_influencer' => $person->isInfluencer
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

			$friend = Person::find($userId);

			if (!$friend->isActive && $friend->isOnMailList) {
				$supportEmail = Config::pick('general')['support_email'];
				$content = ['username' => '@' . $request->person->username, 'email' => $supportEmail];
				$email = new Email();
				$email->to = $friend->email;
				$email->subject = '@'.$request->person->username.' te ha enviado una solicitud de amistad';
				$email->sendFromTemplate($content, 'friend');
			}
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
