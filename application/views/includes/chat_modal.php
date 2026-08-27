<link href="<?= base_url('assets/fonts/bootstrap-icons/bootstrap-icons.css'); ?>" rel="stylesheet">
<?php defined('BASEPATH') OR exit('No direct script access allowed'); ?>

<style>
#floatingChatBox {
  position: fixed;
  bottom: 90px;
  right: 20px;
  width: 320px;
  height: 480px;
  background-color: #ffffff;
  border-radius: 16px;
  box-shadow: 0 4px 20px rgba(0,0,0,0.1);
  display: none;
  display: flex;
  flex-direction: column;
  z-index: 9999;
  font-family: 'Segoe UI', sans-serif;
  border: 1px solid #dee2e6;
  overflow: hidden;
}
#messageContextMenu {
  background-color: #fff;
  border: 1px solid #ccc;
  border-radius: 6px;
  min-width: 120px;
  box-shadow: 0 2px 8px rgba(0,0,0,0.15);
  font-size: 14px;
}
#messageContextMenu .dropdown-item {
  padding: 8px 14px;
  cursor: pointer;
}
#messageContextMenu .dropdown-item:hover {
  background-color: #f1f1f1;
}
#userSearchResults li {
  cursor: pointer; 
  padding: 8px 12px;
  transition: background 0.2s ease;
  font-size: 14px;
}

#userSearchResults li:hover {
  background-color: #eef3fb;
  color: #0061f2; 
}
#userSearchResults li.active {
  background-color: #dbe9ff;
  font-weight: 600;
}


.chat-header {
  background-color: #1d3557;
  color: white;
  padding: 12px 16px;
  font-weight: 600;
  display: flex;
  justify-content: space-between;
  align-items: center;
  font-size: 16px;
}

.chat-body {
  flex: 1;
  display: flex;
  flex-direction: column;
  padding: 10px 10px 0 10px;
  background-color: #f8f9fa;
  overflow: hidden;
}

.chat-select {
  margin-bottom: 10px;
  font-size: 14px;
}

#chatMessages {
  flex: 1;
  overflow-y: auto;
  padding: 8px;
  background-color: #fff;
  display: flex;
  flex-direction: column;
  gap: 6px;
}
.message-wrapper {
  display: flex;
  max-width: 100%;
}

.message-wrapper.align-start {
  justify-content: flex-start;
}

.message-wrapper.align-end {
  justify-content: flex-end;
}


.message {
  background-color: #1877f2;
  color: #fff;
  padding: 8px 12px;
  border-radius: 18px 18px 6px 18px;
  font-size: 13.5px;
  line-height: 1.4;
  max-width: 100%;
  display: inline-block;
  position: relative;
  word-wrap: break-word;
}

.message small.text-muted {
  font-size: 10px;
  margin-top: 2px;
  color: #666;
  text-align: right;
  display: block;
  opacity: 0.75;
}


.bubble-sent {
  background-color: #1877f2;
  color: #fff;
  border-radius: 18px 18px 6px 18px;
}

.bubble-received {
  background-color: #e4e6eb;
  color: #000;
  border-radius: 18px 18px 18px 6px;
}



.chat-footer {
  display: flex;
  align-items: center;
  padding: 10px;
  background-color: #fff;
  gap: 6px;
  border-top: 1px solid #dee2e6;
}

.chat-footer input {
  flex-grow: 1;
  border-radius: 20px;
  border: 1px solid #ccc;
  padding: 8px 14px;
  font-size: 14px;
  outline: none;
  color: #000 !important; 
  background-color: #fff; 
}


.chat-footer button {
  background: none;
  border: none;
  color: #0061f2;
  font-size: 22px;
  padding: 4px 8px;
  line-height: 1;
}

.close-btn {
  background: none;
  border: none;
  font-size: 20px;
  color: white;
  cursor: pointer;
}
.chat-bubble.sent {
  background-color: #1877f2;
  color: white;
  border-radius: 18px 18px 6px 18px;
  position: relative;
  padding: 10px 14px 10px 14px;
}
.dot-menu {
  pointer-events: auto;
  transition: opacity 0.2s ease;
}

.dot-menu.show {
  display: block !important;
}

.dot-menu-container:hover .dot-menu {
  display: block !important;
}


.dot-item {
  color: #333 !important;
  background-color: #fff;
}

