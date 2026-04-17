<?php
session_start();
require "include/dataconnect.php";
$code = $_GET['code'];
 
?>
<html>
<body>
<style>
  :root {
    --primary-color: #4285f4;
    --success-color: #34a853;
    --bg-color: #f8f9fa;
    --border-color: #dadce0;
  }

  body {
    font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
    display: flex;
    justify-content: center;
    align-items: center;
    height: 100vh;
    background-color: var(--bg-color);
    margin: 0;
  }

  .copy-wrapper {
    background: white;
    padding: 24px;
    border-radius: 12px;
    box-shadow: 0 4px 15px rgba(0, 0, 0, 0.05);
    width: 100%;
    max-width: 450px;
  }

  .copy-wrapper h3 {
    margin: 0 0 12px 0;
    font-size: 14px;
    color: #5f6368;
    text-transform: uppercase;
    letter-spacing: 0.5px;
  }

  .input-group {
    display: flex;
    border: 2px solid var(--border-color);
    border-radius: 8px;
    overflow: hidden;
    transition: border-color 0.2s ease;
  }

  .input-group:focus-within {
    border-color: var(--primary-color);
  }

  #urlInput {
    flex: 1;
    border: none;
    padding: 12px 16px;
    font-size: 14px;
    color: #3c4043;
    outline: none;
    background: transparent;
  }

  #copyBtn {
    background-color: var(--primary-color);
    color: white;
    border: none;
    padding: 0 20px;
    font-weight: 600;
    cursor: pointer;
    transition: all 0.2s ease;
    min-width: 100px;
  }

  #copyBtn:hover {
    background-color: #1a73e8;
  }

  #copyBtn:active {
    transform: scale(0.96);
  }

  /* Success State Class */
  #copyBtn.copied {
    background-color: var(--success-color);
  }
</style>

<div class="copy-wrapper">
  <h3>LINK TO ACTIVATE</h3>
  <div class="input-group">
    <input type="text" value="https://trainerbyte.com/trainergenie/<?php echo $code; ?>" id="urlInput" readonly>
    <button onclick="copyUrl()" id="copyBtn">Copy</button>
  </div>
</div>
<div class="review-actions">
            <a href="myevent.php" class="back-home">
                ← Back To Home
            </a>
</div>
<style>

  .back-home {
    position: fixed;
    top: 20px;
    left: 20px;
    text-decoration: none;
    font-size: 14px;
    font-weight: 600;
    color: #4f46e5; /* Indigo */
    background: #ffffff;
    padding: 8px 14px;
    border-radius: 8px;
    box-shadow: 0 4px 12px rgba(0,0,0,0.08);
    transition: all 0.25s ease;
    z-index: 999;
}

.back-home:hover {
    background: #4f46e5;
    color: #ffffff;
    transform: translateX(-3px);
}

</style>
<script>
  function copyUrl() {
    const copyText = document.getElementById("urlInput");
    const button = document.getElementById("copyBtn");

    navigator.clipboard.writeText(copyText.value).then(() => {
      // Add the success class and change text
      button.innerText = "Copied!";
      button.classList.add("copied");
      
      setTimeout(() => {
        button.innerText = "Copy";
        button.classList.remove("copied");
      }, 2000);
    });
  }
</script>
   
</body></html>