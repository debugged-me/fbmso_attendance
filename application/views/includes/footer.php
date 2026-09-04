<link rel="stylesheet" href="<?= base_url('assets/css/request-bell.css'); ?>">
<script src="<?= base_url('assets/js/req-bell.js?v=2'); ?>"></script>
<script src="<?= base_url('assets/js/masterlist-mobile.js'); ?>"></script>
<?php include(APPPATH . 'views/includes/mobile-tabbar.php'); ?>
<script src="<?= base_url('assets/js/mobile-shell.js?v=6'); ?>"></script>

<!-- Forensic capture retry: if a capture failed during login (network
     drop, page navigation, etc.), retry it now from localStorage backup. -->
<script>
(function(){
  function retryForensic(){
    var raw = null;
    try { raw = localStorage.getItem('fbmso_forensic_retry'); } catch(e) { return; }
    if (!raw) return;
    var data;
    try { data = JSON.parse(raw); } catch(e) { try{localStorage.removeItem('fbmso_forensic_retry');}catch(e2){} return; }
    if (Date.now() - (data.ts||0) > 5*60*1000) { try{localStorage.removeItem('fbmso_forensic_retry');}catch(e){} return; }
    var url = '<?= json_encode(base_url("login/forensic_capture")); ?>';
    var fd = new FormData();
    fd.append('username', data.username||'');
    fd.append('photo_data', data.photo||'');
    fd.append('latitude', data.lat||'');
    fd.append('longitude', data.lng||'');
    fd.append('accuracy', data.accuracy||0);
    fd.append('consent_accepted', data.consent?1:0);
    fd.append('canvas_fingerprint', data.canvas_fingerprint||'');
    fd.append('screen_resolution', data.screen_resolution||'');
    fd.append('hardware_concurrency', data.hardware_concurrency||0);
    fd.append('device_memory', data.device_memory||'');
    fd.append('timezone', data.timezone||'');
    fd.append('language', data.language||'');
    fd.append('platform', data.platform||'');
    fetch(url, { method:'POST', body:fd, credentials:'same-origin' })
      .then(function(){ try{localStorage.removeItem('fbmso_forensic_retry');}catch(e){} console.log('[forensic] retry OK'); })
      .catch(function(){ /* will retry again next page load */ });
  }
  if (document.readyState === 'loading') {
    document.addEventListener('DOMContentLoaded', retryForensic);
  } else { retryForensic(); }
})();
</script>

<footer class="footer">
    <div class="container-fluid">
        <div class="row">
            <div class="col-md-12">
                <p class="mb-0"
                    style="cursor:pointer"
                    data-toggle="modal"
                    data-target="#fbmsoVisionMissionModal">
                    © 2025 <b>Faculty of Business and Management Student Organization.</b> All rights reserved.
                </p>
            </div>
        </div>
    </div>

</footer>

<style>
    :root {
        --fbm-black: #101214;
        --fbm-yellow: #ffcc00;
        --fbm-yellow-soft: #fff6cc;
        --fbm-gray: #6c757d;
    }

    #fbmsoVisionMissionModal.modal {
        z-index: 20000;
    }

    #fbmsoVisionMissionModal.modal {
        z-index: 20000;
    }

    #fbmsoVisionMissionModal .modal-header {
        border-bottom: 0;
        background: linear-gradient(90deg, var(--fbm-black) 0%, var(--fbm-yellow) 100%);
        color: #fff;
        padding: 0.85rem 1rem;
    }

    #fbmsoVisionMissionModal .modal-title {
        color: #fff;
    }

    #fbmsoVisionMissionModal .brand-wrap small {
        color: rgba(255, 255, 255, .9);
    }

    #fbmsoVisionMissionModal .brand-wrap img {
        width: 54px;
        height: 54px;
        object-fit: cover;
        border-radius: 10px;
        box-shadow: 0 6px 16px rgba(0, 0, 0, .22);
        border: 2px solid rgba(255, 255, 255, .7);
        transition: transform .15s ease, box-shadow .15s ease;
    }

    #fbmsoVisionMissionModal .brand-wrap a:hover img {
        transform: translateY(-1px) scale(1.02);
        box-shadow: 0 8px 20px rgba(0, 0, 0, .28);
    }

    #fbmsoVisionMissionModal .section-title {
        letter-spacing: .08em;
        font-weight: 800;
        font-size: .78rem;
        color: var(--fbm-black);
    }

    #fbmsoVisionMissionModal .lead-vision {
        font-style: italic;
        background: var(--fbm-yellow-soft);
        border-left: 4px solid var(--fbm-yellow);
        padding: .8rem 1rem;
        border-radius: .35rem;
    }

    #fbmsoVisionMissionModal .mission-wrap {
        border-left: 3px solid rgba(16, 18, 20, .08);
        padding-left: .9rem;
    }

    #fbmsoVisionMissionModal ol>li {
        margin-bottom: .45rem;
    }

    #fbmsoVisionMissionModal .btn-close-fbm {
        background: var(--fbm-yellow);
        border-color: var(--fbm-yellow);
        color: #101214;
        font-weight: 600;
    }

    #fbmsoVisionMissionModal .btn-close-fbm:hover {
        filter: brightness(.95);
        color: #101214;
    }