.dot-menu-trigger {
  padding-left: 6px;
  padding-right: 6px;
}


.typing-indicator {
  display: inline-block;
  height: 16px;
  padding-left: 10px;
  margin-bottom: 6px;
}
.typing-indicator span {
  display: inline-block;
  width: 6px;
  height: 6px;
  margin: 0 1px;
  background-color: #000;
  border-radius: 50%;
  animation: typingBounce 1.2s infinite ease-in-out;
}
.typing-indicator span:nth-child(2) { animation-delay: 0.2s; }
.typing-indicator span:nth-child(3) { animation-delay: 0.4s; }

@keyframes typingBounce {
  0%, 80%, 100% { transform: scale(0.8); }
  40% { transform: scale(1.2); }
}
.message-options .dropdown-menu {
  font-size: 14px;
  z-index: 9999;
}
.chat-bubble .dropdown-toggle {
  color: #fff;
}
.chat-bubble.received .dropdown-toggle {
  color: #000;
}
#emojiPicker span {
  font-size: 22px;
  cursor: pointer;
  margin: 4px;
  display: inline-block;
  transition: transform 0.1s ease-in-out;
}
#emojiPicker span:hover {
  transform: scale(1.2);
}
.chat-footer input:focus {
  border-color: #0061f2;
  box-shadow: 0 0 0 2px rgba(0, 97, 242, 0.15);
}

.search-input-wrapper {
  position: relative;
  margin: 10px 0;
}

.search-icon {
  position: absolute;
  top: 50%;
  left: 14px;
  transform: translateY(-50%);
  color: #999;
  font-size: 16px;
  z-index: 1;
}


#userSearchInput {
  border-radius: 50px;
  padding: 8px 16px 8px 38px;
  font-size: 14px;
  border: 1px solid #ccc;
  background-color: #fff;
  transition: all 0.2s ease-in-out;
}

#userSearchInput:hover {
  background-color: #f5f5f5;
}

#userSearchInput:focus {
  border-color: #0061f2;
  box-shadow: 0 0 0 3px rgba(0, 97, 242, 0.15);
  outline: none;
}


.chat-footer button {
  background: none;
  border: none;
  color: #333;
  font-size: 20px;
  padding: 4px 8px;
  transition: color 0.2s ease-in-out;
}

.chat-footer button:hover {
  color: #0061f2;
}

.chat-fab {
  position: fixed;
  bottom: 90px;
  right: 20px;
  width: 56px;
  height: 56px;
  background-color: #ffffff;
  color: #212529;
  border: none;
  border-radius: 50%;
  box-shadow: 0 6px 16px rgba(0, 0, 0, 0.15);
  font-size: 22px;
  display: flex;
  align-items: center;
  justify-content: center;
  z-index: 9998;
  transition: box-shadow 0.2s ease, transform 0.2s ease;
}

.chat-fab:hover {
  transform: scale(1.05);
  box-shadow: 0 8px 20px rgba(0, 0, 0, 0.2);
}

.chat-fab:focus {
  outline: none;
  box-shadow: 0 0 0 3px rgba(0, 97, 242, 0.25);
}

.chat-fab i {
  font-size: 24px;
}

#chatNotif {
  position: absolute;
  top: 6px;
  right: 6px;
  background: red;
  color: white;
  border-radius: 50%;
  font-size: 10px;
  width: 16px;
  height: 16px;
  display: none;
  align-items: center;
  justify-content: center;
}
@keyframes fadeHighlight {
  0% {
    background-color: #d7ebff;
  }
  100% {
    background-color: transparent;
  }
}

.recent-convo-flash {
  animation: fadeHighlight 1s ease-out;
}
.list-group-item .rounded-circle {
  position: relative;
}
.list-group-item span.bg-success {
  position: absolute !important;
  bottom: 0;
  right: 0;
}
.online-dot {
  position: absolute;
  top: 50%;
  right: -200px;
  transform: translateY(-50%);
  width: 15px;
  height: 15px;
  background-color: #28a745; 
  border: 2px solid #fff;
  border-radius: 50%;
   animation: pulse 1.5s infinite;
}
@keyframes pulse {
  0% {
    box-shadow: 0 0 0 0 rgba(40, 167, 69, 0.7);
  }
  70% {
    box-shadow: 0 0 0 8px rgba(40, 167, 69, 0);
  }
  100% {
    box-shadow: 0 0 0 0 rgba(40, 167, 69, 0);
  }
}
.chat-header button i,
.chat-footer button i {
  color: #003366;
}

