<?php
// index.php

ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);
// Lanjutkan dengan kode Anda setelah ini
session_start();
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Empow Quiz - Uji Pengetahuanmu</title>
    <link rel="stylesheet" href="css/style.css">
    <style>
        /* Gaya untuk Latar Belakang Gambar Baru */
        body {
            margin: 0;
            padding: 0;
            font-family: Arial, sans-serif; 
            background-image: url('images/index_background.png'); 
            background-size: cover;
            background-position: center;
            background-repeat: no-repeat;
            background-attachment: fixed; 
            
            display: flex;
            justify-content: center;
            align-items: center;
            min-height: 100vh;
            overflow: hidden; 
            position: relative; 
        }

        /* Container yang akan menampung teks dan tombol, posisinya di tengah */
        .content-container {
            text-align: center;
            z-index: 2; 
            position: absolute;
            top: 50%; 
            left: 40%; 
            transform: translate(-50%, -50%); 
            width: 70%; 
            max-width: 600px; 
        }

        h1.logo-text {
            font-family: 'Comic Sans MS', cursive, sans-serif; 
            font-size: 5.5em; 
            color: #2a628f; 
            margin-bottom: 5px; 
            letter-spacing: 2px;
            white-space: nowrap;   
            text-shadow: 2px 2px 4px rgba(0,0,0,0.1);
            line-height: 1.1; 
        }

        p.tagline {
            font-family: Arial, sans-serif; 
            font-size: 2em;
            color: #f57c00; 
            margin-bottom: 40px; 
            font-style: italic;
            line-height: 1.4;
            white-space: nowrap;     
            max-width: 100%; 
            margin-left: auto; 
            margin-right: auto; 
        }

        .play-button {
            display: inline-block;
            background-color: #4CAF50; /* Hijau */
            color: white;
            padding: 18px 60px; 
            border-radius: 40px; 
            text-decoration: none;
            font-size: 2em; 
            font-weight: bold;
            letter-spacing: 1px;
            transition: background-color 0.3s ease, transform 0.2s ease;
            box-shadow: 0 5px 15px rgba(0, 0, 0, 0.2);
            border: none; 
            cursor: pointer;
        }

        .play-button:hover {
            background-color:rgb(54, 107, 56);
            transform: translateY(-3px);
        }

        /* Modal Styling (Biarkan seperti sebelumnya, sudah bagus) */
        .modal {
            display: none;
            position: fixed;
            z-index: 10;
            left: 0;
            top: 0;
            width: 100%;
            height: 100%;
            overflow: auto;
            background-color: rgba(0, 0, 0, 0.4);
        }

        .modal-content {
            background-color: #fff;
            margin: 10% auto;
            padding: 30px;
            border: 4px solid #ffd54f;
            border-radius: 20px;
            width: 90%;
            max-width: 500px;
            text-align: center;
        }

        .modal-content h2 {
            margin-bottom: 20px;
            color: #1976d2;
        }

        .modal-content label {
            display: block;
            margin-bottom: 10px;
            font-size: 1.2em;
            color: #444;
        }

        .modal-content input[type="text"] {
            width: 80%;
            padding: 10px;
            font-size: 1em;
            margin-bottom: 20px;
            border-radius: 10px;
            border: 2px solid #ccc;
        }

        .modal-content button[type="submit"] {
            background-color: #4CAF50;
            color: white;
            font-size: 1.2em;
            padding: 10px 25px;
            border: none;
            border-radius: 30px;
            cursor: pointer;
        }

        .modal-content button[type="submit"]:hover {
            background-color: #2e7c31ff;
        }

        .close-button {
            color: #aaa;
            float: right;
            font-size: 28px;
            font-weight: bold;
            cursor: pointer;
        }

        .close-button:hover {
            color: black;
        }

        /* Transisi popup muncul seperti slide dan zoom */
        .popup-fade {
            opacity: 0;
            transform: translateY(40px) scale(0.95);
            transition: all 0.4s ease;
        }
        .popup-fade.show {
            opacity: 1;
            transform: translateY(0) scale(1);
        }

        /* Responsive adjustments */
        @media (max-width: 768px) {
            .content-container {
                width: 90%;
            }
            h1.logo-text {
                font-size: 3em;
            }
            p.tagline {
                font-size: 1.3em;
            }
            .play-button {
                font-size: 1.7em;
                padding: 15px 45px;
            }
        }

        @media (max-width: 480px) {
            h1.logo-text {
                font-size: 2.2em;
            }
            p.tagline {
                font-size: 1.1em;
            }
            .play-button {
                font-size: 1.4em;
                padding: 12px 35px;
            }
        }
    </style>
