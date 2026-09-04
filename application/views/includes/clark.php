<style>
 

  .dropdown-item.noti-title {
    padding: 12px 16px;
    background-color: #f1f1f1;
    border-bottom: 1px solid #ddd;
  }
#recentConversationList .notify-item {
  cursor: pointer;
}
.dropdown-menu.dropdown-lg {
  width: 360px;
  padding: 0;
  border-radius: 8px;
  overflow: hidden;
  transition: all 0.25s ease;
  opacity: 0;
  transform: translateY(10px);
  visibility: hidden;
}

.dropdown-menu.dropdown-lg.show {
  opacity: 1;
  transform: translateY(0);
  visibility: visible;
}

  #recentConversationList .notify-item {
    display: block;
    padding: 12px 16px;
    border-bottom: 1px solid #f2f2f2;
    text-decoration: none;
    transition: background-color 0.2s ease-in-out;
  }

  #recentConversationList .notify-item:hover {
    background-color: #f9f9f9;
  }

  .notify-item .conversation-name {
    font-weight: 600;
    font-size: 14px;
    margin-bottom: 4px;
    color: #333;
  }

  .notify-item .conversation-meta {
    display: flex;
    justify-content: space-between;
    align-items: center;
  }

  .notify-item .conversation-time {
    font-size: 11px;
    color: #999;
    white-space: nowrap;
    margin-left: 8px;
  }

  .notify-item .conversation-preview {
    font-size: 13px;
    color: #555;
    margin: 0;
    text-overflow: ellipsis;
    overflow: hidden;
    white-space: nowrap;
  }
  #recentConversationList .notify-item.unread {
  background-color: #eef3fd;
}

#recentConversationList .notify-item.unread:hover {
  background-color: #dde9fd;
}

#recentConversationList .notify-item img {
  object-fit: cover;
}
.conversation-unread-indicator {
  display: inline-block;
  width: 8px;
  height: 8px;
  background-color: red;
  border-radius: 50%;
  margin-left: 6px;
  margin-bottom: 2px;
  vertical-align: middle;
}


</style>
<!-- jQuery -->
<script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>

<!-- Popper.js (for Bootstrap 4 dropdowns) -->
<script src="https://cdn.jsdelivr.net/npm/popper.js@1.16.1/dist/umd/popper.min.js"></script>

<!-- Bootstrap 4 JS -->
<script src="https://cdn.jsdelivr.net/npm/bootstrap@4.6.2/dist/js/bootstrap.min.js"></script>

