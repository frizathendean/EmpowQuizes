<?php
session_start();
if (!isset($_SESSION['group_id']) || !isset($_SESSION['group_name'])) {
    header("location: index.php");
    exit();
}
$groupName = htmlspecialchars($_SESSION['group_name']);
?>
<!DOCTYPE html>
<html lang="id">
<head>
  <meta charset="UTF-8">
  <title>Pilih Mata Pelajaran - Empow Quiz</title>
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <style>
    body {
      margin: 0;
      padding: 0;
      font-family: 'Comic Sans MS', cursive, sans-serif;
      background: url('images/Quizbg.png') no-repeat center center fixed;
      background-size: cover;
      display: flex;
      justify-content: center;
      align-items: flex-start;
      min-height: 100vh;
    }

    .container {
      background-color: rgba(255, 255, 255, 0.83);
      margin: 80px auto;
      padding: 50px;
      border-radius: 25px;
      max-width: 1050px;
      width: 95%;
      text-align: center;
      box-shadow: 0 8px 25px rgba(0, 0, 0, 0.2);
    }

    h1 {
      color: #0d47a1;
      font-size: 2.6em;
      margin-bottom: 15px;
      text-transform: uppercase;
    }

    p {
      font-size: 1.3em;
      color: #333;
      margin-bottom: 35px;
    }

    .subject-grid {
      display: flex;
      flex-direction: column;
      gap: 30px;
      align-items: center;
    }

    .subject-row {
      display: flex;
      justify-content: center;
      gap: 30px;
      flex-wrap: wrap;
    }

    .subject-card {
      background-color: #fff8e1;
      border: 4px dashed #ffd54f;
      border-radius: 20px;
      padding: 25px;
      text-decoration: none;
      color: #333;
      display: flex;
      justify-content: center;
      text-align: center;
      flex-direction: column;
      align-items: center;
      width: 180px;
      height: 200px;
      transition: all 0.3s ease;
      cursor: pointer;
    }

    .subject-card:hover {
      background-color: #fff3e0;
      transform: scale(1.05);
    }

    .subject-card img {
      max-width: 120px;
      margin-bottom: 45px;
    }

    .subject-card span {
      font-weight: bold;
      font-size: 1.2em;
      text-transform: uppercase;
    }

    .modal {
      display: flex;
      justify-content: center;
      align-items: center;
      position: fixed;
      z-index: 10;
      left: 0; top: 0;
      width: 100%;
      height: 100%;
      background-color: rgba(0, 0, 0, 0.4);
      opacity: 0;
      pointer-events: none;
      transition: opacity 0.4s ease;
    }

    .modal.show {
      opacity: 1;
      pointer-events: auto;
    }

    .modal-content {
      background-color: rgba(255, 255, 255, 0.95);
      padding: 50px;
      border-radius: 30px;
      width: 90%;
      max-width: 550px;
      text-align: center;
      border: 4px dashed #fdd835;
      box-shadow: 0 5px 25px rgba(0, 0, 0, 0.1);
      position: relative;
      transform: scale(0.8);
      opacity: 0;
      transition: transform 0.4s ease, opacity 0.4s ease;
    }

    .modal.show .modal-content {
      transform: scale(1);
      opacity: 1;
    }

    .modal-content h2 {
      margin-bottom: 30px;
      color: #0d47a1;
      font-size: 2em;
      font-weight: bold;
      text-transform: uppercase;
    }

    .level-options {
      display: flex;
      flex-direction: column;
      gap: 20px;
      align-items: center;
    }

    .level-options button {
      padding: 16px 40px;
      font-size: 1.4em;
      border: none;
      border-radius: 50px;
      background-color: #4CAF50;
      color: white;
      cursor: pointer;
      font-weight: bold;
      box-shadow: 0 4px 12px rgba(0, 0, 0, 0.2);
      transition: all 0.3s ease;
      min-width: 240px;
    }

    .level-options button:hover {
      background-color: #43a047;
      transform: scale(1.03);
    }

    .level-options button:active {
      transform: scale(1.05) translateY(-3px);
      box-shadow: 0 6px 15px rgba(0, 0, 0, 0.3);
    }

    .close-button {
      position: absolute;
      top: 15px;
      right: 20px;
      font-size: 24px;
      color: #999;
      cursor: pointer;
      font-weight: bold;
    }

    .close-button:hover {
      color: #000;
    }

    .fade-out {
      opacity: 0;
      transform: scale(0.97);
      transition: opacity 0.5s ease, transform 0.5s ease;
    }

    @media (max-width: 768px) {
      .subject-card {
        width: 140px;
        height: auto;
        padding: 20px;
      }

      .subject-card img {
        max-width: 60px;
      }

      .subject-card span {
        font-size: 1em;
      }

      h1 {
        font-size: 2em;
      }
    }
  </style>