</head>
<body>
    <div class="content-container">
        <h1 class="logo-text">EMPOW-QUIZ</h1>
        <p class="tagline">"Uji pengetahuanmu dan Bangun kepercayaan"</p>
        <button id="startButton" class="play-button">LET'S PLAY</button>
    </div>

    <div id="groupModal" class="modal">
        <div class="modal-content popup-fade">
            <span class="close-button">&times;</span>
            <h2>Masukkan Nama Kelompok</h2>
            <form id="groupForm" action="process_group.php" method="POST">
                <label for="groupName">Nama Kelompok:</label>
                <input type="text" id="groupName" name="group_name" required>
                <button type="submit">Submit</button>
            </form>
        </div>
    </div>

    <script src="js/script.js"></script>
    <script>
        // JavaScript for Modal (Ini biasanya di js/script.js, tapi saya taruh di sini sebagai referensi)
        document.addEventListener('DOMContentLoaded', function() {
            var modal = document.getElementById("groupModal");
            var btn = document.getElementById("startButton");
            var span = document.getElementsByClassName("close-button")[0];

            btn.onclick = function() {
                modal.style.display = "block";
                setTimeout(() => {
                    modal.querySelector(".popup-fade").classList.add("show");
                }, 50); // biar transisinya terasa
            }

            span.onclick = function() {
                const popup = modal.querySelector(".popup-fade");
                popup.classList.remove("show");

                // Mainkan suara swish
                const swishSound = document.getElementById("swish-sound");
                swishSound.currentTime = 0;
                swishSound.play();

                setTimeout(() => {
                    modal.style.display = "none";
                }, 300);
            }

            window.onclick = function(event) {
                if (event.target == modal) {
                    const popup = modal.querySelector(".popup-fade");
                    popup.classList.remove("show");

                    // Mainkan suara swish
                    const swishSound = document.getElementById("swish-sound");
                    swishSound.currentTime = 0;
                    swishSound.play();

                    setTimeout(() => {
                        modal.style.display = "none";
                    }, 300);
                }
            }
        });
    </script>

     <!-- Musik Latar Belakang dan Sound Klik yang Disinkronkan -->
  <!-- Musik Latar Belakang dan Sound Klik yang Disinkronkan -->
<!-- Musik Latar Belakang dan Sound Klik yang Disinkronkan -->
<audio id="bg-music" loop>
  <source src="sounds/Super Mario World.mp3" type="audio/mpeg">
  Your browser does not support the audio element.
</audio>

<audio id="click-sound" src="sounds/click.mp3" preload="auto"></audio>
<audio id="swish-sound" src="sounds/Swish.mp3" preload="auto"></audio>

<!-- Tombol Toggle Musik -->
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
  document.addEventListener('DOMContentLoaded', () => {
    const music = document.getElementById('bg-music');
    const toggleBtn = document.getElementById('music-toggle');
    const clickSound = document.getElementById('click-sound');
    const swishSound = document.getElementById('swish-sound');

    // Set volume awal
    music.volume = 0.4;

    // Pulihkan posisi musik jika ada di localStorage
    music.addEventListener('loadedmetadata', () => {
      const savedTime = localStorage.getItem('musicTime');
      if (savedTime !== null) {
        music.currentTime = parseFloat(savedTime);
      }
    });

    // Simpan posisi musik saat halaman akan ditutup / reload
    window.addEventListener('beforeunload', () => {
      localStorage.setItem('musicTime', music.currentTime);
    });

    // Toggle play/pause musik via tombol toggle
    toggleBtn.addEventListener('click', () => {
      if (music.paused) {
        music.play().then(() => {
          toggleBtn.textContent = "🔊";
        }).catch(err => {
          console.warn("Gagal memutar musik:", err);
        });
      } else {
        music.pause();
        toggleBtn.textContent = "🔇";
      }
    });

    // Simpan status mute/play di localStorage
    music.addEventListener('pause', () => localStorage.setItem('musicMuted', 'true'));
    music.addEventListener('play', () => localStorage.setItem('musicMuted', 'false'));

    // Pulihkan status mute/play saat load halaman
    const wasMuted = localStorage.getItem('musicMuted') === 'true';
    if (wasMuted) {
      music.pause();
      toggleBtn.textContent = "🔇";
    } else {
      toggleBtn.textContent = "🔊";
      // Jika ingin langsung autoplay tanpa klik toggle
      // uncomment baris ini (tapi bisa diblock oleh browser)
      // music.play().catch(err => console.warn("Autoplay gagal:", err));
    }

    // Ambil elemen modal dan tombol start
    const modal = document.getElementById("groupModal");
    const startBtn = document.getElementById("startButton");
    const closeBtn = document.getElementsByClassName("close-button")[0];

    // Saat tombol LET'S PLAY diklik
    startBtn.addEventListener('click', () => {
      // Mainkan suara klik
      clickSound.currentTime = 0;
      clickSound.play();

      // Mainkan musik latar (jika belum jalan)
      if (music.paused) {
        music.play().catch(err => {
          console.warn("Musik tidak bisa autoplay:", err);
        });
      }

      // Tampilkan modal dan animasi popup
      modal.style.display = "block";
      modal.querySelector(".popup-fade").classList.add("show");
    });

    // Tutup modal saat tombol close diklik
    closeBtn.addEventListener('click', () => {
      const popup = modal.querySelector(".popup-fade");
      popup.classList.remove("show");

      // Mainkan suara swish
      swishSound.currentTime = 0;
      swishSound.play();

      setTimeout(() => {
        modal.style.display = "none";
      }, 300);
    });

    // Tutup modal saat klik di luar modal content
    window.addEventListener('click', (event) => {
      if (event.target === modal) {
        const popup = modal.querySelector(".popup-fade");
        popup.classList.remove("show");

        // Mainkan suara swish
        swishSound.currentTime = 0;
        swishSound.play();

        setTimeout(() => {
          modal.style.display = "none";
        }, 300);
      }
    });

  });
</script>

</body>
</html>