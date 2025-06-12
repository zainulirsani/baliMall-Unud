var elementInitChat = $('#init-chat');
var elementSendChat = $('#send-chat');
var elementChatMessage = $('#chat-message');
var elementChatDetail = $('#chat-detail');
var elementChatTimestamp = $('#chat-ts');
var elementMessageContent = $('#messages-content');
var roomId = elementChatDetail.attr('data-room') || '';
var userId = elementChatDetail.attr('data-id') || 0;

var checkForIncomingMessage = function(room, id) {
  var submit = {
    id: id,
    ts: elementChatTimestamp.val() || '',
  };

  $.post(BASE_URL+'/user/chat/'+room+'/fetch', $.extend(true, submit, TOKEN), function(response) {
    if (response.status) {
      var chat_messages = response.messages;

      for (var i = 0; i < chat_messages.length; i++) {
        renderIncomingMessage({
          id: chat_messages[i].cm_id,
          message: chat_messages[i].cm_message,
          name: chat_messages[i].u_firstName+' '+chat_messages[i].u_lastName,
          ts: chat_messages[i].ts,
          formatted: chat_messages[i].formatted,
          type: 'recipient',
        });
      }
    }
  });
};

var renderIncomingMessage = function(data) {
  if ($('#cm-'+data.id).length < 1) {
    var classElement = data.type === 'sender' ? 'msg rs' : 'msg ls';
    var element = $('#user-chat-template').html();
    var content = element
      .replace('**id**', data.id)
      .replace('**class**', classElement)
      .replace('**name**', data.name)
      .replace('**ts**', data.formatted)
      .replace('**msg**', data.message);

    elementMessageContent.append(content);
  }

  elementChatTimestamp.val(data.ts);
};

$(document).ready(function() {
  if (elementInitChat.length) {
    $(document).on('click', '.chat', function(e) {
      e.preventDefault();

      var slug = $(this).attr('data-slug') || '';
      var order = $(this).attr('data-order') || '0';
      var product = $(this).attr('data-product') || '0';

      $('#chat-slug').val(slug);
      $('#chat-order').val(order);
      $('#chat-product').val(product);

      elementInitChat.submit();
    });
  }

  if (elementSendChat.length) {
    elementSendChat.click(function(e) {
      e.preventDefault();

      var message = elementChatMessage.val();

      if (message !== '') {
        var submit = {
          id: userId,
          room: roomId,
          message: message,
        };

        $.post(BASE_URL+'/user/chat/submit', $.extend(true, submit, TOKEN), function(response) {
          if (response.status) {
            renderIncomingMessage({
              id: response.id,
              message: response.message,
              name: response.name,
              ts: response.ts,
              formatted: response.formatted,
              type: 'sender',
            });

            elementChatMessage.val('');
          }
        });
      } else {
        showGeneralPopup(LABEL_MSG_EMPTY);
      }
    });
  }

  if (elementChatDetail.length) {
    if (roomId !== '') {
      setInterval(function() {
        checkForIncomingMessage(roomId, userId);
      }, 10000);
    }
  }
});
