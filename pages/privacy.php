<?php

?>
<!DOCTYPE html>
<html lang="en">

<head>
    
<style>

 
    /* Root Variables for Themes */
    :root {
      --bg-color-light: #f5f5f5;
      --text-color-light: #333;
      --bg-color-dark: #1e1e2f;
      --text-color-dark: #f5f5f5;
      --button-bg-light: #ffffff;
      --button-bg-dark: #333;
      --highlight-color-light: #ffd700; /* Sun Color */
      --highlight-color-dark: #4a90e2; /* Moon Glow */
      --icon-size: 50px;
      --transition-speed: 0.5s;
    }

    body {
      margin: 0;
      font-family: 'Arial', sans-serif;
      background-color: var(--bg-color-light);
      color: var(--text-color-light);
      transition: background-color var(--transition-speed), color var(--transition-speed);
      display: flex;
      justify-content: center;
      align-items: center;
      height: 100vh;
      overflow: hidden;
    }

    /* Toggle Button Container */
    .toggle-container {
      width: 100px;
      height: 100px;
      display: flex;
      justify-content: center;
      align-items: center;
      background: var(--button-bg-light);
      border-radius: 50%;
      box-shadow: 0px 10px 20px rgba(0, 0, 0, 0.2), inset 0px 4px 6px rgba(255, 255, 255, 0.5);
      cursor: pointer;
      transition: background-color var(--transition-speed), box-shadow var(--transition-speed);
      position: relative;
    }

    .toggle-container:hover {
      box-shadow: 0px 15px 25px rgba(0, 0, 0, 0.3), inset 0px 6px 8px rgba(255, 255, 255, 0.6);
      transform: scale(1.1);
      transition: transform 0.3s;
    }

    .toggle-container:active {
      transform: scale(0.95);
    }

    /* Icon Styles */
    .icon {
      font-size: var(--icon-size);
      color: var(--highlight-color-light);
      transition: transform var(--transition-speed), color var(--transition-speed);
    }

    body.dark-mode .icon {
      color: var(--highlight-color-dark);
      transform: rotate(360deg); /* Smooth rotation effect */
    }

    /* Dark Mode Styles */
    body.dark-mode {
      background-color: var(--bg-color-dark);
      color: var(--text-color-dark);
    }

    body.dark-mode .toggle-container {
      background: var(--button-bg-dark);
    }
 

    </style>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Document</title>
    <link rel="stylesheet" href="style.css">
    
</head>

<body>
    <div class="priv">
    <h1 style="font-style:bold">Privacy & Policy</h1>
    <p style="font-style:bold">i miss you😘, i love everything about you.<br>
        i love your eyes(1) your nose(2) your ears(3) your hair(4) by:muffeia</p>
    </div>
    <div class="toggle-container" id="toggle-mode">
    <!-- Sun/Moon Icon -->
    <span class="icon" id="icon">☀️</span>
  </div>

  <script>
    const toggleContainer = document.getElementById('toggle-mode');
    const icon = document.getElementById('icon');
    const body = document.body;

    toggleContainer.addEventListener('click', () => {
      body.classList.toggle('dark-mode');
      
      // Dynamically toggle the icon
      if (body.classList.contains('dark-mode')) {
        icon.textContent = '🌙'; // Moon icon
      } else {
        icon.textContent = '☀️'; // Sun icon
      }
    });
  </script>
</body>
<script src="js/scripts.js"></script>
</html>