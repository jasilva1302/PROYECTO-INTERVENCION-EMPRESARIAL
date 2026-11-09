// Toggle sidebar
document.getElementById('menu-toggle').addEventListener('click', function(e) {
    e.preventDefault();
    document.getElementById('wrapper').classList.toggle('toggled');
});

// Timer for audits
if (document.getElementById('timer')) {
    let startTime = new Date(document.getElementById('timer').dataset.start);
    let timerInterval = setInterval(updateTimer, 1000);
    
    function updateTimer() {
        let now = new Date();
        let diff = now - startTime;
        
        let hours = Math.floor(diff / (1000 * 60 * 60));
        diff -= hours * (1000 * 60 * 60);
        
        let minutes = Math.floor(diff / (1000 * 60));
        diff -= minutes * (1000 * 60);
        
        let seconds = Math.floor(diff / 1000);
        
        document.getElementById('timer').textContent = 
            `${hours.toString().padStart(2, '0')}:${minutes.toString().padStart(2, '0')}:${seconds.toString().padStart(2, '0')}`;
    }
    
    // Pause timer when leaving page
    window.addEventListener('beforeunload', function() {
        clearInterval(timerInterval);
    });
}

// Add more questions in evaluations
if (document.getElementById('add-question')) {
    document.getElementById('add-question').addEventListener('click', function() {
        const container = document.getElementById('questions-container');
        const index = container.children.length;
        
        const questionDiv = document.createElement('div');
        questionDiv.className = 'form-group question-item';
        questionDiv.innerHTML = `
            <label>Pregunta ${index + 1}</label>
            <input type="text" name="preguntas[${index}][texto]" class="form-control" required>
            <label>Respuesta correcta</label>
            <input type="text" name="preguntas[${index}][respuesta]" class="form-control" required>
            <button type="button" class="btn btn-danger btn-sm remove-question">Eliminar</button>
        `;
        
        container.appendChild(questionDiv);
    });
    
    document.addEventListener('click', function(e) {
        if (e.target.classList.contains('remove-question')) {
            e.target.parentElement.remove();
            // Reindex questions
            const questions = document.querySelectorAll('.question-item');
            questions.forEach((question, index) => {
                question.querySelector('label').textContent = `Pregunta ${index + 1}`;
                const inputs = question.querySelectorAll('input');
                inputs[0].name = `preguntas[${index}][texto]`;
                inputs[1].name = `preguntas[${index}][respuesta]`;
            });
        }
    });
}