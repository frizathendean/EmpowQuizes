<?php
session_start();
require_once 'config/database.php';

if (!isset($_SESSION['group_id'])) {
    header("location: index.php");
    exit();
}

$group_id = $_SESSION['group_id'];
$group_name = htmlspecialchars($_SESSION['group_name']);

$quiz_result = null;
$quiz_id = $_SESSION['last_quiz_id'] ?? null;

if ($quiz_id) {
    unset($_SESSION['last_quiz_id']);
    $sql = "SELECT q.id, q.score, q.subject_id, q.level_id, s.subject_name, l.level_name
            FROM quizzes q
            JOIN subjects s ON q.subject_id = s.id
            JOIN levels l ON q.level_id = l.id
            WHERE q.id = ?";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("i", $quiz_id);
} else {
    $sql = "SELECT q.id, q.score, q.subject_id, q.level_id, s.subject_name, l.level_name
            FROM quizzes q
            JOIN subjects s ON q.subject_id = s.id
            JOIN levels l ON q.level_id = l.id
            WHERE q.group_id = ?
            ORDER BY q.completed_at DESC
            LIMIT 1";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("i", $group_id);
}

$stmt->execute();
$result = $stmt->get_result();
if ($result->num_rows > 0) {
    $quiz_result = $result->fetch_assoc();
} else {
    header("location: subjects.php");
    exit();
}
$stmt->close();

$total_questions = 10;
$review_data = $_SESSION['quiz_review_data'] ?? null;
unset($_SESSION['quiz_review_data']);
$conn->close();
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <title>Hasil Kuis - Empow Quiz</title>
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <style>
        body {
            margin: 0;
            font-family: 'Comic Sans MS', cursive, sans-serif;
            background: url('images/resultbg.png') no-repeat center center fixed;
            background-size: cover;
            display: flex;
            align-items: center;
            justify-content: center;
            min-height: 100vh;
        }

        .results-container {
            background-color: rgba(255, 255, 255, 0.9);
            max-width: 800px;
            width: 95%;
            padding: 40px;
            border-radius: 25px;
            box-shadow: 0 8px 25px rgba(0,0,0,0.2);
            text-align: center;
        }

        .results-header {
            color: #1976d2;
            margin-bottom: 25px;
        }

        .results-header h1 {
            font-size: 2.5em;
            margin-bottom: 10px;
        }

        .results-header p {
            font-size: 1.2em;
            color: #555;
        }

        .score-display {
            background-color: #fdd835;
            color: #0d47a1;
            padding: 30px 20px;
            border-radius: 20px;
            margin: 30px 0;
            box-shadow: 0 4px 15px rgba(0,0,0,0.1);
        }

        .score-display h2 {
            font-size: 1.5em;
            margin-bottom: 10px;
        }

        .score-value {
            font-size: 4em;
            font-weight: bold;
        }

        .total-questions {
            font-size: 1.5em;
            color: #0d47a1;
        }

        .score-message {
            font-size: 1.3em;
            margin-top: 25px;
            color: #333;
        }

        .return-button {
            background-color: #4CAF50;
            color: white;
            padding: 15px 30px;
            border: none;
            border-radius: 50px;
            font-size: 1.5em;
            font-weight: bold;
            cursor: pointer;
            margin-top: 30px;
            text-decoration: none;
            display: inline-block;
            box-shadow: 0 4px 10px rgba(0, 0, 0, 0.2);
            transition: transform 0.25s ease, box-shadow 0.25s ease, opacity 0.6s ease;
        }

        .return-button:hover {
            background-color: #66BB6A;
            transform: scale(1.05);
            box-shadow: 0 6px 15px rgba(0, 0, 0, 0.3);
        }

        .return-button.lift {
            transform: translateY(-8px);
            box-shadow: 0 12px 25px rgba(0, 0, 0, 0.3);
            opacity: 0; /* untuk fade */
        }

        body {
            transition: opacity 0.6s ease;
        }

        body.fade-out {
            opacity: 0;
        }

        .fade-out {
            opacity: 0;
            transition: opacity 0.6s ease-out;
        }

        .explanation-block {
            text-align: left;
            margin-top: 40px;
        }

        .explanation-block ol {
            padding-left: 20px;
        }

        .explanation-block li {
            margin-bottom: 25px;
        }

        .explanation-block strong {
            color: #0d47a1;
        }

        .question-review {
            margin-bottom: 30px;
            padding-bottom: 15px;
            border-bottom: 1px dashed #ccc;
        }

        .correct { color: green; }
        .wrong { color: red; }
    </style>
