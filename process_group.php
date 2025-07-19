<?php

// process_group.php
session_start();

require_once 'config/database.php';

// Atur header untuk merespons dalam format JSON
header('Content-Type: application/json');

$response = ['success' => false, 'message' => 'Terjadi kesalahan tidak dikenal.'];

if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['group_name'])) {
    $groupName = $_POST['group_name'];

    if (empty($groupName)) {
        $response['message'] = "Nama kelompok tidak boleh kosong!";
        echo json_encode($response);
        exit();
    }

    $sql = "INSERT INTO groups (group_name) VALUES (?)";

    if ($stmt = $conn->prepare($sql)) {
        $stmt->bind_param("s", $param_group_name);
        $param_group_name = $groupName;

        if ($stmt->execute()) {
            $_SESSION['group_id'] = $conn->insert_id;
            $_SESSION['group_name'] = $groupName;
            $response['success'] = true;
            $response['message'] = "Nama kelompok berhasil disimpan!";
            $response['group_id'] = $conn->insert_id; // Opsional: kirim ID kembali
        } else {
            $response['message'] = "Terjadi kesalahan saat menyimpan data: " . $stmt->error;
        }
        $stmt->close();
    } else {
        $response['message'] = "Error preparing statement: " . $conn->error;
    }
    $conn->close();
} else {
    $response['message'] = "Akses tidak valid.";
}

echo json_encode($response);
exit();
?>

<audio id="bg-music" autoplay loop>
  <source src="sounds/Super Mario World.mp3" type="audio/mpeg">
  Your browser does not support the audio element.
</audio>

<!-- Suara klik -->
<audio id="click-sound" src="sounds/click.mp3" preload="auto"></audio>

<!-- Tombol toggle musik -->
<button id="music-toggle" style="
  position: fixed;
  bottom: 50px;
  right: 50px;
  background-color: #eadda4bb;
  border: none;
  padding: 15px 20px;
  border-radius: 15px;
  font-weight: bold;
  cursor: pointer;
  z-index: 999;
  box-shadow: 0 2px 5px rgba(0,0,0,0.2);
">🔊</button>

<script>
  const music = document.getElementById('bg-music');
  const toggleBtn = document.getElementById('music-toggle');
  const clickSound = document.getElementById('click-sound');

  music.volume = 0.4;

  // Atur posisi musik dari localStorage supaya lanjut dari posisi terakhir
  music.addEventListener('loadedmetadata', () => {
    const savedTime = localStorage.getItem('musicTime');
    if (savedTime !== null) {
      music.currentTime = parseFloat(savedTime);
    }
  });

  window.addEventListener('beforeunload', () => {
    localStorage.setItem('musicTime', music.currentTime);
  });

  // Tombol toggle musik play/pause
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

  // Pulihkan status mute saat reload
  const wasMuted = localStorage.getItem('musicMuted') === 'true';
  if (wasMuted) {
    music.pause();
    toggleBtn.textContent = "🔇";
  } else {
    music.play().catch(() => {});
    toggleBtn.textContent = "🔊";
  }

  // Mainkan suara klik untuk semua tombol kecuali tombol toggle musik
  document.querySelectorAll('button').forEach(btn => {
    btn.addEventListener('click', (e) => {
      if (e.target.id !== 'music-toggle') {
        clickSound.currentTime = 0;
        clickSound.play();
      }
    });
  });
</script>