.chat-header button,
.chat-footer button {
  position: relative;
  background: none;
  border: 1px solid transparent;
  padding: 4px; 
  border-radius: 6px;
  transition: border-color 0.2s ease, color 0.2s ease;
}

.chat-header button:hover,
.chat-footer button:hover {
  border-color: #0061f2 !important;
  background-color: transparent !important;
}

.chat-header button:hover i,
.chat-footer button:hover i {
  color: #0061f2;
}

.chat-header button[title]:hover::after,
.chat-footer button[title]:hover::after {
  content: attr(title);
  position: absolute;
  bottom: 130%;
  left: 50%;
  transform: translateX(-50%);
  background-color: black;
  color: white;
  padding: 3px 8px;
  border-radius: 4px;
  white-space: nowrap;
  font-size: 11px;
  opacity: 0.9;
  z-index: 99999;
}

#changeUserBtn:hover::after {
  content: none !important;
  
}


</style>

<!-- Chat Box -->
<div id="floatingChatBox" style="display: none;">
  <div class="chat-header d-flex justify-content-between align-items-center px-3 py-2" style="background-color: #f5f5f5; border-bottom: 1px solid #ccc;">
    <span id="selectedUserName" style="font-weight: 600; font-size: 14px; color: #333;">Select user to chat</span>

    <!-- Right-side icons -->
    <div class="d-flex align-items-center gap-2">
      <button id="changeUserBtn" title="Search User" style="background: none; border: none; font-size: 18px; color: #003366;">
        <i class="bi bi-search"></i>
      </button>

      <!-- Close Chat -->
      <button id="closeChat" class="close-btn" title="Close" style="background: none; border: none; font-size: 20px; color: #333;">&times;</button>
    </div>
  </div>

  <div class="chat-body">
   <div id="userSearchContainer" style="display: none; position: relative;">
  <div class="search-input-wrapper">
    <i class="bi bi-search search-icon"></i>
    <input type="text" id="userSearchInput" class="form-control" placeholder="Search" autocomplete="off">
  </div>
  <ul id="userSearchResults" class="list-group" style="max-height: 150px; overflow-y: auto; margin-top: 2px; position: absolute; z-index: 1000; width: 100%; display: none;"></ul>
</div>


    <select id="recipientSelect" style="display: none;">
      <option value="">Select User</option>
      <?php foreach ($users as $u): ?>
        <option value="<?= $u->IDNumber ?>"><?= $u->name ?> (<?= $u->position ?>)</option>
      <?php endforeach; ?>
    </select>

    <div id="chatMessages">
    </div>

   <div id="typingStatus">
  <div class="typing-indicator" style="display: none;">
    <span id="typingText" style="margin-right: 8px; font-size: 13px; color: #666;"></span>
    <span></span><span></span><span></span><span></span>
  </div>
</div>

  </div>

  <!-- Chat Footer -->
<div class="chat-footer d-flex align-items-center px-3 py-2" style="border-top: 1px solid #ddd; background: #f9f9f9; gap: 8px;">

  <button type="button" class="btn btn-primary p-1 d-flex align-items-center justify-content-center" 
          data-bs-toggle="tooltip" data-bs-placement="top" title="Attach File" style="width: 36px; height: 36px;">
    <i class="bi bi-paperclip fs-5"></i>
  </button>

  <button type="button" class="btn btn-primary p-1 d-flex align-items-center justify-content-center" 
          data-bs-toggle="tooltip" data-bs-placement="top" title="Attach Image" style="width: 36px; height: 36px;">
    <i class="bi bi-image fs-5"></i>
  </button>

  <div style="position: relative; flex-grow: 1;">
    <input type="text" id="messageInput" placeholder="Aa" class="form-control pe-5" autocomplete="off">
    <button id="emojiToggleBtn" title="Insert Emoji" data-bs-toggle="tooltip" data-bs-placement="top"
            style="position: absolute; right: 8px; top: 50%; transform: translateY(-50%); background: none; border: none; color: #003366;">
      <i class="bi bi-emoji-smile fs-5"></i>
    </button>
  </div>

  <button id="sendBtn" title="Send Message" class="btn btn-primary d-flex align-items-center justify-content-center" 
          data-bs-toggle="tooltip" data-bs-placement="top" style="width: 36px; height: 36px;">
    <i class="bi bi-cursor-fill"></i>
  </button>