</head>
<body>
<div class="results-container">
    <div class="results-header">
        <h1>Selamat, <?php echo $group_name; ?>!</h1>
        <p>Anda telah menyelesaikan kuis: <?php echo htmlspecialchars($quiz_result['subject_name']) . " - Level: " . htmlspecialchars($quiz_result['level_name']); ?></p>
    </div>

    <div class="score-display">
        <div class="score-value">Skor: <?php echo $quiz_result['score']; ?> / <?php echo $total_questions; ?></div>
    </div>

    <div class="score-message">
        <?php
        if ($quiz_result['score'] >= 8) echo "Kerja bagus! Anda memiliki pemahaman yang sangat baik.";
        elseif ($quiz_result['score'] >= 5) echo "Cukup baik! Terus belajar untuk hasil yang lebih baik.";
        else echo "Tetap semangat! Jangan menyerah dan coba lagi nanti.";
        ?>
    </div>

    <?php if ($review_data): ?>
        <div class="explanation-block">
            <h2>Penjelasan Jawaban</h2>
            <?php foreach ($review_data as $index => $q):
                $question_text = htmlspecialchars($q['question_text']);
                $explanation = htmlspecialchars($q['explanation'] ?? '-');

                $options = is_array($q['options_data']) ? $q['options_data'] : json_decode($q['options_data'], true);
                $correct = $q['correct_answer_data'];
                $user = $q['user_answer'];

                $correct_text = is_array($correct) ? json_encode($correct) : ($options[$correct] ?? $correct);
                $user_text = is_array($user) ? json_encode($user) : ($options[$user] ?? $user);

                $is_correct = ($user_text === $correct_text);
                $status_class = $is_correct ? 'correct' : 'wrong';
                $status_icon = $is_correct ? '✔️' : '❌';
            ?>
            <div class="question-review">
                <strong><?php echo ($index + 1) . ". " . $question_text; ?></strong><br>
                <span class="<?php echo $status_class; ?>">
                    Jawaban Anda: <?php echo htmlspecialchars($user_text); ?> <?php echo $status_icon; ?><br>
                </span>
                <?php if (!$is_correct): ?>
                    <span>Jawaban Benar: <strong><?php echo htmlspecialchars($correct_text); ?></strong></span><br>
                <?php endif; ?>
                <em>Penjelasan:</em> <?php echo $explanation; ?>
            </div>
            <?php endforeach; ?>
        </div>
    <?php endif; ?>

        <button class="return-button" id="back-button">Kembali ke Mata Pelajaran</button>

    <script>
        document.getElementById('back-button').addEventListener('click', function() {
            document.body.classList.add('fade-out');
            setTimeout(function() {
                window.location.href = 'subjects.php';
            }, 600); // Sama seperti waktu transisi CSS
        });
    </script>

    <script>
    document.getElementById('back-button').addEventListener('click', function(e) {
        e.preventDefault();
        const button = this;
        button.classList.add('lift'); // tombol keangkat dan mulai fade

        setTimeout(() => {
            document.body.classList.add('fade-out'); // halaman mulai memudar
        }, 150); // tombol naik dulu sedikit

        setTimeout(() => {
            window.location.href = 'subjects.php';
        }, 700); // cukup waktu untuk animasi tombol + fade-out halaman
    });
    </script>

    <audio id="sound-cheering" src="sounds/Cheering.mp3"></audio>
    <audio id="sound-yay" src="sounds/yay.mp3"></audio>
    <audio id="sound-fail" src="sounds/Fail.mp3"></audio>
    <audio id="sound-click" src="sounds/Click2.mp3"></audio>

    <script>
        // Skor dari PHP ke JS
        const userScore = <?php echo (int)$quiz_result['score']; ?>;

        // Tentukan dan mainkan sound berdasarkan skor
        window.addEventListener('DOMContentLoaded', function() {
            if (userScore >= 8) {
                document.getElementById('sound-cheering').play();
            } else if (userScore >= 5) {
                document.getElementById('sound-yay').play();
            } else {
                document.getElementById('sound-fail').play();
            }
        });

        // Tambahkan efek suara saat tombol ditekan
        const backButton = document.getElementById('back-button');
        backButton.addEventListener('click', function(e) {
            const clickSound = document.getElementById('sound-click');
            clickSound.play();
        });
    </script>
</div>
</body>
</html>
