<?php
session_start();
require_once 'config/database.php';

if (!isset($_SESSION['group_id'])) {
    header("location: index.php");
    exit();
}

$group_id = $_SESSION['group_id'];
$group_name = htmlspecialchars($_SESSION['group_name']);

if (!isset($_GET['subject_id']) || !isset($_GET['level_id'])) {
    header("location: subjects.php");
    exit();
}

$subject_id = intval($_GET['subject_id']);
$level_id = intval($_GET['level_id']);
$quiz_key = $subject_id . '_' . $level_id;

$stmt = $conn->prepare("SELECT subject_name FROM subjects WHERE id = ?");
$stmt->bind_param("i", $subject_id);
$stmt->execute();
$stmt->bind_result($subject_name);
$stmt->fetch();
$stmt->close();

$stmt = $conn->prepare("SELECT level_name FROM levels WHERE id = ?");
$stmt->bind_param("i", $level_id);
$stmt->execute();
$stmt->bind_result($level_name);
$stmt->fetch();
$stmt->close();

if (!isset($_SESSION['current_quiz_data'][$quiz_key])) {
    $_SESSION['current_quiz_data'][$quiz_key] = [
        'questions' => [],
        'current_question_index' => 0,
        'score' => 0,
        'started_at' => date('Y-m-d H:i:s'),
        'end_time' => time() + 480, // 8 menit = 480 detik
    ];

    $stmt = $conn->prepare("SELECT id, question_text, question_type, options_data, correct_answer_data, explanation FROM questions WHERE subject_id = ? AND level_id = ? ORDER BY RAND() LIMIT 10");
    $stmt->bind_param("ii", $subject_id, $level_id);
    $stmt->execute();
    $result = $stmt->get_result();

    if ($result->num_rows < 10) {
        echo "<script>alert('Jumlah soal kurang dari 10.'); window.location.href='subjects.php';</script>";
        exit();
    }

    while ($row = $result->fetch_assoc()) {
        $row['options_data'] = (!empty($row['options_data']) && is_string($row['options_data'])) ? json_decode($row['options_data'], true) : [];
        $decoded = (!empty($row['correct_answer_data']) && is_string($row['correct_answer_data'])) ? json_decode($row['correct_answer_data'], true) : null;
        $row['correct_answer_data'] = $decoded !== null ? $decoded : $row['correct_answer_data'];
        $row['user_answer'] = null;
        $_SESSION['current_quiz_data'][$quiz_key]['questions'][] = $row;
    }
    $stmt->close();
}

$quiz_data = &$_SESSION['current_quiz_data'][$quiz_key];
$total_questions = count($quiz_data['questions']);

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $index = $quiz_data['current_question_index'];
    $current_q = $quiz_data['questions'][$index];
    $user_answer = null;

    switch ($current_q['question_type']) {
        case 'multiple_choice':
        case 'true_false':
        case 'short_answer':
            $user_answer = $_POST['answer'] ?? null;
            break;
        case 'matching':
            $user_answer = $_POST['matches'] ?? [];
            break;
    }

    $quiz_data['questions'][$index]['user_answer'] = $user_answer;

    if ($_POST['action'] === 'go_back' && $index > 0) {
        $quiz_data['current_question_index']--;
        header("Location: quiz.php?subject_id=$subject_id&level_id=$level_id");
        exit();
    }

    if ($_POST['action'] === 'submit_answer') {
        $is_correct = false;
        $correct = $current_q['correct_answer_data'];

        switch ($current_q['question_type']) {
            case 'multiple_choice':
            case 'true_false':
                $is_correct = ($user_answer === $correct);
                break;
            case 'short_answer':
                if (is_array($correct)) {
                    foreach ($correct as $c) {
                        if (trim(strtolower($user_answer)) === trim(strtolower($c))) {
                            $is_correct = true;
                            break;
                        }
                    }
                } elseif (is_string($correct)) {
                    $is_correct = (trim(strtolower($user_answer)) === trim(strtolower($correct)));
                }
                break;
            case 'matching':
                if (is_array($user_answer) && is_array($correct) && count($user_answer) === count($correct)) {
                    $is_correct = !array_diff_assoc($user_answer, $correct);
                }
                break;
        }

        $status_key = 'score_status_' . $index;
        if (!isset($quiz_data[$status_key])) {
            if ($is_correct) $quiz_data['score']++;
            $quiz_data[$status_key] = $is_correct;
        }

        $quiz_data['current_question_index']++;

        if ($quiz_data['current_question_index'] >= $total_questions) {
            $stmt = $conn->prepare("INSERT INTO quizzes (group_id, subject_id, level_id, score, started_at, completed_at) VALUES (?, ?, ?, ?, ?, ?)");
            $stmt->bind_param("iiiiss", $group_id, $subject_id, $level_id, $quiz_data['score'], $quiz_data['started_at'], date('Y-m-d H:i:s'));
            $stmt->execute();
            $_SESSION['last_quiz_id'] = $stmt->insert_id;
            $stmt->close();

            $_SESSION['quiz_review_data'] = $quiz_data['questions'];
            unset($_SESSION['current_quiz_data'][$quiz_key]);
            $_SESSION['quiz_completed'][$quiz_key] = true;
            header("Location: transition.php");
            exit();
        } else {
            header("Location: quiz.php?subject_id=$subject_id&level_id=$level_id");
            exit();
        }
    }
}

