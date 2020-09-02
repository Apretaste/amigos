$(function () {
    // initialize components
    $('.tabs').tabs();
    $('.modal').modal();
});

var currentUser = null;
var currentUsername = null;

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
            name: 'addFriendCallback',
            data: message
        }
    });
}

function addFriendCallback(message) {
    showToast(message);
    friends.push(1);

    var friendsCounter = $('#friendsCounter');

    if (friendsCounter.length > 0) {
        friendsCounter.html(friends.length + ' ' + (friends.length > 1 ? 'amigos' : 'amigo'));
    } else {
        $('.tags').append('<div class="chip tiny">\n' +
            '    <i class="fa fa-user-alt"></i>\n' +
            '    <span id="friendsCounter">\n' +
            '    </span>\n' +
            '</div>');

        friendsCounter = $('#friendsCounter');
        friendsCounter.html(friends.length + ' ' + (friends.length > 1 ? 'amigos' : 'amigo'));
    }

    var waitingCounter = $('#waitingCounter');
    if (waiting.length === 1) {
        waitingCounter.parent().remove();
    } else {
        waiting.pop();
        waitingCounter.html(waiting.length + ' ' + (waiting.length > 1 ? 'peticiones' : 'petición'));
    }

    $('#' + currentUser + ' .action').html(
        '<a href="#!">\n' +
        '    <i class="material-icons red-text"\n' +
        '       onclick="deleteModalOpen(\'' + currentUser + '\', \'' + currentUsername + '\')">\n' +
        '        do_not_disturb_alt\n' +
        '    </i>\n' +
        '</a>\n' +
        '<a href="#!">\n' +
        '    <i class="material-icons"\n' +
        '       onclick="apretaste.send({command: \'chat\', data: {userId: \'' + currentUser + '\'}})">\n' +
        '        chat\n' +
        '    </i>\n' +
        '</a>');

}

function deleteFriend() {
    apretaste.send({
        command: 'amigos eliminar',
        data: {id: currentUser},
        redirect: false,
        callback: {
            name: 'deleteFriendCallback',
        }
    });
}

function deleteFriendCallback() {
    showToast('Amigo eliminado');
    $('#' + currentUser).remove();
    var friendsCounter = $('#friendsCounter');
    if (friends.length === 1) {
        friendsCounter.parent().remove();
    } else {
        friends.pop();
        friendsCounter.html(friends.length + ' ' + (friends.length > 1 ? 'amigos' : 'amigo'));
    }
}

function rejectFriend(message) {
    apretaste.send({
        command: 'amigos rechazar',
        data: {id: currentUser},
        redirect: false,
        callback: {
            name: 'rejectFriendCallback',
            data: {message: message, id: currentUser}
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
    currentUsername = username;
    $('.username').html('@' + username);
}

function rejectFriendCallback(data) {
    showToast(data.message);
    $('#' + data.id).remove();

    var waitingCounter = $('#waitingCounter');
    if (waiting.length === 1) {
        waitingCounter.parent().remove();
    } else {
        waiting.pop();
        waitingCounter.html(waiting.length + ' ' + (waiting.length > 1 ? 'peticiones' : 'petición'));
    }
}