<!-- Activate dropdowns -->
<script>
(function () {
  const currentUserID = <?= json_encode($this->session->userdata('IDNumber')) ?>;
  let previousConvoIDs = [];

  function loadRecentConversations() {
    $.post("<?= base_url('Messaging/recent_conversations') ?>", function (data) {
      try {
        const users = JSON.parse(data);
        let html = '';
        const newIDs = [];

        if (users.length > 0) {
          users.forEach(u => {
            const isSender = u.sender_id == currentUserID;
            const otherID = isSender ? u.receiver_id : u.sender_id;
            const convoID = `convo-${otherID}`;
            const isNew = !previousConvoIDs.includes(convoID);
            if (isNew) newIDs.push(convoID);

            const preview = u.message.length > 50 ? u.message.substring(0, 50) + '...' : u.message;
            const time = u.timestamp ?? '';

            html += `
<a href="javascript:void(0);" id="${convoID}" class="dropdown-item notify-item ${u.seen == 0 ? 'unread' : ''} ${isNew ? 'recent-convo-flash' : ''}"
   data-sender-id="${otherID}">
  <div class="d-flex align-items-start">
    <img src="<?= base_url('upload/profile/') ?>${u.avatar}" class="rounded-circle mr-2" width="36" height="36" alt="${u.name}">
    <div class="flex-grow-1 ml-2">
      <div class="d-flex justify-content-between align-items-center">
        <div class="d-flex align-items-center">
          <strong class="conversation-name">${u.name}</strong>
          ${parseInt(u.seen) === 0 && u.receiver_id == currentUserID ? '<span class="conversation-unread-indicator"></span>' : ''}
        </div>
        <small class="conversation-time">${time}</small>
      </div>
      <p class="conversation-preview mb-0">${preview}</p>
    </div>
  </div>
</a>`;
          });

          previousConvoIDs = users.map(u => {
            const isSender = u.sender_id == currentUserID;
            return `convo-${isSender ? u.receiver_id : u.sender_id}`;
          });

        } else {
          html = '<em class="dropdown-item-text text-muted px-3 py-2">No messages yet</em>';
          previousConvoIDs = [];
        }

        $('#recentConversationList').html(html);
      } catch (e) {
        console.error('JSON parse error in recent_conversations:', e);
      }
    }).fail(function (xhr, status, error) {
      console.warn('Request failed:', status, error);
    });
  }

  function openChatFromDropdown(senderID) {
    $('#floatingChatBox').fadeIn();
    $('#chatNotif').hide();

    const $recipientSelect = $('#recipientSelect');
    if ($recipientSelect.length) {
      $recipientSelect.val(senderID).trigger('change');

      const userText = $recipientSelect.find(`option[value="${senderID}"]`).text();
      if (userText) $('#selectedUserName').text(userText);

    
      localStorage.setItem('selectedUserID', senderID);
    } else {
      console.warn('recipientSelect not found');
    }

    if (typeof startPollingMessages === 'function') {
      startPollingMessages();
    }
  }

  $(document).ready(function () {
    $('.dropdown-toggle').dropdown();

    loadRecentConversations();
    const convoInterval = setInterval(() => {
      if ($('#recentConversationList').length) {
        loadRecentConversations();
      } else {
        clearInterval(convoInterval); 
      }
    }, 2000);

    $(document).on('click', '.dropdown-item.notify-item', function () {
      const senderID = $(this).data('sender-id');
      if (!senderID) return;

      $.post("<?= base_url('Messaging/mark_seen') ?>", { sender_id: senderID }, function () {
        $(`#convo-${senderID} .conversation-unread-indicator`).remove();
      });

      openChatFromDropdown(senderID);
    });
  });
})();
</script>
<script>
(function() {
  var base = "<?= base_url(); ?>";
  var LS_LAST_SEEN = 'ann_last_seen_iso';

  function parseISO(d) {
    var dt = d ? new Date(d) : null;
    return (dt && !isNaN(dt.getTime())) ? dt : null;
  }
  function isoString(d) {
    return d ? new Date(d).toISOString() : null;
  }
  function getLastSeen() {
    var s = localStorage.getItem(LS_LAST_SEEN);
    return parseISO(s);
  }
  function setLastSeen(iso) {
    if (iso) localStorage.setItem(LS_LAST_SEEN, iso);
  }
  function latestDate(items) {
    var max = null;
    (items || []).forEach(function(a){
      if (!a || !a.datePosted) return;
      var d = parseISO(a.datePosted);
      if (d && (!max || d > max)) max = d;
    });
    return max;
  }
  function countNew(items, lastSeen) {
    if (!items || !items.length) return 0;
    var last = lastSeen || new Date(0);
    var c = 0;
    items.forEach(function(a){
      var d = parseISO(a.datePosted);
      if (d && d > last) c++;
    });
    return c;
  }

  function renderList(items) {
    var container = document.getElementById('recentAnnouncementList');
    if (!container) return;

    if (!items || !items.length) {
      container.innerHTML = '<em class="dropdown-item-text text-muted">No announcements.</em>';
      return;
    }

    var html = items.slice(0, 8).map(function(a) {
      var title = (a.title || '').replace(/</g,'&lt;').replace(/>/g,'&gt;');
      var msg   = (a.message || '').replace(/</g,'&lt;').replace(/>/g,'&gt;');
      var date  = a.datePosted ? new Date(a.datePosted).toLocaleDateString() : '';
      var img   = a.image ? '<?= base_url('upload/announcements/'); ?>' + a.image : '';

      return (
        '<a href="#" class="inbox-item dropdown-item ann-open" ' +
          'data-toggle="modal" data-target="#viewAnnouncementModal" ' +
          'data-title="'+title+'" ' +
          'data-message="'+msg.replace(/\n/g, '<br>')+'" ' +
          'data-image="'+img+'" ' +
          'data-hasimage="'+(img ? '1':'0')+'">' +
            '<div class="inbox-item-img">' +
              (img ? '<img src="'+img+'" class="rounded-circle" alt="img" style="width:36px;height:36px;object-fit:cover;">'
                   : '<span class="mdi mdi-bell-outline" style="font-size:22px;"></span>') +
            '</div>' +
            '<p class="inbox-item-author">'+title+'</p>' +
            '<p class="inbox-item-text">'+(msg.length>60 ? msg.substring(0,60)+'…' : msg)+'</p>' +
            '<p class="inbox-item-date">'+date+'</p>' +
        '</a>'
      );
    }).join('');

    container.innerHTML = html;
  }

  function showBadge(count) {
    var badge = document.getElementById('announcementCountBadge');
    if (!badge) return;
    if (count > 0) {
      badge.textContent = count;
      badge.style.display = 'inline-block';
    } else {
      badge.textContent = '0';
      badge.style.display = 'none';
    }
  }

  function fetchAnnouncements() {
    fetch(base + 'Announcement/active', { credentials: 'same-origin' })
      .then(r => r.json())
      .then(function(items){
        renderList(items);

        var lastSeen = getLastSeen();
        var newest = latestDate(items);
        var unseenCount = countNew(items, lastSeen);

        showBadge(unseenCount || 0);

        if (newest) {
          window.__ANN_NEWEST_ISO = isoString(newest);
        }
      })
      .catch(()=>{ /* silent */ });
  }

  fetchAnnouncements();
  setInterval(fetchAnnouncements, 20000);

  $('.notification-list > a[data-toggle="dropdown"]').on('click', function() {
    var newestISO = window.__ANN_NEWEST_ISO;
    if (newestISO) {
      setLastSeen(newestISO);
      showBadge(0);
    } else {
      showBadge(0);
    }
  });

  $(document).on('click', '.ann-open', function (e) {
    var newestISO = window.__ANN_NEWEST_ISO;
    if (newestISO) setLastSeen(newestISO);
    showBadge(0);
  });

  $('#viewAnnouncementModal').on('show.bs.modal', function (e) {
    var t = $(e.relatedTarget);
    var title   = t.data('title')   || '';
    var message = t.data('message') || '';
    var img     = t.data('image')   || '';
    var hasImg  = t.data('hasimage') === 1 || t.data('hasimage') === '1';

    $('#vamTitle').text(title);
    $('#vamMessage').html(message);
    if (hasImg && img) {
      $('#vamImage').attr('src', img);
      $('#vamImageWrap').show();
    } else {
      $('#vamImage').attr('src', '');
      $('#vamImageWrap').hide();
    }
  });
})();
</script>



			
			<div class="navbar-custom">
                <ul class="list-unstyled topnav-menu float-right mb-0">
				<?php if($this->session->userdata('level')==='Student'):?>
					
					<?php else:?>
                <li class="dropdown notification-list">
                    <a class="nav-link dropdown-toggle  waves-effect" data-toggle="dropdown" href="#" role="button" aria-haspopup="false" aria-expanded="false">
                        <i class="mdi mdi-cake-variant"></i>
             
                    </a>
                   
					<div class="dropdown-menu dropdown-menu-right dropdown-lg">
                        
					
                        <!-- item-->
                        <div class="dropdown-item noti-title">
                            <h5 class="font-16 m-0">
                                Birthday Celebrants
                            </h5>
                        </div>

                        <div class="slimscroll noti-scroll">

                            <div class="inbox-widget">
                                <a href="<?= base_url(); ?>Page/bdayToday">
                                    <div class="inbox-item">
                                        <div class="inbox-item-img"><img src="<?= base_url(); ?>assets/images/cake.png" class="rounded-circle" alt=""></div>
                                        <p class="inbox-item-author">Today's</p>
                                        <p class="inbox-item-text text-truncate">Birthday Celebrants</p>
                                    </div>
                                </a>
                                <a href="<?= base_url(); ?>Page/bdayMonth">
                                    <div class="inbox-item">
                                        <div class="inbox-item-img"><img src="<?= base_url(); ?>assets/images/cake.png" class="rounded-circle" alt=""></div>
                                        <p class="inbox-item-author">This Month's</p>
										<p class="inbox-item-text text-truncate">Birthday Celebrants</p>
                                    </div>
                                </a>

                            </div> <!-- end inbox-widget -->

                        </div>

                    </div>
					
                </li>
			<?php endif;?>
   <li class="dropdown notification-list">
  <a class="nav-link dropdown-toggle waves-effect position-relative" data-toggle="dropdown" href="#" role="button" aria-haspopup="false" aria-expanded="false">
    <i class="mdi mdi-email-outline" style="font-size: 20px;"></i>
    <span id="messageCountBadge" class="badge badge-danger badge-pill position-absolute" style="top: 4px; right: 0; font-size: 10px; display: none;">0</span>
  </a>

  <div class="dropdown-menu dropdown-menu-right dropdown-lg">
    <div class="dropdown-item noti-title">
      <h5 class="font-16 m-0">Recent Conversations</h5>
    </div>
    <div class="slimscroll noti-scroll">
      <div id="recentConversationList" class="inbox-widget">
        <em class="dropdown-item-text text-muted">Loading...</em>
      </div>
    </div>
  </div>