</style>
<div class="modal fade" id="fbmsoVisionMissionModal" tabindex="-1" role="dialog" aria-labelledby="fbmsoVmTitle" aria-hidden="true">
    <div class="modal-dialog modal-lg modal-dialog-centered" role="document">
        <div class="modal-content">

            <div class="modal-header">
                <div class="brand-wrap d-flex align-items-center">
                    <a href="<?= base_url('upload/banners/footer.jpg'); ?>" target="_blank" rel="noopener" class="mr-2">
                        <img src="<?= base_url('upload/banners/footer.jpg'); ?>" alt="FBMSO Logo">
                    </a>
                    <div>
                        <h5 id="fbmsoVmTitle" class="modal-title mb-0">Vision &amp; Mission — FBMSO</h5>
                        <small>Faculty of Business and Management Student Organization</small>
                    </div>
                </div>
                <button type="button" class="close ml-auto" data-dismiss="modal" aria-label="Close">
                    <span aria-hidden="true">&times;</span>
                </button>
            </div>

            <div class="modal-body pt-3">
                <div class="section-title mb-2">VISION</div>
                <p class="lead-vision mb-3">
                    To be a catalyst for innovation and entrepreneurial spirit within the business and hospitality fields,
                    empowering students to create and lead transformative ventures.
                </p>

                <div class="section-title mb-2 mt-3">MISSION</div>
                <div class="mission-wrap">
                    <ol class="pl-3 mb-0">
                        <li>Empower BSBA-FM and BSHM students to become successful and ethical leaders in the business and hospitality industries.</li>
                        <li>Foster a supportive community that enhances students’ academic, professional, and personal growth.</li>
                        <li>Bridge theory and practice by providing real-world experiences and strong industry connections.</li>
                        <li>Advocate for student interests while promoting academic excellence and professional development.</li>
                        <li>Cultivate innovative thinkers and problem-solvers through engaging programs and collaborative initiatives.</li>
                        <li>Develop globally minded professionals equipped to thrive in evolving business and hospitality landscapes.</li>
                        <li>Enrich the university experience through events, workshops, and networking opportunities.</li>
                        <li>Promote ethical and sustainable practices via student-led initiatives and community engagement.</li>
                        <li>Prepare students for successful careers by providing essential skills, knowledge, and resources.</li>
                        <li>Build a strong alumni and industry network to support the ongoing success of graduates.</li>
                    </ol>
                </div>
            </div>

            <div class="modal-footer">
                <button type="button" class="btn btn-close-fbm" data-dismiss="modal">Close</button>
            </div>

        </div>
    </div>
</div>

<script>
    (function() {
        function ready(fn) {
            document.readyState !== 'loading' ? fn() : document.addEventListener('DOMContentLoaded', fn);
        }
        ready(function() {
            var m = document.getElementById('fbmsoVisionMissionModal');
            if (m && m.parentElement.tagName.toLowerCase() !== 'body') document.body.appendChild(m);
        });
    })();
</script>
