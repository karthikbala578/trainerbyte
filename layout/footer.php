<?php
require_once "include/dataconnect.php";

$footerQuery = mysqli_query($conn, "SELECT * FROM tb_cms_footer_links");

$footerLinks = [];

while ($row = mysqli_fetch_assoc($footerQuery)) {
    $footerLinks[$row['fl_id']] = $row;
}
?>

<!-- ================= FOOTER ================= -->
<footer class="main-footer">

    <div class="footer-container">

        <!-- BRAND -->
        <div class="footer-col brand-col">
            <div class="footer-logo">
                <img src="<?= BASE_PATH ?>/assets/images/puzzle-icon.png"
                    alt="TrainerByte Logo"
                    class="footer-logo-img">

                <span class="logo-text">trainer <span class="byte">BYTE</span></span>
            </div>

            <br>

            <p class="footer-desc">
                Building the future of interactive corporate learning
                through gamified template design.
            </p>

            <p class="brand-sub">
                TrainerByte is a brand of <strong>ERM SANDBOX</strong>.
            </p>
        </div>


        <!-- COMPANY -->
        <div class="footer-col">
            <h4>Company</h4>
            <ul>
                <li><a href="javascript:void(0)" onclick="openFooterPopup(1)">About Us</a></li>
                <li><a href="javascript:void(0)" onclick="openFooterPopup(2)">Contact</a></li>
            </ul>
        </div>


        <!-- TERMS -->
        <div class="footer-col">
            <h4>Terms</h4>
            <ul>
                <li><a href="javascript:void(0)" onclick="openFooterPopup(3)">Payment Terms</a></li>
                <li><a href="javascript:void(0)" onclick="openFooterPopup(4)">Refund Policy</a></li>
                <li><a href="javascript:void(0)" onclick="openFooterPopup(5)">User Agreement</a></li>
                <li><a href="javascript:void(0)" onclick="openFooterPopup(6)">Licensing</a></li>
            </ul>
        </div>


        <!-- SOCIAL -->
        <div class="footer-col">
            <h4>Social</h4>

            <div class="social-icons">

                <a href="https://www.linkedin.com/company/ermsandbox/" target="_blank" class="social-btn">
                    <svg viewBox="0 0 24 24" fill="currentColor">
                        <path d="M4.98 3.5C4.98 4.88 3.87 6 2.49 6S0 4.88 0 3.5 1.11 1 2.49 1s2.49 1.12 2.49 2.5zM.5 8h4V24h-4zM8 8h3.8v2.2h.05c.53-1 1.83-2.2 3.75-2.2C19.3 8 21 10.1 21 13.3V24h-4v-9.3c0-2.2-.8-3.7-2.8-3.7-1.53 0-2.44 1.03-2.84 2.03-.15.37-.18.88-.18 1.4V24H8z" />
                    </svg>
                </a>

                <a href="https://www.facebook.com/sarasventure/" target="_blank" class="social-btn">
                    <svg viewBox="0 0 24 24" fill="currentColor">
                        <path d="M22 12a10 10 0 1 0-11.5 9.9v-7H8v-3h2.5V9.5c0-2.5 1.5-3.9 3.8-3.9 1.1 0 2.2.2 2.2.2v2.4h-1.3c-1.3 0-1.7.8-1.7 1.6V12H17l-.4 3h-2.3v7A10 10 0 0 0 22 12z" />
                    </svg>
                </a>

                <a href="https://x.com/ilangovasudevan" target="_blank" class="social-btn">
                    <svg viewBox="0 0 24 24" fill="currentColor">
                        <path d="M18.244 2H21l-6.56 7.5L22 22h-6.8l-5.3-6.9L3.8 22H1l7.1-8.1L2 2h6.9l4.8 6.3L18.244 2z" />
                    </svg>
                </a>

            </div>
        </div>

    </div>


    <!-- FOOTER BOTTOM -->
    <div class="footer-bottom">
        <p>© <?= date("Y"); ?> trainerBYTE. All rights reserved.</p>
    </div>

</footer>


<!-- ================= POPUP ================= -->

<div class="footer-popup" id="footerPopup">

    <div class="popup-content">

        <span class="close-btn" onclick="closeFooterPopup()">×</span>

        <h2 id="footerPopupTitle"></h2>

        <div id="footerPopupBody"></div>

    </div>

</div>


<script>
    var footerContent = <?= json_encode($footerLinks); ?>;

    function openFooterPopup(id) {

        let data = footerContent[id];

        document.getElementById("footerPopupTitle").innerText = data.fl_title;

        document.getElementById("footerPopupBody").innerHTML = data.fl_content;

        document.getElementById("footerPopup").style.display = "flex";

    }

    function closeFooterPopup() {
        document.getElementById("footerPopup").style.display = "none";
    }

    window.onclick = function(e) {
        if (e.target === document.getElementById("footerPopup")) {
            closeFooterPopup();
        }
    }
</script>

</body>

</html>