</li>

<li class="dropdown notification-list">
  <a class="nav-link dropdown-toggle waves-effect position-relative" data-toggle="dropdown" href="#" role="button" aria-haspopup="false" aria-expanded="false">
    <i class="mdi mdi-bell-outline" style="font-size: 20px;"></i>
    <span id="announcementCountBadge" class="badge badge-danger badge-pill position-absolute" style="top: 4px; right: 0; font-size: 10px; display: none;">0</span>
  </a>

  <div class="dropdown-menu dropdown-menu-right dropdown-lg">
    <div class="dropdown-item noti-title">
      <h5 class="font-16 m-0">Recent Announcements</h5>
    </div>
    <div class="slimscroll noti-scroll">
      <div id="recentAnnouncementList" class="inbox-widget">
        <em class="dropdown-item-text text-muted">Loading...</em>
      </div>
    </div>
  </div>
</li>


                    <li class="dropdown notification-list">
                        <a class="nav-link dropdown-toggle nav-user mr-0 waves-effect" data-toggle="dropdown" href="#" role="button" aria-haspopup="false" aria-expanded="false">
                            <img src="<?= base_url(); ?>upload/profile/<?php echo htmlspecialchars($this->session->userdata('avatar'), ENT_QUOTES, 'UTF-8'); ?>" alt="user-image" class="rounded-circle">
                            <span class="pro-user-name ml-1">
                                <?php echo $this->session->userdata('fname');?>   <i class="mdi mdi-chevron-down"></i> 
                            </span>
                        </a>
                        
                        <div class="dropdown-menu dropdown-menu-right profile-dropdown ">
                            <!-- item-->





							 <!-- item-->
                            <a href="<?= base_url(); ?>Page/changeDP?id=<?php echo $this->session->userdata('username');?>" class="dropdown-item notify-item"> 
                                <i class="mdi mdi-settings-outline"></i>
                                <span>Change Profile Pic</span>
                            </a>
							
							<?php if($this->session->userdata('level')==='Student'):?>
                            <a href="<?= base_url(); ?>Page/studentsprofile" class="dropdown-item notify-item">
                                <i class="mdi mdi-account-outline"></i>
                                <span>My Profile</span>
                            </a>
							<?php else:?>
							<a href="<?= base_url(); ?>Page/staffprofile?id=<?php echo $this->session->userdata('IDNumber');?>" class="dropdown-item notify-item">
                                <i class="mdi mdi-account-outline"></i>
                                <span>My Profile</span>
                            </a>
							
							 <?php endif;?>

                            <!-- item-->
                            <a href="<?= base_url(); ?>Page/lockScreen?id=<?php echo $this->session->userdata('username');?>" class="dropdown-item notify-item">
                                <i class="mdi mdi-lock-outline"></i>
                                <span>Lock Screen</span>
                            </a>

                            <div class="dropdown-divider"></div>

                            <!-- item-->
                            <a href="<?php echo site_url('login/logout');?>" class="dropdown-item notify-item">
                                <i class="mdi mdi-logout-variant"></i>
                                <span>Logout</span>
                            </a>

                        </div>
                    </li>

                    <li class="dropdown notification-list">
                        <a href="javascript:void(0);" class="nav-link right-bar-toggle waves-effect">
                            <i class="mdi mdi-settings-outline noti-icon"></i>
                        </a>
                    </li>


                </ul>

                <!-- LOGO -->
                <div class="logo-box">
                    <a href="#" class="logo text-center logo-dark">
                        <span class="logo-lg">
                            <img src="<?= base_url(); ?>assets/images/srms-logo.png" alt="" height="18">
                            <!-- <span class="logo-lg-text-dark">Velonic</span> -->
                        </span>
                        <span class="logo-sm">
                            <!-- <span class="logo-lg-text-dark">V</span> -->
                            <img src="<?= base_url(); ?>assets/images/srms-logo.png" alt="" height="22">
                        </span>
                    </a>

                    <a href="#" class="logo text-center logo-light">
                        <span class="logo-lg">
                            <img src="<?= base_url(); ?>assets/images/srms-logo.png" alt="" height="50">
                            <!-- <span class="logo-lg-text-dark">Velonic</span> -->
                        </span>
                        <span class="logo-sm">
                            <!-- <span class="logo-lg-text-dark">V</span> -->
                            <img src="<?= base_url(); ?>assets/images/logo-sm1.png" alt="" height="40">
                        </span>
                    </a>
                </div>

                <!-- LOGO -->
  

                <ul class="list-unstyled topnav-menu topnav-menu-left m-0">
                    <li>
                        <button class="button-menu-mobile waves-effect">
                            <i class="mdi mdi-menu"></i>
                        </button>
                    </li>
        
                    <li class="d-none d-lg-block">
                        <form class="app-search">
                            <div class="app-search-box">
                                <div class="input-group">
                                    <input type="text" class="form-control" placeholder="Search...">
                                    <div class="input-group-append">
                                        <button class="btn" type="submit">
                                            <i class="fas fa-search"></i>
                                        </button>
                                    </div>
                                </div>
                            </div>
                        </form>
                    </li>
                </ul>
            </div>