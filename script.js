// js/script.js

document.addEventListener('DOMContentLoaded', function() {
    // --- Elemen untuk Pop-up Nama Kelompok di index.php ---
    const startButton = document.getElementById('startButton');
    const groupModal = document.getElementById('groupModal');
    const groupForm = document.getElementById('groupForm');
    const closeGroupModalButton = groupModal ? groupModal.querySelector('.close-button') : null; // Close button for group modal

    // --- Elemen untuk Pop-up Level di subjects.php ---
    const subjectCards = document.querySelectorAll('.subject-card'); // Semua tombol mata pelajaran
    const levelModal = document.getElementById('levelModal');
    const closeLevelModalButton = levelModal ? levelModal.querySelector('.close-button') : null; // Close button for level modal
    const levelOptionsContainer = document.getElementById('levelOptions');

    let selectedSubjectId = null; // Menyimpan ID mata pelajaran yang dipilih
    let selectedSubjectName = null; // Menyimpan Nama mata pelajaran yang dipilih

    // ===============================================
    // LOGIKA UNTUK POP-UP NAMA KELOMPOK (di index.php)
    // ===============================================

    if (startButton) { // Pastikan elemen ada (hanya di index.php)
        startButton.onclick = function() {
            groupModal.style.display = 'flex';
        }
    }

    if (closeGroupModalButton) { // Pastikan elemen ada
        closeGroupModalButton.onclick = function() {
            groupModal.style.display = 'none';
        }
    }

    if (groupModal) { // Pastikan elemen ada
        window.onclick = function(event) {
            if (event.target == groupModal) {
                groupModal.style.display = 'none';
            }
        }
    }

    if (groupForm) { // Pastikan elemen ada (hanya di index.php)
        groupForm.addEventListener('submit', function(event) {
            event.preventDefault();

            const groupName = document.getElementById('groupName').value.trim();

            if (groupName === '') {
                alert('Nama kelompok tidak boleh kosong!');
                return;
            }

            // Kirim data nama kelompok ke server
            const formData = new FormData(groupForm);
            fetch('process_group.php', {
                method: 'POST',
                body: formData
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    // Jika berhasil, alihkan ke halaman subjects.php
                    window.location.href = 'subjects.php';
                } else {
                    alert('Gagal menyimpan nama kelompok: ' + data.message);
                }
            })
            .catch(error => {
                console.error('Error:', error);
                alert('Terjadi kesalahan saat menyimpan data kelompok.');
            });
        });
    }

    // ===============================================
    // LOGIKA UNTUK POP-UP PILIHAN LEVEL (di subjects.php)
    // ===============================================

    if (subjectCards.length > 0) { // Pastikan elemen ada (hanya di subjects.php)
        subjectCards.forEach(card => {
            card.addEventListener('click', function(event) {
                event.preventDefault(); // Mencegah langsung navigasi

                selectedSubjectId = this.dataset.subjectId; // Ambil dari data-attribute
                selectedSubjectName = this.dataset.subjectName; // Ambil dari data-attribute

                if (!selectedSubjectId || !selectedSubjectName) {
                    console.error("Subject ID atau Name tidak ditemukan.");
                    return;
                }

                // Kosongkan dan isi ulang pilihan level
                levelOptionsContainer.innerHTML = ''; // Pastikan bersih
                const levels = [
                    { id: 1, name: 'Kelas 6 SD' },
                    { id: 2, name: 'Kelas 7 SMP' }
                ];

                levels.forEach(level => {
                    const levelButton = document.createElement('a');
                    levelButton.href = `quiz.php?subject_id=${selectedSubjectId}&level_id=${level.id}`;
                    levelButton.className = 'level-button';
                    levelButton.textContent = level.name;
                    levelOptionsContainer.appendChild(levelButton);
                });

                levelModal.style.display = 'flex'; // Tampilkan modal level
            });
        });
    }

    if (closeLevelModalButton) { // Pastikan elemen ada
        closeLevelModalButton.onclick = function() {
            levelModal.style.display = 'none';
        }
    }

    if (levelModal) { // Pastikan elemen ada
        window.addEventListener('click', function(event) {
            if (event.target == levelModal) {
                levelModal.style.display = 'none';
            }
        });
    }

});