$current_question = $quiz_data['questions'][$quiz_data['current_question_index']];
$remaining_time = $quiz_data['end_time'] - time();
if ($remaining_time <= 0) {
    // Hitung skor akhir berdasarkan jawaban yang sudah diberikan
    $score = 0;
    foreach ($quiz_data['questions'] as $index => $q) {
        $user_answer = $q['user_answer'];
        $correct = $q['correct_answer_data'];
        $is_correct = false;

        switch ($q['question_type']) {
            case 'multiple_choice':
            case 'true_false':
                $is_correct = ($user_answer === $correct);
                break;
            case 'short_answer':
                if (is_array($correct)) {
                    foreach ($correct as $c) {
                        if (trim(strtolower($user_answer)) === trim(strtolower($c))) {
                            $is_correct = true;
                            break;
                        }
                    }
                } elseif (is_string($correct)) {
                    $is_correct = (trim(strtolower($user_answer)) === trim(strtolower($correct)));
                }
                break;
            case 'matching':
                if (is_array($user_answer) && is_array($correct) && count($user_answer) === count($correct)) {
                    $is_correct = !array_diff_assoc($user_answer, $correct);
                }
                break;
        }

        if ($is_correct) {
            $score++;
        }
    }

    // Simpan hasil ke database
    $stmt = $conn->prepare("INSERT INTO quizzes (group_id, subject_id, level_id, score, started_at, completed_at) VALUES (?, ?, ?, ?, ?, ?)");
    $stmt->bind_param("iiiiss", $group_id, $subject_id, $level_id, $score, $quiz_data['started_at'], date('Y-m-d H:i:s'));
    $stmt->execute();
    $_SESSION['last_quiz_id'] = $stmt->insert_id;
    $stmt->close();

    $_SESSION['quiz_review_data'] = $quiz_data['questions'];
    unset($_SESSION['current_quiz_data'][$quiz_key]);
    $_SESSION['quiz_completed'][$quiz_key] = true;
    
    header("Location: result.php"); // Ganti ke result.php
    exit();
}
$question_number = $quiz_data['current_question_index'] + 1;
$conn->close();
?>