</div>

<div id="emojiPicker" style="display: none; position: absolute; bottom: 130px; right: 90px; background: #fff; border: 1px solid #ccc; border-radius: 10px; padding: 10px; max-width: 320px; overflow-y: auto; max-height: 250px; box-shadow: 0 2px 8px rgba(0,0,0,0.2); z-index: 10000; font-size: 22px; line-height: 1.5;">
  <span onclick="addEmoji('😀')" title="Grinning">😀</span>
  <span onclick="addEmoji('😁')" title="Beaming">😁</span>
  <span onclick="addEmoji('😂')" title="Tears of Joy">😂</span>
  <span onclick="addEmoji('🤣')" title="ROFL">🤣</span>
  <span onclick="addEmoji('😃')" title="Smiley">😃</span>
  <span onclick="addEmoji('😄')" title="Grinning Eyes">😄</span>
  <span onclick="addEmoji('😅')" title="Relieved">😅</span>
  <span onclick="addEmoji('😆')" title="Grin">😆</span>
  <span onclick="addEmoji('😉')" title="Wink">😉</span>
  <span onclick="addEmoji('😊')" title="Blush">😊</span>
  <span onclick="addEmoji('😇')" title="Innocent">😇</span>
  <span onclick="addEmoji('🥰')" title="Smiling Hearts">🥰</span>
  <span onclick="addEmoji('😍')" title="Heart Eyes">😍</span>
  <span onclick="addEmoji('🤩')" title="Star Eyes">🤩</span>
  <span onclick="addEmoji('😘')" title="Kiss">😘</span>
  <span onclick="addEmoji('😗')" title="Kissing">😗</span>
  <span onclick="addEmoji('😚')" title="Kiss Closed Eyes">😚</span>
  <span onclick="addEmoji('😋')" title="Yum">😋</span>
  <span onclick="addEmoji('😜')" title="Tongue Out Wink">😜</span>
  <span onclick="addEmoji('🤪')" title="Zany">🤪</span>
  <span onclick="addEmoji('😝')" title="Squinting Tongue">😝</span>
  <span onclick="addEmoji('👍')" title="Thumbs Up">👍</span>
  <span onclick="addEmoji('👎')" title="Thumbs Down">👎</span>
  <span onclick="addEmoji('👏')" title="Clap">👏</span>
  <span onclick="addEmoji('🙌')" title="Raise Hands">🙌</span>
  <span onclick="addEmoji('👐')" title="Open Hands">👐</span>
  <span onclick="addEmoji('🤲')" title="Palms Together">🤲</span>
  <span onclick="addEmoji('🙏')" title="Pray">🙏</span>
  <span onclick="addEmoji('🤝')" title="Handshake">🤝</span>
  <span onclick="addEmoji('✌️')" title="Victory">✌️</span>
  <span onclick="addEmoji('🤟')" title="Love-You Gesture">🤟</span>
  <span onclick="addEmoji('👌')" title="OK">👌</span>
  <span onclick="addEmoji('👋')" title="Wave">👋</span>
  <span onclick="addEmoji('🤘')" title="Rock">🤘</span>
  <span onclick="addEmoji('🤞')" title="Fingers Crossed">🤞</span>
  <span onclick="addEmoji('💪')" title="Flex">💪</span>
  <span onclick="addEmoji('👀')" title="Eyes">👀</span>
  <span onclick="addEmoji('👶')" title="Baby">👶</span>
  <span onclick="addEmoji('🧒')" title="Child">🧒</span>
  <span onclick="addEmoji('👨')" title="Man">👨</span>
  <span onclick="addEmoji('👩')" title="Woman">👩</span>
  <span onclick="addEmoji('🧑‍💻')" title="Developer">🧑‍💻</span>
  <span onclick="addEmoji('🎓')" title="Graduate">🎓</span>
  <span onclick="addEmoji('🎉')" title="Party">🎉</span>
  <span onclick="addEmoji('🎂')" title="Cake">🎂</span>
  <span onclick="addEmoji('🥳')" title="Celebration">🥳</span>
  <span onclick="addEmoji('❤️')" title="Heart">❤️</span>
  <span onclick="addEmoji('💔')" title="Broken Heart">💔</span>
  <span onclick="addEmoji('💕')" title="Two Hearts">💕</span>
  <span onclick="addEmoji('💖')" title="Sparkle Heart">💖</span>
  <span onclick="addEmoji('💗')" title="Growing Heart">💗</span>
  <span onclick="addEmoji('💘')" title="Heart Arrow">💘</span>
  <span onclick="addEmoji('💝')" title="Heart Gift">💝</span>
  <span onclick="addEmoji('💞')" title="Revolving Hearts">💞</span>
  <span onclick="addEmoji('🌞')" title="Sun">🌞</span>
  <span onclick="addEmoji('🌙')" title="Moon">🌙</span>
  <span onclick="addEmoji('⭐')" title="Star">⭐</span>
  <span onclick="addEmoji('🌈')" title="Rainbow">🌈</span>
  <span onclick="addEmoji('🔥')" title="Fire">🔥</span>
  <span onclick="addEmoji('💧')" title="Droplet">💧</span>
  <span onclick="addEmoji('❄️')" title="Snowflake">❄️</span>