</head>
<body>
  <div class="container">
    <h1>Halo, <?php echo $groupName; ?>!</h1>
    <p>Pilih Mata Pelajaran yang ingin kamu kerjakan:</p>

    <div class="subject-grid">
      <div class="subject-row">
        <a href="#" class="subject-card" data-subject-id="1"><img src="images/Pancasila.png"><span>PANCASILA</span></a>
        <a href="#" class="subject-card" data-subject-id="2"><img src="images/Citizenship.png"><span>KEWARGANEGARAAN</span></a>
        <a href="#" class="subject-card" data-subject-id="3"><img src="images/Religion.png"><span>AGAMA</span></a>
      </div>
      <div class="subject-row">
        <a href="#" class="subject-card" data-subject-id="4"><img src="images/bhsindo.png"><span>BHS. INDO</span></a>
        <a href="#" class="subject-card" data-subject-id="5"><img src="images/bhsinggris.png"><span>BHS. INGGRIS</span></a>
      </div>
    </div>
  </div>

  <div id="levelModal" class="modal">
    <div class="modal-content">
      <span class="close-button">&times;</span>
      <h2>Pilih Level Kesulitan</h2>
      <div id="levelOptions" class="level-options"></div>
    </div>
  </div>

  <!-- 🔊 Musik dan Sound -->
  <audio id="bg-music" autoplay loop>
    <source src="sounds/Super Mario World.mp3" type="audio/mpeg">
  </audio>
  <audio id="click-sound" src="sounds/click.mp3" preload="auto"></audio>
  <audio id="swish-sound" src="sounds/swish.mp3" preload="auto"></audio>
  <audio id="click2-sound" src="sounds/Click2.mp3" preload="auto"></audio>
  
  <button id="music-toggle" style="
    position: fixed;
    bottom: 40px;
    right: 40px;
    z-index: 999;
    background: #f9e79f5f;
    padding: 10px 15px;
    border: none;
    border-radius: 15px;
    font-weight: bold;
    font-size: 1.2em;
    cursor: pointer;
    ">🔊</button>

  <script>
    const subjectCards = document.querySelectorAll('.subject-card');
    const modal = document.getElementById('levelModal');
    const closeBtn = document.querySelector('.close-button');
    const levelOptions = document.getElementById('levelOptions');
    const music = document.getElementById('bg-music');
    const clickSound = document.getElementById('click-sound');
    const toggleBtn = document.getElementById('music-toggle');

    music.volume = 0.4;
    const savedTime = localStorage.getItem('musicTime');
    music.addEventListener('loadedmetadata', () => {
      if (savedTime !== null) music.currentTime = parseFloat(savedTime);
    });
    window.addEventListener('beforeunload', () => {
      localStorage.setItem('musicTime', music.currentTime);
    });

    const wasMuted = localStorage.getItem('musicMuted') === 'true';
    if (wasMuted) {
      music.pause();
      toggleBtn.textContent = "🔇";
    } else {
      music.play().catch(() => {});
      toggleBtn.textContent = "🔊";
    }

    toggleBtn.addEventListener('click', () => {
      if (music.paused) {
        music.play();
        toggleBtn.textContent = "🔊";
        localStorage.setItem('musicMuted', 'false');
      } else {
        music.pause();
        toggleBtn.textContent = "🔇";
        localStorage.setItem('musicMuted', 'true');
      }
    });

    subjectCards.forEach(card => {
      card.addEventListener('click', () => {
        clickSound.currentTime = 0;
        clickSound.play();
        const subjectId = card.getAttribute('data-subject-id');
        levelOptions.innerHTML = `
          <button onclick="startQuiz(${subjectId}, 1)">Kelas 6 SD</button>
          <button onclick="startQuiz(${subjectId}, 2)">Kelas 7 SMP</button>
        `;
        modal.classList.add('show');
      });
    });

    document.querySelectorAll('button').forEach(btn => {
      btn.addEventListener('click', (e) => {
        if (e.target.id !== 'music-toggle') {
          clickSound.currentTime = 0;
          clickSound.play();
        }
      });
    });

    closeBtn.addEventListener('click', () => {
      const swishSound = document.getElementById('swish-sound');
      swishSound.currentTime = 0;
      swishSound.play();

      modal.classList.remove('show');
    });

    window.addEventListener('click', e => {
      if (e.target === modal) {
        const swishSound = document.getElementById('swish-sound');
        swishSound.currentTime = 0;
        swishSound.play();

        modal.classList.remove('show');
      }
    });

    function startQuiz(subjectId, levelId) {
      const click2Sound = document.getElementById('click2-sound');
      click2Sound.currentTime = 0;
      click2Sound.play();

      document.body.classList.add('fade-out');
      setTimeout(() => {
        window.location.href = `quiz.php?subject_id=${subjectId}&level_id=${levelId}`;
      }, 500);
    }

  </script>
</body>
</html>