<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <title>Kuis <?php echo htmlspecialchars($subject_name); ?> - Empow Quiz</title>
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <style>
        /* ===== Gaya Umum ===== */
        body {
            margin: 0;
            font-family: 'Comic Sans MS', cursive, sans-serif;
            background: url('images/Quizbg.png') no-repeat center center fixed;
            background-size: cover;
            display: flex;
            align-items: center;
            justify-content: center;
            min-height: 100vh;
        }
        .quiz-container {
            background-color: rgba(255, 255, 255, 0.9);
            max-width: 850px;
            width: 90%;
            padding: 40px;
            border-radius: 25px;
            box-shadow: 0 8px 25px rgba(0,0,0,0.2);
            z-index: 2;
        }
        .question-header {
            text-align: center;
            color: #1976d2;
        }
        .question-text {
            font-size: 1.5em;
            text-align: center;
            color: #333;
            margin: 20px 0;
        }
        .options-list {
            list-style: none;
            padding: 0;
            display: flex;
            flex-direction: column;
            gap: 15px;
        }
        .option-item {
            background-color: #fff8e1;
            border: 3px dashed #ffcc80;
            border-radius: 20px;
            padding: 15px 20px;
            font-size: 1.2em;
            cursor: pointer; /* Default cursor for clickable options */
            transition: 0.3s ease;
            display: flex;
            align-items: center;
        }
        /* Override for short answer input type */
        .option-item.short-answer-container { /* New class for the list item holding text input */
            /* Mengatur ulang gaya untuk input teks agar tidak memiliki hover/cursor pointer */
            background-color: #fff8e1; /* Maintain background */
            border: 3px dashed #ffcc80; /* Maintain border */
            cursor: default; /* Change cursor to default for text input */
            display: block; /* Pastikan ini sebagai block agar input di dalamnya tidak terganggu flex */
            padding-bottom: 10px; /* Sedikit padding bawah untuk estetika */
        }
        .option-item.short-answer-container:hover {
            background-color: #fff8e1; /* Prevent hover effect */
            transform: none; /* Prevent scale effect */
        }

        .option-item input[type="radio"] {
            margin-right: 15px;
            transform: scale(1.5);
            cursor: pointer;
            flex-shrink: 0;
        }
        /* Ubah warna lingkaran radio ke hijau */
        input[type="radio"]:checked {
            accent-color: #4CAF50;
        }
        .option-item label {
            flex-grow: 1;
            cursor: pointer;
            display: block;
            padding: 5px 0;
        }
        /* Gaya untuk opsi yang dipilih (lingkaran hijau/highlight) */
        .option-item.selected-option {
            background-color: #e8f5e9;
            border-color: #4CAF50;
            box-shadow: 0 0 10px rgba(76, 175, 80, 0.5);
        }

        /* Styling for the textarea itself */
        .short-answer-textarea-field { /* New class for the textarea */
            width: 100%;
            padding: 10px;
            font-size: 1.1em;
            border-radius: 10px;
            border: 2px solid #1976d2;
            box-sizing: border-box; /* Include padding and border in the element's total width and height */
            margin-top: 10px; /* Add some space above the input field */
            min-height: 100px; /* Pastikan tinggi yang cukup untuk "note" */
            resize: vertical; /* Izinkan pengguna mengubah ukuran secara vertikal */
            display: block; /* Ensure it takes full width */
            font-family: 'Comic Sans MS', cursive, sans-serif; /* Pastikan font konsisten */
        }

        .navigation-buttons {
            display: flex;
            justify-content: space-between;
            gap: 20px;
            margin-top: 30px;
        }
        .nav-button {
            flex: 1;
            font-size: 1.3em;
            padding: 12px 20px;
            border-radius: 50px;
            font-weight: bold;
            letter-spacing: 1px;
            cursor: pointer;
            transition: all 0.3s ease;
            text-align: center;
            display: inline-block;
            box-sizing: border-box;
        }
        .prev-button {
            background-color:rgb(247, 183, 87);
            color:rgb(103, 65, 4);
            border: 3px solid rgb(103, 65, 4);
        }
        .prev-button:hover {
            border: 3px solid rgb(103, 65, 4);
            background-color: rgb(214, 180, 125);
            transform: scale(1.03);
        }
        .next-button {
            background-color: #fdd835;
            color: #0d47a1;
            border: 3px solid #0d47a1;
        }
        .next-button:hover {
            background-color: #fff176;
            transform: scale(1.03);
        }
        .progress-bar-container {
            background-color: #e0e0e0;
            border-radius: 30px;
            margin-top: 25px;
            height: 12px;
            overflow: hidden;
        }
        .progress-bar {
            height: 100%;
            width: 0%;
            background-color: #4CAF50;
            border-radius: 30px;
            transition: width 0.5s ease;
        }
        .matching-placeholder {
            background-color: #f1f8e9;
            padding: 20px;
            border: 2px dashed #aed581;
            border-radius: 20px;
            font-style: italic;
            margin-top: 20px;
        }
        /* Styles for Matching Question (Initial setup) */
        .matching-area {
            background-color: #f8f8f8;
            border: 1px solid #ddd;
            border-radius: 15px;
            padding: 20px;
            display: grid;
            grid-template-columns: 1fr 1fr; /* Two columns for pairs */
            gap: 15px;
        }
        .matching-column {
            display: flex;
            flex-direction: column;
            gap: 10px;
        }
        .matching-item-left, .matching-item-right {
            background-color: #e0f2f7;
            border: 1px solid #b3e5fc;
            padding: 10px 15px;
            border-radius: 10px;
            text-align: center;
            font-weight: bold;
            min-height: 50px; /* Ensure visibility */
            display: flex;
            align-items: center;
            justify-content: center;
        }
        .matching-item-left {
            background-color: #e3f2fd;
            border-color: #90caf9;
        }
        .matching-item-right {
            background-color: #f0f4c3;
            border-color: #cddc39;
        }
        .matching-input-select {
            width: 100%;
            padding: 8px;
            border-radius: 5px;
            border: 1px solid #ccc;
            font-size: 1em;
            margin-top: 5px;
        }
        .matching-item-left img { /* Style for images in matching questions */
            max-width: 100px; /* Adjust as needed */
            max-height: 100px; /* Adjust as needed */
            display: block;
            margin: 0 auto;
        }

        body {
            opacity: 0;
            transform: scale(1.03);
            transition: opacity 0.5s ease, transform 0.5s ease;
        }

        body.loaded {
            opacity: 1;
            transform: scale(1);
        }

        .listen-button-wrapper {
            display: flex;
            justify-content: flex-end; /* arahkan ke kanan */
            margin-top: 10px;
            margin-bottom: 30px; /* Jarak dari pilihan jawaban */
        }

        .listen-button {
            background-color: #fff8e1;
            color: #5d4037;
            border: 2px solid #ffcc80;
            border-radius: 20px;
            font-size: 0.95em;
            font-weight: bold;
            padding: 6px 14px;
            cursor: pointer;
            transition: 0.3s ease;
            font-family: 'Comic Sans MS', cursive, sans-serif;
        }
        .listen-button:hover {
            background-color: #ffe0b2;
            transform: scale(1.05);
        }
    </style>
