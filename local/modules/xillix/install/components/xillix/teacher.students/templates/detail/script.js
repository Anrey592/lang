(function () {
    'use strict';

    document.addEventListener('DOMContentLoaded', function () {
        const notesForm = document.getElementById('studentNotesForm');
        const saveBtn = document.getElementById('saveNotesBtn');
        const statusEl = document.getElementById('saveStatus');
        const notesTextarea = document.getElementById('studentNotes');

        if (!notesForm) return;

        let saveTimeout;

        // Автосохранение при изменении текста
        notesTextarea.addEventListener('input', function () {
            clearTimeout(saveTimeout);
            saveTimeout = setTimeout(saveNotes, 1000);
        });

        // Ручное сохранение по кнопке
        notesForm.addEventListener('submit', function (e) {
            e.preventDefault();
            saveNotes();
        });

        function saveNotes() {
            const formData = new FormData(notesForm);
            const studentId = formData.get('student_id');
            const notes = formData.get('notes');

            // Показываем состояние загрузки
            saveBtn.disabled = true;
            saveBtn.textContent = 'Сохранение...';
            showStatus('Сохранение...', 'info');

            if (typeof BX !== 'undefined') {
                BX.ajax.runComponentAction('xillix:teacher.students', 'updateStudentNotes', {
                    mode: 'class',
                    data: {
                        studentId: studentId,
                        notes: notes
                    }
                }).then(function (response) {
                    if (response.data.success) {
                        showStatus('Заметки сохранены', 'success');
                    } else {
                        showStatus('Ошибка сохранения: ' + (response.data.message || 'Неизвестная ошибка'), 'error');
                    }
                }).catch(function (error) {
                    console.error('Save notes error:', error);
                    showStatus('Ошибка сохранения', 'error');
                }).finally(function () {
                    saveBtn.disabled = false;
                    saveBtn.textContent = 'Сохранить заметки';

                    // Скрываем статус через 3 секунды
                    setTimeout(function () {
                        hideStatus();
                    }, 3000);
                });
            } else {
                // Fallback для случаев когда BX не доступен
                fetch('/local/components/xillix/teacher.students/ajax.php', {
                    method: 'POST',
                    body: formData
                }).then(function (response) {
                    return response.json();
                }).then(function (data) {
                    if (data.success) {
                        showStatus('Заметки сохранены', 'success');
                    } else {
                        showStatus('Ошибка сохранения', 'error');
                    }
                }).catch(function (error) {
                    console.error('Save notes error:', error);
                    showStatus('Ошибка сохранения', 'error');
                }).finally(function () {
                    saveBtn.disabled = false;
                    saveBtn.textContent = 'Сохранить заметки';

                    setTimeout(function () {
                        hideStatus();
                    }, 3000);
                });
            }
        }

        function showStatus(message, type) {
            statusEl.textContent = message;
            statusEl.className = 'save-status ' + type;
            statusEl.style.display = 'inline-block';
        }

        function hideStatus() {
            statusEl.style.display = 'none';
            statusEl.textContent = '';
        }

        // Подсветка изменений
        let originalNotes = notesTextarea.value;

        notesTextarea.addEventListener('input', function () {
            if (notesTextarea.value !== originalNotes) {
                notesTextarea.style.borderColor = 'var(--color-accent)';
                notesTextarea.style.backgroundColor = 'rgba(130, 58, 217, 0.05)';
            } else {
                notesTextarea.style.borderColor = '';
                notesTextarea.style.backgroundColor = '';
            }
        });
    });
})();