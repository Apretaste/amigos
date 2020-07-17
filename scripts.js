$(function () {
	// initialize components
	$('.tabs').tabs();
	$('.modal').modal();
});

var currentUser = null;

function openSearchModal() {
	M.Modal.getInstance($('#searchModal')).open();
	$('#search').focus();
}

function deleteModalOpen(id, username) {
	currentUser = id;
	setCurrentUsername(username);
	M.Modal.getInstance($('#deleteModal')).open();
}

function rejectModalOpen(id, username) {
	currentUser = id;
	setCurrentUsername(username);
	M.Modal.getInstance($('#rejectModal')).open();
}

function cancelRequestModalOpen(id, username) {
	currentUser = id;
	setCurrentUsername(username);
	M.Modal.getInstance($('#cancelRequestModal')).open();
}

function blockModalOpen(id, username) {
	currentUser = id;
	setCurrentUsername(username);
	M.Modal.getInstance($('#blockModal')).open();
}

function addFriendModalOpen(id, username) {
	currentUser = id;
	setCurrentUsername(username);
	M.Modal.getInstance($('#addFriendModal')).open();
}

function acceptModalOpen(id, username) {
	currentUser = id;
	setCurrentUsername(username);
	M.Modal.getInstance($('#acceptFriendModal')).open();
}

function searchUser() {
	var username = $('#search').val();
	if (username.length < 4) {
		showToast('Minimo 4 caracteres');
		return;
	} else if (username.length > 16) {
		showToast('Maximo 16 caracteres');
		return;
	}

	apretaste.send({command: 'amigos buscar', data: {username: username}});
}

function addFriend(message) {
	apretaste.send({
		command: 'amigos agregar',
		data: {id: currentUser},
		redirect: false,
		callback: {
			name: 'showToast',
			data: message
		}
	});
}

function deleteFriend() {
	apretaste.send({
		command: 'amigos eliminar',
		data: {id: currentUser},
		redirect: false,
		callback: {
			name: 'showToast',
			data: 'Amigo eliminado'
		}
	});
}

function rejectFriend(message) {
	apretaste.send({
		command: 'amigos rechazar',
		data: {id: currentUser},
		redirect: false,
		callback: {
			name: 'showToast',
			data: message
		}
	});
}

function blockUser() {
	apretaste.send({
		command: 'amigos bloquear',
		data: {id: currentUser},
		redirect: false,
		callback: {
			name: 'showToast',
			data: 'Usuario bloqueado'
		}
	});
}

function openProfile(id) {
	apretaste.send({command: 'perfil', data: {username: id}});
}

function showToast(text) {
	M.toast({html: text});
}

function setCurrentUsername(username) {
	$('.username').html('@' + username);
}