</head>
<body>
    <div class="quiz-container">
    <div id="timer" style="text-align:center; font-size: 1.4em; color: red; font-weight: bold; margin-bottom: 10px;"></div>
        <h1 class="question-header">Kuis <?php echo htmlspecialchars($subject_name); ?></h1>
        <p style="text-align:center; font-size: 1.1em;">Level: <?php echo htmlspecialchars($level_name); ?> | Peserta: <?php echo $group_name; ?></p>

        <?php if (isset($current_question)): ?>
           <form method="post" action="quiz.php?subject_id=<?php echo $subject_id; ?>&level_id=<?php echo $level_id; ?>">
              <p class="question-text">
                    Soal <?php echo $question_number; ?> dari <?php echo $total_questions; ?><br><br>
                    <span id="questionText"><?php echo htmlspecialchars($current_question['question_text']); ?></span>
                </p>
            <div class="listen-button-wrapper">
                <button type="button" onclick="speakQuestion()" class="listen-button">🔊 Baca Soal</button>
                
            </div>

                <ul class="options-list">
                <?php
                $q_type = $current_question['question_type'];
                $user_answer = $current_question['user_answer'];

               if (($q_type === 'multiple_choice' || $q_type === 'true_false') 
                    && isset($current_question['options_data']) 
                    && is_array($current_question['options_data'])) {

                    foreach ($current_question['options_data'] as $key => $option) {
                        $checked = ($user_answer !== null && $user_answer == $key) ? 'checked' : '';
                        echo "<li class='option-item'><label><input type='radio' name='answer' value='" . htmlspecialchars($key) . "' $checked required> " . htmlspecialchars($option) . "</label></li>";
                    }

                } elseif ($q_type === 'short_answer') {
                    echo "<li class='option-item'>";
                    echo "<label for='shortAnswer'>Jawaban kamu:</label><br>";
                    echo "<textarea id='shortAnswer' name='answer' rows='4' style='width:100%; padding:10px; font-size:1.1em; border-radius:10px; border:2px solid #1976d2;'>" . htmlspecialchars($user_answer ?? '') . "</textarea>";
                    echo "</li>";
                } elseif ($q_type === 'matching') {
                    echo "<div class='matching-placeholder'>Soal menjodohkan akan ditampilkan di sini.</div>";
                }
                ?>
                </ul>
                <input type="hidden" name="action" value="submit_answer">
                <div class="navigation-buttons">
                    <?php if ($quiz_data['current_question_index'] > 0): ?>
                        <button type="submit" class="nav-button prev-button" name="action" value="go_back">Kembali</button>
                    <?php endif; ?>
                        <button type="submit" class="nav-button next-button" name="action" value="submit_answer">Jawab</button>
                </div>
            </form>

            <div class="progress-bar-container">
                <div class="progress-bar" style="width: <?php echo (($question_number - 1) / $total_questions) * 100; ?>%;"></div>
            </div>
        <?php endif; ?>
    </div>

    <script>
        // JavaScript untuk mengelola tampilan opsi yang dipilih pada radio buttons
        document.addEventListener('DOMContentLoaded', function() {
            const radioOptions = document.querySelectorAll('.option-item input[type="radio"]');

            // Tambahkan event listener untuk setiap radio button
            radioOptions.forEach(radio => {
                radio.addEventListener('change', function() {
                    // Hapus kelas 'selected-option' dari semua elemen .option-item
                    radioOptions.forEach(r => {
                        // Pastikan hanya menghapus dari yang bukan short-answer-container
                        if (!r.closest('.option-item').classList.contains('short-answer-container')) {
                            r.closest('.option-item').classList.remove('selected-option');
                        }
                    });
                    // Tambahkan kelas 'selected-option' ke elemen .option-item yang baru dipilih
                    this.closest('.option-item').classList.add('selected-option');
                });
            });

            // Saat halaman dimuat, cek radio button yang sudah terpilih dari data sesi (ditandai oleh PHP)
            // dan langsung berikan efek visual 'selected-option'
            radioOptions.forEach(radio => {
                if (radio.checked) {
                    radio.closest('.option-item').classList.add('selected-option');
                }
            });

            // Untuk textarea, nilai akan otomatis diisi ulang oleh PHP dari value attribute
            // saat halaman dimuat. Jadi tidak ada JS tambahan yang diperlukan untuk menyimpan/memuat
            // nilai textarea di sisi klien. Interaksi mengetik akan berfungsi secara alami.
        });
    </script>

    <script>
    document.addEventListener("DOMContentLoaded", () => {
        const endTime = <?php echo $_SESSION['current_quiz_data'][$quiz_key]['end_time']; ?>;
        const timerDisplay = document.getElementById("timer");

        function updateTimer() {
            const now = Math.floor(Date.now() / 1000);
            const remaining = endTime - now;

            if (remaining <= 0) {
                timerDisplay.textContent = "Waktu habis!";
                window.location.href = "transition.php";
                return;
            }

            const minutes = Math.floor(remaining / 60);
            const seconds = remaining % 60;
            timerDisplay.textContent = `Sisa waktu: ${minutes}:${seconds.toString().padStart(2, '0')}`;
            setTimeout(updateTimer, 1000);
        }

        updateTimer();
    });
    </script>

    <script>
    window.addEventListener('DOMContentLoaded', () => {
        document.body.classList.add('loaded');
    });
    </script>

    <!-- Musik Latar Belakang -->
    <audio id="bg-music" loop>
        <source src="sounds/Super Mario World.mp3" type="audio/mpeg">
    </audio>

    <audio id="click2-sound" src="sounds/Click2.mp3" preload="auto"></audio>

    <audio id="click3-sound" src="sounds/click3.mp3" preload="auto"></audio>

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
    document.addEventListener("DOMContentLoaded", function () {
        const clickSound = document.getElementById("click2-sound");
        const click3Sound = document.getElementById("click3-sound"); 
        const music = document.getElementById('bg-music');
        const toggleBtn = document.getElementById('music-toggle');
        
        const MUSIC_POSITION_KEY = "empow_quiz_music_position";
        const MUSIC_STATUS_KEY = "empow_quiz_music_status";
        
        const status = localStorage.getItem(MUSIC_STATUS_KEY) || 'on';
        const savedTime = localStorage.getItem(MUSIC_POSITION_KEY);
        music.addEventListener('loadedmetadata', () => {
            if (savedTime !== null) {
            music.currentTime = parseFloat(savedTime);
            }

            if (status === 'on') {
                music.play().catch(() => {});
                toggleBtn.textContent = "🔊";
            } else {
                music.pause(); // <-- Tambahkan baris ini
                toggleBtn.textContent = "🔇";
            }
        });

        setInterval(() => {
            if (!music.paused) {
            localStorage.setItem(MUSIC_POSITION_KEY, music.currentTime);
            }
        }, 1000);

        toggleBtn.addEventListener('click', () => {
            if (music.paused) {
                music.play();
                toggleBtn.textContent = "🔊";
                localStorage.setItem(MUSIC_STATUS_KEY, 'on');
            } else {
                music.pause();
                toggleBtn.textContent = "🔇";
                localStorage.setItem(MUSIC_STATUS_KEY, 'off');
            }
        });
        
        const form = document.querySelector("form");
        const navButtons = form.querySelectorAll("button[type='submit']");
        let clickedButton = null; // simpan tombol yang diklik

        navButtons.forEach(button => {
        button.addEventListener("click", function (event) {
            event.preventDefault(); // cegah submit default
            clickedButton = button;

            if (clickSound) {
            clickSound.currentTime = 0;
            clickSound.play();
            }

            setTimeout(() => {
            // Buat input hidden untuk mengirim value tombol yang diklik
            if (clickedButton && clickedButton.name) {
                const hiddenInput = document.createElement("input");
                hiddenInput.type = "hidden";
                hiddenInput.name = clickedButton.name;
                hiddenInput.value = clickedButton.value;
                form.appendChild(hiddenInput);
            }

            clickedButton.disabled = true;
            form.submit();
            }, 200);
        });
    });

            const radioOptions = document.querySelectorAll('.option-item input[type="radio"]');
        radioOptions.forEach(radio => {
            radio.addEventListener('click', function () {
                if (click3Sound) {
                    click3Sound.currentTime = 0;
                    click3Sound.play();
                }
            });
        });
    });
    </script>

    <script>
    function speakQuestion() {
        const text = document.getElementById('questionText')?.innerText || "";
        if (!text) return;

        const utterance = new SpeechSynthesisUtterance(text);
        utterance.lang = 'id-ID'; // Bahasa Indonesia
        utterance.rate = 1;       // Kecepatan bicara
        utterance.pitch = 1;      // Nada suara

        window.speechSynthesis.cancel(); // Hentikan suara sebelumnya
        window.speechSynthesis.speak(utterance);
    }
    </script>

</body>
</html>