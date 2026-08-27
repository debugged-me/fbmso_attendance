           <!-- <script type="text/javascript"> 
        window.history.forward(); 
        function noBack() { 
            window.history.forward(); 
        } 
    </script> -->


           <div class="navbar-custom ms-appbar" id="ms-appbar">
               <ul class="list-unstyled topnav-menu float-right mb-0" id="ms-top-actions">
                   <?php if ($this->session->userdata('level') === 'Student'): ?>

                   <?php else: ?>
                       <li class="dropdown notification-list ms-overflow-source" id="bday-li">
                           <a id="bdayDropdown"
                               class="nav-link dropdown-toggle waves-effect"
                               data-toggle="dropdown" href="#" role="button"
                               aria-haspopup="false" aria-expanded="false">
                               <i class="mdi mdi-cake-variant"></i>
                               <span id="bday-badge" class="badge badge-danger noti-icon-badge" style="display:none;">0</span>
                           </a>

                           <div class="dropdown-menu dropdown-menu-right dropdown-lg">
                               <!-- item-->
                               <div class="dropdown-item noti-title">
                                   <h5 class="font-16 m-0">Birthday Celebrants</h5>
                               </div>

                               <div class="slimscroll noti-scroll">
                                   <div class="inbox-widget">
                                       <a href="<?= base_url(); ?>Page/bdayToday" class="bday-link">
                                           <div class="inbox-item">
                                               <div class="inbox-item-img">
                                                   <img src="<?= base_url(); ?>assets/images/cake.png" class="rounded-circle" alt="">
                                               </div>
                                               <p class="inbox-item-author">Today's</p>
                                               <p class="inbox-item-text text-truncate">Birthday Celebrants</p>
                                           </div>
                                       </a>

                                       <a href="<?= base_url(); ?>Page/bdayMonth" class="bday-link">
                                           <div class="inbox-item">
                                               <div class="inbox-item-img">
                                                   <img src="<?= base_url(); ?>assets/images/cake.png" class="rounded-circle" alt="">
                                               </div>
                                               <p class="inbox-item-author">This Month's</p>
                                               <p class="inbox-item-text text-truncate">Birthday Celebrants</p>
                                           </div>
                                       </a>
                                   </div> <!-- end inbox-widget -->
                               </div>
                           </div>
                       </li>

                       <?php include(APPPATH . 'views/includes/req_bell.php'); ?>
                   <?php endif; ?>
                   <li class="dropdown notification-list ms-profile-action">
                       <a class="nav-link dropdown-toggle nav-user mr-0 waves-effect" data-toggle="dropdown" href="#" role="button" aria-haspopup="false" aria-expanded="false">
                           <img src="<?= base_url(); ?>upload/profile/<?php echo $this->session->userdata('avatar'); ?>" alt="user-image" class="rounded-circle">
                           <span class="pro-user-name ml-1">
                               <?php echo $this->session->userdata('fname'); ?> <i class="mdi mdi-chevron-down"></i>
                           </span>
                       </a>
                       <div class="dropdown-menu dropdown-menu-right profile-dropdown ">
                           <!-- item-->
                           <a href="#" class="dropdown-item notify-item" data-toggle="modal" data-target="#changeProfilePicModal">
                               <i class="mdi mdi-account-circle-outline"></i>
                               <span>Change Profile Pic</span>
                           </a>

                           <a href="#" class="dropdown-item notify-item" data-toggle="modal" data-target="#changePasswordModal">
                               <i class="mdi mdi-shield-lock-outline"></i>
                               <span>Change Password</span>
                           </a>

                           <?php if ($this->session->userdata('level') === 'Student'): ?>
                               <a href="<?= base_url(); ?>Page/studentsprofile" class="dropdown-item notify-item">
                                   <i class="mdi mdi-account-outline"></i>
                                   <span>My Profile</span>
                               </a>
                           <?php else: ?>
                               <a href="<?= base_url(); ?>Page/staffprofile?id=<?php echo $this->session->userdata('IDNumber'); ?>" class="dropdown-item notify-item">
                                   <i class="mdi mdi-account-outline"></i>
                                   <span>My Profile</span>
                               </a>

                           <?php endif; ?>

                           <!-- item-->
                           <a href="<?= base_url(); ?>Page/lockScreen?id=<?php echo $this->session->userdata('username'); ?>" class="dropdown-item notify-item">
                               <i class="mdi mdi-lock-outline"></i>
                               <span>Lock Screen</span>
                           </a>

                           <div class="dropdown-divider"></div>

                           <!-- item-->
                           <a href="<?php echo site_url('login/logout'); ?>" class="dropdown-item notify-item">
                               <i class="mdi mdi-logout-variant"></i>
                               <span>Logout</span>
                           </a>

                       </div>
                   </li>

                   <li class="dropdown notification-list ms-settings-action ms-overflow-source">
                       <a href="javascript:void(0);" class="nav-link right-bar-toggle waves-effect">
                           <i class="mdi mdi-settings-outline noti-icon"></i>
                       </a>
                   </li>


               </ul>

               <span class="ms-appbar-title" aria-live="polite">Attendance Portal</span>

               <!-- LOGO -->
               <div class="logo-box">
                   <a href="#" class="logo text-center logo-dark">
                       <span class="logo-lg">
                           <img src="<?= base_url(); ?>assets/images/srms-logo-1.png" alt="" height="14">
                           <!-- <span class="logo-lg-text-dark">Velonic</span> -->
                       </span>
                       <span class="logo-sm">
                           <!-- <span class="logo-lg-text-dark">V</span> -->
                           <img src="<?= base_url(); ?>assets/images/Attendance.png" alt="" height="22">
                       </span>
                   </a>

                   <a href="#" class="logo text-center logo-light">
                       <span class="logo-lg">
                           <img src="<?= base_url(); ?>assets/images/srms-logo-1.png" alt="" height="30">
                           <!-- <span class="logo-lg-text-dark">Velonic</span> -->
                       </span>
                       <span class="logo-sm">
                           <!-- <span class="logo-lg-text-dark">V</span> -->
                           <img src="<?= base_url(); ?>assets/images/Attendance.png" alt="" height="22">
                       </span>
                   </a>
               </div>

               <!-- LOGO -->


               <ul class="list-unstyled topnav-menu topnav-menu-left m-0" id="ms-topnav-left">
                   <li>
                       <button class="button-menu-mobile waves-effect" id="ms-drawer-toggle" type="button" aria-label="Open menu">
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

           <!-- ===== Change Profile Pic Modal ===== -->
           <link rel="stylesheet" href="<?= base_url('assets/css/uniform-page.css?v=20260831'); ?>">
           <style>
             /* ===== Shared modal styles ===== */
             #changeProfilePicModal .modal-content,
             #changePasswordModal .modal-content {
               border:none !important; border-radius:20px !important; overflow:hidden; box-shadow:0 24px 60px rgba(13,27,75,.3);
             }
             #changeProfilePicModal .modal-header,
             #changePasswordModal .modal-header {
               background:linear-gradient(135deg,#1a2a6c,#2a4090) !important; color:#fff !important; border:none !important; padding:18px 24px;
             }
             #changeProfilePicModal .modal-header .modal-title,
             #changePasswordModal .modal-header .modal-title {
               font-weight:800; font-size:1.05rem; display:flex; align-items:center; gap:8px; color:#fff !important;
             }
             #changeProfilePicModal .modal-header .close,
             #changePasswordModal .modal-header .close { color:#fff !important; opacity:.8; font-size:1.6rem; text-shadow:none; }
             #changeProfilePicModal .modal-header .close:hover,
             #changePasswordModal .modal-header .close:hover { opacity:1; }
             #changeProfilePicModal .modal-body,
             #changePasswordModal .modal-body { padding:24px !important; background:#f8fafc !important; }
             #changeProfilePicModal .modal-footer,
             #changePasswordModal .modal-footer { border:none !important; padding:14px 24px !important; background:#f8fafc !important; }

             /* Labels — force readable color */
             #changeProfilePicModal label,
             #changePasswordModal label {
               font-size:.78rem !important; font-weight:700 !important; color:#3b4a6b !important;
               margin-bottom:5px; display:block;
             }

             /* Password input wrapper */
             .pwd-field { position:relative; }
             .pwd-field .form-control {
               border-radius:12px !important; border:1px solid #d1d9e6 !important;
               padding:11px 44px 11px 14px !important; font-size:.88rem !important;
               color:#1a2942 !important; background:#fff !important;
             }
             .pwd-field .form-control:focus {
               border-color:#4266d4 !important; box-shadow:0 0 0 3px rgba(66,102,212,.12) !important;
             }
             .pwd-field .form-control::placeholder { color:#9aa5b8 !important; }
             .pwd-toggle-eye {
               position:absolute; right:10px; top:50%; transform:translateY(-50%);
               background:none; border:none; cursor:pointer; padding:4px;
               color:#6b7a99; font-size:20px; line-height:1; z-index:2;
             }
             .pwd-toggle-eye:hover { color:#2a4090; }

             /* Caps lock warning */
             .caps-warn {
               display:none; align-items:center; gap:5px; margin-top:6px;
               font-size:.76rem; font-weight:600; color:#b45309;
               background:#fef3c7; border:1px solid #fcd34d; border-radius:8px; padding:5px 10px;
             }
             .caps-warn.show { display:flex; }
             .caps-warn i { font-size:16px; }

             /* Password strength bar */
             .pwd-strength { margin-top:8px; }
             .pwd-strength-bar {
               height:5px; border-radius:5px; background:#e6ebf5; overflow:hidden; transition:all .2s ease;
             }
             .pwd-strength-fill {
               height:100%; width:0%; border-radius:5px; transition:all .25s ease;
             }
             .pwd-strength-label {
               font-size:.72rem; font-weight:700; margin-top:4px; color:#9aa5b8;
             }
             .pwd-strength-fill.s-weak   { background:#ef4444; }
             .pwd-strength-fill.s-fair   { background:#f59e0b; }
             .pwd-strength-fill.s-good   { background:#3b82f6; }
             .pwd-strength-fill.s-strong { background:#16a34a; }
             .pwd-strength-label.s-weak   { color:#ef4444; }
             .pwd-strength-label.s-fair   { color:#f59e0b; }
             .pwd-strength-label.s-good   { color:#3b82f6; }
             .pwd-strength-label.s-strong { color:#16a34a; }

             /* Match indicator */
             .pwd-match { font-size:.76rem; font-weight:600; margin-top:6px; }
             .pwd-match.match-no  { color:#ef4444; }
             .pwd-match.match-yes { color:#16a34a; }

             /* ===== Profile pic modal ===== */
             .pp-dropzone {
               border:2px dashed #c7d2fe; border-radius:16px; padding:28px 20px;
               text-align:center; cursor:pointer; transition:all .2s ease; background:#fff;
             }
             .pp-dropzone:hover { border-color:#4266d4; background:#f5f7fc; }
             .pp-dropzone .pp-dz-icon { font-size:42px; color:#4266d4; margin-bottom:10px; }
             .pp-dropzone .pp-dz-text { font-size:.86rem; font-weight:600; color:#3b4a6b; }
             .pp-dropzone .pp-dz-sub { font-size:.76rem; color:#9aa5b8; margin-top:4px; }
             .pp-dropzone.has-preview { padding:20px; border-style:solid; border-color:#e6ebf5; }

             .pp-preview-wrap {
               display:none; margin-top:16px; text-align:center;
             }
             .pp-preview-wrap.show { display:block; }
             .pp-preview-img {
               width:160px; height:160px; border-radius:50%; object-fit:cover;
               border:4px solid #e6ebf5; box-shadow:0 6px 18px rgba(13,27,75,.12);
               margin:0 auto;
             }
             .pp-preview-name { font-size:.78rem; color:#6b7a99; margin-top:10px; font-weight:600; }
             .pp-preview-change {
               display:inline-block; margin-top:8px; font-size:.78rem; font-weight:700;
               color:#4266d4; cursor:pointer; text-decoration:none;
             }
             .pp-preview-change:hover { text-decoration:underline; }
             .pp-hint {
               font-size:.76rem; color:#6b7a99; margin-top:14px; text-align:center;
             }
             .pp-hint b { color:#ef4444; }
           </style>

           <!-- ===== Change Profile Picture Modal ===== -->
           <div class="modal fade" id="changeProfilePicModal" tabindex="-1" role="dialog" aria-modal="true" aria-labelledby="changePicTitle">
             <div class="modal-dialog modal-dialog-centered" role="document">
               <div class="modal-content">
                 <div class="modal-header">
                   <h5 class="modal-title" id="changePicTitle"><i class="mdi mdi-account-circle-outline"></i> Change Profile Picture</h5>
                   <button type="button" class="close" data-dismiss="modal" aria-label="Close"><span aria-hidden="true">&times;</span></button>
                 </div>
                 <div class="modal-body">
                   <form action="<?= site_url('Page/uploadProfPic'); ?>" method="POST" enctype="multipart/form-data" id="changePicForm">
                     <input type="hidden" name="StudentNumber" value="<?= htmlspecialchars($this->session->userdata('username'), ENT_QUOTES, 'UTF-8'); ?>">

                     <!-- Dropzone / file picker -->
                     <div class="pp-dropzone" id="ppDropzone" onclick="document.getElementById('ppFileInput').click();">
                       <div class="pp-dz-icon"><i class="mdi mdi-cloud-upload-outline"></i></div>
                       <div class="pp-dz-text">Click to choose a photo</div>
                       <div class="pp-dz-sub">JPG, PNG, or GIF</div>
                       <input type="file" id="ppFileInput" name="nonoy" accept="image/*" required style="display:none;">
                     </div>

                     <!-- Preview -->
                     <div class="pp-preview-wrap" id="ppPreviewWrap">
                       <img id="ppPreviewImg" class="pp-preview-img" src="" alt="Preview">
                       <div class="pp-preview-name" id="ppFileName"></div>
                       <a class="pp-preview-change" onclick="document.getElementById('ppFileInput').click();">Choose a different photo</a>
                     </div>

                     <div class="pp-hint">
                       Limit the size to <b>2MB only</b>. Recommended size is <b>215px by 215px</b>.
                     </div>
                   </form>
                 </div>
                 <div class="modal-footer">
                   <button type="button" class="up-btn up-btn-ghost" data-dismiss="modal">Cancel</button>
                   <button type="submit" form="changePicForm" class="up-btn up-btn-primary" id="ppSubmitBtn" disabled><i class="mdi mdi-upload"></i> Upload</button>
                 </div>
               </div>
             </div>
           </div>

           <!-- ===== Change Password Modal ===== -->
           <div class="modal fade" id="changePasswordModal" tabindex="-1" role="dialog" aria-modal="true" aria-labelledby="changePwdTitle">
             <div class="modal-dialog modal-dialog-centered" role="document">
               <div class="modal-content">
                 <div class="modal-header">
                   <h5 class="modal-title" id="changePwdTitle"><i class="mdi mdi-shield-lock-outline"></i> Change Password</h5>
                   <button type="button" class="close" data-dismiss="modal" aria-label="Close"><span aria-hidden="true">&times;</span></button>
                 </div>
                 <div class="modal-body">
                   <form method="POST" action="<?= base_url(); ?>page/update_password" enctype="multipart/form-data" id="changePwdForm">
                     <input type="hidden" name="txt_hidden" value="<?= htmlspecialchars($this->session->userdata('username'), ENT_QUOTES, 'UTF-8'); ?>">

                     <!-- Current Password -->
                     <div class="form-group">
                       <label for="modalCurrentPwd">Current Password</label>
                       <div class="pwd-field">
                         <input type="password" class="form-control" id="modalCurrentPwd" name="currentpassword" placeholder="Enter your current password" required>
                         <button type="button" class="pwd-toggle-eye" data-target="modalCurrentPwd" aria-label="Show password"><i class="mdi mdi-eye-outline"></i></button>
                       </div>
                       <div class="caps-warn" id="capsWarn1"><i class="mdi mdi-alert"></i> Caps Lock is ON</div>
                     </div>

                     <!-- New Password -->
                     <div class="form-group">
                       <label for="modalNewPwd">New Password</label>
                       <div class="pwd-field">
                         <input type="password" class="form-control" id="modalNewPwd" name="newpassword" placeholder="Enter your new password" minlength="8" required>
                         <button type="button" class="pwd-toggle-eye" data-target="modalNewPwd" aria-label="Show password"><i class="mdi mdi-eye-outline"></i></button>
                       </div>
                       <div class="caps-warn" id="capsWarn2"><i class="mdi mdi-alert"></i> Caps Lock is ON</div>
                       <div class="pwd-strength">
                         <div class="pwd-strength-bar"><div class="pwd-strength-fill" id="pwdStrengthFill"></div></div>
                         <div class="pwd-strength-label" id="pwdStrengthLabel">Enter a password</div>
                       </div>
                     </div>

                     <!-- Confirm Password -->
                     <div class="form-group">
                       <label for="modalConfirmPwd">Confirm Password</label>
                       <div class="pwd-field">
                         <input type="password" class="form-control" id="modalConfirmPwd" name="cnewpassword" placeholder="Repeat your new password" required>
                         <button type="button" class="pwd-toggle-eye" data-target="modalConfirmPwd" aria-label="Show password"><i class="mdi mdi-eye-outline"></i></button>
                       </div>
                       <div class="caps-warn" id="capsWarn3"><i class="mdi mdi-alert"></i> Caps Lock is ON</div>
                       <div class="pwd-match" id="pwdMatch"></div>
                     </div>
                   </form>
                 </div>
                 <div class="modal-footer">
                   <button type="button" class="up-btn up-btn-ghost" data-dismiss="modal">Cancel</button>
                   <button type="submit" form="changePwdForm" class="up-btn up-btn-primary" id="pwdSubmitBtn" disabled><i class="mdi mdi-content-save"></i> Update Password</button>
                 </div>
               </div>
             </div>
           </div>

           <!-- ===== Modal scripts ===== -->
           <script>
           (function(){
             /* ---- Password eye toggle ---- */
             document.querySelectorAll('.pwd-toggle-eye').forEach(function(btn){
               btn.addEventListener('click', function(){
                 var target = document.getElementById(this.getAttribute('data-target'));
                 if (!target) return;
                 var icon = this.querySelector('i');
                 if (target.type === 'password') {
                   target.type = 'text';
                   icon.className = 'mdi mdi-eye-off-outline';
                 } else {
                   target.type = 'password';
                   icon.className = 'mdi mdi-eye-outline';
                 }
               });
             });

             /* ---- Caps Lock detection ---- */
             function bindCapsLock(inputId, warnId){
               var input = document.getElementById(inputId);
               var warn  = document.getElementById(warnId);
               if (!input || !warn) return;
               input.addEventListener('keyup', function(e){
                 if (e.getModifierState && e.getModifierState('CapsLock')) {
                   warn.classList.add('show');
                 } else {
                   warn.classList.remove('show');
                 }
               });
               input.addEventListener('blur', function(){ warn.classList.remove('show'); });
             }
             bindCapsLock('modalCurrentPwd','capsWarn1');
             bindCapsLock('modalNewPwd','capsWarn2');
             bindCapsLock('modalConfirmPwd','capsWarn3');

             /* ---- Password strength ---- */
             var newPwd = document.getElementById('modalNewPwd');
             var fill   = document.getElementById('pwdStrengthFill');
             var label  = document.getElementById('pwdStrengthLabel');
             function checkStrength(v){
               var score = 0;
               if (v.length >= 8) score++;
               if (v.length >= 12) score++;
               if (/[A-Z]/.test(v) && /[a-z]/.test(v)) score++;
               if (/[0-9]/.test(v)) score++;
               if (/[^A-Za-z0-9]/.test(v)) score++;
               return score;
             }
             newPwd.addEventListener('input', function(){
               var v = this.value;
               var s = checkStrength(v);
               fill.className = 'pwd-strength-fill';
               label.className = 'pwd-strength-label';
               if (!v) {
                 fill.style.width = '0%';
                 label.textContent = 'Enter a password';
               } else if (s <= 1) {
                 fill.classList.add('s-weak'); fill.style.width = '25%';
                 label.classList.add('s-weak'); label.textContent = 'Weak';
               } else if (s <= 2) {
                 fill.classList.add('s-fair'); fill.style.width = '50%';
                 label.classList.add('s-fair'); label.textContent = 'Fair';
               } else if (s <= 3) {
                 fill.classList.add('s-good'); fill.style.width = '75%';
                 label.classList.add('s-good'); label.textContent = 'Good';
               } else {
                 fill.classList.add('s-strong'); fill.style.width = '100%';
                 label.classList.add('s-strong'); label.textContent = 'Strong';
               }
               updateMatch();
             });

             /* ---- Confirm password match ---- */
             var confirmPwd = document.getElementById('modalConfirmPwd');
             var matchEl = document.getElementById('pwdMatch');
             function updateMatch(){
               var nv = newPwd.value;
               var cv = confirmPwd.value;
               if (!cv) { matchEl.textContent=''; matchEl.className='pwd-match'; return; }
               if (nv === cv) {
                 matchEl.textContent = 'Passwords match';
                 matchEl.className = 'pwd-match match-yes';
               } else {
                 matchEl.textContent = 'Passwords do not match';
                 matchEl.className = 'pwd-match match-no';
               }
             }
             confirmPwd.addEventListener('input', updateMatch);

             /* ---- Enable/disable submit ---- */
             var pwdSubmit = document.getElementById('pwdSubmitBtn');
             function validatePwdForm(){
               var nv = newPwd.value;
               var cv = confirmPwd.value;
               pwdSubmit.disabled = !(nv.length >= 8 && nv === cv && nv.length > 0);
             }
             newPwd.addEventListener('input', validatePwdForm);
             confirmPwd.addEventListener('input', validatePwdForm);

             /* ---- Profile pic preview ---- */
             var ppInput = document.getElementById('ppFileInput');
             var ppPreviewWrap = document.getElementById('ppPreviewWrap');
             var ppPreviewImg = document.getElementById('ppPreviewImg');
             var ppFileName = document.getElementById('ppFileName');
             var ppDropzone = document.getElementById('ppDropzone');
             var ppSubmit = document.getElementById('ppSubmitBtn');

             ppInput.addEventListener('change', function(){
               var file = this.files && this.files[0];
               if (!file) return;
               if (file.size > 2 * 1024 * 1024) {
                 alert('File is too large. Maximum size is 2MB.');
                   this.value = '';
                   ppPreviewWrap.classList.remove('show');
                   ppDropzone.classList.remove('has-preview');
                   ppSubmit.disabled = true;
                   return;
                 }
                 var reader = new FileReader();
                 reader.onload = function(e){
                   ppPreviewImg.src = e.target.result;
                   ppFileName.textContent = file.name;
                   ppPreviewWrap.classList.add('show');
                   ppDropzone.classList.add('has-preview');
                   ppSubmit.disabled = false;
                 };
                 reader.readAsDataURL(file);
             });

             /* Reset modals on close */
             $('#changeProfilePicModal').on('hidden.bs.modal', function(){
               ppPreviewWrap.classList.remove('show');
               ppDropzone.classList.remove('has-preview');
               ppSubmit.disabled = true;
               ppInput.value = '';
             });
             $('#changePasswordModal').on('hidden.bs.modal', function(){
               document.getElementById('changePwdForm').reset();
               fill.className = 'pwd-strength-fill'; fill.style.width='0%';
               label.className = 'pwd-strength-label'; label.textContent='Enter a password';
               matchEl.textContent=''; matchEl.className='pwd-match';
               pwdSubmit.disabled = true;
               document.querySelectorAll('.caps-warn').forEach(function(w){ w.classList.remove('show'); });
             });
           })();
           </script>
