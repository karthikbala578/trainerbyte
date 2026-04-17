

<style>

    /* The Hamburger Button on the page */

    .menu-trigger {

        position: fixed;

        top: 20px;

        left: 20px;

        font-size: 1.5rem;

        cursor: pointer;

        z-index: 999;

        background: #fff;

        padding: 10px;

        border-radius: 5px;

        box-shadow: 0 2px 5px rgba(0,0,0,0.1);

    }



    /* Sidebar Styling */

    .sidebar {

        position: fixed;

        top: 0;

        left: 0;

        width: 250px;

        height: 100vh;

        background: #ffffff;

        z-index: 1001;

        display: flex;

        flex-direction: column;

        /* This hides the sidebar to the left */

        transform: translateX(-100%); 

        transition: transform 0.3s ease-in-out;

        border-right: 1px solid #e2e8f0;

    }



    /* The class that shows the sidebar */

    .sidebar.active {

        transform: translateX(0);

    }



    /* Dark Background Overlay when menu is open */

    .sidebar-overlay {

        position: fixed;

        top: 0;

        left: 0;

        width: 100vw;

        height: 100vh;

        background: rgba(0, 0, 0, 0.5);

        z-index: 1000;

        display: none; /* Hidden by default */

    }



    .sidebar-overlay.active {

        display: block;

    }



    /* Items inside */

    .sidebar-header {

        padding: 20px;

        display: flex;

        justify-content: space-between;

        align-items: center;

        border-bottom: 1px solid #eee;

    }



    .nav-items-bottom {

        margin-top: auto; /* Pushes items to the bottom */

        padding-bottom: 30px;

    }



    .nav-item {

        display: flex;

        align-items: center;

        padding: 15px 25px;

        font-size: 1.1rem;

        cursor: pointer;

    }



    .nav-item i { margin-right: 15px; width: 25px; }



</style>



    <div class="menu-trigger" onclick="toggleSidebar()">

    <i class="fas fa-bars"></i>

</div>



<div class="sidebar-overlay" id="sidebarOverlay" onclick="toggleSidebar()"></div>



<nav class="sidebar" id="sidebar">

    <div class="sidebar-header">

        <h3>Menu</h3>

         <!-- <img src="../assets/images/ERM sandbox.png" width="60" > -->

        <i class="fas fa-times" onclick="toggleSidebar()" style="cursor:pointer;"></i>

    </div>

    <div class="nav-items-top">

        <!-- <div class="nav-item"><i class="fas fa-home"></i> <span>Home</span></div> -->

        <div class="nav-item" onclick="window.location.href='../teaminstance_be.php?user_id=<?php echo $_SESSION['user_id']; ?>&code=<?php echo $_SESSION['event_code']; ?>'"><i class="fas fa-gamepad"></i> <span>Exercise Menu</span></div>

        
    </div>

    <div class="nav-items-bottom">

        <!-- <div class="nav-item"><i class="fas fa-user"></i> <span>Profile</span></div> -->

        <div class="nav-item" onclick="logoutUser()"><i class="fas fa-sign-out-alt"></i> <span>Logout</span></div>

    </div>

</nav>



<script>

function logoutUser() {

    // 1. Clear game data from browser memory

    sessionStorage.clear();

    

    // 2. Redirect to logout/home page

    // window.location.href = 'http://localhost/trainergenie/<?php //echo $_SESSION['event_code']; ?>';

    window.location.href = 'https://gceteam.simbcm.com/trainergenie/<?php echo $_SESSION['event_code']; ?>';

}

</script>

  