</div>

</div>

<button id="openChatBtn" class="chat-fab" title="Chat">
  <i class="bi bi-pencil-square"></i>
  <span id="chatNotif">!</span>
</button>



<div id="chatToast" style="
  position: fixed;
  bottom: 160px;
  right: 20px;
  background-color: #323232;
  color: white;
  padding: 12px 16px;
  border-radius: 8px;
  box-shadow: 0 2px 8px rgba(0,0,0,0.2);
  font-size: 14px;
  display: none;
  z-index: 99999;
"><span id="toastUser">New message received</span>
</div>



<script>
  if (performance.getEntriesByType("navigation")[0]?.type === "reload") {
    sessionStorage.setItem('chatWasOpen', 'false');
  }
</script>


<!-- jQuery (local — avoids CDN DNS round-trip) -->
<script src="<?= base_url(); ?>assets/js/jquery-3.6.0.min.js"></script>
<script>
let isActionActive = false;
let isDialogOpen = false;
let selectedUser = localStorage.getItem('selectedUserID') || '';
let pollingInterval;
let typingTimeout;

if (window.__CHAT_POLL_RUNNING__) {
  console.log('Polling already running!');
} else {
  window.__CHAT_POLL_RUNNING__ = true;

  $(document).ready(function () {
    $(document).on('click', '#openChatBtn', function () {
      $('#floatingChatBox').fadeIn();
      $('#chatNotif').hide();
      if (selectedUser) startPollingMessages();
      window.sessionStorage.setItem('chatWasOpen', 'true');
    });

    $(document).on('click', '#closeChat', function () {
      $('#floatingChatBox').fadeOut();
      stopPollingMessages();
      window.sessionStorage.setItem('chatWasOpen', 'false');
    });

    $(document).on('click', '#changeUserBtn', function () {
      $('#userSearchContainer').slideToggle('fast');
      $('#userSearchInput').val('').trigger('input');
      $('#userSearchResults').hide();
    });

    $(document).on('input', '#userSearchInput', function () {
      const searchTerm = $(this).val().trim();
      const resultsContainer = $('#userSearchResults');
      resultsContainer.empty().hide();
      if (searchTerm.length < 2) return;
      $.post("<?= base_url('Messaging/search_users') ?>", { search: searchTerm }, function (res) {
        const users = JSON.parse(res);
        if (users.length > 0) {
          users.forEach(user => {
            let basePath = '<?= base_url('upload/profile/') ?>';
            let avatarPath = basePath + user.avatar;
            let onlineDot = user.isOnline == 1 ? `<span class="online-dot"></span>` : '';
            resultsContainer.append(`
              <li class="list-group-item d-flex align-items-center" data-id="${user.IDNumber}">
                <div class="avatar-wrapper position-relative me-2">
                  <img src="${avatarPath}" width="36" height="36" class="rounded-circle"
                    onerror="this.onerror=null; this.src='<?= base_url('upload/profile/default_user.png') ?>'">
                  ${onlineDot}
                </div>
                <div>
                  ${user.name}<br><small class="text-muted">(${user.position})</small>
                </div>
              </li>
            `);
          });
          resultsContainer.show();
        } else {
          resultsContainer.html('<li class="list-group-item text-muted">No results found</li>').show();
        }
      });
    });

    $(document).on('click', '#userSearchResults li', function () {
      const userId = $(this).data('id');
      const userText = $(this).text();
      $('#recipientSelect').val(userId).trigger('change');
      $('#selectedUserName').text(userText);
      $('#userSearchContainer').slideUp();
      $('#userSearchResults').hide();
      $('#userSearchInput').val('');
    });

    if (selectedUser && window.sessionStorage.getItem('chatWasOpen') === 'true') {
      $('#floatingChatBox').fadeIn();
      $('#recipientSelect').val(selectedUser);
      $('#selectedUserName').text($('#recipientSelect option:selected').text());
      loadConversation(selectedUser);
      startPollingMessages();
    }

    $(document).on('change', '#recipientSelect', function () {
      selectedUser = $(this).val();
      localStorage.setItem('selectedUserID', selectedUser);

      const selectedText = $('#recipientSelect option:selected').text();
      $('#selectedUserName').text(selectedText);

      $('#recipientSelect').slideUp();

      loadConversation(selectedUser);
      stopPollingMessages();
      startPollingMessages();
    });

    $(document).on('click', '#sendBtn', function () {
      const message = $('#messageInput').val().trim();
      if (!selectedUser || !message) return;
      
      $.post("<?= base_url('Messaging/send') ?>", {
        receiver_id: selectedUser,
        message: message
      }, function () {
        $('#messageInput').val('');
        loadConversation(selectedUser, true, null, true);
        loadRecentConversations();
      });
    });

    $(document).on('input', '#messageInput', function () {
      if (!selectedUser) return;

      $.post("<?= base_url('Messaging/typing') ?>", {
        receiver_id: selectedUser
      });

      clearTimeout(typingTimeout);
      typingTimeout = setTimeout(() => {
        $.post("<?= base_url('Messaging/stop_typing') ?>", {
          receiver_id: selectedUser
        });
      }, 2000);
    });

    $(document).on('click', '#emojiToggleBtn', function (e) {
      e.stopPropagation();
      $('#emojiPicker').toggle();
    });

    window.addEmoji = function(emoji) {
      const $input = $('#messageInput');
      $input.val($input.val() + emoji);
      $('#emojiPicker').hide();
    };

    $(document).on('click', function (e) {
      if (!$(e.target).closest('#emojiPicker').length && !$(e.target).is('#emojiToggleBtn, #emojiToggleBtn *')) {
        $('#emojiPicker').hide();
      }
    });

    setInterval(globalMessageChecker, 2000);
  });

  function startPollingMessages() {
    if (pollingInterval) return; 
    pollingInterval = setInterval(() => {
      
      if (selectedUser && !isDialogOpen) {
        if (typeof isActionActive === 'undefined' || !isActionActive) {
          loadConversation(selectedUser, false);
        }
        checkTypingStatus();
        checkForNewMessages();
        loadRecentConversations();
      }
    }, 2000);
  }

  function stopPollingMessages() {
    if (pollingInterval) {
      clearInterval(pollingInterval);
      pollingInterval = null;
    }
  }

let lastRenderedHtml = '';
function loadConversation(userID, scrollToBottom = true, highlightId = null, forceRender = false) {
  const $container = $('#chatMessages');
  const container = $container[0];

  if (!container) return;

  const isAtBottom = container.scrollHeight - container.scrollTop <= container.clientHeight + 20;

  $.post("<?= base_url('Messaging/get_conversation') ?>", {
    otherID: userID,
    markRead: $('#floatingChatBox').is(':visible') ? '1' : '0'
  }, function (data) {
    const previousScrollTop = container.scrollTop;
    const previousScrollHeight = container.scrollHeight;

    if (forceRender || data !== lastRenderedHtml) {
      lastRenderedHtml = data;
      const temp = $('<div>').html(data);
      $container.empty().append(temp.children());

      const newScrollHeight = container.scrollHeight;

      if (scrollToBottom || isAtBottom) {
        container.scrollTop = newScrollHeight;
      } else {
        container.scrollTop = previousScrollTop + (newScrollHeight - previousScrollHeight);
      }

      if (highlightId) {
        const $target = $(`[data-msg-id="${highlightId}"]`);
        if ($target.length) {
          container.scrollTop = $target.position().top + container.scrollTop - 40;
          $target.css('box-shadow', '0 0 8px 2px #0061f2');
          setTimeout(() => $target.css('box-shadow', 'none'), 1500);
        }
      }
    }
  }); 
}

function checkTypingStatus() {
  if (!selectedUser) return;
  $.post("<?= base_url('Messaging/check_typing') ?>", {
    otherID: selectedUser
  }, function (data) {
    if (data === '1') {
      $('.typing-indicator').show();
    } else {
      $('.typing-indicator').hide();
    }
  });
}

function checkForNewMessages() {
  if ($('#floatingChatBox').is(':visible')) {
    $('#chatNotif').hide();
    return;
  }
  $.post("<?= base_url('Messaging/check_unseen') ?>", function (data) {
    if (data === '1') {
      $('#chatNotif').css('display', 'flex');
    } else {
      $('#chatNotif').hide();
    }
  });
}


  function globalMessageChecker() {
    if ($('#floatingChatBox').is(':visible')) return;
    $.post("<?= base_url('Messaging/check_unseen_sender') ?>", function (data) {
      try {
        const result = JSON.parse(data);
        if (result.has_unseen === '1') {
          $('#chatNotif').css('display', 'flex');
          $('#chatDropdownNotif').show();
          $('#toastUser').text('Message from ' + result.sender_name);
          $('#chatToast').fadeIn();
          setTimeout(() => $('#chatToast').fadeOut(), 2000);
        } else {
          $('#chatNotif').hide();
          $('#chatDropdownNotif').hide();
        }
      } catch (err) {
        console.error('Invalid JSON from check_unseen_sender:', data);
      }
    });
    $.post("<?= base_url('Messaging/check_unseen_total') ?>", function (res) {
      try {
        const parsed = JSON.parse(res);
        const count = parsed.count || 0;
        if (count > 0) {
          $('#messageCountBadge').text(count).show();
        } else {
          $('#messageCountBadge').hide();
        }
      } catch (err) {
        console.error('Invalid JSON from check_unseen_total:', res);
      }
    });
    $.post("<?= base_url('Messaging/get_unseen_messages_preview') ?>", function (data) {
      try {
        const messages = JSON.parse(data);
        if (messages.length > 0) {
          let html = '';
          messages.forEach(msg => {
            html += `
              <a href="javascript:void(0);" class="dropdown-item notify-item" onclick="openChatFromDropdown('${msg.sender_id}')">
                <div class="notify-details">
                  <strong>${msg.name}</strong>
                  <small class="text-muted d-block" style="font-size: 11px;">${truncate(atob(msg.message), 60)}</small>
                </div>
              </a>`;
          });
          $('#messageDropdownContent').html(html);
        } else {
          $('#messageDropdownContent').html('<em class="dropdown-item-text text-muted">No new messages</em>');
        }
      } catch (err) {
        console.error('Invalid JSON from get_unseen_messages_preview:', data);
      }
    });
  }

  function truncate(text, max) {
    return text.length > max ? text.substring(0, max) + '…' : text;
  }

  window.openChatFromDropdown = function(senderID) {
    $('#floatingChatBox').fadeIn();
    $('#chatNotif').hide();
    $('#chatDropdownNotif').hide();
    $('#messageCountBadge').hide();
    $('#recipientSelect').val(senderID).trigger('change');
    selectedUser = senderID;
    localStorage.setItem('selectedUserID', senderID);
    const userText = $('#recipientSelect option[value="'+senderID+'"]').text();
    if(userText) $('#selectedUserName').text(userText);
    if (typeof startPollingMessages === 'function') startPollingMessages();
  };

  function scrollToBottom() {
    const container = $('#chatMessages')[0];
    container.scrollTop = container.scrollHeight;
  }

  setInterval(() => {
    fetch('<?= base_url('chat/update_online_status') ?>')
      .then(res => {
        if (!res.ok) throw new Error('Update failed');
        return res.text();
      })
      .then(data => console.log('Online status updated:', data))
      .catch(err => console.log('User not logged in or offline'));
  }, 30000);
}
$(document).mouseup(function(e) {
  const chatBox = $("#floatingChatBox");
  if (chatBox.is(":visible") && !chatBox.is(e.target) && chatBox.has(e.target).length === 0) {
    chatBox.fadeOut(); 
    localStorage.removeItem('selectedUserID');
  }
});
$(document).on('keydown', '#messageInput', function(e) {
  if (e.key === 'Enter' && !e.shiftKey) {
    e.preventDefault();
    $('#sendBtn').click(); 
  }
});


</script>
